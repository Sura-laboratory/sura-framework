<?php

namespace Sura\Services;

class Validator
{
    protected array $errors = [];

    /**
     * Валидация данных по правилам
     *
     * @param array $data
     * @param array $rules
     * @return bool
     */
    public function validate(array $data, array $rules): bool
    {
        $this->errors = [];

        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;
            $fieldRules = is_string($fieldRules) ? explode('|', $fieldRules) : $fieldRules;

            foreach ($fieldRules as $rule) {
                $result = match (true) {
                    $rule === 'required' => $this->validateRequired($field, $value),
                    str_starts_with($rule, 'min:') => $this->validateMin($field, $value, (int) substr($rule, 4)),
                    str_starts_with($rule, 'max:') => $this->validateMax($field, $value, (int) substr($rule, 4)),
                    $rule === 'email' => $this->validateEmail($field, $value),
                    default => true, // Пропускаем неизвестные правила
                };

                if ($result !== true) {
                    $this->errors[$field][] = $result;
                }
            }
        }

        return empty($this->errors);
    }

    protected function validateRequired(string $field, $value): bool|string
    {
        if ($value === null || $value === '') {
            return "Поле {$field} обязательно.";
        }
        return true;
    }

    protected function validateMin(string $field, $value, int $min): bool|string
    {
        if (is_string($value) && strlen($value) < $min) {
            return "Поле {$field} должно быть не менее {$min} символов.";
        }
        return true;
    }

    protected function validateMax(string $field, $value, int $max): bool|string
    {
        if (is_string($value) && strlen($value) > $max) {
            return "Поле {$field} не должно превышать {$max} символов.";
        }
        return true;
    }

    protected function validateEmail(string $field, $value): bool|string
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return "Поле {$field} должно быть действительным email.";
        }
        return true;
    }

    /**
     * Возвращает ошибки валидации
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Проверяет, есть ли ошибки
     */
    public function fails(): bool
    {
        return !empty($this->errors);
    }
}