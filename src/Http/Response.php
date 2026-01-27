<?php
declare(strict_types=1);
namespace Sura\Http;

use JsonException;

/**
 * Утилитный класс для формирования HTTP-ответов.
 *
 * Содержит методы для отправки данных в различных форматах (в данный момент — JSON).
 */
class Response
{
    /**
     * Отправляет JSON-ответ клиенту.
     *
     * Устанавливает заголовок `Content-Type: application/json; charset=utf-8`
     * и выводит JSON-кодированное представление переданного значения.
     * Используется флаг `JSON_THROW_ON_ERROR`, чтобы любые ошибки кодирования
     * были представлены в виде `JsonException`.
     *
     * @param mixed $value Значение для кодирования в JSON
     * @return void
     * @throws JsonException ��ри ошибке кодирования в JSON
     */
    public function _e_json(mixed $value): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
