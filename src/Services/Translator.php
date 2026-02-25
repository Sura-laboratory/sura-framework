<?php

namespace Sura\Services;

class Translator
{
    private string $locale;
    private string $langPath;
    private array $translations = [];

    public function __construct(string $locale, string $langPath)
    {
        $this->locale = $locale;
        $this->langPath = rtrim($langPath, '/');
        $this->load();
    }

    private function load(): void
    {
        $file = $this->langPath . '/' . $this->locale . '/messages.json';
        $file_en = $this->langPath . '/en/messages.json';

        $translations_en = [];
        $translations = [];

        // Загружаем английский (fallback)
        if (is_file($file_en)) {
            $json_en = file_get_contents($file_en);
            $decoded_en = json_decode($json_en, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $translations_en = is_array($decoded_en) ? $decoded_en : [];
            }
        }

        // Загружаем текущую локаль
        if (is_file($file)) {
            $json = file_get_contents($file);
            $decoded = json_decode($json, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $translations = is_array($decoded) ? $decoded : [];
            }
        }

        // ГЛУБОКОЕ СЛИЯНИЕ — важно!
        $this->translations = array_replace_recursive($translations_en, $translations);
    }

    /**
     * Возвращает перевод по ключу с поддержкой иерархии (например: home.title)
     */
    public function trans(string $key, array $replacements = []): string
    {
        $value = $this->getDotValue($this->translations, $key) ?? $key;

        // Замена :ключ → значению
        foreach ($replacements as $k => $v) {
            $value = str_replace(":$k", $v, $value);
        }

        return $value;
    }

    /**
     * Получает значение из вложенного массива по ключу с точкой.
     * Пример: getDotValue($arr, 'home.features.messaging')
     */
    private function getDotValue(array $data, string $key): ?string
    {
        $keys = explode('.', $key);

        foreach ($keys as $segment) {
            if (is_array($data) && array_key_exists($segment, $data)) {
                $data = $data[$segment];
            } else {
                return null;
            }
        }

        return is_string($data) ? $data : null;
    }

    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
        $this->load();
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function getLocaleName(): string
    {
        $lang = $this->locale;
        $langs = require __DIR__ . '/../../../../../config/langs.php';

        if (array_key_exists($lang, $langs)) {
            return $langs[$lang]['name'];
        }

        return 'English';
    }   

    public function getTranslations(): array
    {
        return $this->translations;
    }

    public function debug(): void
    {
        var_dump($this->translations);
    }

    /**
     * Простейшее склонение (формирование множественного числа) для английских слов.
     * Поддерживает базовые правила:
     * - слова на s, x, z, ch, sh → +es
     * - слова на y после согласной → меняется на ies
     * - остальные → +s
     */
    public function pluralize(string $word): string
    {
        if (in_array(strtolower($word), ['information', 'equipment', 'money', 'species', 'data', 'series'])) {
            return $word; // Неисчисляемые существительные
        }

        $last = strtolower(substr($word, -1));
        $lastTwo = strtolower(substr($word, -2));

        if (in_array($lastTwo, ['ss', 'sh', 'ch', 'x', 'z'])) {
            return $word . 'es';
        }

        if ($last === 'y') {
            $rest = substr($word, 0, -1);
            $vowels = ['a', 'e', 'i', 'o', 'u'];
            if (!in_array(strtolower(substr($word, -2, 1)), $vowels)) {
                return $rest . 'ies'; // консонант + y → ies
            }
        }

        return $word . 's';
    }
    
    /**
     * Простейшее склонение русских имён по падежам.
     * Поддерживает мужские, женские имена, исключения и несклоняемые имена.
     *
     * @param string $name Имя (например: "Иван", "Анна", "Мария", "Любовь", "Маша")
     * @param string $case Падеж: nominative, genitive, dative, accusative, instrumental, prepositional
     * @return string Склонённая форма
     */
    public function declineName(string $name, string $case): string
    {
        $name = trim($name);
        $lowerName = mb_strtolower($name, 'UTF-8');
        if ($case === 'nominative' || $name === '') {
            return $name;
        }

        // Список имён, которые не склоняются
        $indeclinable = [
            'любовь', 'надежда', 'милость', 'благодать', 'жизель', 'фемида',
            'эльза', 'анастасия', 'кристина', 'регина', 'таисия', 'ксения', 'лиана'
        ];

        if (in_array($lowerName, $indeclinable, true)) {
            return $name; // Не склоняется
        }

        // Исключения: короткие формы или особые правила
        $exceptions = [
            'маша' => ['genitive' => 'Маши', 'dative' => 'Маше', 'accusative' => 'Машу', 'instrumental' => 'Машей', 'prepositional' => 'о Маше'],
            'даша' => ['genitive' => 'Даши', 'dative' => 'Даше', 'accusative' => 'Дашу', 'instrumental' => 'Дашей', 'prepositional' => 'о Даше'],
            'саша' => ['genitive' => 'Саши', 'dative' => 'Саше', 'accusative' => 'Сашу', 'instrumental' => 'Сашей', 'prepositional' => 'о Саше'],
            'паша' => ['genitive' => 'Паши', 'dative' => 'Паше', 'accusative' => 'Пашу', 'instrumental' => 'Пашей', 'prepositional' => 'о Паше'],
            'гриша' => ['genitive' => 'Гриши', 'dative' => 'Грише', 'accusative' => 'Гришу', 'instrumental' => 'Гришей', 'prepositional' => 'о Грише'],
        ];

        if (isset($exceptions[$lowerName])) {
            $forms = $exceptions[$lowerName];
            $caseKey = strtolower($case);
            return $forms[$caseKey] ?? $name;
        }

        $last = mb_substr($name, -1, 1, 'UTF-8');
        $base = mb_substr($name, 0, -1, 'UTF-8');

        switch (strtolower($case)) {
            case 'genitive':
                if (in_array($last, ['а', 'я'], true)) {
                    return $base . 'ы';
                }
                if ($last === 'ь') {
                    return $base . 'я';
                }
                break;

            case 'dative':
                if (in_array($last, ['а', 'я'], true)) {
                    return $base . 'е';
                }
                if ($last === 'ь') {
                    return $base . 'ю';
                }
                break;

            case 'accusative':
                if (in_array($last, ['а', 'я'], true)) {
                    return $base . 'у';
                }
                if ($last === 'ь') {
                    return $base . 'я';
                }
                break;

            case 'instrumental':
                if (in_array($last, ['а', 'я'], true)) {
                    return $base . 'ой';
                }
                if ($last === 'ь') {
                    return $base . 'ем';
                }
                break;

            case 'prepositional':
                if (in_array($last, ['а', 'я'], true)) {
                    return $base . 'е';
                }
                if ($last === 'ь') {
                    return $base . 'е';
                }
                break;
        }

        // По умолчанию — возвращаем имя без изменений
        return $name;
    }
}