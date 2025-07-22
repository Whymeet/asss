<?php

trait PrintCalculationTrait {
    /**
     * Проверяет базовые параметры печати
     * @throws InvalidArgumentException
     */
    protected function validatePrintParameters($size, $quantity) {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Количество должно быть больше нуля');
        }
        
        if (!isset($this->priceConfig['size_coefficients'][$size])) {
            throw new InvalidArgumentException("Неподдерживаемый размер: $size");
        }
    }
    
    /**
     * Определяет тип печати на основе количества листов
     */
    protected function determinePrintingType($baseA3Sheets) {
        return $baseA3Sheets > $this->priceConfig['offset_threshold'] 
            ? CalculatorConstants::PRINTING_TYPE_OFFSET 
            : CalculatorConstants::PRINTING_TYPE_DIGITAL;
    }
    
    /**
     * Рассчитывает стоимость бумаги
     */
    protected function calculatePaperCost($paperType, $sheets) {
        if (isset($this->priceConfig['paper'][$paperType])) {
            return $sheets * $this->priceConfig['paper'][$paperType];
        }
        
        if (is_numeric($paperType)) {
            return $sheets * self::DEFAULT_PAPER_PRICE;
        }
        
        throw new InvalidArgumentException("Неподдерживаемый тип бумаги: $paperType");
    }
}
