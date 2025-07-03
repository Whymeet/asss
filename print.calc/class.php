<?php
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Context;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

class PrintCalcComponent extends CBitrixComponent implements Controllerable
{
    private $calculatorFile;
    private $debugFile;

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

        // Подключаем файл в глобальном контексте
        global $priceConfig;
        require_once $this->calculatorFile;
        
        // Проверяем, что конфигурация загружена
        if (!isset($priceConfig) || !is_array($priceConfig)) {
            $this->debug('Конфигурация $priceConfig не найдена после подключения');
            
            // Принудительно устанавливаем глобальную переменную
            $GLOBALS['priceConfig'] = $priceConfig ?? null;
            
            if (!isset($GLOBALS['priceConfig']) || !is_array($GLOBALS['priceConfig'])) {
                $this->debug('Критическая ошибка: $priceConfig недоступна');
                return false;
            }
        }
        
        $this->debug('Calculator.php успешно загружен', [
            'priceConfig_isset' => isset($priceConfig),
            'priceConfig_global_isset' => isset($GLOBALS['priceConfig']),
            'functions_count' => count(get_defined_functions()['user'] ?? [])
        ]);
        
        return true;
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
        
        // Подготавливаем данные в зависимости от типа калькулятора
        switch ($calcType) {
            case 'list':
                $this->prepareListData($priceConfig);
                break;
            case 'booklet':
                $this->prepareBookletData($priceConfig);
                break;
            case 'vizit':
                $this->prepareVizitData($priceConfig);
                break;
            default:
                $this->prepareDefaultData($priceConfig);
        }
        
        $this->arResult['CALC_TYPE'] = $calcType;
        $this->arResult['CONFIG_LOADED'] = true;
        
        $this->includeComponentTemplate();
    }

    private function prepareListData($priceConfig)
    {
        $availableSizes = ["A7", "A6", "Евро", "A5", "A4", "A3"];
        
        // Форматируем типы бумаги
        $paperTypes = [];
        foreach ($priceConfig['paper'] as $type => $price) {
            $paperTypes[] = [
                'ID' => $type,
                'NAME' => is_numeric($type) ? $type . ' г/м²' : $type,
                'PRICE' => $price
            ];
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
        
        $this->arResult = [
            'PAPER_TYPES' => $paperTypes,
            'FORMATS' => $formats,
            'AVAILABLE_SIZES' => $availableSizes
        ];
    }

    private function prepareBookletData($priceConfig)
    {
        $availableSizes = ["A4", "A3"];
        $availablePapers = [80.0, 120.0, 90.0, 105.0, 115.0, 130.0, 150.0, 170.0, 200.0, 250.0, 270.0, 300.0, "Самоклейка", "Картон Одн", "Картон Двух"];
        
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
        
        $this->arResult = [
            'PAPER_TYPES' => $paperTypes,
            'FORMATS' => $formats,
            'AVAILABLE_SIZES' => $availableSizes,
            'AVAILABLE_PAPERS' => $availablePapers
        ];
    }

    private function prepareVizitData($priceConfig)
    {
        // Данные для визиток
        $this->arResult = [
            'DIGITAL_PRICES' => $priceConfig['digital_vizit_prices'],
            'OFFSET_PRICES' => $priceConfig['offset_vizit_prices']
        ];
    }

    private function prepareDefaultData($priceConfig)
    {
        // Базовые данные для всех типов
        $paperTypes = [];
        foreach ($priceConfig['paper'] as $type => $price) {
            $paperTypes[] = [
                'ID' => $type,
                'NAME' => is_numeric($type) ? $type . ' г/м²' : $type,
                'PRICE' => $price
            ];
        }
        
        $formats = [];
        foreach ($priceConfig['size_coefficients'] as $size => $coef) {
            $formats[] = [
                'ID' => $size,
                'NAME' => $size,
                'COEFFICIENT' => $coef
            ];
        }
        
        $this->arResult = [
            'PAPER_TYPES' => $paperTypes,
            'FORMATS' => $formats
        ];
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

            switch ($calcType) {
                case 'list':
                    return $this->calculateList($paperType, $size, $quantity, $printType, $bigovka, $cornerRadius, $perforation, $drill, $numbering);
                case 'booklet':
                    $foldingCount = (int)$_POST['foldingCount'] ?? 0;
                    return $this->calculateBooklet($paperType, $size, $quantity, $printType, $foldingCount, $bigovka, $cornerRadius, $perforation, $drill, $numbering);
                default:
                    return $this->calculateList($paperType, $size, $quantity, $printType, $bigovka, $cornerRadius, $perforation, $drill, $numbering);
            }
        } catch (Exception $e) {
            $this->debug("Ошибка в calcAction", $e->getMessage());
            return ['error' => 'Ошибка расчета: ' . $e->getMessage()];
        }
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

    private function addLogMessage($message) {
        $this->debug($message);
    }
}