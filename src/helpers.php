<?php

/*
 * Copyright (c) 2023 Sura
 *
 *  For the full copyright and license information, please view the LICENSE
 *   file that was distributed with this source code.
 *
 */

use JetBrains\PhpStorm\Deprecated;

/**
 * Переводит английские названия месяцев и дней на русский в результате вызова \date.
 *
 * @param string $format Формат для функции date\(\)
 * @param int $stamp UNIX‑метка времени
 * @return string Форматированная дата с русскими названиями месяцев/дней
 */
function langDate(string $format, int $stamp): string
{
    $lang_date = [
        'January' => "января",
        'February' => "февраля",
        'March' => "марта",
        'April' => "апреля",
        'May' => "мая",
        'June' => "июня",
        'July' => "июля",
        'August' => "августа",
        'September' => "сентября",
        'October' => "октября",
        'November' => "ноября",
        'December' => "декабря",
        'Jan' => "янв",
        'Feb' => "фев",
        'Mar' => "мар",
        'Apr' => "апр",
        'Jun' => "июн",
        'Jul' => "июл",
        'Aug' => "авг",
        'Sep' => "сен",
        'Oct' => "окт",
        'Nov' => "ноя",
        'Dec' => "дек",

        'Sunday' => "Воскресенье",
        'Monday' => "Понедельник",
        'Tuesday' => "Вторник",
        'Wednesday' => "Среда",
        'Thursday' => "Четверг",
        'Friday' => "Пятница",
        'Saturday' => "Суббота",

        'Sun' => "Вс",
        'Mon' => "Пн",
        'Tue' => "Вт",
        'Wed' => "Ср",
        'Thu' => "Чт",
        'Fri' => "Пт",
        'Sat' => "Сб",
    ];
    return strtr(date($format, $stamp), $lang_date);
}

/**
 * Очищает строку от HTML‑тегов, управляющих и нежелательных символов.
 *
 * Удаляет экранирующие слеши, обрезает и безопасно заменяет набор символов.
 *
 * @param string $text Входной текст
 * @return string Очищенная строка
 */
function strip_data(string $text): string
{
    $quotes = [
        "\x27", "\x22", "\x60", "\t", "\n", "\r", "'", ",", "/", ";", ":", "@", "[", "]", "{", "}", "=", ")",
        "(", "*", "&", "^", "%", "$", "<", ">", "?", "!", '"'
    ];
    $good_quotes = ["-", "+", "#"];
    $rep_quotes = ["\\-", "\\+", "\\#"];

    $text = stripslashes($text);
    $text = trim(strip_tags($text));

    // Формирование массивов поиска и замены: для основных символов — пустая строка, для "good" — экранированная форма
    $search = array_merge($quotes, $good_quotes);
    $replace = array_merge(array_fill(0, count($quotes), ''), $rep_quotes);

    /**
     * @var array<int,string> $search
     * @var array<int,string> $replace
     */
    return str_replace($search, $replace, $text);
}

/**
 * Формирует HTML \<option\> список и отмечает опцию с указанным id как selected.
 *
 * @param string $id Значение опции, которую необходимо пометить как selected
 * @param array $list Ассоциативный массив value => label
 * @return string Сформированный HTML код опций
 * @since 4.0
 */
function addToList(string $id, array $list): string
{
    $options = '';
    foreach ($list as $key => $value) {
        $options .= '<option value="' . $key . '">' . $value . '</option>';
    }
    return str_replace('value="' . $id . '"', 'value="' . $id . '" selected', $options);
}

/**
 * Форматирует дату в удобочитаемый вид с использованием русских названий.
 *
 * Если дата — сегодня/вчера, возвращает соответствующую строку. Параметр \$func
 * контролирует форму без года, \$full — с полным годом и месяцем.
 *
 * @param int $date UNIX‑метка времени
 * @param bool $func Если true — формат без года (j M в H:i)
 * @param bool $full Если true — полный формат (j F Y в H:i)
 * @return string Отформатированная строка даты
 */
function megaDate(int $date, bool $func = false, bool $full = false): string
{
    if (date('Y-m-d', $date) === date('Y-m-d', time())) {
        return langDate('сегодня в H:i', $date);
    } elseif (date('Y-m-d', $date) === date('Y-m-d', (time() - 84600))) {
        return langDate('вчера в H:i', $date);
    } elseif ($func) {
        //no_year
        return langDate('j M в H:i', $date);
    } elseif ($full) {
        return langDate('j F Y в H:i', $date);
    } else {
        return langDate('j M Y в H:i', $date);
    }
}

/**
 * Выбирает правильную форму слова в зависимости от числа.
 *
 * Ожидается массив из трёх форм, например \[\'яблок\', \'яблоко\', \'яблока\'\].
 *
 * @param int $number Число для определения формы
 * @param array<int,string> $titles Массив форм слова
 * @return string Подходящая форма слова
 */
function declOfNum(int $number, array $titles): string
{
    $cases = [2, 0, 1, 1, 1, 2];
    return (string)$titles[($number % 100 > 4 and $number % 100 < 20) ? 2 : $cases[min($number % 10, 5)]];
}
