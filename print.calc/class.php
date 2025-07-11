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

    public function configureActions()
    {
        return [
            'calc' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod(
                        [ActionFilter\HttpMethod::METHOD_POST]
                    ),
                    new ActionFilter\Csrf()
                ],
                'postfilters' => []
            ]
        ];
    }

    private function loadCalculator()
    {
        try {
            if (!file_exists($this->calculatorFile)) {
                throw new \Exception("Файл калькулятора не найден: " . $this->calculatorFile);
            }

            require_once($this->calculatorFile);
            
            if (!class_exists('Calculator')) {
                throw new \Exception("Класс Calculator не найден в файле " . $this->calculatorFile);
            }

            $this->calculator = new \Calculator();
            return true;

        } catch (\Exception $e) {
            $this->debug("Ошибка загрузки калькулятора", [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return false;
        }
    }

    private function loadConfig($type)
    {
        $configFile = dirname(__FILE__) . '/config/' . $type . '.php';
        
        try {
            if (!file_exists($configFile)) {
                throw new \Exception("Файл конфигурации не найден: " . $configFile);
            }

            $config = include($configFile);
            if (!is_array($config)) {
                throw new \Exception("Некорректный формат конфигурации в файле " . $configFile);
            }

            return $config;

        } catch (\Exception $e) {
            $this->debug("Ошибка загрузки конфигурации", [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return false;
        }
    }

    public function executeComponent()
    {
        try {
            if (!$this->loadCalculator()) {
                throw new \Exception("Не удалось загрузить калькулятор");
            }

            $calcType = $this->arParams['CALC_TYPE'] ?? 'list';
            $config = $this->loadConfig($calcType);

            if (!$config) {
                throw new \Exception("Не удалось загрузить конфигурацию для типа " . $calcType);
            }

            // Подготавливаем данные для шаблона
            $this->arResult = array_merge($config, [
                'CALC_TYPE' => $calcType,
                'CONFIG_LOADED' => true,
                'PAPER_TYPES' => $this->preparePaperTypes($config['papers'] ?? []),
                'FORMATS' => $this->prepareFormats($config['sizes'] ?? []),
                'FEATURES' => $config['features'] ?? []
            ]);

            $this->includeComponentTemplate();

        } catch (\Exception $e) {
            $this->debug("Ошибка в executeComponent", [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            $this->arResult = [
                'CONFIG_LOADED' => false,
                'ERROR' => $e->getMessage()
            ];
            
            $this->includeComponentTemplate();
        }
    }

    private function preparePaperTypes($papers)
    {
        $result = [];
        foreach ($papers as $paper) {
            $result[] = [
                'ID' => $paper,
                'NAME' => $this->formatPaperName($paper)
            ];
        }
        return $result;
    }

    private function prepareFormats($sizes)
    {
        $result = [];
        foreach ($sizes as $size) {
            $result[] = [
                'ID' => $size,
                'NAME' => $size
            ];
        }
        return $result;
    }

    private function formatPaperName($paper)
    {
        if (is_numeric($paper)) {
            return $paper . ' г/м²';
        }
        return $paper;
    }

    // AJAX-методы для разных типов калькуляторов
    public function calcAction($paperType = '', $size = '', $quantity = 0, $printType = 'single', $bigovka = false, $cornerRadius = 0, $perforation = false, $drill = false, $numbering = false, $calcType = 'list', $withLamination = false, $laminationType = '', $laminationSide = '', $folding = 0)
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
            $withLamination = filter_var($withLamination, FILTER_VALIDATE_BOOLEAN);
            
            // Преобразуем числовые значения
            $quantity = (int)$quantity;
            $cornerRadius = (int)$cornerRadius;
            $folding = (int)$folding;
            
            // Проверяем обязательные параметры
            if (empty($paperType) || empty($size) || $quantity <= 0) {
                return ['error' => 'Не заполнены обязательные поля'];
            }

            // Загружаем конфигурацию для типа калькулятора
            $config = $this->loadConfig($calcType);
            if (!$config) {
                return ['error' => 'Не удалось загрузить конфигурацию'];
            }

            // Проверяем ограничения
            if (isset($config['min_quantity']) && $quantity < $config['min_quantity']) {
                return ['error' => "Минимальный тираж: {$config['min_quantity']} шт."];
            }
            if (isset($config['max_quantity']) && $quantity > $config['max_quantity']) {
                return ['error' => "Максимальный тираж: {$config['max_quantity']} шт."];
            }

            // Проверяем поддержку ламинации
            if ($withLamination) {
                if (empty($config['features']['lamination'])) {
                    return ['error' => 'Ламинация не поддерживается для данного типа продукции'];
                }
                if (empty($laminationType) || empty($laminationSide)) {
                    return ['error' => 'Не указаны параметры ламинации'];
                }
            }

            // Проверяем поддержку сложения для буклетов
            if ($calcType === 'booklet' && $folding > 0) {
                if (empty($config['features']['folding'])) {
                    return ['error' => 'Сложение не поддерживается'];
                }
                if ($folding > ($config['max_folding'] ?? 2)) {
                    return ['error' => "Максимальное количество сложений: {$config['max_folding']}"];
                }
            }

            // Вызываем метод расчета
            $result = $this->calculator->calculatePrice(
                $calcType,
                $paperType,
                $size,
                $quantity,
                $printType,
                [
                    'bigovka' => $bigovka,
                    'cornerRadius' => $cornerRadius,
                    'perforation' => $perforation,
                    'drill' => $drill,
                    'numbering' => $numbering,
                    'folding' => $folding,
                    'withLamination' => $withLamination,
                    'laminationType' => $laminationType,
                    'laminationSide' => $laminationSide
                ]
            );

            $this->debug("Результат расчета", $result);
            return $result;

        } catch (\Exception $e) {
            $this->debug("Ошибка при расчете", [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return ['error' => 'Ошибка при расчете: ' . $e->getMessage()];
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