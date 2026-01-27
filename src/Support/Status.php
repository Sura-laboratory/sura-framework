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
 * Класс статусов, используемых в приложении.
 *
 * Каждая константа представляет код состояния/результата операции.
 */
class Status
{
    /**
     * Успешная операция.
     */
    public const OK = 1;
    /**
     * Общая ошибка.
     */
    public const BAD = 0;
    /**
     * Пользователь уже авторизован.
     */
    public const LOGGED = 2;
    /**
     * Ошибка авторизации/входа.
     */
    public const BAD_LOGGED = 3;
    /**
     * Неверный e-mail.
     */
    public const BAD_MAIL = 4;
    /**
     * Неверный пароль.
     */
    public const BAD_PASSWORD = 5;
    /**
     * Пароли не совпадают.
     */
    public const PASSWORD_DOESNT_MATCH = 6;
    /**
     * Неверный пользователь.
     */
    public const BAD_USER = 7;
    /**
     * Пользователь не найден.
     */
    public const NOT_USER = 8;
    /**
     * Данные некорректны/невалидны.
     */
    public const NOT_VALID = 9;
    /**
     * Неверный код (например, подтверждения).
     */
    public const BAD_CODE = 10;
    /**
     * Неверное действие или перемещение.
     */
    public const BAD_MOVE = 11;
    /**
     * Файл не существует.
     */
    public const FILE_NOT_EXIST = 12;
    /**
     * Файл уже существует.
     */
    public const FILE_EXIST = 13;
    /**
     * Слишком большой размер файла/данных.
     */
    public const BIG_SIZE = 14;
    /**
     * Неверный формат файла/данных.
     */
    public const BAD_FORMAT = 15;
    /**
     * Не найдено.
     */
    public const NOT_FOUND = 16;
    /**
     * Найдено.
     */
    public const FOUND = 17;
    /**
     * Найден владелец ресурса.
     */
    public const OWNER_FOUND = 18;
    /**
     * Является владельцем.
     */
    public const OWNER = 19;
    /**
     * Нет данных.
     */
    public const NOT_DATA = 20;
    /**
     * Превышен лимит.
     */
    public const LIMIT = 21;
    /**
     * Достигнут максимум.
     */
    public const MAX = 22;
    /**
     * Недостаточно прав.
     */
    public const BAD_RIGHTS = 23;
    /**
     * Отсутствует разрешение/право.
     */
    public const PERMISSION = 24;
    /**
     * Нарушение приватности/приватное.
     */
    public const PRIVACY = 25;
    /**
     * Неверный друг.
     */
    public const BAD_FRIEND = 26;
    /**
     * Является другом.
     */
    public const FRIEND = 27;
    /**
     * Неверный запрос/требование.
     */
    public const BAD_DEMAND = 28;
    /**
     * Запрос/требование принято/существует.
     */
    public const DEMAND = 29;
    /**
     * Неверный владелец запроса.
     */
    public const BAD_DEMAND_OWNER = 30;
    /**
     * Владелец запроса.
     */
    public const DEMAND_OWNER = 31;
    /**
     * Недостаточно средств/нет денег.
     */
    public const NOT_MONEY = 32;
    /**
     * В черном списке.
     */
    public const BLACKLIST = 33;
    /**
     * Срабатывание антиспама.
     */
    public const ANTISPAM = 34;
    /**
     * Подписка / статус подписки.
     */
    public const SUBSCRIPTION = 35;
}
