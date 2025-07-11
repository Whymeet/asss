<?php
namespace Bitrix\PrintCalc;

class Calculator {
    private $priceConfig;

    public function __construct() {
        $this->priceConfig = [
            // Цены на бумагу (плотность => цена за лист, тип бумаги => цена)
            "paper" => [
                80.0   => 1.7,
                120.0  => 2.5,
                90.0   => 2.5,
                105.0  => 2.8,
                115.0  => 3.1,
                130.0  => 3.4,
                150.0  => 3.9,
                170.0  => 4.4,
                200.0  => 5.4,
                250.0  => 6.9,
                270.0  => 7.5,
                300.0  => 8.5,
                "Крафтовая" => 1.5,
                "Самоклейка" => 17.5,
                "Картон Одн" => 12.0,
                "Картон Двух" => 10.0
            ],
            // Цены на ламинацию
            "lamination" => [
                "offset" => [
                    "1+0" => 7,
                    "1+1" => 14
                ],
                "digital" => [
                    "32" => ["1+0" => 40, "1+1" => 80],
                    "75" => ["1+0" => 60, "1+1" => 120],
                    "125" => ["1+0" => 80, "1+1" => 160],
                    "250" => ["1+0" => 90, "1+1" => 180]
                ]
            ],
            "size_coefficients" => [
                "A7"   => 16.0,
                "A6"   => 8.0,
                "Евро" => 6.0,
                "A5"   => 4.0,
                "A4"   => 2.0,
                "A3"   => 1.0,
                "200X210" => 2.0,
                "9X9" => 15.0,          
                "100X70" => 16.0
            ],
            "adjustment_sheets" => [
                ["min" => 250, "max" => 500, "sheets" => 100],
                ["min" => 501, "max" => 1500, "sheets" => 150],
                ["min" => 1501, "max" => 3000, "sheets" => 250],
                ["min" => 3001, "max" => 5000, "sheets" => 350],
                ["min" => 5001, "max" => 10000, "sheets" => 450],
            ],
            "offset_prices" => [
                ["min" => 200, "max" => 500, "4+0" => 2580, "4+4" => 5170, "custom" => 3850],
                ["min" => 501, "max" => 700, "4+0" => 2860, "4+4" => 5370, "custom" => 4290],
                ["min" => 701, "max" => 900, "4+0" => 2940, "4+4" => 5530, "custom" => 4400],
                ["min" => 901, "max" => 1000, "4+0" => 3070, "4+4" => 5990, "custom" => 4600],
                ["min" => 1001, "max" => 1500, "4+0" => 3460, "4+4" => 6900, "custom" => 5170],
                ["min" => 1501, "max" => 2000, "4+0" => 3840, "4+4" => 7700, "custom" => 5760],
                ["min" => 2001, "max" => 2500, "4+0" => 4400, "4+4" => 8800, "custom" => 6600]
            ],
            "digital_prices" => [
                20 => ["4+0" => 50, "4+4" => 60],
                50 => ["4+0" => 45, "4+4" => 55],
                100 => ["4+0" => 35, "4+4" => 45],
                150 => ["4+0" => 25, "4+4" => 40],
                200 => ["4+0" => 20, "4+4" => 38]
            ],
            "plate_prices" => [
                "A3_double" => 2400,
                "default" => 1200
            ],
            "offset_threshold" => 200,
            "bigovka" => 1,
            "corner_radius" => 0.3,
            "perforation" => 0.5,
            "drill" => 0.4,
            "numbering_small" => 0.5,
            "numbering_large" => 0.3
        ];
    }

    public function calculatePrice($calcType, $paperType, $size, $quantity, $printType, $options = []) {
        // Проверка корректности данных
        if ($quantity <= 0 || !isset($this->priceConfig['size_coefficients'][$size])) {
            return ['error' => 'Некорректные данные'];
        }

        // Расчёт базовых значений
        $sizeCoefficient = $this->priceConfig['size_coefficients'][$size];
        $baseA3Sheets = ceil($quantity / $sizeCoefficient);
        $totalA3Sheets = $baseA3Sheets;
        
        // Определение типа печати
        $printingType = $baseA3Sheets > $this->priceConfig['offset_threshold'] ? 'Офсетная' : 'Цифровая';
        $adjustment = 0;
        $printingCost = 0;
        $plateCost = 0;
        $paperCost = 0;
        $totalPrice = 0;

        if ($printingType === 'Офсетная') {
            // Определение колонки для стоимости
            $priceColumn = ($printType === 'double') ? ($size === 'A3' ? '4+4' : 'custom') : '4+0';

            // Поиск стоимости печати
            foreach ($this->priceConfig['offset_prices'] as $range) {
                if ($totalA3Sheets >= $range['min'] && $totalA3Sheets <= $range['max']) {
                    $printingCost = $range[$priceColumn];
                    break;
                }
            }

            // Стоимость пластины
            $plateCost = ($size === 'A3' && $printType === 'double') 
                ? $this->priceConfig['plate_prices']['A3_double'] 
                : $this->priceConfig['plate_prices']['default'];
            
            // Расчёт приладочных листов
            foreach ($this->priceConfig['adjustment_sheets'] as $range) {
                if ($baseA3Sheets >= $range['min'] && $baseA3Sheets <= $range['max']) {
                    $adjustment = $range['sheets'];
                    break;
                }
            }
            $totalA3Sheets += $adjustment;

            // Стоимость бумаги
            $paperCost = $totalA3Sheets * $this->priceConfig['paper'][$paperType];
            $totalPrice = $paperCost + $printingCost + $plateCost;

        } else { // Цифровая печать
            // Определение колонки стоимости
            $priceColumn = ($printType === 'double') ? '4+4' : '4+0';
            $digitalPrice = 0;

            // Поиск цены в таблице цифровой печати
            foreach ($this->priceConfig['digital_prices'] as $max => $prices) {
                if ($baseA3Sheets <= $max) {
                    $digitalPrice = $prices[$priceColumn];
                    break;
                }
            }

            // Если количество больше максимального в таблице
            if ($digitalPrice === 0) {
                $lastRange = end($this->priceConfig['digital_prices']);
                $digitalPrice = $lastRange[$priceColumn];
            }

            $printingCost = $baseA3Sheets * $digitalPrice;
            $paperCost = $baseA3Sheets * $this->priceConfig['paper'][$paperType];
            $totalPrice = $paperCost + $printingCost;
        }

        // Добавляем дополнительные услуги
        $additionalCosts = $this->calculateAdditionalCosts(
            $options['bigovka'] ?? false,
            $options['corner_radius'] ?? 0,
            $options['perforation'] ?? false,
            $options['drill'] ?? false,
            $options['numbering'] ?? false,
            $quantity
        );
        $totalPrice += $additionalCosts;

        // Добавляем ламинацию если она запрошена
        if (!empty($options['lamination'])) {
            $laminationCost = $this->calculateLaminationCost(
                $printingType,
                $options['lamination_thickness'],
                $options['lamination_type'],
                $quantity
            );
            $totalPrice += $laminationCost;
        }

        return [
            'printingType' => $printingType,
            'baseA3Sheets' => $baseA3Sheets,
            'adjustment' => $adjustment,
            'totalA3Sheets' => $totalA3Sheets,
            'printingCost' => $printingCost,
            'plateCost' => $plateCost,
            'paperCost' => $paperCost,
            'totalPrice' => $totalPrice,
            'additionalCosts' => $additionalCosts
        ];
    }

    private function calculateAdditionalCosts($bigovka, $cornerRadius, $perforation, $drill, $numbering, $quantity) {
        $cost = 0;

        // Биговка
        if ($bigovka) {
            $cost += $quantity * $this->priceConfig['bigovka'];
        }

        // Скругление углов
        if ($cornerRadius > 0) {
            $cost += $cornerRadius * $this->priceConfig['corner_radius'] * $quantity;
        }

        // Перфорация
        if ($perforation) {
            $cost += $quantity * $this->priceConfig['perforation'];
        }

        // Сверление
        if ($drill) {
            $cost += $quantity * $this->priceConfig['drill'];
        }

        // Нумерация
        if ($numbering) {
            if ($quantity <= 1000) {
                $cost += $quantity * $this->priceConfig['numbering_small'];
            } else {
                $cost += $quantity * $this->priceConfig['numbering_large'];
            }
        }

        return $cost;
    }

    public function calculateLaminationCost($printingType, $thickness, $type, $quantity) {
        if ($printingType === 'Офсетная') {
            return $quantity * $this->priceConfig['lamination']['offset'][$type];
        } else {
            return $quantity * $this->priceConfig['lamination']['digital'][$thickness][$type];
        }
    }
}

?>
