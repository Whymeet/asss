<?php

namespace PrintCalc\Core;

interface CalculatorInterface
{
    /**
     * Выполнить расчет стоимости
     * 
     * @param array $params Параметры для расчета
     * @return array Результат расчета
     */
    public function calculate(array $params): array;

    /**
     * Валидация входных параметров
     * 
     * @param array $params Параметры для валидации
     * @return array Массив ошибок (пустой если ошибок нет)
     */
    public function validate(array $params): array;
}
