<?php

namespace App\Core;

class Validator 
{
    protected array $errors = [];

    /**
     * Валидация входных данных по правилам
     * 
     * @-param array $data Данные для проверки (например, $_POST)
     * @-param array $rules Правила (например, ['email' => 'required|email|unique:users,email,15'])
     * @return bool
     */
    public function validate(array $data, array $rules): bool 
    {
        foreach ($rules as $field => $ruleset) {
            $rulesArray = explode('|', $ruleset);
            
            foreach ($rulesArray as $rule) {
                $parts = explode(':', $rule);
                $ruleName = $parts[0];
                $param = $parts[1] ?? null;
                
                $value = $data[$field] ?? null;
                $this->applyRule($field, $value, $ruleName, $param);
            }
        }

        return empty($this->errors);
    }

    /**
     * Применение конкретного правила к полю
     */
    protected function applyRule($field, $value, $rule, $param): void 
    {
        switch ($rule) {
            case 'required':
                if (empty($value) && $value !== 0 && $value !== '0') {
                    $this->errors[$field][] = "Поле $field обязательно для заполнения.";
                }
                break;

            case 'string':
                if (!is_string($value)) {
                    $this->errors[$field][] = "Поле $field должно быть строкой.";
                }
                break;

            case 'numeric':
                if (!is_numeric($value)) {
                    $this->errors[$field][] = "Поле $field должно быть числом.";
                }
                break;

            case 'email':
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->errors[$field][] = "Поле $field должно быть корректным email адресом.";
                }
                break;

            case 'min':
                if (strlen((string)$value) < (int)$param) {
                    $this->errors[$field][] = "Поле $field должно быть не короче $param символов.";
                }
                break;

            case 'max':
                if (strlen((string)$value) > (int)$param) {
                    $this->errors[$field][] = "Поле $field должно быть не длиннее $param символов.";
                }
                break;

            case 'unique':
                if (!empty($value)) {
                    $params = explode(',', $param);
                    $table = $params[0];
                    $column = $params[1];
                    $ignoreId = $params[2] ?? null;

                    $sqlQuery = DB::table($table)->where($column, $value);

                    if ($ignoreId !== null) {
                       $sqlQuery->where('id', '!=', $ignoreId);
                        $sql .= " AND id != ?";
                        $bindings[] = $ignoreId;
                    }
                    $hasField = $sqlQuery->first();

                    if ($hasField !== null) {
                        $this->errors[$field][] = "Такое значение поля $field уже занято.";
                    }
                }
                break;
        }
    }

    /**
     * Получить массив всех ошибок
     */
    public function getErrors(): array 
    {
        return $this->errors;
    }
}