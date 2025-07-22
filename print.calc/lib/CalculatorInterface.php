<?php

interface CalculatorInterface {
    /**
     * Основной метод расчета стоимости
     * @param array $params Параметры для расчета
     * @return array Результат расчета
     */
    public function calculate(array $params): array;
}
