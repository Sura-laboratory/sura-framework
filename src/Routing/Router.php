<?php

/*
 * Copyright (c) 2023 Sura
 *
 *  For the full copyright and license information, please view the LICENSE
 *   file that was distributed with this source code.
 *
 */

namespace Sura\Support;

use Error;

/**
 * Простой роутер для сопоставления URI с обработчиками.
 * Добавлено кэширование для ускорения поиска маршрутов.
 *
 * Поддерживает:
 * - регистрация правил маршрутов (строка => обработчик)
 * - точные совпадения и шаблоны с плейсхолдерами (\:seg, \:num, \:any)
 * - выполнение callable или контроллеров в формате "Controller@action"
 */
class Router
{
    /**
     * Кэш для результатов сопоставления маршрутов.
     *
     * @var array<string, array{handler: string|callable|null, params: array}>
     */
    private static array $cache = [];

    /**
     * Карта маршрутов: ключ — шаблон маршрута, значение — обработчик (string|callable).
     *
     * @var array<int|string, string|callable>
     */
    private static array $routes = [];

    /**
     * Текущий обрабатываемый URI (без query string).
     *
     * @var string
     */
    private static string $requestUri;

    /**
     * HTTP метод текущего запроса.
     *
     * @var string
     */
    private static string $requestMethod;

    /**
     * Обработчик, сопоставленный с текущим URI (string или callable), либо null если не найден.
     *
     * @var string|callable|null
     */
    private static $requestHandler;

    /**
     * Параметры, извлечённые из URI по плейсхолдерам (в порядке появления).
     *
     * @var array<int, string>
     */
    private static array $params = [];

    /**
     * Таблица плейсхолдеров для преобразования в регулярные выражения.
     *
     * @var string[]
     */
    private static array $placeholders = [':seg' => '([^\/]+)', ':num' => '([0-9]+)', ':any' => '(.+)'];

    /**
     * Имя контроллера, вычисленное при исполнении строкового обработчика вида "Controller@action".
     *
     * @var string|null
     */
    private static ?string $controllerName;

    /**
     * Имя действия/метода контроллера, вычисленное при исполнении строкового обработчика.
     *
     * @var string
     */
    private static string $actionName = '';

    /**
     * Инициализация роутера с указанным URI и HTTP методом.
     *
     * @param string $uri Запрошенный URI (без query string)
     * @param string $method HTTP метод (по умолчанию 'GET')
     */
    public function __construct(string $uri, string $method = 'GET')
    {
        self::$requestUri = $uri;
        self::$requestMethod = $method;
    }

    /**
     * Создаёт экземпляр Router, используя глобальные серверные переменные.
     *
     * Попытка извлечь URI из \$_SERVER['REQUEST_URI'], затем из \$_SERVER['HTTP_HOST'].
     *
     * @return Router
     * @throws Error Если не удалось определить URI запроса
     */
    public static function fromGlobals(): Router
    {
        $url = $_SERVER['HTTP_HOST'];
        $method = getenv('REQUEST_METHOD');
        if (isset($_SERVER['REQUEST_URI'])) {
            $uri_data = $_SERVER['REQUEST_URI'];
        } elseif ($url) {
            $uri_data = $url;
        } else {
            throw new Error('err');
        }
        if (false !== $pos__ = strpos($uri_data, '?')) {
            $uri_data = substr($uri_data, 0, $pos__);
        }
        $uri_data = rawurldecode($uri_data);
        return new static($uri_data, $method);
    }

    /**
     * Возвращает текущую карту маршрутoв.
     *
     * @return array<int|string, string|callable> Текущие правила маршрутизации
     */
    public static function getRoutes(): array
    {
        return self::$routes;
    }

    /**
     * Возвращает текущий обработанный URI запроса.
     *
     * @return string Текущий request URI
     */
    public static function getRequestUri(): string
    {
        return self::$requestUri;
    }

    /**
     * Возвращает HTTP метод текущего запроса.
     *
     * @return string HTTP метод (например, 'GET', 'POST')
     */
    public static function getRequestMethod(): string
    {
        return self::$requestMethod;
    }

    /**
     * Возвращает обработчик, сопоставленный с текущим URI.
     *
     * @return string|callable|null Обработчик или null, если не найден
     */
    public static function getRequestHandler()
    {
        return self::$requestHandler;
    }

    /**
     * Устанавливает обработчик для текущего роутера.
     *
     * @param string|callable $handler Имя обработчика (строка) или callable
     * @return void
     */
    final public function setRequestHandler(string|callable $handler): void
    {
        self::$requestHandler = $handler;
    }

    /**
     * Возвращает параметры маршрута, извлечённые из URI по плейсхолдерам.
     *
     * @deprecated Используйте getControllerName/getActionName при работе с контроллерами
     * @return array<int, string> Параметры пути
     */
    public static function getParams(): array
    {
        return self::$params;
    }

    /**
     * Возвращает имя последнего вычисленного контроллера (из обработчика "Controller@action").
     *
     * @return string|null Имя контроллера или null если не задано
     */
    public static function getControllerName(): ?string
    {
        return self::$controllerName;
    }

    /**
     * Возвращает имя действия (метода) контроллера из последнего обработчика.
     *
     * @return string Имя метода контроллера или пустая строка
     */
    public static function getActionName(): string
    {
        return self::$actionName;
    }

    /**
     * Добавляет правила маршрутизации в существующую карту.
     *
     * Сливает переданный массив с уже зарегистрированными маршрутами.
     *
     * @param array<int|string, string|callable> $route Массив правил маршрутизации
     * @return Router Текущий экземпляр для цепочек вызовов
     */
    final public function add(array $route): Router
    {
        self::$routes = array_merge(self::$routes, $route);
        return $this;
    }

    /**
     * Проверяет, найден ли маршрут для текущего URI.
     *
     * Поддерживает точное совпадение и совпадение по шаблонам с плейсхолдерами.
     *
     * @return bool true если маршрут найден и обработчик установлен, иначе false
     */
    public function isFound(): bool
    {
        $uri_data = self::getRequestUri();

        // Проверка наличия в кэше
        if (isset(self::$cache[$uri_data])) {
            $cached = self::$cache[$uri_data];
            self::$requestHandler = $cached['handler'];
            self::$params = $cached['params'];
            return true;
        }

        // Проверка точного совпадения маршрута
        if (isset(self::$routes[$uri_data])) {
            self::$requestHandler = self::$routes[$uri_data];
            self::$params = [];
            // Сохранение в кэш
            self::$cache[$uri_data] = [
                'handler' => self::$requestHandler,
                'params' => self::$params
            ];
            return true;
        }

        $find_placeholder = array_keys(self::$placeholders);
        $replace = array_values(self::$placeholders);
        foreach (self::$routes as $route => $handler) {
            /**
             *  Replace wildcards by regex
             */
            if (str_contains($route, ':')) {
                $route = str_replace($find_placeholder, $replace, $route);
            }
            /**
             *  Route rule matched
             */
            if (preg_match('#^' . $route . '$#', $uri_data, $matches)) {
                self::$requestHandler = $handler;
                self::$params = array_slice($matches, 1);
                // Сохранение в кэш
                self::$cache[$uri_data] = [
                    'handler' => self::$requestHandler,
                    'params' => self::$params
                ];
                return true;
            }
        }
        return false;
    }

    /**
     * Выполняет заданный обработчик маршрута.
     *
     * Поддерживаются:
     * - callable — вызывается напрямую с параметрами
     * - строковый формат "Controller@action" — создаётся экземпляр контроллера и вызывается метод
     *
     * @param callable|string $handler Обработчик маршрута
     * @param array<int, mixed> $params Параметры для передачи в обработчик
     * @return mixed Результат выполнения обработчика
     * @throws Error При отсутствии класса/метода или неверном формате обработчика
     */
    final public function executeHandler(callable|string $handler, array $params = []): mixed
    {
        // execute action in callable
        if (is_callable($handler)) {
            return call_user_func_array($handler, $params);
        }
        // execute action in controllers
        if (strpos($handler, '@')) {
            $ca = explode('@', $handler);
            $controller_name = self::$controllerName = $ca['0'];
            $action = self::$actionName = $ca['1'];
            if (class_exists('\\Mozg\\modules\\' . $controller_name)) {
                if (!method_exists('\\Mozg\\modules\\' . $controller_name, $action)) {
                    throw new Error("Method '\\App\\Modules\\{$controller_name}::{$action}()' not found");
                }

                $class = '\\Mozg\\modules\\' . $controller_name;
                $controller = new $class();
                $params['params'] = '';
                $params = [$params];
                return call_user_func_array([$controller, $action], $params);
            }
            throw new Error("Class '{$controller_name}' not found");
        }
        throw new Error('Execute handler error');
    }
}
