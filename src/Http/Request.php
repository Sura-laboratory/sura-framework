<?php
namespace Sura\Http;

/**
 * Класс для представления HTTP-запроса
 * 
 * Содержит всю информацию о входящем HTTP-запросе: метод, путь, параметры,
 * заголовки, данные формы и атрибуты маршрута.
 */
use Sura\Http\UserAgent;

class Request
{
    private ?UserAgent $userAgent = null;
    public string $method;           // HTTP-метод запроса (GET, POST, PUT и т.д.)
    public string $path;              // Путь запроса без параметров
    public array $query = [];         // GET-параметры из строки запроса
    public array $post = [];          // POST-данные формы
    public array $server = [];        // Данные из $_SERVER
    public array $headers = [];       // HTTP-заголовки запроса
    public array $attributes = [];    // Атрибуты запроса (параметры маршрута и др.)
    public array $files = []; // Данные о загруженных файлах из $_FILES

    /**
     * Конструктор объекта запроса
     *
     * Инициализирует объект Request с данными. Если параметры не переданы,
     * используются глобальные суперглобальные переменные PHP.
     *
     * @param array|null $server Данные из $_SERVER
     * @param array|null $get GET-параметры
     * @param array|null $post POST-данные
     * @param array|null $headers HTTP-заголовки
     */
    public function __construct(
        ?array $server = null,
        ?array $get = null,
        ?array $post = null,
        // ?array $headers = null,
        ?array $files = null
        ) {
        $server = $server ?? $_SERVER;
        $this->server = $server;
        $this->method = $server['REQUEST_METHOD'] ?? 'GET';
        $uri = $server['REQUEST_URI'] ?? '/';
        $this->path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $this->query = $get ?? $_GET;
        $this->post = $post ?? $_POST;
        $this->files = $files ?? $_FILES;
        // $this->headers = $headers ?? $this->parseHeaders();
        // $this->userAgent = new UserAgent($this->headers['User-Agent'] ?? null);
    }

    /**
     * Проверить, были ли в запросе загруженные файлы
     *
     * @return bool true, если есть загруженные файлы, иначе false
     */
    public function hasFile(string $key): bool
    {
        $file = $this->files[$key] ?? null;
        if (!$file) {
            return false;
        }
        if (is_array($file['tmp_name'])) {
            return !empty(array_filter($file['tmp_name']));
        }
        return !empty($file['tmp_name']);
    }  

    /**
     * Получить информацию о загруженном файле по имени поля
     *
     * @param string $key Имя поля файла (как в HTML-форме)
     * @return array|null Массив с информацией о файле или null, если не найден
     */
    public function getFile(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    /**
     * Получить объект UserAgent для определения типа устройства
     *
     * @return UserAgent Объект для работы с User-Agent
     */
    public function userAgent(): UserAgent
    {
        return $this->userAgent;
    }


    /**
     * Разобрать HTTP-заголовки из суперглобальной переменной $_SERVER
     *
     * Извлекает HTTP-заголовки из массива $_SERVER, преобразуя ключи
     * в читаемый формат (например, HTTP_CONTENT_TYPE -> Content-Type)
     *
     * @return array Массив HTTP-заголовков
     */
    protected function parseHeaders(): array
    {
        $headers = [];
        foreach ($this->server as $k => $v) {
            if (str_starts_with($k, 'HTTP_')) {
                $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($k, 5)))));
                $headers[$name] = $v;
            }
        }
        return $headers;
    }

    /**
     * Вернуть новый экземпляр с добавленным атрибутом
     *
     * Создает клон текущего объекта и добавляет к нему указанный атрибут.
     * Используется для иммутабельного добавления данных в запрос.
     *
     * @param string $key Ключ атрибута
     * @param mixed $value Значение атрибута
     * @return self Новый экземпляр Request с добавленным атрибутом
     */
    public function withAttribute(string $key, mixed $value): self
    {
        $clone = clone $this;
        $clone->attributes[$key] = $value;
        return $clone;
    }

    /**
     * Получить значение атрибута по ключу
     *
     * Возвращает значение атрибута по указанному ключу.
     * Если атрибут не найден, возвращает значение по умолчанию.
     *
     * @param string $key Ключ атрибута
     * @param mixed $default Значение по умолчанию, если атрибут не найден
     * @return mixed Значение атрибута или значение по умолчанию
     */
    public function getAttribute(string $key, $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    /**
     * Проверить, является ли запрос AJAX-запросом
     *
     * Определяет, является ли запрос асинхронным (AJAX) по наличию
     * специального флага в POST-данных или JSON-запросе.
     *
     * @return bool true, если запрос является AJAX, иначе false
     */
    public function isAjax(): bool
    {
        // var_dump($this->post);

        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        if (isset($data['ajax'])) {
           return true;
        }
        return isset($this->post['ajax']) && $this->post['ajax'] === 'yes';
    }
    
    /**
     * Получить входные данные запроса в формате JSON
     *
     * Читает тело запроса и декодирует JSON-данные.
     * Используется для получения данных из AJAX-запросов и API.
     *
     * @throws \JsonException При ошибках декодирования JSON
     */
    public function input($name)
    {
        $data = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
        return $data[$name] ?? null;
    }

    /**
     * Проверить, является ли запрос POST-запросом
     *
     * Определяет, содержит ли запрос данные POST (отправленные формы и т.д.)
     *
     * @return bool true, если запрос содержит POST-данные, иначе false
     */
    public function isPost(): bool
    {
        return (bool)$this->post;
    }
    
}