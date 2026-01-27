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
 * Registry
 *
 * Простое статическое хранилище значений по ключу.
 * Используется для централизованного доступа к общим объектам/данным в приложении.
 */
class Registry
{
    /**
     * Статическое хранилище для данных.
     *
     * Ключ — строка, значение — произвольный тип.
     *
     * @var array<string, mixed>
     */
    protected static array $store = [];

    /**
     * Защита от создания экземпляров статического класса.
     *
     * Конструктор объявлен как protected, чтобы предотвратить создание объекта.
     */
    protected function __construct()
    {
    }

    /**
     * Защита от клонирования.
     *
     * Чтобe исключить возможность клонирования статического класса.
     */
    protected function __clone()
    {
    }

    /**
     * Проверяет, существуют ли данные по указанному ключу.
     *
     * @param string $name Ключ в реестре
     * @return bool true, если значение с таким ключом зарегистрировано, иначе false
     */
    public static function exists(string $name): bool
    {
        return isset(self::$store[$name]);
    }

    /**
     * Возвращает данные по ключу или null, если данные отсутствуют.
     *
     * @param string $name Ключ в реестре
     * @return mixed Значение из хранилища или null, если ключ не найден
     */
    public static function get(string $name): mixed
    {
        return self::$store[$name] ?? null;
    }

    /**
     * Сохраняет значение по ключу в статическом хранилище.
     *
     * @param string $name Ключ для хранения
     * @param mixed $obj Значение для сохранения
     * @return mixed Сохранённое значение
     */
    public static function set(string $name, mixed $obj): mixed
    {
        return self::$store[$name] = $obj;
    }
}
