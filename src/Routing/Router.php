<?php
namespace Sura\Routing;

use Closure;
use Sura\Http\Request;

/**
 * Роутер для обработки HTTP-запросов
 * 
 * Этот класс отвечает за регистрацию маршрутов и сопоставление входящих запросов
 * с соответствующими обработчиками. Поддерживает группировку маршрутов,
 * параметризованные пути и различные HTTP-методы.
 * 
 * Основные возможности:
 * - Регистрация маршрутов для GET, POST, PUT, PATCH, DELETE и других методов
 * - Группировка маршрутов с общим префиксом (например, /api/v1)
 * - Параметры в путях (например, /users/{id})
 * - Поддержка RESTful API
 * - Гибкая система диспетчеризации запросов
 * 
 * Примеры использования:
 * 
 * // Простой маршрут
 * $router->get('/users', 'UserController@index');
 * 
 * // Маршрут с параметром
 * $router->get('/users/{id}', 'UserController@show');
 * 
 * // Группа маршрутов
 * $router->group('/api/v1', function ($r) {
 *     $r->get('/users', 'ApiController@users');
 *     $r->post('/users', 'ApiController@create');
 * });
 * 
 * // RESTful ресурс
 * $router->group('/posts', function ($r) {
 *     $r->get('', 'PostController@index');      // GET /posts
 *     $r->post('', 'PostController@store');     // POST /posts
 *     $r->get('/{id}', 'PostController@show');   // GET /posts/{id}
 *     $r->put('/{id}', 'PostController@update'); // PUT /posts/{id}
 *     $r->delete('/{id}', 'PostController@destroy'); // DELETE /posts/{id}
 * });
 */
class Router
{
    protected array $routes = []; // [['method','path','handler','regex','params_keys']]
    protected string $currentGroupPrefix = '';

    /**
     * Создает группу маршрутов с общим префиксом
     */
    public function group(string $prefix, \Closure $callback): void
    {
        // Сохраняем текущий префикс
        $previousPrefix = $this->currentGroupPrefix;
        
        // Устанавливаем новый префикс
        $this->currentGroupPrefix = $previousPrefix . $prefix;
        
        // Выполняем коллбек с текущим контекстом
        $callback($this);
        
        // Восстанавливаем предыдущий префикс
        $this->currentGroupPrefix = $previousPrefix;
    }

    /**
     * Регистрирует маршрут для указанного HTTP-метода и пути
     * 
     * @param string $method HTTP-метод (GET, POST, PUT, DELETE и т.д.)
     * @param string $path Путь маршрута (может содержать параметры вида {param})
     * @param callable|string $handler Обработчик маршрута
     * 
     * Обработчик может быть:
     * - Строка в формате "ControllerClass@method"
     * - Анонимная функция (Closure)
     * - Массив [object, 'method']
     * 
     * Примеры:
     * $router->add('GET', '/users', 'UserController@index');
     * $router->add('POST', '/users', function() { ... });
     */
    public function add(string $method, string $path, $handler): void
    {
        // Добавляем префикс группы если он есть
        $fullPath = $this->currentGroupPrefix . $path;
        
        $route = $this->compile($fullPath);
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $fullPath,
            'handler' => $handler,
            'regex' => $route['regex'],
            'keys' => $route['keys']
        ];
    }

    /** Convenience methods for common HTTP methods */
    public function get(string $path, $handler): void { $this->add('GET', $path, $handler); }
    public function post(string $path, $handler): void { $this->add('POST', $path, $handler); }
    public function put(string $path, $handler): void { $this->add('PUT', $path, $handler); }
    public function patch(string $path, $handler): void { $this->add('PATCH', $path, $handler); }
    public function delete(string $path, $handler): void { $this->add('DELETE', $path, $handler); }
    public function any(string $path, $handler): void 
    {
        $this->add('GET', $path, $handler);
        $this->add('POST', $path, $handler); 
    }

    /**
     * Компилирует путь с параметрами в регулярное выражение
     * 
     * Преобразует путь с параметрами вида /users/{id} в регулярное выражение
     * и извлекает имена параметров. Поддерживает валидные имена параметров,
     * начинающиеся с буквы или подчеркивания, за которыми следуют буквы,
     * цифры, подчеркивания или дефисы.
     * 
     * @param string $path Путь с параметрами (например, /users/{id})
     * @return array Массив с ключами 'regex' (регулярное выражение) и 'keys' (имена параметров)
     * 
     * Пример:
     * $this->compile('/users/{id}/posts/{post_id}') возвращает
     * [
     *     'regex' => '#^/users/([^\/]+)/posts/([^\/]+)$#',
     *     'keys' => ['id', 'post_id']
     * ]
     */
    protected function compile(string $path): array
    {
        $keys = [];
        $regex = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_-]*)\}/', function ($m) use (&$keys) {
            $keys[] = $m[1];
            return '([^\/]+)';
        }, $path);

        $regex = '#^' . $regex . '$#';
        return ['regex' => $regex, 'keys' => $keys];
    }

    /**
     * Диспетчеризация HTTP-запроса
     * 
     * Находит соответствующий маршрут для входящего запроса,
     * извлекает параметры из пути и возвращает обработчик с параметрами.
     * Использует метод и путь запроса для сопоставления с зарегистрированными маршрутами.
     * 
     * @param Request $request Входящий HTTP-запрос
     * @return array|null Массив [handler, params] или null, если маршрут не найден
     * 
     * Где:
     * - handler: обработчик маршрута (строка, Closure или массив)
     * - params: ассоциативный массив параметров из пути (имя => значение)
     * 
     * Пример возвращаемого значения:
     * [
     *     'UserController@show',
     *     ['id' => '123']
     * ]
     */
    public function dispatch(Request $request): ?array
    {
        $method = strtoupper($request->method);
        $path = $request->path;

        foreach ($this->routes as $r) {
            if ($r['method'] !== $method) {
                continue;
            }
            if (preg_match($r['regex'], $path, $matches)) {
                array_shift($matches);
                $params = [];
                foreach ($r['keys'] as $i => $key) {
                    $params[$key] = $matches[$i] ?? null;
                }
                return [$r['handler'], $params];
            }
        }
        return null;
    }
}