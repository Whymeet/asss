<?php

namespace PrintCalc\Core;

/**
 * Трейт для общих методов расчета печати
 */
trait PrintCalculationTrait {
    /**
     * Определяет метод печати на основе количества листов
     */
    protected function determinePrintMethod(int $sheets): string {
        return $sheets > $this->config->get('offset_threshold') 
            ? Constants\PrintMethods::OFFSET 
            : Constants\PrintMethods::DIGITAL;
    }

    /**
     * Рассчитывает количество приладочных листов
     */
    protected function calculateAdjustmentSheets(int $baseSheets): int {
        foreach ($this->config->get('adjustment_sheets') as $range) {
            if ($baseSheets >= $range['min'] && $baseSheets <= $range['max']) {
                return $range['sheets'];
            }
        }
        return 0;
    }

    /**
     * Рассчитывает стоимость пластин для офсетной печати
     */
    protected function calculatePlateCost(string $size, string $printType): float {
        return ($size === 'A3' && $printType === Constants\PrintTypes::DOUBLE)
            ? $this->config->get('plate_prices.A3_double')
            : $this->config->get('plate_prices.default');
    }

    /**
     * Находит цену для цифровой печати
     */
    protected function findDigitalPrice(int $sheets, string $printType): float {
        $priceColumn = ($printType === Constants\PrintTypes::DOUBLE) ? '4+4' : '4+0';
        $digitalPrices = $this->config->get('digital_prices');
        
        foreach ($digitalPrices as $max => $prices) {
            if ($sheets <= $max) {
                return $prices[$priceColumn];
            }
        }
        
        // Если количество больше максимального в таблице
        $lastPrice = end($digitalPrices);
        return $lastPrice[$priceColumn];
    }
}
