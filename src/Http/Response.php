<?php
namespace Sura\Http;

use Sura\Container;
use Sura\Exceptions\NotFoundException;

/**
 * Класс для формирования и отправки HTTP-ответа клиенту
 * 
 * Предоставляет методы для установки статуса, заголовков, тела ответа
 * и отправки различных типов данных (HTML, JSON, редирект и т.д.)
 */
class Response
{
    protected int $status = 200;    // HTTP статус ответа (по умолчанию 200)
    protected array $headers = [];   // Массив HTTP заголовков
    protected string $body = '';     // Тело ответа

    /**
     * Установить HTTP-статус ответа
     *
     * @param int $code Код HTTP-статуса (200, 404, 500 и т.д.)
     * @return self Текущий экземпляр Response для цепочки вызовов
     */
    public function setStatus(int $code): self
    {
        $this->status = $code;
        return $this;
    }

    /**
     * Добавить HTTP-заголовок к ответу
     *
     * @param string $name Имя заголовка
     * @param string $value Значение заголовка
     * @return self Текущий экземпляр Response для цепочки вызовов
     */
    public function header(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Добавить текст к телу ответа
     *
     * Прибавляет текст к существующему телу ответа
     * @param string $text Текст для добавления
     * @return self Текущий экземпляр Response для цепочки вызовов
     */
    public function write(string $text): self
    {
        $this->body .= $text;
        return $this;
    }

    /**
     * Отправить ответ клиенту
     *
     * Отправляет HTTP-статус, заголовки и тело ответа браузеру
     * @param bool $visible Отображать ли тело ответа (true) или только отправить заголовки
     * @return void
     */
    public function send($visible = true): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);
            foreach ($this->headers as $k => $v) {
                header("{$k}: {$v}");
            }
        }
        if ($visible == true) {
            echo $this->body;
        }
        
    }

    /**
     * Установить тело ответа как HTML-контент
     *
     * Устанавливает Content-Type в text/html и отправляет HTML-код
     * @param string $html HTML-контент для отправки
     * @return void
     */
    public function html(string $html)
    {
        $this->header('Content-Type', 'text/html');
        $this->body = $html;
        $this->send();
    }

    /**
     * Перенаправить клиента на другой URL
     *
     * Устанавливает статус перенаправления и заголовок Location
     * Использует meta-обновление для надежного перенаправления
     * @param string $url URL для перенаправления
     * @param int $status Код HTTP-статуса (301 - постоянное, 302 - временное)
     * @return void
     */
    public function redirect(string $url, int $status = 302): void
    {
        $this->setStatus($status);
        $this->header('Location', $url);
        header("Location: ".$url, true, 301);
        $this->body =
        "<html>
        <head>
        <meta http-equiv='refresh' content='0;url=$url'>
        </head>
        </html>";
        $this->send();
    }

    /**
     * Отправить данные в формате JSON
     *
     * Устанавливает Content-Type в application/json и отправляет данные в JSON-формате
     * Использует JSON_UNESCAPED_UNICODE для корректного отображения кириллицы
     * @param mixed $data Данные для кодирования в JSON
     * @return void
     */
    public function json(mixed $data): void
    {
        $this->header('Content-Type', 'application/json');
        $this->body = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $this->send();
    }

    /**
     * Отрендерить шаблон и отправить ответ
     *
     * Загружает шаблон Blade, передает в него переменные и рендерит.
     * Для AJAX-запросов возвращает JSON с содержимым и заголовком.
     * Для обычных запросов возвращает отрендеренный HTML.
     *
     * @param string|null $view Путь к шаблону или null
     * @param array $variables Переменные для передачи в шаблон
     * @return string|null Отрендеренный шаблон или null
     * @throws NotFoundException Если шаблон не найден
     * @throws \Exception При ошибках рендеринга
     */
    public function render(?string $view, array $variables): ?string
    {
        $auth = Container::getInstance()->get('auth');
        $user = $auth->user ?? null;
        $isLogged = $auth->isLogged;
        $translators = Container::getInstance()->get('translator');
        $translations = $translators->getTranslations();
        $is_ajax = new Request()->isAjax();

        $views = __DIR__ . '/../../resources/views/';
        $cache = __DIR__ . '/../../resources/views/cache/';
        $blade = new \Sura\View\Templates($views, $cache, \Sura\View\View::MODE_AUTO);

        $blade::$svgDirectory = '/resources/icons/';
        $blade::$dictionary = $translations;

        $url = ((!empty($_SERVER['HTTPS'])) ? 'https' : 'http') . '://' .
            $_SERVER['HTTP_HOST'] ;

        $blade->setBaseUrl($url);

        $seoFile = __DIR__ . '/../../config/seo.json';
        $seo_data = file_exists($seoFile) ? json_decode(file_get_contents($seoFile), true) : [];



        $variables['title']  = $variables['title'] ?? $translators->trans('welcome');
        $variables['site_name'] = $seo_data['title'];
        $variables['user_owner'] = $user;
        $variables['is_ajax'] = $is_ajax;
        $variables['isLogged'] = $isLogged;
        $variables['locale'] = $translators->getLocale();
        $variables['locale_name'] = $translators->getLocaleName();
        if ($is_ajax) {
            $json_content = $blade->run($view, $variables);
            $json_header = $user ? $blade->run('main.header', $variables) : '';
            $result = [
                'title' => $variables['title'],
                'content' => $json_content,
                'header' => $json_header
            ];
            return $this->json($result);
        }
        return $blade->run($view, $variables);
    }

    /**
     * @throws NotFoundException
     * @throws \Exception
     */
    public function renderString(?string $view, array $variables): ?string
    {
        $translators = Container::getInstance()->get('translator');
        $translations = $translators->getTranslations();
        $views = __DIR__ . '/../../resources/views/';
        $cache = __DIR__ . '/../../resources/views/cache/';
        $blade = new \Sura\View\Templates($views, $cache, \Sura\View\View::MODE_AUTO);

        $blade::$svgDirectory = '/resources/icons/';
        $blade::$dictionary = $translations;
        $variables['title']  = $variables['title'] ?? $translators->trans('welcome');
        return $blade->run($view, $variables);
    }    
}