<?php
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Context;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

class PrintCalcComponent extends CBitrixComponent implements Controllerable
{
    private $calculatorFile;
    private $debugFile;
    private $configCache = [];

    public function __construct($component = null)
    {
        parent::__construct($component);
        $this->calculatorFile = dirname(__FILE__) . '/lib/Calculator.php';
        $this->debugFile = $_SERVER['DOCUMENT_ROOT'] . '/calc_debug.log';
    }

    private function debug($message, $data = null)
    {
        $log = date('Y-m-d H:i:s') . " | " . $message;
        if ($data !== null) {
            $log .= ":\n" . print_r($data, true);
        }
        $log .= "\n";
        file_put_contents($this->debugFile, $log, FILE_APPEND);
    }

    public function configureActions(): array
    {
        return [
            'calc' => [
                'prefilters' => [],
                'postfilters' => []
            ]
        ];
    }

    private function loadCalculator()
    {
        if (!file_exists($this->calculatorFile)) {
            $this->debug('Файл Calculator.php не найден: ' . $this->calculatorFile);
            return false;
        }

        global $priceConfig;
        require_once $this->calculatorFile;
        
        if (!isset($priceConfig) || !is_array($priceConfig)) {
            $this->debug('Конфигурация $priceConfig не найдена после подключения');
            $GLOBALS['priceConfig'] = $priceConfig ?? null;
            
            if (!isset($GLOBALS['priceConfig']) || !is_array($GLOBALS['priceConfig'])) {
                $this->debug('Критическая ошибка: $priceConfig недоступна');
                return false;
            }
        }
        
        $this->debug('Calculator.php успешно загружен');
        return true;
    }

    /**
     * Загружает конфигурацию для конкретного типа калькулятора
     */
    private function loadCalcConfig($calcType)
    {
        $this->debug("Попытка загрузить конфигурацию для: {$calcType}");
        
        // Проверяем кэш
        if (isset($this->configCache[$calcType])) {
            $this->debug("Конфигурация {$calcType} найдена в кэше");
            return $this->configCache[$calcType];
        }

        // Ищем в папке config (не configs!)
        $configFile = dirname(__FILE__) . "/config/{$calcType}.php";
        $this->debug("Ищем конфигурацию в: {$configFile}");
        
        if (!file_exists($configFile)) {
            $this->debug("Файл конфигурации не найден: {$configFile}");
            
            // Для совместимости: если это list, пробуем существующий list.php
            if ($calcType === 'list') {
                $oldConfigFile = dirname(__FILE__) . "/list.php";
                $this->debug("Пробуем старый формат: {$oldConfigFile}");
                
                if (file_exists($oldConfigFile)) {
                    $config = include $oldConfigFile;
                    $this->debug("Старая конфигурация загружена", $config);
                    
                    // Преобразуем в новый формат
                    $newConfig = [
                        'sizes' => $config['sizes'] ?? [],
                        'papers' => $config['papers'] ?? [],
                        'description' => 'Калькулятор печати листовок',
                        'default_quantity' => 1000,
                        'features' => [
                            'bigovka' => true,
                            'perforation' => true,
                            'drill' => true,
                            'numbering' => true,
                            'corner_radius' => true,
                            'lamination' => true
                        ]
                    ];
                    
                    // Кэшируем
                    $this->configCache[$calcType] = $newConfig;
                    $this->debug("Конфигурация {$calcType} преобразована и закэширована", $newConfig);
                    
                    return $newConfig;
                }
            }
            
            $this->debug("Конфигурация для {$calcType} не найдена");
            return null;
        }

        $config = include $configFile;
        
        if (!is_array($config)) {
            $this->debug("Некорректная конфигурация в файле: {$configFile}");
            return null;
        }
        
        // Кэшируем конфигурацию
        $this->configCache[$calcType] = $config;
        
        $this->debug("Конфигурация {$calcType} загружена", $config);
        
        return $config;
    }

    public function executeComponent()
    {
        // Загружаем калькулятор
        if (!$this->loadCalculator()) {
            ShowError('Ошибка загрузки системы расчетов');
            return;
        }

        global $priceConfig;
        
        // Определяем тип калькулятора из параметров
        $calcType = $this->arParams['CALC_TYPE'] ?? 'list';
        $this->debug("Тип калькулятора: {$calcType}");
        
        // Загружаем конфигурацию для конкретного типа
        $calcConfig = $this->loadCalcConfig($calcType);
        
        if (!$calcConfig) {
            $this->debug("Не удалось загрузить конфигурацию для {$calcType}");
            ShowError("Конфигурация для калькулятора '{$calcType}' не найдена");
            return;
        }
        
        // Подготавливаем данные в зависимости от типа калькулятора
        $this->prepareData($priceConfig, $calcConfig, $calcType);
        
        $this->arResult['CALC_TYPE'] = $calcType;
        $this->arResult['CONFIG_LOADED'] = true;
        $this->arResult['DESCRIPTION'] = $calcConfig['description'] ?? 'Калькулятор печати';
        $this->arResult['FEATURES'] = $calcConfig['features'] ?? [];
        
        // Добавляем дополнительную информацию из конфигурации в arResult
        if (isset($calcConfig['additional'])) {
            foreach ($calcConfig['additional'] as $key => $value) {
                $this->arResult[$key] = $value;
            }
        }
        
        $this->debug("Данные подготовлены для шаблона", [
            'CALC_TYPE' => $calcType,
            'PAPER_TYPES_COUNT' => count($this->arResult['PAPER_TYPES'] ?? []),
            'FORMATS_COUNT' => count($this->arResult['FORMATS'] ?? [])
        ]);
        
        $this->includeComponentTemplate();
    }

    /**
     * Универсальная подготовка данных на основе конфигурации
     */
    private function prepareData($priceConfig, $calcConfig, $calcType)
    {
        // Получаем доступные размеры из конфигурации
        $availableSizes = $calcConfig['sizes'] ?? [];
        
        // Получаем доступные типы бумаги из конфигурации
        $availablePapers = $calcConfig['papers'] ?? [];
        
        $this->debug("Подготовка данных", [
            'availableSizes' => $availableSizes,
            'availablePapers' => $availablePapers
        ]);
        
        // Форматируем типы бумаги (только доступные)
        $paperTypes = [];
        if (!empty($availablePapers)) {
            foreach ($priceConfig['paper'] as $type => $price) {
                if (in_array($type, $availablePapers)) {
                    $paperTypes[] = [
                        'ID' => $type,
                        'NAME' => is_numeric($type) ? $type . ' г/м²' : $type,
                        'PRICE' => $price
                    ];
                }
            }
        }
        
        // Форматируем размеры (только доступные)
        $formats = [];
        if (!empty($availableSizes)) {
            foreach ($priceConfig['size_coefficients'] as $size => $coef) {
                if (in_array($size, $availableSizes)) {
                    $formats[] = [
                        'ID' => $size,
                        'NAME' => $size,
                        'COEFFICIENT' => $coef
                    ];
                }
            }
        }
        
        // Базовый результат
        $this->arResult = [
            'PAPER_TYPES' => $paperTypes,
            'FORMATS' => $formats,
            'AVAILABLE_SIZES' => $availableSizes,
            'AVAILABLE_PAPERS' => $availablePapers,
            'DEFAULT_QUANTITY' => $calcConfig['default_quantity'] ?? 1000,
            'MIN_QUANTITY' => $calcConfig['min_quantity'] ?? 1,
            'MAX_QUANTITY' => $calcConfig['max_quantity'] ?? null
        ];
        
        // Добавляем специфичные для типа калькулятора данные
        switch ($calcType) {
            case 'vizit':
                $this->arResult['DIGITAL_PRICES'] = $priceConfig['digital_vizit_prices'];
                $this->arResult['OFFSET_PRICES'] = $priceConfig['offset_vizit_prices'];
                break;
                
            case 'booklet':
                $this->arResult['MAX_FOLDING'] = $calcConfig['max_folding'] ?? 2;
                break;
                
            case 'rizo':
                // Специфичные данные для ризографии
                $this->arResult['RIZO_THRESHOLD'] = 499; // Порог переключения на офсет
                $this->arResult['PRINT_TYPES_INFO'] = $calcConfig['additional']['print_info'] ?? [];
                break;
                
            case 'catalog':
                $this->arResult['CATALOG_CONFIG'] = $priceConfig['catalog'];
                $this->arResult['AVAILABLE_PAGES'] = $calcConfig['additional']['available_pages'] ?? [];
                break;
                
            case 'note':
                $this->arResult['NOTE_CONFIG'] = $priceConfig['note'];
                break;
                
            case 'canvas':
                $this->arResult['CANVAS_CONFIG'] = $calcConfig['additional'] ?? [];
                break;
                
            case 'sticker':
                // Специфичные данные для наклеек - НЕ ПЕРЕЗАПИСЫВАЕМ то что уже добавлено из additional
                break;
                
            case 'stend':
                // Специфичные данные для ПВХ стендов уже добавлены через additional
                // Дополнительная обработка не требуется, так как все данные передаются через конфигурацию
                break;
                
            case 'placement':
                // Специфичные данные для размещения уже добавлены через additional
                // Дополнительная обработка не требуется
                break;
        }
        
        // Добавляем дополнительные параметры из конфигурации
        if (isset($calcConfig['additional'])) {
            $this->arResult = array_merge($this->arResult, $calcConfig['additional']);
        }
        
        $this->debug("Данные подготовлены", [
            'paperTypes_count' => count($paperTypes),
            'formats_count' => count($formats)
        ]);
    }

    // AJAX-методы для разных типов калькуляторов
    public function calcAction($paperType = '', $size = '', $quantity = 0, $printType = 'single', $bigovka = false, $cornerRadius = 0, $perforation = false, $drill = false, $numbering = false, $calcType = 'list')
    {
        $this->debug("calcAction вызван с параметрами", func_get_args());
        
        if (!$this->loadCalculator()) {
            return ['error' => 'Система расчетов не загружена'];
        }

        try {
            // Преобразуем булевые значения
            $bigovka = filter_var($bigovka, FILTER_VALIDATE_BOOLEAN);
            $perforation = filter_var($perforation, FILTER_VALIDATE_BOOLEAN);
            $drill = filter_var($drill, FILTER_VALIDATE_BOOLEAN);
            $numbering = filter_var($numbering, FILTER_VALIDATE_BOOLEAN);
            
            $this->debug("Обработанные параметры", [
                'paperType' => $paperType,
                'size' => $size,
                'quantity' => $quantity,
                'printType' => $printType,
                'bigovka' => $bigovka,
                'cornerRadius' => $cornerRadius,
                'calcType' => $calcType
            ]);

            // Валидация на основе конфигурации
            $calcConfig = $this->loadCalcConfig($calcType);
            if ($calcConfig && !$this->validateInput($calcConfig, $paperType, $size)) {
                return ['error' => 'Недопустимые параметры для данного типа калькулятора'];
            }

            switch ($calcType) {
                case 'list':
                    return $this->calculateList($paperType, $size, $quantity, $printType, $bigovka, $cornerRadius, $perforation, $drill, $numbering);
                    
                case 'booklet':
                    $foldingCount = (int)($_POST['foldingCount'] ?? 0);
                    return $this->calculateBooklet($paperType, $size, $quantity, $printType, $foldingCount, $bigovka, $cornerRadius, $perforation, $drill, $numbering);
                    
                case 'rizo':
                    return $this->calculateRizo($paperType, $size, $quantity, $printType, $bigovka, $cornerRadius, $perforation, $drill, $numbering);
                    
                case 'vizit':
                    $sideType = $_POST['sideType'] ?? 'single';
                    return $this->calculateVizit($printType, $quantity, $sideType);
                    
                case 'canvas':
                    $width = (float)($_POST['width'] ?? 0);
                    $height = (float)($_POST['height'] ?? 0);
                    $includePodramnik = filter_var($_POST['includePodramnik'] ?? false, FILTER_VALIDATE_BOOLEAN);
                    return $this->calculateCanvas($width, $height, $includePodramnik);
                    
                case 'sticker':
                    $length = (float)($_POST['length'] ?? 0);
                    $width = (float)($_POST['width'] ?? 0);
                    $stickerType = $_POST['stickerType'] ?? '';
                    return $this->calculateSticker($length, $width, $quantity, $stickerType);
                    
                case 'stend':
                    $width = (float)($_POST['width'] ?? 0);
                    $height = (float)($_POST['height'] ?? 0);
                    $pvcType = $_POST['pvcType'] ?? '3mm';
                    $flatA4 = (int)($_POST['flatA4'] ?? 0);
                    $flatA5 = (int)($_POST['flatA5'] ?? 0);
                    $volumeA4 = (int)($_POST['volumeA4'] ?? 0);
                    $volumeA5 = (int)($_POST['volumeA5'] ?? 0);
                    return $this->calculateStend($width, $height, $pvcType, $flatA4, $flatA5, $volumeA4, $volumeA5);
                    
                case 'placement':
                    // Для размещения используем стандартную функцию calculatePrice
                    // но с проверкой ламинации
                    $result = $this->calculateList($paperType, $size, $quantity, $printType, $bigovka, $cornerRadius, $perforation, $drill, $numbering);
                    
                    // Добавляем обработку ламинации если указана
                    if (!empty($_POST['lamination_type'])) {
                        $result = $this->addLamination($result, $_POST, $quantity);
                    }
                    
                    return $result;
                    
                case 'note':
                    return $this->calculateNote($paperType, $size, $quantity, $printType, $bigovka, $cornerRadius, $perforation, $drill, $numbering);
                    
                default:
                    return $this->calculateList($paperType, $size, $quantity, $printType, $bigovka, $cornerRadius, $perforation, $drill, $numbering);
            }
        } catch (Exception $e) {
            $this->debug("Ошибка в calcAction", $e->getMessage());
            return ['error' => 'Ошибка расчета: ' . $e->getMessage()];
        }
    }

    /**
     * Валидация входных данных на основе конфигурации
     */
    private function validateInput($calcConfig, $paperType, $size)
    {
        $availableSizes = $calcConfig['sizes'] ?? [];
        $availablePapers = $calcConfig['papers'] ?? [];
        
        if (!empty($availableSizes) && !in_array($size, $availableSizes)) {
            $this->debug("Недопустимый размер: {$size}. Доступные: " . implode(', ', $availableSizes));
            return false;
        }
        
        if (!empty($availablePapers) && !in_array($paperType, $availablePapers)) {
            $this->debug("Недопустимый тип бумаги: {$paperType}. Доступные: " . implode(', ', $availablePapers));
            return false;
        }
        
        return true;
    }

    /**
     * Добавление ламинации к результату расчета
     */
    private function addLamination($result, $postData, $quantity)
    {
        if (isset($result['error'])) {
            return $result; // Если была ошибка, не обрабатываем ламинацию
        }

        global $priceConfig;
        if (!isset($priceConfig['lamination'])) {
            return $result; // Если конфигурация ламинации недоступна
        }

        $laminationType = $postData['lamination_type'] ?? '';
        $laminationThickness = $postData['lamination_thickness'] ?? '';
        
        if (empty($laminationType)) {
            return $result; // Если тип ламинации не указан
        }

        $laminationCost = 0;

        try {
            if ($result['printingType'] === 'Офсетная') {
                // Офсетная печать: простые тарифы
                if (isset($priceConfig['lamination']['offset'][$laminationType])) {
                    $laminationCost = $quantity * $priceConfig['lamination']['offset'][$laminationType];
                }
            } else {
                // Цифровая печать: зависит от толщины
                if (!empty($laminationThickness) && 
                    isset($priceConfig['lamination']['digital'][$laminationThickness][$laminationType])) {
                    $laminationCost = $quantity * $priceConfig['lamination']['digital'][$laminationThickness][$laminationType];
                }
            }

            if ($laminationCost > 0) {
                $result['totalPrice'] += $laminationCost;
                $result['laminationCost'] = $laminationCost;
                $result['laminationType'] = $laminationType;
                $result['laminationThickness'] = $laminationThickness;
            }

        } catch (Exception $e) {
            $this->debug("Ошибка при добавлении ламинации", $e->getMessage());
        }

        return $result;
    }

    private function calculateList($paperType, $size, $quantity, $printType, $bigovka, $cornerRadius, $perforation, $drill, $numbering)
    {
        $this->debug("calculateList вызван", [
            'paperType' => $paperType,
            'size' => $size,
            'quantity' => $quantity
        ]);

        // Проверяем доступность функции
        if (!function_exists('calculatePrice')) {
            $this->debug("Функция calculatePrice не найдена");
            return ['error' => 'Функция calculatePrice не найдена'];
        }

        // Проверяем доступность конфигурации
        global $priceConfig;
        if (!isset($priceConfig) && isset($GLOBALS['priceConfig'])) {
            $priceConfig = $GLOBALS['priceConfig'];
        }
        
        if (!isset($priceConfig) || !is_array($priceConfig)) {
            $this->debug("priceConfig недоступна в calculateList");
            return ['error' => 'Конфигурация цен недоступна'];
        }

        // Выполняем расчет
        try {
            $result = calculatePrice($paperType, $size, $quantity, $printType, 0, $bigovka, $cornerRadius, $perforation, $drill, $numbering);
            
            $this->debug("Результат calculatePrice", $result);
            
            if (!$result) {
                return ['error' => 'Ошибка выполнения расчета'];
            }
            
            return $result;
            
        } catch (Exception $e) {
            $this->debug("Исключение в calculatePrice", $e->getMessage());
            return ['error' => 'Ошибка расчета: ' . $e->getMessage()];
        }
    }

    private function calculateBooklet($paperType, $size, $quantity, $printType, $foldingCount, $bigovka, $cornerRadius, $perforation, $drill, $numbering)
    {
        return calculatePrice($paperType, $size, $quantity, $printType, $foldingCount, $bigovka, $cornerRadius, $perforation, $drill, $numbering);
    }

    private function calculateRizo($paperType, $size, $quantity, $printType, $bigovka, $cornerRadius, $perforation, $drill, $numbering)
    {
        $this->debug("calculateRizo вызван", [
            'paperType' => $paperType,
            'size' => $size,
            'quantity' => $quantity,
            'printType' => $printType
        ]);

        if (!function_exists('calculateRizoPrice')) {
            $this->debug("Функция calculateRizoPrice не найдена");
            return ['error' => 'Функция calculateRizoPrice не найдена'];
        }
        
        try {
            $result = calculateRizoPrice($paperType, $size, $quantity, $printType, $bigovka, $cornerRadius, $perforation, $drill, $numbering);
            $this->debug("Результат calculateRizoPrice", $result);
            
            if (!$result) {
                return ['error' => 'Ошибка выполнения расчета ризографии'];
            }
            
            return $result;
            
        } catch (Exception $e) {
            $this->debug("Исключение в calculateRizoPrice", $e->getMessage());
            return ['error' => 'Ошибка расчета ризографии: ' . $e->getMessage()];
        }
    }

    private function calculateVizit($printType, $quantity, $sideType)
    {
        if (!function_exists('calculateVizitPrice')) {
            return ['error' => 'Функция calculateVizitPrice не найдена'];
        }
        
        return calculateVizitPrice($printType, $quantity, $sideType);
    }

    private function calculateCanvas($width, $height, $includePodramnik)
    {
        if (!function_exists('calculateCanvasPrice')) {
            return ['error' => 'Функция calculateCanvasPrice не найдена'];
        }
        
        return calculateCanvasPrice($width, $height, $includePodramnik);
    }

    /**
     * Расчет стоимости наклеек
     */
    private function calculateSticker($length, $width, $quantity, $stickerType)
    {
        $this->debug("calculateSticker вызван", [
            'length' => $length,
            'width' => $width,
            'quantity' => $quantity,
            'stickerType' => $stickerType
        ]);

        // Загружаем конфигурацию наклеек
        $calcConfig = $this->loadCalcConfig('sticker');
        if (!$calcConfig) {
            return ['error' => 'Конфигурация наклеек не найдена'];
        }

        $priceRanges = $calcConfig['additional']['price_ranges'] ?? [];
        
        // Валидация входных данных
        $errors = [];
        if ($length <= 0) $errors[] = "Длина должна быть больше 0";
        if ($width <= 0) $errors[] = "Ширина должна быть больше 0";
        if ($quantity <= 0) $errors[] = "Количество должно быть больше 0";
        if (!isset($priceRanges[$stickerType])) $errors[] = "Некорректный тип наклейки";
        
        if (!empty($errors)) {
            return ['error' => implode("<br>", $errors)];
        }

        // Вычисляем площади
        $areaPerSticker = $length * $width; // м²
        $totalArea = $areaPerSticker * $quantity; // общая площадь м²
        
        // Находим цену за м² в зависимости от общей площади
        $pricePerM2 = 0;
        $ranges = $priceRanges[$stickerType];
        
        foreach ($ranges as $range) {
            if ($totalArea >= $range[0] && $totalArea < $range[1]) {
                $pricePerM2 = $range[2];
                break;
            }
        }
        
        if ($pricePerM2 === 0) {
            return ['error' => 'Не удалось определить цену для данной площади'];
        }
        
        // Вычисляем итоговую стоимость
        $totalPrice = $totalArea * $pricePerM2;
        
        return [
            'length' => $length,
            'width' => $width,
            'quantity' => $quantity,
            'stickerType' => $stickerType,
            'areaPerSticker' => $areaPerSticker,
            'totalArea' => $totalArea,
            'pricePerM2' => $pricePerM2,
            'totalPrice' => $totalPrice
        ];
    }

    /**
     * Расчет стоимости ПВХ стендов
     */
    private function calculateStend($width, $height, $pvcType, $flatA4, $flatA5, $volumeA4, $volumeA5)
    {
        $this->debug("calculateStend вызван", [
            'width' => $width,
            'height' => $height,
            'pvcType' => $pvcType,
            'flatA4' => $flatA4,
            'flatA5' => $flatA5,
            'volumeA4' => $volumeA4,
            'volumeA5' => $volumeA5
        ]);

        // Загружаем конфигурацию стендов
        $calcConfig = $this->loadCalcConfig('stend');
        if (!$calcConfig) {
            return ['error' => 'Конфигурация стендов не найдена'];
        }

        $pvcPrices = $calcConfig['additional']['pvc_prices'] ?? [];
        $pocketPrices = $calcConfig['additional']['pocket_prices'] ?? [];
        $pocketLimits = $calcConfig['additional']['pocket_limits'] ?? [];
        
        // Валидация входных данных
        $errors = [];
        if ($width <= 0) $errors[] = "Ширина должна быть больше 0";
        if ($height <= 0) $errors[] = "Высота должна быть больше 0";
        if (!isset($pvcPrices[$pvcType])) $errors[] = "Некорректный тип ПВХ";
        if ($flatA4 < 0 || $flatA5 < 0 || $volumeA4 < 0 || $volumeA5 < 0) {
            $errors[] = "Количество карманов не может быть отрицательным";
        }
        
        if (!empty($errors)) {
            return ['error' => implode("<br>", $errors)];
        }

        // Расчет площади (переводим см в м)
        $area = ($width / 100) * ($height / 100);
        
        // Расчет баллов карманов
        $totalPoints = ($flatA4 + $volumeA4) * $pocketLimits['a4_points'] 
                     + ($flatA5 + $volumeA5) * $pocketLimits['a5_points'];
                     
        $maxAllowedPoints = $area * $pocketLimits['max_points_per_m2'];
        
        if ($totalPoints > $maxAllowedPoints) {
            return ['error' => "Превышено максимальное количество карманов. Максимум: " . floor($maxAllowedPoints) . " баллов для данной площади"];
        }
        
        // Расчет стоимости ПВХ
        $pvcCost = $area * $pvcPrices[$pvcType];
        
        // Расчет стоимости карманов
        $pocketsCost = 
            $flatA4 * $pocketPrices['flat_a4'] +
            $flatA5 * $pocketPrices['flat_a5'] +
            $volumeA4 * $pocketPrices['volume_a4'] +
            $volumeA5 * $pocketPrices['volume_a5'];
        
        // Итоговая стоимость
        $totalPrice = $pvcCost + $pocketsCost;
        
        return [
            'width' => $width,
            'height' => $height,
            'pvcType' => $pvcType,
            'area' => $area,
            'pvcCost' => $pvcCost,
            'pocketsCost' => $pocketsCost,
            'totalPrice' => $totalPrice,
            'totalPoints' => $totalPoints,
            'maxPoints' => $maxAllowedPoints,
            'pockets' => [
                'flatA4' => $flatA4,
                'flatA5' => $flatA5,
                'volumeA4' => $volumeA4,
                'volumeA5' => $volumeA5
            ]
        ];
    }

    /**
     * Расчет стоимости блокнотов
     */
    private function calculateNote($paperType, $size, $quantity, $printType, $bigovka, $cornerRadius, $perforation, $drill, $numbering)
    {
        $this->debug("calculateNote вызван", [
            'paperType' => $paperType,
            'size' => $size,
            'quantity' => $quantity
        ]);

        // Проверяем доступность функции
        if (!function_exists('calculateNotePrice')) {
            $this->debug("Функция calculateNotePrice не найдена");
            return ['error' => 'Функция calculateNotePrice не найдена'];
        }

        // Проверяем доступность конфигурации
        global $priceConfig;
        if (!isset($priceConfig) && isset($GLOBALS['priceConfig'])) {
            $priceConfig = $GLOBALS['priceConfig'];
        }
        
        if (!isset($priceConfig) || !is_array($priceConfig)) {
            $this->debug("priceConfig недоступна в calculateNote");
            return ['error' => 'Конфигурация цен недоступна'];
        }

        // Собираем параметры для расчета блокнота
        $params = [
            'size' => $size,
            'quantity' => $quantity,
            'inner_pages' => (int)($_POST['inner_pages'] ?? 40),
            'cover_print' => $_POST['cover_print'] ?? '4+0',
            'back_print' => $_POST['back_print'] ?? '0+0',
            'inner_print' => $_POST['inner_print'] ?? '0+0',
            'bigovka' => $bigovka,
            'perforation' => $perforation,
            'drill' => $drill,
            'numbering' => $numbering,
            'corner_radius' => $cornerRadius
        ];

        // Выполняем расчет
        try {
            $result = calculateNotePrice($params);
            
            $this->debug("Результат calculateNotePrice", $result);
            
            if (!$result) {
                return ['error' => 'Ошибка выполнения расчета блокнота'];
            }
            
            // Добавляем обработку ламинации если указана
            if (!empty($_POST['lamination_type'])) {
                $result = $this->addNoteLamination($result, $_POST, $quantity);
            }
            
            return $result;
            
        } catch (Exception $e) {
            $this->debug("Исключение в calculateNotePrice", $e->getMessage());
            return ['error' => 'Ошибка расчета блокнота: ' . $e->getMessage()];
        }
    }

    /**
     * Добавление ламинации к результату расчета блокнота
     */
    private function addNoteLamination($result, $postData, $quantity)
    {
        if (isset($result['error'])) {
            return $result; // Если была ошибка, не обрабатываем ламинацию
        }

        global $priceConfig;
        if (!isset($priceConfig['lamination'])) {
            return $result; // Если конфигурация ламинации недоступна
        }

        $laminationType = $postData['lamination_type'] ?? '';
        $laminationThickness = $postData['lamination_thickness'] ?? '';
        
        if (empty($laminationType)) {
            return $result; // Если тип ламинации не указан
        }

        $laminationCost = 0;

        try {
            // Для блокнотов ламинация применяется только к обложке
            // Определяем тип печати обложки
            $coverPrintingType = 'Цифровая';
            if ($result['components'] && $result['components']['cover'] && $result['components']['cover']['base']) {
                $coverPrintingType = $result['components']['cover']['base']['printingType'] || 'Цифровая';
            }

            if ($coverPrintingType === 'Офсетная') {
                // Офсетная печать: простые тарифы
                if (isset($priceConfig['lamination']['offset'][$laminationType])) {
                    $laminationCost = $quantity * $priceConfig['lamination']['offset'][$laminationType];
                }
            } else {
                // Цифровая печать: зависит от толщины
                if (!empty($laminationThickness) && 
                    isset($priceConfig['lamination']['digital'][$laminationThickness][$laminationType])) {
                    $laminationCost = $quantity * $priceConfig['lamination']['digital'][$laminationThickness][$laminationType];
                }
            }

            if ($laminationCost > 0) {
                $result['total'] += $laminationCost;
                $result['laminationCost'] = $laminationCost;
                $result['laminationType'] = $laminationType;
                $result['laminationThickness'] = $laminationThickness;
            }

        } catch (Exception $e) {
            $this->debug("Ошибка при добавлении ламинации к блокноту", $e->getMessage());
        }

        return $result;
    }

    private function addLogMessage($message) {
        $this->debug($message);
    }
}
?>