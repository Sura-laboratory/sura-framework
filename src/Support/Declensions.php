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
 * Класс для выбора правильной формы слова (склонения) в зависимости от числа.
 *
 * Ожидает массив склонений по ключам-типам, где для каждого типа
 * предоставлен набор форм в нужном порядке.
 *
 * @property array<string, array<int, string>> $declensions Словарь склонений по типам
 */
class Declensions
{
    /**
     * Инициализирует объект со словарём склонений.
     *
     * Формат: ['type' => [form0, form1, form2, form3, form4], ...]
     *
     * @param array<string, array<int, string>> $declensions Массив склонений по типам
     */
    public function __construct(public array $declensions)
    {
    }

    /**
     * Возвращает подходящую форму слова для указанного числа и типа.
     *
     * Алгоритм извлекает последние значащие цифры числа (для корректной обработки десятков/сотен и т.д.)
     * и возвращает соответствующую форму из переданного набора склонений.
     *
     * @param int $num Число, по которому определяется склонение
     * @param string $type Ключ типа склонения в массиве `declensions`
     * @return string Соответствующая форма слова или пустая строка, если тип/форма не найдены
     */
    final public function makeWord(int $num, string $type): string
    {
        if (!isset($this->declensions[$type])) {
            return '';
        }

        $forms = $this->declensions[$type];

        // Проверяем последние две цифры
        $n = $num % 100;
        if ($n >= 11 && $n <= 19) {
            return $forms[3]; // для 11–19: "сообщений"
        }

        // Последняя цифра
        $n = $num % 10;
        if ($n == 1) {
            return $forms[1]; // 1 → "сообщение"
        } elseif ($n >= 2 && $n <= 4) {
            return $forms[2]; // 2–4 → "сообщения"
        } else {
            return $forms[3]; // 0,5–9, а также 11–19 → "сообщений"
        }
    }
}
