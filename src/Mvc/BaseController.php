<?php

namespace Sura\Mvc;

use Sura\Container;
use Sura\Http\Request;
use App\Http\Response;
use Sura\Exceptions\NotFoundException;

class BaseController
{
    /**
     * @var Request
     */
    protected Request $request;

    /**
     * @var Response
     */
    protected Response $response;

    /**
     * @var array Параметры для передачи в шаблон
     */
    protected array $viewData = [];

    /**
     * @var string|null Текущий шаблон по умолчанию
     */
    protected ?string $layout = null;

    public function __construct(Request $request)
    {
        $this->request = $request; 
        $this->response = new Response();
    }

    /**
     * Установка переменной для шаблона
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    protected function set(string $key, $value): self
    {
        $this->viewData[$key] = $value;
        return $this;
    }

    /**
     * Установка нескольких параметров сразу
     *
     * @param array $data
     * @return $this
     */
    protected function with(array $data): self
    {
        $this->viewData = array_merge($this->viewData, $data);
        return $this;
    }

    /**
     * Рендеринг шаблона
     *
     * @param string $view
     * @param array|null $data
     * @return Response
     * @throws NotFoundException
     */
    protected function render(string $view, ?array $data = null): Response
    {
        $data = $data ?? $this->viewData;

        return $this->response->render($view, $data, $this->layout);
    }

    /**
     * Прямой вывод HTML
     *
     * @param string $html
     * @return Response
     */
    protected function html(string $html): Response
    {
        return $this->response->html($html);
    }

    /**
     * JSON-ответ
     *
     * @param mixed $data
     * @param int $status
     * @return Response
     */
    protected function json($data, int $status = 200): Response
    {
        return $this->response->json($data, $status);
    }

    /**
     * Перенаправление
     *
     * @param string $url
     * @return Response
     */
    protected function redirect(string $url): Response
    {
        return $this->response->redirect($url);
    }

    /**
     * Получение сервиса из контейнера
     *
     * @param string $service
     * @return mixed
     */
    protected function get(string $service)
    {
        return Container::getInstance()->get($service);
    }

    /**
     * Ярлык для функции перевода
     *
     * @param string $key
     * @param array $params
     * @return string
     */
    protected function trans(string $key, array $params = []): string
    {
        return trans($key, $params);
    }
}