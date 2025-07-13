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
        foreach ($priceConfig['paper'] as $type => $price) {
            if (in_array($type, $availablePapers)) {
                $paperTypes[] = [
                    'ID' => $type,
                    'NAME' => is_numeric($type) ? $type . ' г/м²' : $type,
                    'PRICE' => $price
                ];
            }
        }
        
        // Форматируем размеры (только доступные)
        $formats = [];
        foreach ($priceConfig['size_coefficients'] as $size => $coef) {
            if (in_array($size, $availableSizes)) {
                $formats[] = [
                    'ID' => $size,
                    'NAME' => $size,
                    'COEFFICIENT' => $coef
                ];
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
        if (!function_exists('calculateRizoPrice')) {
            return ['error' => 'Функция calculateRizoPrice не найдена'];
        }
        
        return calculateRizoPrice($paperType, $size, $quantity, $printType, $bigovka, $cornerRadius, $perforation, $drill, $numbering);
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

    private function addLogMessage($message) {
        $this->debug($message);
    }
}