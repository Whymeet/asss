<?php

$priceConfig = [
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
        300.0  => 8.5,  // Плотность бумаги 300.0 добавлена в конфигурацию
        "Крафтовая" => 1.5,

        "Самоклейка" => 17.5,
        "Картон Одн" => 12.0,
        "Картон Двух" => 10.0
    ],
    // Настройки каталогов
"catalog" => [ // Правильная структура
        "sheet_conversion" => [
            "A4" => [8=>2,12=>3,16=>4,20=>5,24=>6,28=>7,32=>8,36=>9,40=>10,44=>11,48=>12,52=>13,56=>14,60=>15,64=>16],
            "A5" => [8=>1,12=>2,16=>2,20=>3,24=>3,28=>4,32=>4,36=>5,40=>5,44=>6,48=>6,52=>7,56=>7,60=>8,64=>8], // Добавлены недостающие
            "A6" => [8=>1,12=>1,16=>1,20=>2,24=>2,28=>2,32=>2,36=>3,40=>3,44=>3,48=>3,52=>3,56=>4,60=>4,64=>4]  // Добавлены недостающие
        ],
        // Стоимость листоподборки (руб/лист)
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
        //Коэфиценты бумаги
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
    // цены на офсетную печать
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
    // цены на цифровую печать
    "digital_prices" => [
        20 => ["4+0" => 50, "4+4" => 60],
        50 => ["4+0" => 45, "4+4" => 55],
        100 => ["4+0" => 35, "4+4" => 45],
        150 => ["4+0" => 25, "4+4" => 40],
        200 => ["4+0" => 20, "4+4" => 38]
    ],
    // Цены на цифровую печать визиток
    "digital_vizit_prices" => [
        100 => ["4+0" => 5, "4+4" => 6],
        300 => ["4+0" => 4, "4+4" => 5.5],
        500 => ["4+0" => 3.9, "4+4" => 5.2],
        1000 => ["4+0" => 3.7, "4+4" => 5.1]
    ],
    // Цены на офсетную печать визиток
    "offset_vizit_prices" => [
        ["min" => 1000, "max" => 1999, "price" => 1.5],
        ["min" => 2000, "max" => 2999, "price" => 1.4],
        ["min" => 3000, "max" => 3999, "price" => 1.3],
        ["min" => 4000, "max" => 12000, "price" => 1.2]
    ],
    // Цены на пластины для офсетной печати
    "plate_prices" => [
        "A3_double" => 2400,
        "default" => 1200
    ],
    // Порог для перехода на офсетную печать
    "offset_threshold" => 200,
    "bigovka" => 1, // 1 руб. за лист
    "corner_radius" => 0.3, // 0.3 рубля за угол
    "perforation" => 0.5, // 0.5 руб. за лист
    "drill" => 0.4, // 0.4 руб. за лист
    "numbering_small" => 0.5, // 0.5 руб. за лист при тиражах до 1000
    "numbering_large" => 0.3, // 0.3 руб. за лист при тиражах более 1000
    "rizo_prices" => [
        100 => ["1+0" => 2.9, "1+1" => 4.6],
        200 => ["1+0" => 2.4, "1+1" => 3.8],
        300 => ["1+0" => 2.1, "1+1" => 3.0],
        499 => ["1+0" => 1.6, "1+1" => 2.6]
    ],
    "offset_rizo_prices" => [
        ["min" => 500, "max" => 2000, "1+0" => 1240, "1+1" => 1600, "adjustment" => 100],
        ["min" => 2001, "max" => 4000, "1+0" => 1270, "1+1" => 1650, "adjustment" => 150],
        ["min" => 4001, "max" => 10000, "1+0" => 1350, "1+1" => 1800, "adjustment" => 200]
    ],
"plate_price" => 600,
"canvas_prices" => [
        30 => [30 => 500, 40 => 700, 50 => 800, 60 => 1000, 70 => 1200, 80 => 1300, 90 => 1400, 100 => 1500],
        40 => [30 => 700, 40 => 800, 50 => 1000, 60 => 1200, 70 => 1300, 80 => 1400, 90 => 1500, 100 => 1600],
        50 => [30 => 800, 40 => 1000, 50 => 1200, 60 => 1300, 70 => 1400, 80 => 1500, 90 => 1600, 100 => 1700],
        60 => [30 => 1000, 40 => 1200, 50 => 1300, 60 => 1400, 70 => 1500, 80 => 1600, 90 => 1700, 100 => 1790],
        70 => [30 => 1200, 40 => 1300, 50 => 1400, 60 => 1500, 70 => 1600, 80 => 1700, 90 => 1790, 100 => 2100],
        80 => [30 => 1300, 40 => 1400, 50 => 1500, 60 => 1600, 70 => 1700, 80 => 1790, 90 => 2100, 100 => 2300],
        90 => [30 => 1400, 40 => 1500, 50 => 1600, 60 => 1700, 70 => 1790, 80 => 2100, 90 => 2300, 100 => 2400],
        100 => [30 => 1500, 40 => 1600, 50 => 1700, 60 => 1790, 70 => 2100, 80 => 2300, 90 => 2400, 100 => 2700]
    ],
    "podramnik_prices" => [
        30 => [30 => 500, 40 => 600, 50 => 700, 60 => 800, 70 => 900, 80 => 1000, 90 => 1100, 100 => 1200],
        40 => [30 => 600, 40 => 700, 50 => 800, 60 => 900, 70 => 1000, 80 => 1100, 90 => 1200, 100 => 1300],
        50 => [30 => 700, 40 => 800, 50 => 900, 60 => 1000, 70 => 1100, 80 => 1200, 90 => 1300, 100 => 1400],
        60 => [30 => 800, 40 => 900, 50 => 1000, 60 => 1100, 70 => 1200, 80 => 1300, 90 => 1400, 100 => 1500],
        70 => [30 => 900, 40 => 1000, 50 => 1100, 60 => 1200, 70 => 1300, 80 => 1400, 90 => 1500, 100 => 1600],
        80 => [30 => 1000, 40 => 1100, 50 => 1200, 60 => 1300, 70 => 1400, 80 => 1500, 90 => 1600, 100 => 1700],
        90 => [30 => 1100, 40 => 1200, 50 => 1300, 60 => 1400, 70 => 1500, 80 => 1600, 90 => 1700, 100 => 1800],
        100 => [30 => 1200, 40 => 1300, 50 => 1400, 60 => 1500, 70 => 1600, 80 => 1700, 90 => 1800, 100 => 1900]
    ],
    "pocket_limits" => [
        "a4_points" => 2,   
        "a5_points" => 1,   
        "max_points_per_m2" => 20 
    ],
    
    "pvc_prices" => [
        "3mm" => 1500, // цена за м² для 3 мм
        "5mm" => 2000  // цена за м² для 5 мм
    ],
    
    "pocket_prices" => [
        "flat_a4" => 50,   // цена плоского кармана А4
        "flat_a5" => 30,   // цена плоского кармана А5
        "volume_a4" => 80, // цена объемного кармана А4
        "volume_a5" => 50  // цена объемного кармана А5
    ],
    "calendar_prices" => [
        "wall_assembly" => [
            "A4" => [
                100 => 28,
                500 => 26,
                1000 => 24,
                "max" => 23
            ],
            "A3" => [
                100 => 31,
                500 => 29,
                1000 => 27,
                "max" => 26
            ]
        ]
    ],
"note" => [
        "available_sizes" => ["A4", "A5", "A6"],
        "paper" => [
            "cover" => 300.0,
            "back" => 300.0,
            "inner" => 80.0
        ],
        "inner_pages" => [40, 50],
        "binding" => [
            "spiral" => [
                "A4" => [100 => 18, 500 => 16, 1000 => 14, "max" => 13],
                "A5" => [100 => 16, 500 => 14, 1000 => 12, "max" => 11],
                "A6" => [100 => 14, 500 => 12, 1000 => 10, "max" => 9]
            ]
        ],
        "services" => [
            "bigovka" => 0.15,
            "perforation" => 0.20,
            "drill" => 0.30,
            "numbering" => 0.05,
            "corner_radius" => [
                0 => 0,
                1 => 0.10,
                2 => 0.20,
                3 => 0.30,
                4 => 0.40
            ]
        ]
    ]
];

// Функция для вычисления стоимости
// Базовая функция для печати. На основе этой функции считаются следующие функции 
function calculatePrice($paperType, $size, $quantity, $printType, $foldingCount = 0, $bigovka = false, $cornerRadius = 0, $perforation = false, $drill = false, $numbering = false) {
    global $priceConfig;

    // Проверка корректности данных
    if ($quantity <= 0 || !isset($priceConfig['size_coefficients'][$size])) {
        return ['error' => 'Некорректные данные'];
    }

    // Расчёт базовых значений
    $sizeCoefficient = $priceConfig['size_coefficients'][$size];
    $baseA3Sheets = ceil($quantity / $sizeCoefficient);
    $totalA3Sheets = $baseA3Sheets;
    
    // Определение типа печати
    $printingType = $baseA3Sheets > $priceConfig['offset_threshold'] ? 'Офсетная' : 'Цифровая';
    $adjustment = 0;
    $printingCost = 0;
    $plateCost = 0;
    $paperCost = 0;
    $totalPrice = 0;

    if ($printingType === 'Офсетная') {
        // Определение колонки для стоимости
        $priceColumn = ($printType === 'double') ? ($size === 'A3' ? '4+4' : 'custom') : '4+0';

        // Поиск стоимости печати
        foreach ($priceConfig['offset_prices'] as $range) {
            if ($totalA3Sheets >= $range['min'] && $totalA3Sheets <= $range['max']) {
                $printingCost = $range[$priceColumn];
                break;
            }
        }

        // Стоимость пластины
        $plateCost = ($size === 'A3' && $printType === 'double') 
            ? $priceConfig['plate_prices']['A3_double'] 
            : $priceConfig['plate_prices']['default'];
        
        // Расчёт приладочных листов
        foreach ($priceConfig['adjustment_sheets'] as $range) {
            if ($baseA3Sheets >= $range['min'] && $baseA3Sheets <= $range['max']) {
                $adjustment = $range['sheets'];
                break;
            }
        }
        $totalA3Sheets += $adjustment;

        // Стоимость бумаги
        $paperCost = $totalA3Sheets * $priceConfig['paper'][$paperType];
        $totalPrice = $paperCost + $printingCost + $plateCost;

        // Добавляем сложения для офсетной печати
        $totalPrice += $foldingCount * ($size === 'A3' ? 0.2 : 0.4) * $quantity;
    } else { // Цифровая печать
        // Определение колонки стоимости
        $priceColumn = ($printType === 'double') ? '4+4' : '4+0';
        $digitalPrice = 0;

        // Поиск цены в таблице цифровой печати
        foreach ($priceConfig['digital_prices'] as $max => $prices) {
            if ($baseA3Sheets <= $max) {
                $digitalPrice = $prices[$priceColumn];
                break;
            }
        }

        // Если количество больше максимального в таблице
        if ($digitalPrice === 0) {
            $lastRange = end($priceConfig['digital_prices']);
            $digitalPrice = $lastRange[$priceColumn];
        }

        $printingCost = $baseA3Sheets * $digitalPrice;
        $paperCost = $baseA3Sheets * $priceConfig['paper'][$paperType];
        $totalPrice = $paperCost + $printingCost;

        // Добавляем сложения для цифровой печати
        $totalPrice += $foldingCount * 0.4 * $quantity;
    }

    // Добавляем дополнительные услуги
    $additionalCosts = calculateAdditionalCosts($bigovka, $cornerRadius, $perforation, $drill, $numbering, $quantity);
    $totalPrice += $additionalCosts;

    return [
        'printingType' => $printingType,
        'baseA3Sheets' => $baseA3Sheets,
        'adjustment' => $adjustment,
        'totalA3Sheets' => $totalA3Sheets,
        'printingCost' => $printingCost,
        'plateCost' => $plateCost,
        'paperCost' => $paperCost,
        'totalPrice' => $totalPrice,
        'additionalCosts' => $additionalCosts, // Возвращаем дополнительные услуги
        'printingType' => $printingType, // Добавляем тип печати в результат
        'laminationAvailable' => true // Флаг доступности ламинации
        

    ];
}

// Функция для вычисления стоимости ризографической и офсетной печати
function calculateRizoPrice($paperType, $size, $quantity, $printType, $bigovka = false, $cornerRadius = 0, $perforation = false, $drill = false, $numbering = false) {
    global $priceConfig;

    // Проверка корректности данных
    if ($quantity <= 0 || !isset($priceConfig['size_coefficients'][$size])) {
        return ['error' => 'Некорректные данные'];
    }

    // Расчёт базовых значений
    $sizeCoefficient = $priceConfig['size_coefficients'][$size];
    $baseA3Sheets = ceil($quantity / $sizeCoefficient);
    $totalA3Sheets = $baseA3Sheets;

    // Определение типа печати
    $printingType = $baseA3Sheets > 499 ? 'Офсетная' : 'Ризографическая';
    $adjustment = 0;
    $printingCost = 0;
    $plateCost = 0;
    $paperCost = 0;
    $totalPrice = 0;

    if ($printingType === 'Офсетная') {
        // Определение колонки для стоимости
        $priceColumn = ($printType === 'double') ? '1+1' : '1+0';

        // Поиск стоимости печати
        foreach ($priceConfig['offset_rizo_prices'] as $range) {
            if ($totalA3Sheets >= $range['min'] && $totalA3Sheets <= $range['max']) {
                $printingCost = $range[$priceColumn];
                $adjustment = $range['adjustment'];
                break;
            }
        }

        // Стоимость пластины
        $plateCost = $priceConfig['plate_price'];
        $totalA3Sheets += $adjustment;

        // Стоимость бумаги
        $paperCost = $totalA3Sheets * $priceConfig['paper'][$paperType];
        $totalPrice = $paperCost + $printingCost + $plateCost;
    } else { // Ризографическая печать
        // Определение колонки стоимости
        $priceColumn = ($printType === 'double') ? '1+1' : '1+0';
        $rizoPrice = 0;

        // Поиск цены в таблице ризографической печати
        foreach ($priceConfig['rizo_prices'] as $max => $prices) {
            if ($baseA3Sheets <= $max) {
                $rizoPrice = $prices[$priceColumn];
                break;
            }
        }

        $printingCost = $baseA3Sheets * $rizoPrice;
        $paperCost = $baseA3Sheets * $priceConfig['paper'][$paperType];
        $totalPrice = $paperCost + $printingCost;
    }

    // Добавляем дополнительные услуги
    $additionalCosts = calculateAdditionalCosts($bigovka, $cornerRadius, $perforation, $drill, $numbering, $quantity);
    $totalPrice += $additionalCosts;

    return [
        'printingType' => $printingType,
        'baseA3Sheets' => $baseA3Sheets,
        'adjustment' => $adjustment,
        'totalA3Sheets' => $totalA3Sheets,
        'printingCost' => $printingCost,
        'plateCost' => $plateCost,
        'paperCost' => $paperCost,
        'totalPrice' => $totalPrice,
        'additionalCosts' => $additionalCosts // Возвращаем дополнительные услуги
    ];
}

// Функция для вычисления стоимости визиток
function calculateVizitPrice($printType, $quantity, $sideType = 'single') {
    global $priceConfig;

    // Валидация данных
    $errors = [];
    if ($printType === 'offset') {
        if ($quantity < 1000 || $quantity > 12000 || $quantity % 1000 !== 0) {
            $errors[] = "Для офсетной печати выберите тираж из списка";
        }
    } else {
        if ($quantity < 100 || $quantity > 999) {
            $errors[] = "Для цифровой печати введите тираж от 100 до 999";
        }
    }

    if (!empty($errors)) {
        return ['error' => implode("<br>", $errors)];
    }

    // Расчет стоимости
    $totalPrice = 0;
    
    if ($printType === 'offset') {
        foreach ($priceConfig['offset_vizit_prices'] as $range) {
            if ($quantity >= $range['min'] && $quantity <= $range['max']) {
                $totalPrice = $quantity * $range['price'];
                break;
            }
        }
    } else {
        $priceColumn = ($sideType === 'double') ? '4+4' : '4+0';
        foreach ($priceConfig['digital_vizit_prices'] as $max => $prices) {
            if ($quantity <= $max) {
                $totalPrice = $quantity * $prices[$priceColumn];
                break;
            }
        }
    }

    return [
        'printType' => $printType === 'offset' ? 'Офсетная' : 'Цифровая',
        'quantity' => $quantity,
        'totalPrice' => $totalPrice
    ];
}

// Функция для округления значения до ближайшего разрешенного размера
function ceilToNearest($value, $allowed) {
    // Находим максимальное разрешенное значение
    $maxAllowed = max($allowed);
    
    // Если значение больше максимального разрешенного, возвращаем его без изменений
    if ($value > $maxAllowed) {
        return $value;
    }
    
    // Сортируем массив разрешенных значений
    sort($allowed);
    
    // Проходим по каждому разрешенному значению
    foreach ($allowed as $a) {
        // Если текущее разрешенное значение больше или равно заданному, возвращаем его
        if ($a >= $value) {
            return $a;
        }
    }
    
    // Если не найдено подходящее значение, возвращаем исходное значение
    return $value;
}

// Функция для вычисления стоимости холста
function calculateCanvasPrice($width, $height, $includePodramnik) {
    global $priceConfig; 

    // Проверка на корректность входных данных: ширина и высота должны быть больше нуля
    if ($width <= 0 || $height <= 0) {
        return ['error' => 'Ширина и высота должны быть больше нуля.'];
    }

    // Разрешенные размеры для округления
    $allowedSizes = [30, 40, 50, 60, 70, 80, 90, 100];
    
    // Округляем ширину и высоту до ближайших разрешенных значений
    $roundedWidth = ceilToNearest($width, $allowedSizes);
    $roundedHeight = ceilToNearest($height, $allowedSizes);

    // Инициализация переменных для стоимости холста и подрамника
    $canvasPrice = 0;
    $podramnikPrice = 0;

    // Если хотя бы один размер больше 100 см
    if ($roundedWidth > 100 || $roundedHeight > 100) {
        // Вычисляем площадь в квадратных метрах
        $area = ($width * $height) / 10000; // Переводим см² в м²
        // Стоимость холста для больших размеров
        $canvasPrice = $area * 2700; // 2700 руб. за м²
        // Если подрамник включен, рассчитываем его стоимость
        $podramnikPrice = $includePodramnik ? $area * 1900 : 0; // 1900 руб. за м²
    } else {
        // Проверяем, существует ли цена для заданных округленных размеров
        if (!isset($priceConfig['canvas_prices'][$roundedHeight][$roundedWidth])) {
            return ['error' => 'Неверный размер холста.']; // Возвращаем ошибку, если размер неверный
        }
        // Получаем стоимость холста из конфигурации
        $canvasPrice = $priceConfig['canvas_prices'][$roundedHeight][$roundedWidth];

        // Если подрамник включен, проверяем его стоимость
        if ($includePodramnik) {
            if (!isset($priceConfig['podramnik_prices'][$roundedHeight][$roundedWidth])) {
                return ['error' => 'Неверный размер подрамника.']; // Возвращаем ошибку, если размер подрамника неверный
            }
            // Получаем стоимость подрамника из конфигурации
            $podramnikPrice = $priceConfig['podramnik_prices'][$roundedHeight][$roundedWidth];
        }
    }

    // Общая стоимость = стоимость холста + стоимость подрамника
    $totalPrice = $canvasPrice + $podramnikPrice;

    // Возвращаем массив с деталями расчета
    return [
        'canvasPrice' => $canvasPrice, // Стоимость холста
        'podramnikPrice' => $podramnikPrice, // Стоимость подрамника
        'totalPrice' => $totalPrice, // Общая стоимость
        'roundedWidth' => $roundedWidth, // Округленная ширина
        'roundedHeight' => $roundedHeight, // Округленная высота
        'area' => isset($area) ? $area : null // Площадь, если она была рассчитана
    ];
}
// Функция для вычисления дополнительных услуг
function calculateAdditionalCosts($bigovka, $cornerRadius, $perforation, $drill, $numbering, $quantity) {
    global $priceConfig;

    $cost = 0;

    // Биговка
    if ($bigovka) {
        $cost += $quantity * $priceConfig['bigovka'];
    }

    // Скругление углов
    if ($cornerRadius > 0) {
        $cost += $cornerRadius * $priceConfig['corner_radius'] * $quantity;
    }

    // Перфорация
    if ($perforation) {
        $cost += $quantity * $priceConfig['perforation'];
    }

    // Сверление диаметром 5мм
    if ($drill) {
        $cost += $quantity * $priceConfig['drill'];
    }

    // Нумерация
    if ($numbering) {
        if ($quantity <= 1000) {
            $cost += $quantity * $priceConfig['numbering_small'];
        } else {
            $cost += $quantity * $priceConfig['numbering_large'];
        }
    }

    return $cost;
}


function calculatePVCPrice($width, $height, $pvcType, $flatA4, $flatA5, $volumeA4, $volumeA5) {
    global $priceConfig;
    
    // Валидация входных данных
    $errors = [];
    if ($width <= 0 || $height <= 0) $errors[] = "Некорректные размеры";
    if ($flatA4 < 0 || $flatA5 < 0 || $volumeA4 < 0 || $volumeA5 < 0) $errors[] = "Количество карманов не может быть отрицательным";
    
    if (!empty($errors)) return ['error' => implode("<br>", $errors)];
    
    // Расчет площади
    $area = ($width / 100) * ($height / 100); // Перевод см в метры
    $totalPoints = ($flatA4 + $volumeA4) * $priceConfig['pocket_limits']['a4_points'] 
                 + ($flatA5 + $volumeA5) * $priceConfig['pocket_limits']['a5_points'];
                 
    $maxAllowedPoints = $area * $priceConfig['pocket_limits']['max_points_per_m2'];
    
    if ($totalPoints > $maxAllowedPoints) {
        return ['error' => "Превышено максимальное количество карманов. Максимум: " . floor($maxAllowedPoints) . " баллов"];
    }
    
    // Расчет стоимости
    $pvcCost = $area * $priceConfig['pvc_prices'][$pvcType];
    
    $pocketsCost = 
        $flatA4 * $priceConfig['pocket_prices']['flat_a4'] +
        $flatA5 * $priceConfig['pocket_prices']['flat_a5'] +
        $volumeA4 * $priceConfig['pocket_prices']['volume_a4'] +
        $volumeA5 * $priceConfig['pocket_prices']['volume_a5'];
    
    return [
        'pvcCost' => $pvcCost,
        'pocketsCost' => $pocketsCost,
        'totalPrice' => $pvcCost + $pocketsCost,
        'area' => $area,
        'totalPoints' => $totalPoints,
        'maxPoints' => $maxAllowedPoints
    ];
}
function calculateCalendarPrice($type, $size, $quantity, $printType, $pages = 14) {
    global $priceConfig;
    
    $result = [];
    $additionalCost = 0;

    switch($type) {
        case 'desktop':
            // Настольный A4 300гр 4+0 с 3 биговками
            $result = calculatePrice(300.0, "A4", $quantity, 'single', 0, true, 0, false, false, false);
            $result['totalPrice'] += 3 * $quantity * $priceConfig['bigovka'];
            break;

        case 'pocket':
            // Карманный A6 300гр с 4 углами
            $result = calculatePrice(300.0, "100X70", $quantity, $printType, 0, false, 4, false, false, false);
            break;

        case 'wall':
            // Настенный перекидной
            $baseSize = ($size === "A4") ? "A4" : "A3";
            $sheets = ($baseSize === "A4") ? 7 : 14; // Для A4 7 листов A3
            
            // Расчет стоимости печати
            $printResult = calculatePrice(300.0, $baseSize, $quantity * $sheets, $printType);
            
            // Расчет стоимости сборки
            $assemblyPrice = 0;
            foreach ($priceConfig['calendar_prices']['wall_assembly'][$size] as $max => $price) {
                if ($quantity <= $max) {
                    $assemblyPrice = $price * $quantity;
                    break;
                }
            }
            
            $result = [
                'printingCost' => $printResult['printingCost'],
                'assemblyCost' => $assemblyPrice,
                'totalPrice' => $printResult['totalPrice'] + $assemblyPrice
            ];
            break;
    }

    return $result;
}

function calculateBindingCost($size, $quantity) {
    $bindingPrices = [
        "A4" => [
            100 => 18,
            500 => 16,
            1000 => 14,
            "max" => 13
        ],
        "A5" => [
            100 => 16,
            500 => 14,
            1000 => 12,
            "max" => 11
        ],
        "A6" => [
            100 => 14,
            500 => 12,
            1000 => 10,
            "max" => 9
        ]
    ];

    $price = 0;
    if ($quantity <= 100) {
        $price = $bindingPrices[$size][100];
    } elseif ($quantity <= 500) {
        $price = $bindingPrices[$size][500];
    } elseif ($quantity <= 1000) {
        $price = $bindingPrices[$size][1000];
    } else {
        $price = $bindingPrices[$size]["max"];
    }

    return $price * $quantity;
}

function calculateStapleCost($quantity) {
    return 0.4 * 2 * $quantity; // 0.4 руб/скоба * 2 скобы * тираж
}
function calculateCatalogPrice(
    $coverPaper, 
    $coverPrintType,
    $innerPaper,
    $innerPrintType,
    $size,
    $pages,
    $quantity,
    $bindingType
) {
    global $priceConfig;

    // Нормализация размера
    $size = mb_convert_case($size, MB_CASE_UPPER, "UTF-8");

    // Проверка существования формата
    if (!isset($priceConfig["catalog"]["sheet_conversion"][$size])) {
        $allowedSizes = implode(', ', array_keys($priceConfig["catalog"]["sheet_conversion"]));
        return ['error' => "Неверный формат каталога. Допустимые форматы: $allowedSizes"];
    }

    $conversionData = $priceConfig["catalog"]["sheet_conversion"][$size];
    $pages = (int)$pages;

    // Проверка страниц
    if (!isset($conversionData[$pages])) {
        $allowedPages = implode(', ', array_keys($conversionData));
        return ['error' => "Недопустимое количество страниц для формата $size. Допустимые значения: $allowedPages"];
    }

    // Валидация других параметров
    $errors = [];
    if (!in_array($coverPaper, [130, 170, 300], true)) {
        $errors[] = "Некорректная плотность обложки";
    }
    if (!in_array($innerPaper, [130, 170], true)) {
        $errors[] = "Некорректная плотность внутренних листов";
    }
    if ($quantity <= 0) {
        $errors[] = "Некорректный тираж";
    }
    
    if (!empty($errors)) {
        return ['error' => implode("<br>", $errors)];
    }

    $totalPrice = 0;
    $isSamePaper = ($coverPaper == $innerPaper);

    // Расчет обложки и внутренних листов
    if ($isSamePaper) {
        // Если бумага одинаковая
        $a3Sheets = $conversionData[$pages] * $quantity;
        $coverResult = calculatePrice(
            $coverPaper,
            $size,
            $a3Sheets,
            $coverPrintType
        );
        $totalPrice += $coverResult['totalPrice'];
    } else {
        // Если бумага разная
        $coverSizeMap = [
            "A4" => "A3",
            "A5" => "A4",
            "A6" => "A5"
        ];
        
        if (!isset($coverSizeMap[$size])) {
            return ['error' => "Неверный формат для расчета обложки"];
        }

        // Расчет обложки
        $coverSize = $coverSizeMap[$size];
        $coverSheets = ceil($quantity / 2);
        $coverResult = calculatePrice(
            $coverPaper,
            $coverSize,
            $coverSheets,
            $coverPrintType
        );
        $totalPrice += $coverResult['totalPrice'];

        // Расчет внутренних листов
        $adjustedPages = max(8, $pages - 4);
        $innerSheets = $conversionData[$adjustedPages] * $quantity;
        $innerResult = calculatePrice(
            $innerPaper,
            $size,
            $innerSheets,
            $innerPrintType
        );
        $totalPrice += $innerResult['totalPrice'];
    }

    // Листоподборка
    $collationCost = $pages * $quantity * $priceConfig["catalog"]["collation_price"];
    $totalPrice += $collationCost;

    // Расчет стоимости сборки
    if ($bindingType === 'staple') {
        $bindingCost = calculateStapleCost($quantity);
    } else {
        $bindingCost = calculateBindingCost($size, $quantity);
    }
    $totalPrice += $bindingCost;

    return [
        'coverCost' => $isSamePaper ? 0 : $coverResult['totalPrice'],
        'innerCost' => $isSamePaper ? $coverResult['totalPrice'] : $innerResult['totalPrice'],
        'collationCost' => $collationCost,
        'bindingCost' => $bindingCost,
        'totalPrice' => $totalPrice,
        'sheets' => $conversionData[$pages],
        'isSamePaper' => $isSamePaper,
        'adjustedPages' => $adjustedPages ?? $pages
    ];
}



function calculateNotePrice($params) {
    global $priceConfig;
    
    // Получаем конфигурацию блокнотов
    $noteConfig = $priceConfig['note'];
    
    // 1. Валидация и подготовка параметров
    $size = in_array($params['size'], $noteConfig['available_sizes']) 
        ? $params['size'] 
        : $noteConfig['available_sizes'][0];
    
    $quantity = max(1, (int)$params['quantity']);
    $innerPages = in_array($params['inner_pages'], $noteConfig['inner_pages']) 
        ? $params['inner_pages'] 
        : $noteConfig['inner_pages'][0];
    
    $services = [
        'bigovka' => (bool)$params['bigovka'],
        'perforation' => (bool)$params['perforation'],
        'drill' => (bool)$params['drill'],
        'numbering' => (bool)$params['numbering'],
        'cornerRadius' => (int)$params['corner_radius']
    ];

    // 2. Расчет обложки
    $coverParams = [
        'paperType' => $noteConfig['paper']['cover'],
        'size' => $size,
        'quantity' => $quantity,
        'printType' => ($params['cover_print'] === '4+4') ? 'double' : 'single',
        'services' => $services
    ];
    
    $coverResult = calculateComponent($coverParams);
    
    // 3. Расчет задника
    $backPrintType = $params['back_print'];
    $backParams = [
        'paperType' => $noteConfig['paper']['back'],
        'size' => $size,
        'quantity' => $quantity,
        'printType' => ($backPrintType === '4+4') ? 'double' : 
                      ($backPrintType === '4+0' ? 'single' : 'none'),
        'services' => $services
    ];
    
    $backResult = calculateComponent($backParams);
    
    // 4. Расчет внутреннего блока
    $totalInnerSheets = $quantity * $innerPages;
    $innerPrintType = $params['inner_print'];
    
    if (strpos($innerPrintType, '1+') === 0) {
        // Ризография
        $innerResult = calculateRizoComponent([
            'paperType' => $noteConfig['paper']['inner'],
            'size' => $size,
            'quantity' => $totalInnerSheets,
            'printType' => $innerPrintType,
            'services' => $services
        ]);
    } else {
        // Обычная печать
        $innerParams = [
            'paperType' => $noteConfig['paper']['inner'],
            'size' => $size,
            'quantity' => $totalInnerSheets,
            'printType' => ($innerPrintType === '4+4') ? 'double' : 'single',
            'services' => $services
        ];
        
        $innerResult = calculateComponent($innerParams);
    }
    
    // 5. Расчет сборки
    $bindingCost = calculateNoteBinding([
        'size' => $size,
        'quantity' => $quantity,
        'config' => $noteConfig['binding']
    ]);
    
    // 6. Суммирование всех компонентов
    $totalPrice = array_sum([
        $coverResult['total'],
        $backResult['total'],
        $innerResult['total'],
        $bindingCost
    ]);
    
    return [
        'components' => [
            'cover' => $coverResult,
            'back' => $backResult,
            'inner' => $innerResult
        ],
        'binding' => $bindingCost,
        'total' => $totalPrice,
        'details' => [
            'quantity' => $quantity,
            'size' => $size,
            'inner_pages' => $innerPages,
            'services' => $services
        ]
    ];
}

// Вспомогательные функции для расчета компонентов
function calculateComponent($params) {
    global $priceConfig;
    
    // Базовый расчет стоимости печати
    $baseResult = calculatePrice(
        $params['paperType'],
        $params['size'],
        $params['quantity'],
        $params['printType']
    );
    
    // Добавляем дополнительные услуги
    $additionalCost = calculateAdditionalServices(
        $params['services'],
        $params['quantity']
    );
    
    return [
        'base' => $baseResult,
        'additional' => $additionalCost,
        'total' => $baseResult['totalPrice'] + $additionalCost
    ];
}

function calculateRizoComponent($params) {
    global $priceConfig;
    
    // Расчет ризографии
    $baseResult = calculateRizoPrice(
        $params['paperType'],
        $params['size'],
        $params['quantity'],
        $params['printType']
    );
    
    // Добавляем дополнительные услуги
    $additionalCost = calculateAdditionalServices(
        $params['services'],
        $params['quantity']
    );
    
    return [
        'base' => $baseResult,
        'additional' => $additionalCost,
        'total' => $baseResult['totalPrice'] + $additionalCost
    ];
}

function calculateNoteBinding($params) {
    $config = $params['config']['spiral'][$params['size']];
    $quantity = $params['quantity'];
    
    if ($quantity <= 100) {
        return $config[100] * $quantity;
    } elseif ($quantity <= 500) {
        return $config[500] * $quantity;
    } elseif ($quantity <= 1000) {
        return $config[1000] * $quantity;
    } else {
        return $config['max'] * $quantity;
    }
}

function calculateAdditionalServices($services, $quantity) {
    global $priceConfig;
    
    $cost = 0;
    $noteServices = $priceConfig['note']['services'];
    
    // Биговка
    if ($services['bigovka']) {
        $cost += $quantity * $noteServices['bigovka'];
    }
    
    // Перфорация
    if ($services['perforation']) {
        $cost += $quantity * $noteServices['perforation'];
    }
    
    // Сверление
    if ($services['drill']) {
        $cost += $quantity * $noteServices['drill'];
    }
    
    // Нумерация
    if ($services['numbering']) {
        $cost += $quantity * $noteServices['numbering'];
    }
    
    // Скругление углов
    $cornerCost = $noteServices['corner_radius'][$services['cornerRadius']] ?? 0;
    $cost += $quantity * $cornerCost;
    
    return $cost;
}

?>
