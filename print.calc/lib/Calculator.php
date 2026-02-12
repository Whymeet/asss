<?php

// Все цены вынесены в отдельный файл lib/prices.php
$priceConfig = require __DIR__ . '/prices.php';

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
    global $priceConfig;
    $bindingPrices = $priceConfig['note']['binding']['spiral'];

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

/**
 * Расчет стоимости баннера
 * @param float $length Длина в метрах
 * @param float $width Ширина в метрах  
 * @param string $bannerType Тип баннера
 * @param bool $hasHemming Проклейка
 * @param bool $hasGrommets Люверсы
 * @param float $grommetStep Шаг люверсов
 * @return array
 */
function calculateBanner($length, $width, $bannerType, $hasHemming = false, $hasGrommets = false, $grommetStep = 0.5)
{
    global $priceConfig;
    
    // Валидация
    if ($length <= 0 || $width <= 0) {
        return ['error' => 'Размеры должны быть больше нуля'];
    }
    
    if ($hasGrommets && !$hasHemming) {
        return ['error' => 'При выборе люверсов проклейка обязательна!'];
    }
    
    if ($hasGrommets && ($grommetStep <= 0 || $grommetStep > max($length, $width))) {
        return ['error' => 'Некорректный шаг люверсов!'];
    }
    
    if (!isset($priceConfig["banner"]["banner_types"][$bannerType])) {
        return ['error' => 'Неизвестный тип баннера'];
    }
    
    // Расчет площади
    $area = $length * $width;
    
    // Стоимость полотна
    $bannerCost = $area * $priceConfig["banner"]["banner_types"][$bannerType];
    
    // Расчет проклейки
    $hemmingCost = 0;
    $perimeter = 0;
    if ($hasHemming || $hasGrommets) {
        $perimeter = ($length + $width) * 2;
        $hemmingCost = $perimeter * $priceConfig["banner"]["additional_services"]["hemming"];
    }
    
    // Расчет люверсов
    $grommetCost = 0;
    $grommetCount = 0;
    if ($hasGrommets) {
        // Расчет количества люверсов
        $verticalCount = ceil($length / $grommetStep) * 2;    // 2 вертикальные стороны
        $horizontalCount = ceil($width / $grommetStep) * 2;   // 2 горизонтальные стороны
        $grommetCount = $verticalCount + $horizontalCount;
        $grommetCost = $grommetCount * $priceConfig["banner"]["additional_services"]["grommets"];
    }
    
    // Итоговая стоимость
    $totalCost = $bannerCost + $hemmingCost + $grommetCost;
    
    return [
        'area' => round($area, 2),
        'perimeter' => round($perimeter, 2),
        'bannerCost' => round($bannerCost, 2),
        'hemmingCost' => round($hemmingCost, 2),
        'grommetCount' => $grommetCount,
        'grommetCost' => round($grommetCost, 2),
        'totalPrice' => round($totalCost, 2),
        'bannerType' => $bannerType,
        'dimensions' => [
            'length' => $length,
            'width' => $width
        ]
    ];
}

?>
