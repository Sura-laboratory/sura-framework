<?php

/*
 * Copyright (c) 2023 Sura
 *
 *  For the full copyright and license information, please view the LICENSE
 *   file that was distributed with this source code.
 *
 */

namespace Sura\Support;

/**
 * Утилитный класс для работы с HTTP cookie.
 *
 * Поддерживает удаление, добавление и получение значений cookie.
 * Для расчёта времени истечения используется \DateTimeImmutable для корректной работы при смене DST.
 */
class Cookie
{
    /**
     * Удаляет cookie по имени.
     *
     * Устанавливает пустое значение и короткий срок жизни для принудительного удаления.
     *
     * @param string $name Имя cookie
     * @return void
     */
    public static function remove(string $name): void
    {
        $domain = $_SERVER['HTTP_HOST'] ?? '';
        $expires = time() + 100;
        setcookie($name, '', $expires, '/', $domain, true, true);
    }

    /**
     * Добавляет или об��овляет cookie.
     *
     * Если в параметре \$expires передано положительное число (целое, дни),
     * время истечения вычисляется через \DateTimeImmutable с использованием \DateInterval.
     * Если \$expires равно false или 0, cookie будет сессийной (время = 0).
     *
     * @param string $name Имя cookie
     * @param string $value Значение cookie
     * @param false|int \$expires Количество дней до истечения или false/0 для сессийной cookie
     * @return void
     */
    public static function append(string $name, string $value, false|int $expires): void
    {
        $domain = $_SERVER['HTTP_HOST'] ?? '';

        if ($expires && (int)$expires > 0) {
            $dt = new \DateTimeImmutable();
            $interval = new \DateInterval('P' . ((int)$expires) . 'D');
            $expiresAt = $dt->add($interval)->getTimestamp();
        } else {
            $expiresAt = 0;
        }

        setcookie($name, $value, $expiresAt, '/', $domain, true, true);
    }

    /**
     * Возвращает значение cookie по имени.
     *
     * Если cookie не установлена, возвращает пустую строку.
     *
     * @param string $name Имя cookie
     * @return string Значение cookie или пустая строка
     */
    public static function get(string $name): string
    {
        return $_COOKIE[$name] ?? '';
    }
}
