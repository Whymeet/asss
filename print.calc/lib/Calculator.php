<?php
namespace Bitrix\PrintCalc;

class Calculator {
    private $priceConfig;

    public function __construct() {
        $this->priceConfig = require_once(__DIR__ . '/../config/price_config.php');
    }

    // Добавляем метод для логирования
    private function logData($method, $data, $message = '') {
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'method' => $method,
            'data' => $data,
            'message' => $message
        ];
        error_log(print_r($logData, true));
    }

    // Функция для вычисления стоимости
    // Базовая функция для печати. На основе этой функции считаются следующие функции 
    function calculatePrice($paperType, $size, $quantity, $printType, $foldingCount = 0, $bigovka = false, $cornerRadius = 0, $perforation = false, $drill = false, $numbering = false) {
        // Логируем входные данные
        $this->logData(__METHOD__, [
            'paperType' => $paperType,
            'size' => $size,
            'quantity' => $quantity,
            'printType' => $printType,
            'foldingCount' => $foldingCount,
            'bigovka' => $bigovka,
            'cornerRadius' => $cornerRadius,
            'perforation' => $perforation,
            'drill' => $drill,
            'numbering' => $numbering
        ], 'Start calculation');

        // Проверка корректности данных
        if ($quantity <= 0) {
            $this->logData(__METHOD__, ['quantity' => $quantity], 'Error: Invalid quantity');
            return ['error' => 'Некорректные данные: количество должно быть больше 0'];
        }

        if (!isset($this->priceConfig['size_coefficients'][$size])) {
            $this->logData(__METHOD__, ['size' => $size], 'Error: Invalid size');
            return ['error' => 'Некорректные данные: неверный формат'];
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

            // Добавляем сложения для офсетной печати
            $totalPrice += $foldingCount * ($size === 'A3' ? 0.2 : 0.4) * $quantity;
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

            // Добавляем сложения для цифровой печати
            $totalPrice += $foldingCount * 0.4 * $quantity;
        }

        // Добавляем дополнительные услуги
        $additionalCosts = $this->calculateAdditionalCosts($bigovka, $cornerRadius, $perforation, $drill, $numbering, $quantity);
        $totalPrice += $additionalCosts;

        $result = [
            'printingType' => $printingType,
            'baseA3Sheets' => $baseA3Sheets,
            'adjustment' => $adjustment,
            'totalA3Sheets' => $totalA3Sheets,
            'printingCost' => $printingCost,
            'plateCost' => $plateCost,
            'paperCost' => $paperCost,
            'totalPrice' => $totalPrice,
            'additionalCosts' => $additionalCosts,
            'printingType' => $printingType,
            'laminationAvailable' => true
        ];

        // Логируем результат перед возвратом
        $this->logData(__METHOD__, $result, 'Calculation completed');
        return $result;
    }

    // Функция для вычисления стоимости ризографической и офсетной печати
    function calculateRizoPrice($paperType, $size, $quantity, $printType, $bigovka = false, $cornerRadius = 0, $perforation = false, $drill = false, $numbering = false) {
        // Логируем входные данные
        $this->logData(__METHOD__, [
            'paperType' => $paperType,
            'size' => $size,
            'quantity' => $quantity,
            'printType' => $printType,
            'bigovka' => $bigovka,
            'cornerRadius' => $cornerRadius,
            'perforation' => $perforation,
            'drill' => $drill,
            'numbering' => $numbering
        ], 'Start Rizo calculation');

        // Проверка корректности данных
        if ($quantity <= 0) {
            $this->logData(__METHOD__, ['quantity' => $quantity], 'Error: Invalid quantity');
            return ['error' => 'Некорректные данные: количество должно быть больше 0'];
        }

        if (!isset($this->priceConfig['size_coefficients'][$size])) {
            $this->logData(__METHOD__, ['size' => $size], 'Error: Invalid size');
            return ['error' => 'Некорректные данные: неверный формат'];
        }

        // Расчёт базовых значений
        $sizeCoefficient = $this->priceConfig['size_coefficients'][$size];
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
            foreach ($this->priceConfig['offset_rizo_prices'] as $range) {
                if ($totalA3Sheets >= $range['min'] && $totalA3Sheets <= $range['max']) {
                    $printingCost = $range[$priceColumn];
                    $adjustment = $range['adjustment'];
                    break;
                }
            }

            // Стоимость пластины
            $plateCost = $this->priceConfig['plate_price'];
            $totalA3Sheets += $adjustment;

            // Стоимость бумаги
            $paperCost = $totalA3Sheets * $this->priceConfig['paper'][$paperType];
            $totalPrice = $paperCost + $printingCost + $plateCost;
        } else { // Ризографическая печать
            // Определение колонки стоимости
            $priceColumn = ($printType === 'double') ? '1+1' : '1+0';
            $rizoPrice = 0;

            // Поиск цены в таблице ризографической печати
            foreach ($this->priceConfig['rizo_prices'] as $max => $prices) {
                if ($baseA3Sheets <= $max) {
                    $rizoPrice = $prices[$priceColumn];
                    break;
                }
            }

            $printingCost = $baseA3Sheets * $rizoPrice;
            $paperCost = $baseA3Sheets * $this->priceConfig['paper'][$paperType];
            $totalPrice = $paperCost + $printingCost;
        }

        // Добавляем дополнительные услуги
        $additionalCosts = $this->calculateAdditionalCosts($bigovka, $cornerRadius, $perforation, $drill, $numbering, $quantity);
        $totalPrice += $additionalCosts;

        $result = [
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

        // Логируем результат перед возвратом
        $this->logData(__METHOD__, $result, 'Rizo calculation completed');
        return $result;
    }

    // Функция для вычисления стоимости визиток
    function calculateVizitPrice($printType, $quantity, $sideType = 'single') {
        // Логируем входные данные
        $this->logData(__METHOD__, [
            'printType' => $printType,
            'quantity' => $quantity,
            'sideType' => $sideType
        ], 'Start Vizit calculation');

        // Валидация данных с логированием
        $errors = [];
        if ($printType === 'offset') {
            if ($quantity < 1000 || $quantity > 12000 || $quantity % 1000 !== 0) {
                $this->logData(__METHOD__, ['quantity' => $quantity], 'Error: Invalid quantity for offset');
                $errors[] = "Для офсетной печати выберите тираж из списка";
            }
        } else {
            if ($quantity < 100 || $quantity > 999) {
                $this->logData(__METHOD__, ['quantity' => $quantity], 'Error: Invalid quantity for digital');
                $errors[] = "Для цифровой печати введите тираж от 100 до 999";
            }
        }

        if (!empty($errors)) {
            return ['error' => implode("<br>", $errors)];
        }

        // Расчет стоимости
        $totalPrice = 0;
        
        if ($printType === 'offset') {
            foreach ($this->priceConfig['offset_vizit_prices'] as $range) {
                if ($quantity >= $range['min'] && $quantity <= $range['max']) {
                    $totalPrice = $quantity * $range['price'];
                    break;
                }
            }
        } else {
            $priceColumn = ($sideType === 'double') ? '4+4' : '4+0';
            foreach ($this->priceConfig['digital_vizit_prices'] as $max => $prices) {
                if ($quantity <= $max) {
                    $totalPrice = $quantity * $prices[$priceColumn];
                    break;
                }
            }
        }

        $result = [
            'printType' => $printType === 'offset' ? 'Офсетная' : 'Цифровая',
            'quantity' => $quantity,
            'totalPrice' => $totalPrice
        ];

        // Логируем результат перед возвратом
        $this->logData(__METHOD__, $result, 'Vizit calculation completed');
        return $result;
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
        // Логируем входные данные
        $this->logData(__METHOD__, [
            'width' => $width,
            'height' => $height,
            'includePodramnik' => $includePodramnik
        ], 'Start Canvas calculation');

        // Проверка на корректность входных данных: ширина и высота должны быть больше нуля
        if ($width <= 0 || $height <= 0) {
            $this->logData(__METHOD__, ['width' => $width, 'height' => $height], 'Error: Invalid dimensions');
            return ['error' => 'Ширина и высота должны быть больше нуля.'];
        }

        // Разрешенные размеры для округления
        $allowedSizes = [30, 40, 50, 60, 70, 80, 90, 100];
        
        // Округляем ширину и высоту до ближайших разрешенных значений
        $roundedWidth = $this->ceilToNearest($width, $allowedSizes);
        $roundedHeight = $this->ceilToNearest($height, $allowedSizes);

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
            if (!isset($this->priceConfig['canvas_prices'][$roundedHeight][$roundedWidth])) {
                $this->logData(__METHOD__, ['roundedWidth' => $roundedWidth, 'roundedHeight' => $roundedHeight], 'Error: Invalid canvas size');
                return ['error' => 'Неверный размер холста.']; // Возвращаем ошибку, если размер неверный
            }
            // Получаем стоимость холста из конфигурации
            $canvasPrice = $this->priceConfig['canvas_prices'][$roundedHeight][$roundedWidth];

            // Если подрамник включен, проверяем его стоимость
            if ($includePodramnik) {
                if (!isset($this->priceConfig['podramnik_prices'][$roundedHeight][$roundedWidth])) {
                    $this->logData(__METHOD__, ['roundedWidth' => $roundedWidth, 'roundedHeight' => $roundedHeight], 'Error: Invalid podramnik size');
                    return ['error' => 'Неверный размер подрамника.']; // Возвращаем ошибку, если размер подрамника неверный
                }
                // Получаем стоимость подрамника из конфигурации
                $podramnikPrice = $this->priceConfig['podramnik_prices'][$roundedHeight][$roundedWidth];
            }
        }

        // Общая стоимость = стоимость холста + стоимость подрамника
        $totalPrice = $canvasPrice + $podramnikPrice;

        // Возвращаем массив с деталями расчета
        $result = [
            'canvasPrice' => $canvasPrice, // Стоимость холста
            'podramnikPrice' => $podramnikPrice, // Стоимость подрамника
            'totalPrice' => $totalPrice, // Общая стоимость
            'roundedWidth' => $roundedWidth, // Округленная ширина
            'roundedHeight' => $roundedHeight, // Округленная высота
            'area' => isset($area) ? $area : null // Площадь, если она была рассчитана
        ];

        // Логируем результат перед возвратом
        $this->logData(__METHOD__, $result, 'Canvas calculation completed');
        return $result;
    }
    // Функция для вычисления дополнительных услуг
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

        // Сверление диаметром 5мм
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


    function calculatePVCPrice($width, $height, $pvcType, $flatA4, $flatA5, $volumeA4, $volumeA5) {
        // Логируем входные данные
        $this->logData(__METHOD__, [
            'width' => $width,
            'height' => $height,
            'pvcType' => $pvcType,
            'flatA4' => $flatA4,
            'flatA5' => $flatA5,
            'volumeA4' => $volumeA4,
            'volumeA5' => $volumeA5
        ], 'Start PVC calculation');

        // Валидация входных данных
        $errors = [];
        if ($width <= 0 || $height <= 0) $errors[] = "Некорректные размеры";
        if ($flatA4 < 0 || $flatA5 < 0 || $volumeA4 < 0 || $volumeA5 < 0) $errors[] = "Количество карманов не может быть отрицательным";
        
        if (!empty($errors)) {
            $this->logData(__METHOD__, ['errors' => $errors], 'Error: Invalid PVC input');
            return ['error' => implode("<br>", $errors)];
        }
        
        // Расчет площади
        $area = ($width / 100) * ($height / 100); // Перевод см в метры
        $totalPoints = ($flatA4 + $volumeA4) * $this->priceConfig['pocket_limits']['a4_points'] 
                     + ($flatA5 + $volumeA5) * $this->priceConfig['pocket_limits']['a5_points'];
                     
        $maxAllowedPoints = $area * $this->priceConfig['pocket_limits']['max_points_per_m2'];
        
        if ($totalPoints > $maxAllowedPoints) {
            $this->logData(__METHOD__, ['totalPoints' => $totalPoints, 'maxAllowedPoints' => $maxAllowedPoints], 'Error: PVC points exceeded');
            return ['error' => "Превышено максимальное количество карманов. Максимум: " . floor($maxAllowedPoints) . " баллов"];
        }
        
        // Расчет стоимости
        $pvcCost = $area * $this->priceConfig['pvc_prices'][$pvcType];
        
        $pocketsCost = 
            $flatA4 * $this->priceConfig['pocket_prices']['flat_a4'] +
            $flatA5 * $this->priceConfig['pocket_prices']['flat_a5'] +
            $volumeA4 * $this->priceConfig['pocket_prices']['volume_a4'] +
            $volumeA5 * $this->priceConfig['pocket_prices']['volume_a5'];
        
        $result = [
            'pvcCost' => $pvcCost,
            'pocketsCost' => $pocketsCost,
            'totalPrice' => $pvcCost + $pocketsCost,
            'area' => $area,
            'totalPoints' => $totalPoints,
            'maxPoints' => $maxAllowedPoints
        ];

        // Логируем результат перед возвратом
        $this->logData(__METHOD__, $result, 'PVC calculation completed');
        return $result;
    }
    function calculateCalendarPrice($type, $size, $quantity, $printType, $pages = 14) {
        $result = [];
        $additionalCost = 0;

        switch($type) {
            case 'desktop':
                // Настольный A4 300гр 4+0 с 3 биговками
                $result = $this->calculatePrice(300.0, "A4", $quantity, 'single', 0, true, 0, false, false, false);
                $result['totalPrice'] += 3 * $quantity * $this->priceConfig['bigovka'];
                break;

            case 'pocket':
                // Карманный A6 300гр с 4 углами
                $result = $this->calculatePrice(300.0, "100X70", $quantity, $printType, 0, false, 4, false, false, false);
                break;

            case 'wall':
                // Настенный перекидной
                $baseSize = ($size === "A4") ? "A4" : "A3";
                $sheets = ($baseSize === "A4") ? 7 : 14; // Для A4 7 листов A3
                
                // Расчет стоимости печати
                $printResult = $this->calculatePrice(300.0, $baseSize, $quantity * $sheets, $printType);
                
                // Расчет стоимости сборки
                $assemblyPrice = 0;
                foreach ($this->priceConfig['calendar_prices']['wall_assembly'][$size] as $max => $price) {
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
        $bindingPrices = $this->priceConfig['binding_prices'];
        return $this->calculateBindingPrice($size, $quantity, $bindingPrices);
    }

    private function calculateBindingPrice($size, $quantity, $prices) {
        if ($quantity <= 100) {
            return $prices[$size][100] * $quantity;
        } elseif ($quantity <= 500) {
            return $prices[$size][500] * $quantity;
        } elseif ($quantity <= 1000) {
            return $prices[$size][1000] * $quantity;
        } else {
            return $prices[$size]["max"] * $quantity;
        }
    }

    function calculateStapleCost($quantity) {
        return $this->priceConfig['staple_price'] * 2 * $quantity; // 2 скобы на изделие
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
        // Логируем входные данные
        $this->logData(__METHOD__, [
            'coverPaper' => $coverPaper,
            'coverPrintType' => $coverPrintType,
            'innerPaper' => $innerPaper,
            'innerPrintType' => $innerPrintType,
            'size' => $size,
            'pages' => $pages,
            'quantity' => $quantity,
            'bindingType' => $bindingType
        ], 'Start Catalog calculation');

        // Нормализация размера
        $size = mb_convert_case($size, MB_CASE_UPPER, "UTF-8");

        // Проверка существования формата
        if (!isset($this->priceConfig["catalog"]["sheet_conversion"][$size])) {
            $allowedSizes = implode(', ', array_keys($this->priceConfig["catalog"]["sheet_conversion"]));
            $this->logData(__METHOD__, ['size' => $size, 'allowedSizes' => $allowedSizes], 'Error: Invalid catalog size');
            return ['error' => "Неверный формат каталога. Допустимые форматы: $allowedSizes"];
        }

        $conversionData = $this->priceConfig["catalog"]["sheet_conversion"][$size];
        $pages = (int)$pages;

        // Проверка страниц
        if (!isset($conversionData[$pages])) {
            $allowedPages = implode(', ', array_keys($conversionData));
            $this->logData(__METHOD__, ['size' => $size, 'pages' => $pages, 'allowedPages' => $allowedPages], 'Error: Invalid pages for catalog size');
            return ['error' => "Недопустимое количество страниц для формата $size. Допустимые значения: $allowedPages"];
        }

        // Валидация других параметров
        $errors = [];
        if (!in_array($coverPaper, [130, 170, 300], true)) {
            $this->logData(__METHOD__, ['coverPaper' => $coverPaper], 'Error: Invalid cover paper density');
            $errors[] = "Некорректная плотность обложки";
        }
        if (!in_array($innerPaper, [130, 170], true)) {
            $this->logData(__METHOD__, ['innerPaper' => $innerPaper], 'Error: Invalid inner paper density');
            $errors[] = "Некорректная плотность внутренних листов";
        }
        if ($quantity <= 0) {
            $this->logData(__METHOD__, ['quantity' => $quantity], 'Error: Invalid quantity');
            $errors[] = "Некорректный тираж";
        }
        
        if (!empty($errors)) {
            $this->logData(__METHOD__, ['errors' => $errors], 'Error: Invalid catalog input');
            return ['error' => implode("<br>", $errors)];
        }

        $totalPrice = 0;
        $isSamePaper = ($coverPaper == $innerPaper);

        // Расчет обложки и внутренних листов
        if ($isSamePaper) {
            // Если бумага одинаковая
            $a3Sheets = $conversionData[$pages] * $quantity;
            $coverResult = $this->calculatePrice(
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
                $this->logData(__METHOD__, ['size' => $size], 'Error: Invalid cover size for calculation');
                return ['error' => "Неверный формат для расчета обложки"];
            }

            // Расчет обложки
            $coverSize = $coverSizeMap[$size];
            $coverSheets = ceil($quantity / 2);
            $coverResult = $this->calculatePrice(
                $coverPaper,
                $coverSize,
                $coverSheets,
                $coverPrintType
            );
            $totalPrice += $coverResult['totalPrice'];

            // Расчет внутренних листов
            $adjustedPages = max(8, $pages - 4);
            $innerSheets = $conversionData[$adjustedPages] * $quantity;
            $innerResult = $this->calculatePrice(
                $innerPaper,
                $size,
                $innerSheets,
                $innerPrintType
            );
            $totalPrice += $innerResult['totalPrice'];
        }

        // Листоподборка
        $collationCost = $pages * $quantity * $this->priceConfig["catalog"]["collation_price"];
        $totalPrice += $collationCost;

        // Расчет стоимости сборки
        if ($bindingType === 'staple') {
            $bindingCost = $this->calculateStapleCost($quantity);
        } else {
            $bindingCost = $this->calculateBindingCost($size, $quantity);
        }
        $totalPrice += $bindingCost;

        $result = [
            'coverCost' => $isSamePaper ? 0 : $coverResult['totalPrice'],
            'innerCost' => $isSamePaper ? $coverResult['totalPrice'] : $innerResult['totalPrice'],
            'collationCost' => $collationCost,
            'bindingCost' => $bindingCost,
            'totalPrice' => $totalPrice,
            'sheets' => $conversionData[$pages],
            'isSamePaper' => $isSamePaper,
            'adjustedPages' => $adjustedPages ?? $pages
        ];

        // Логируем результат перед возвратом
        $this->logData(__METHOD__, $result, 'Catalog calculation completed');
        return $result;
    }



    function calculateNotePrice($params) {
        // Логируем входные данные
        $this->logData(__METHOD__, $params, 'Start Note calculation');

        // Получаем конфигурацию блокнотов
        $noteConfig = $this->priceConfig['note'];
        
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
        
        $coverResult = $this->calculateComponent($coverParams);
        
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
        
        $backResult = $this->calculateComponent($backParams);
        
        // 4. Расчет внутреннего блока
        $totalInnerSheets = $quantity * $innerPages;
        $innerPrintType = $params['inner_print'];
        
        if (strpos($innerPrintType, '1+') === 0) {
            // Ризография
            $innerResult = $this->calculateRizoComponent([
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
            
            $innerResult = $this->calculateComponent($innerParams);
        }
        
        // 5. Расчет сборки
        $bindingCost = $this->calculateNoteBinding([
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
        
        $result = [
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

        // Логируем результат перед возвратом
        $this->logData(__METHOD__, $result, 'Note calculation completed');
        return $result;
    }

    // Вспомогательные функции для расчета компонентов
    function calculateComponent($params) {
        // Логируем входные данные
        $this->logData(__METHOD__, $params, 'Start Component calculation');

        // Базовый расчет стоимости печати
        $baseResult = $this->calculatePrice(
            $params['paperType'],
            $params['size'],
            $params['quantity'],
            $params['printType']
        );
        
        // Добавляем дополнительные услуги
        $additionalCost = $this->calculateAdditionalServices(
            $params['services'],
            $params['quantity']
        );
        
        $result = [
            'base' => $baseResult,
            'additional' => $additionalCost,
            'total' => $baseResult['totalPrice'] + $additionalCost
        ];

        // Логируем результат перед возвратом
        $this->logData(__METHOD__, $result, 'Component calculation completed');
        return $result;
    }

    function calculateRizoComponent($params) {
        // Логируем входные данные
        $this->logData(__METHOD__, $params, 'Start RizoComponent calculation');

        // Расчет ризографии
        $baseResult = $this->calculateRizoPrice(
            $params['paperType'],
            $params['size'],
            $params['quantity'],
            $params['printType']
        );
        
        // Добавляем дополнительные услуги
        $additionalCost = $this->calculateAdditionalServices(
            $params['services'],
            $params['quantity']
        );
        
        $result = [
            'base' => $baseResult,
            'additional' => $additionalCost,
            'total' => $baseResult['totalPrice'] + $additionalCost
        ];

        // Логируем результат перед возвратом
        $this->logData(__METHOD__, $result, 'RizoComponent calculation completed');
        return $result;
    }

    function calculateNoteBinding($params) {
        // Логируем входные данные
        $this->logData(__METHOD__, $params, 'Start NoteBinding calculation');

        $config = $params['config']['spiral'][$params['size']];
        $quantity = $params['quantity'];
        
        if ($quantity <= 100) {
            $result = $config[100] * $quantity;
        } elseif ($quantity <= 500) {
            $result = $config[500] * $quantity;
        } elseif ($quantity <= 1000) {
            $result = $config[1000] * $quantity;
        } else {
            $result = $config['max'] * $quantity;
        }

        // Логируем результат перед возвратом
        $this->logData(__METHOD__, ['quantity' => $quantity, 'bindingCost' => $result], 'NoteBinding calculation completed');
        return $result;
    }

    function calculateAdditionalServices($services, $quantity) {
        $noteServices = $this->priceConfig['note']['services'];
        
        $cost = 0;
        
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

}

?>
