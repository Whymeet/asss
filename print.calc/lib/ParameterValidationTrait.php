<?php

trait ParameterValidationTrait {
    /**
     * Проверяет наличие обязательных параметров
     * @param array $params Массив параметров
     * @param array $required Массив обязательных ключей
     * @throws \InvalidArgumentException если отсутствует обязательный параметр
     */
    protected function validateRequired(array $params, array $required) {
        foreach($required as $param) {
            if (!isset($params[$param])) {
                throw new \InvalidArgumentException("Отсутствует обязательный параметр: $param");
            }
        }
    }
    
    /**
     * Проверяет корректность числовых параметров
     * @param array $params Массив параметров
     * @param array $numeric Массив ключей числовых параметров
     * @throws \InvalidArgumentException если параметр не является числом или меньше/равен нулю
     */
    protected function validateNumeric(array $params, array $numeric) {
        foreach($numeric as $param) {
            if (isset($params[$param])) {
                if (!is_numeric($params[$param]) || $params[$param] <= 0) {
                    throw new \InvalidArgumentException("Параметр $param должен быть положительным числом");
                }
            }
        }
    }
}
