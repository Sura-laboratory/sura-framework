<?php
namespace Sura\Http;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Sura\Container;
use Sura\Exceptions\NotFoundException;
use Sura\Routing\Router;
use Sura\Exceptions\ContainerException;
use Psr\Log\LoggerInterface;

class Kernel
{
    protected Container $container;
    protected array $middleware = [];

    /**
     * Конструктор ядра HTTP-обработчика
     *
     * Инициализирует контейнер зависимостей и список middleware
     *
     * @param Container $container Контейнер зависимостей
     * @param array $middleware Массив имен классов middleware для применения
     */
    public function __construct(Container $container, array $middleware = [])
    {
        $this->container = $container;
        $this->middleware = $middleware;
    }

    /**
     * Основной метод обработки HTTP-запроса
     *
     * Обрабатывает входящий запрос, проходя следующие этапы:
     * 1. Маршрутизация запроса
     * 2. Построение цепочки middleware
     * 3. Вызов обработчика маршрута
     * 4. Обработка исключений и логирование
     *
     * @param Request $request Входящий HTTP-запрос
     * @return Response HTTP-ответ для отправки клиенту
     *
     * @throws ContainerExceptionInterface При ошибке контейнера зависимостей
     * @throws NotFoundExceptionInterface При отсутствии требуемого сервиса в контейнере
     */
    public function handle(Request $request): Response
    {
        $dispatch = $this->container->get(Router::class)->dispatch($request);

        if ($dispatch === null) {
            $res = new Response();
            $res->setStatus(404)->write('');
            echo new \App\Controllers\ErrorController($request)->index();
            return $res;
        }

        [$handler, $routeParams] = $dispatch;

        // Присоединение параметров маршрута к атрибутам запроса
        // Позволяет получать параметры из контроллера через getAttribute()
        foreach ($routeParams as $k => $v) {
            $request = $request->withAttribute($k, $v);
        }
//        var_dump($request);exit;

        // Основной обработчик, вызывающий обработчик маршрута
        $core = function (Request $req) use ($handler) : Response
        {
            return $this->invokeHandler($handler, $req);
        };

        // Построение цепочки middleware
        // Middleware применяются в обратном порядке (от последнего к первому)
        // Каждый middleware вызывает следующий в цепочке через $next
        $pipeline = array_reduce(
            array_reverse($this->middleware),
            function ($next, $middlewareClass) {
                return function (Request $req) use ($next, $middlewareClass) {
                    return $this->container->make($middlewareClass)->handle($req, $next);
                };
            },
            $core
        );

        try {
            $response = $pipeline($request);
            if (! $response instanceof Response) {
                $r = new Response();
                $r->write((string)$response);
                return $r;
            }
            return $response;
        } catch (\Throwable $e) {
            var_dump($e);
            $logger = $this->container->has(LoggerInterface::class) ? $this->container->get(LoggerInterface::class) : null;
            if ($logger) {
                $logger->error($e->getMessage(), ['exception' => $e]);
            }
            $r = new Response();

            $r->setStatus(500)->write('Internal Server Error');
            return $r;
        }
    }

    /**
     * Вызов обработчика маршрута
     *
     * Обработчик может быть:
     * - Вызываемой функцией (callable)
     * - Строкой в формате "Контроллер@метод"
     *
     * В случае строки создает экземпляр контроллера через контейнер зависимостей
     *
     * @param mixed $handler Обработчик маршрута
     * @param Request $request Текущий HTTP-запрос
     * @return Response HTTP-ответ
     *
     * @throws NotFoundException Когда контроллер не найден
     * @throws \ReflectionException При ошибках рефлексии
     */
    protected function invokeHandler($handler, Request $request): Response
    {
        // Нормализация обработчика
        // Если передана строка "Контроллер@метод", преобразуем в массив
        if (is_string($handler) && str_contains($handler, '@')) {
            [$controller, $method] = explode('@', $handler, 2);
            $callable = [ $controller, $method ];
        } else {
            $callable = $handler;
        }

        // Если это контроллер, создаем его экземпляр через контейнер зависимостей
        if (is_array($callable) && is_string($callable[0])) {
            $controller = $this->container->make($callable[0]);
            $callable = [$controller, $callable[1]];
        }

        // Разрешение параметров обработчика через рефлексию
        // Параметры могут быть внедрены из запроса, параметров маршрута или контейнера
        $params = $this->resolveCallableParameters($callable, $request);

        // Вызов обработчика с разрешенными параметрами
        $result = call_user_func_array($callable, $params);

        // Если результат уже является Response объектом, возвращаем его
        if ($result instanceof Response) {
            return $result;
        }

        // Если результат - что-то другое, оборачиваем в Response
        $response = new Response();
        $response->write((string)$result);
        return $response;
    }

    /**
     * Разрешение параметров вызываемого обработчика с помощью рефлексии
     *
     * Метод анализирует параметры вызываемой функции/метода контроллера и определяет их значения:
     * 1. Объект Request по типу
     * 2. Параметры маршрута по имени
     * 3. Сервисы из контейнера по типу
     * 4. Значения по умолчанию
     * 5. null как последняя альтернатива
     *
     * @param callable $callable Вызываемый обработчик
     * @param Request $request Текущий HTTP-запрос
     * @return array Массив значений параметров в порядке их объявления
     *
     * @throws \ReflectionException При ошибках рефлексии
     * @throws NotFoundException При отсутствии требуемого сервиса
     */
    protected function resolveCallableParameters($callable, Request $request): array
    {
        $ref = is_array($callable)
            ? new \ReflectionMethod($callable[0], $callable[1])
            : new \ReflectionFunction($callable);

        $params = [];
        foreach ($ref->getParameters() as $p) {
            $type = $p->getType();
            $name = $p->getName();

            // Внедрение объекта Request по типу
            if ($type && !$type->isBuiltin() && $type->getName() === Request::class) {
                $params[] = $request;
                continue;
            }

            // Получение параметров маршрута по имени
            // Например, если в URL /user/{id}, параметр id будет доступен
            if ($request->getAttribute($name) !== null) {
                $params[] = $request->getAttribute($name);
                continue;
            }

            // Внедрение сервисов из контейнера по типу
            // Автоматическое DI для зависимостей, объявленных в сигнатуре метода
            if ($type && !$type->isBuiltin() && $this->container->has($type->getName())) {
                $params[] = $this->container->get($type->getName());
                continue;
            }

            // Использование значений параметров по умолчанию
            // Если параметр не был разрешен выше, используем значение по умолчанию
            if ($p->isDefaultValueAvailable()) {
                $params[] = $p->getDefaultValue();
                continue;
            }

            // В качестве последней альтернативы используем null
            // Это случается, когда параметр не имеет значения по умолчанию
            $params[] = null;
        }
        return $params;
    }
}