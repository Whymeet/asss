<?php

namespace PrintCalc\Core;

abstract class AbstractCalculator implements CalculatorInterface
{
    protected PriceConfig $config;

    public function __construct(PriceConfig $config)
    {
        $this->config = $config;
    }

    /**
     * Вычисление дополнительных услуг (общий функционал для всех калькуляторов)
     * 
     * @param bool $bigovka Биговка
     * @param int $cornerRadius Радиус скругления углов
     * @param bool $perforation Перфорация
     * @param bool $drill Сверление
     * @param bool $numbering Нумерация
     * @param int $quantity Тираж
     * @return float
     */
    protected function calculateAdditionalCosts(
        bool $bigovka,
        int $cornerRadius,
        bool $perforation,
        bool $drill,
        bool $numbering,
        int $quantity
    ): float {
        $cost = 0;

        if ($bigovka) {
            $cost += $quantity * $this->config->get('bigovka');
        }

        if ($cornerRadius > 0) {
            $cost += $cornerRadius * $this->config->get('corner_radius') * $quantity;
        }

        if ($perforation) {
            $cost += $quantity * $this->config->get('perforation');
        }

        if ($drill) {
            $cost += $quantity * $this->config->get('drill');
        }

        if ($numbering) {
            $cost += $quantity * ($quantity <= 1000 
                ? $this->config->get('numbering_small') 
                : $this->config->get('numbering_large'));
        }

        return $cost;
    }
}
