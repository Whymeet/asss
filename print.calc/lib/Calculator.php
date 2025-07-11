<?php

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
            // Настройки каталогов
            "catalog" => [
                "sheet_conversion" => [
                    "A4" => [8=>2,12=>3,16=>4,20=>5,24=>6,28=>7,32=>8,36=>9,40=>10,44=>11,48=>12,52=>13,56=>14,60=>15,64=>16],
                    "A5" => [8=>1,12=>2,16=>2,20=>3,24=>3,28=>4,32=>4,36=>5,40=>5,44=>6,48=>6,52=>7,56=>7,60=>8,64=>8],
                    "A6" => [8=>1,12=>1,16=>1,20=>2,24=>2,28=>2,32=>2,36=>3,40=>3,44=>3,48=>3,52=>3,56=>4,60=>4,64=>4]
                ],
                "collation_price" => 0.1
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
                ["min" => 2001, "max" => 2500, "4+0" => 4400, "4+4" => 8800, "custom" => 6600],
                ["min" => 2501, "max" => 3000, "4+0" => 4800, "4+4" => 9580, "custom" => 7200],
                ["min" => 3001, "max" => 3500, "4+0" => 4990, "4+4" => 9970, "custom" => 7480],
                ["min" => 3501, "max" => 4000, "4+0" => 5760, "4+4" => 10340, "custom" => 8630],
                ["min" => 4001, "max" => 4500, "4+0" => 6140, "4+4" => 11120, "custom" => 9200],
                ["min" => 4501, "max" => 5000, "4+0" => 6530, "4+4" => 12590, "custom" => 9780],
                ["min" => 5001, "max" => 6000, "4+0" => 7370, "4+4" => 14440, "custom" => 11044],
                ["min" => 6001, "max" => 7000, "4+0" => 8210, "4+4" => 16290, "custom" => 12308],
                ["min" => 7001, "max" => 8000, "4+0" => 9050, "4+4" => 18140, "custom" => 13572],
                ["min" => 8001, "max" => 9000, "4+0" => 9890, "4+4" => 19990, "custom" => 14836],
                ["min" => 9001, "max" => 10000, "4+0" => 10740, "4+4" => 21840, "custom" => 16100],
                ["min" => 10001, "max" => 40000, "4+0" => 10740, "4+4" => 21840, "custom" => 16100]
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

    public function calculatePrice($calcType, $paperType, $size, $quantity, $printType, $options = [])
    {
        // Проверяем обязательные параметры
        if (empty($paperType) || empty($size) || $quantity <= 0) {
            return ['error' => 'Не заполнены обязательные поля'];
        }

        // Проверяем корректность размера
        if (!isset($this->priceConfig['size_coefficients'][$size])) {
            return ['error' => 'Неподдерживаемый формат'];
        }

        // Извлекаем опции
        $bigovka = !empty($options['bigovka']);
        $cornerRadius = (int)($options['cornerRadius'] ?? 0);
        $perforation = !empty($options['perforation']);
        $drill = !empty($options['drill']);
        $numbering = !empty($options['numbering']);
        $folding = (int)($options['folding'] ?? 0);
        $withLamination = !empty($options['withLamination']);
        $laminationType = $options['laminationType'] ?? '';
        $laminationSide = $options['laminationSide'] ?? '';

        // Расчёт базовых значений
        $sizeCoefficient = $this->priceConfig['size_coefficients'][$size];
        $baseA3Sheets = ceil($quantity / $sizeCoefficient);
        $totalA3Sheets = $baseA3Sheets;
        
        // Определение типа печати
        $printingType = $baseA3Sheets > $this->priceConfig['offset_threshold'] ? 'Офсетная' : 'Цифровая';
        
        // Расчет стоимости печати
        $printingCost = 0;
        $plateCost = 0;
        $paperCost = 0;
        $additionalCosts = 0;
        $laminationCost = 0;
        $foldingCost = 0;

        // Расчет стоимости бумаги
        if (isset($this->priceConfig['paper'][$paperType])) {
            $paperCost = $totalA3Sheets * $this->priceConfig['paper'][$paperType];
        }

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

            // Стоимость пластин
            $plateCost = $this->priceConfig['plate_prices'][$size === 'A3' ? 'A3_double' : 'default'];
        } else {
            // Цифровая печать
            $priceKey = min(array_keys($this->priceConfig['digital_prices']));
            foreach ($this->priceConfig['digital_prices'] as $threshold => $prices) {
                if ($totalA3Sheets <= $threshold) {
                    break;
                }
                $priceKey = $threshold;
            }
            $printingCost = $totalA3Sheets * $this->priceConfig['digital_prices'][$priceKey][$printType === 'double' ? '4+4' : '4+0'];
        }

        // Расчет дополнительных услуг
        if ($bigovka) {
            $additionalCosts += $quantity * $this->priceConfig['bigovka'];
        }
        if ($cornerRadius > 0) {
            $additionalCosts += $quantity * $cornerRadius * $this->priceConfig['corner_radius'];
        }
        if ($perforation) {
            $additionalCosts += $quantity * $this->priceConfig['perforation'];
        }
        if ($drill) {
            $additionalCosts += $quantity * $this->priceConfig['drill'];
        }
        if ($numbering) {
            $numberingPrice = $quantity > 1000 ? $this->priceConfig['numbering_large'] : $this->priceConfig['numbering_small'];
            $additionalCosts += $quantity * $numberingPrice;
        }

        // Расчет стоимости сложения для буклетов
        if ($calcType === 'booklet' && $folding > 0) {
            $foldingCost = $quantity * $folding * 0.5; // 0.5 руб. за одно сложение
        }

        // Расчет стоимости ламинации
        if ($withLamination && !empty($laminationType) && !empty($laminationSide)) {
            if ($printingType === 'Офсетная') {
                $laminationCost = $quantity * $this->priceConfig['lamination']['offset'][$laminationSide];
            } else {
                $thickness = '32'; // По умолчанию используем 32 мкм
                $laminationCost = $quantity * $this->priceConfig['lamination']['digital'][$thickness][$laminationSide];
            }
        }

        // Общая стоимость
        $totalPrice = $printingCost + $plateCost + $paperCost + $additionalCosts + $laminationCost + $foldingCost;

        return [
            'printingType' => $printingType,
            'printingCost' => $printingCost,
            'plateCost' => $plateCost,
            'paperCost' => $paperCost,
            'additionalCosts' => $additionalCosts,
            'laminationCost' => $laminationCost,
            'foldingCost' => $foldingCost,
            'totalPrice' => $totalPrice,
            'baseA3Sheets' => $baseA3Sheets
        ];
    }
}

?>
