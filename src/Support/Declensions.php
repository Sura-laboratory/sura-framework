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
        $str_len_num = strlen((string)$num);
        if ($str_len_num === 2) {
            $parse_num = substr((string)$num, 1, 2);
            $num = (int)str_replace('0', '10', $parse_num);
        } elseif ($str_len_num === 3) {
            $parse_num = substr((string)$num, 2, 3);
            $num = (int)str_replace('0', '10', $parse_num);
        } elseif ($str_len_num === 4) {
            $parse_num = substr((string)$num, 3, 4);
            $num = (int)str_replace('0', '10', $parse_num);
        } elseif ($str_len_num === 5) {
            $parse_num = substr((string)$num, 4, 5);
            $num = (int)str_replace('0', '10', $parse_num);
        }

        if ($num === 0) {
            return $this->declensions[$type][0];
        }
        if ($num === 1) {
            return $this->declensions[$type][1];
        }
        if ($num < 5) {
            return $this->declensions[$type][2];
        }
        if ($num < 21) {
            return $this->declensions[$type][3];
        }
        if ($num === 21) {
            return $this->declensions[$type][4];
        }
        return '';
    }
}
