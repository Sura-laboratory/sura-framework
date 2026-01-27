<?php

/*
 * Copyright (c) 2023 Sura
 *
 *  For the full copyright and license information, please view the LICENSE
 *   file that was distributed with this source code.
 *
 */

namespace Sura\Http;

/**
 * Класс для работы с входящими HTTP‑данными запроса.
 *
 * Предоставляет утилиты для фильтрации строковых значений, приведения к целому
 * и проверки AJAX‑запросов. Методы читают значения из глобальных массивов
 * \$_POST и \$_GET по имени поля.
 */
class Request
{
    /**
     * Фильтр для входных данных по имени поля.
     *
     * Если значение в \$_POST с указанным именем присутствует, используется оно.
     * В противном случае проверяется \$_GET. Если в \$_GET значение — массив,
     * для безопасности возвращается пустая строка.
     * Результат дополнительно обрабатывается методом `textFilter`.
     *
     * @param string $source Имя поля во входных данных (\$_POST или \$_GET)
     * @param int $substr_num Максимальная длина возвращаемой строки (по умолчанию 25000)
     * @param bool $strip_tags Если true — сначала будет применена функция `strip_tags`
     * @return string Отфильтрованное значение или пустая строка, если поле не найдено
     */
    public function filter(string $source, int $substr_num = 25000, bool $strip_tags = false): string
    {
        if (empty($source)) {
            return '';
        }
        if (!empty($_POST[$source])) {
            $source = $_POST[$source];
        } elseif (!empty($_GET[$source])) {
            if (is_array($_GET[$source])) {
                return '';
            }
            $source = $_GET[$source];
        } else {
            return '';
        }
        return $this->textFilter($source, $substr_num, $strip_tags);
    }

    /**
     * Базовая строковая фильтрация.
     *
     * Обрезает строку до указанной длины, опционально удаляет HTML‑теги,
     * удаляет экранирующие слеши, заменяет переводы строк на `<br>` и
     * экранирует специальные HTML‑символы.
     *
     * @param string $input_text Входной текст
     * @param int $substr_num Максимальная длина строки
     * @param bool $strip_tags Удалять ли HTML‑теги перед остальной фильтрацией
     * @return string Отфильтрованный и безопасный для вывода HTML текст
     */
    public function textFilter(string $input_text, int $substr_num = 25000, bool $strip_tags = false): string
    {
        $input_text = substr($input_text, 0, $substr_num);
        if (empty($input_text)) {
            return '';
        }
        if ($strip_tags) {
            $input_text = strip_tags($input_text);
        }
        $input_text = trim($input_text);
        $input_text = stripslashes($input_text);
        $input_text = str_replace(PHP_EOL, '<br>', $input_text);
        return htmlspecialchars($input_text, ENT_QUOTES);
    }

    /**
     * Приведение входного поля к целому числу.
     *
     * Проверяет сначала \$_POST, затем \$_GET. Если поле отсутствует — возвращает значение по умолчанию.
     *
     * @param string $source Имя поля во входных данных
     * @param int $default З��ачение по умолчанию, возвращаемое при отсутствии поля
     * @return int Целое значение из входных данных или значение по умолчанию
     */
    public function int(string $source, int $default = 0): int
    {
        if (isset($_POST[$source])) {
            $source = $_POST[$source];
        } elseif (isset($_GET[$source])) {
            $source = $_GET[$source];
        } else {
            return $default;
        }
        return (int)$source;
    }

    /**
     * Проверка, является ли запрос AJAX.
     *
     * Метод проверяет наличие в \$_POST параметра `ajax` со значением `yes`.
     *
     * @return bool true, если это AJAX‑запрос, иначе false
     */
    public function checkAjax(): bool
    {
        return !empty($_POST['ajax']) && $_POST['ajax'] === 'yes';
    }
}
