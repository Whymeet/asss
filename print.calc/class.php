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
                
            case 'kubaric':
                // Специфичные данные для кубариков уже добавлены через additional
                // Дополнительная обработка не требуется
                break;
                
            case 'doorhanger':
                // Дополнительная обработка не требуется, все данные передаются через additional
                break;
                
            case 'envelope':
                // Дополнительная обработка не требуется, все данные передаются через additional
                break;

            case 'calendar':
                // НОВЫЙ КОД ДЛЯ КАЛЕНДАРЕЙ
                // Специфичные данные для календарей уже добавлены через additional
                // Дополнительная обработка не требуется, все данные передаются через конфигурацию
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
    public function calcAction($paperType = '', $size = '', $quantity = 0, $printType = 'single', 
                         $bigovka = false, $cornerRadius = 0, $perforation = false, $drill = false, 
                         $numbering = false, $calcType = 'list', $calendarType = '', $pages = 14)
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

            // Валидация на основе конфигурации (пропускаем для специальных калькуляторов)
            $specialCalcs = ['kubaric', 'vizit', 'canvas', 'sticker', 'stend', 'calendar'];
            $calcConfig = $this->loadCalcConfig($calcType);

            if ($calcConfig && !in_array($calcType, $specialCalcs) && !$this->validateInput($calcConfig, $paperType, $size)) {
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
                    
                case 'catalog':
                    $coverPaper = (int)($_POST['coverPaper'] ?? 130);
                    $coverPrintType = $_POST['coverPrintType'] ?? '4+0';
                    $innerPaper = (int)($_POST['innerPaper'] ?? 130);
                    $innerPrintType = $_POST['innerPrintType'] ?? '4+4';
                    $pages = (int)($_POST['pages'] ?? 8);
                    $bindingType = $_POST['bindingType'] ?? 'spiral';
                    return $this->calculateCatalog($coverPaper, $coverPrintType, $innerPaper, $innerPrintType, $size, $pages, $quantity, $bindingType);
                    
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
                    
                case 'kubaric':
                    $sheetsPerPack = (int)($_POST['sheetsPerPack'] ?? 0);
                    $packsCount = (int)($_POST['packsCount'] ?? 0);
                    $kubaricPrintType = $_POST['printType'] ?? '4+0';
                    return $this->calculateKubaric($sheetsPerPack, $packsCount, $kubaricPrintType);
                    
                case 'doorhanger':
                    $paperType = (float)($_POST['paperType'] ?? 150.0);
                    $quantity = (int)($_POST['quantity'] ?? 0);
                    $printType = $_POST['printType'] ?? 'single';
                    return $this->calculateDoorhanger($paperType, $quantity, $printType);
                    
                case 'envelope':
                    $format = $_POST['format'] ?? '';
                    $quantity = (int)($_POST['quantity'] ?? 0);
                    return $this->calculateEnvelope($format, $quantity);

                case 'calendar':
                    // КАЛЕНДАРИ - НОВЫЙ ФУНКЦИОНАЛ
                    if (!function_exists('calculateCalendarPrice')) {
                        return ['error' => 'Функция расчета календарей не найдена'];
                    }
                    
                    // Преобразуем тип печати из 4+0/4+4 в single/double для внутренней логики
                    $internalPrintType = $printType;
                    if ($printType === '4+0') {
                        $internalPrintType = 'single';
                    } elseif ($printType === '4+4') {
                        $internalPrintType = 'double';
                    }
                    
                    $result = calculateCalendarPrice($calendarType, $size, $quantity, $internalPrintType, $pages);
                    
                    if (isset($result['error'])) {
                        return $result;
                    }
                    
                    // Преобразуем тип печати обратно для отображения
                    $displayPrintType = $printType;
                    if ($printType === 'single') {
                        $displayPrintType = '4+0';
                    } elseif ($printType === 'double') {
                        $displayPrintType = '4+4';
                    }
                    
                    return [
                        'totalPrice' => $result['totalPrice'],
                        'printingCost' => $result['printingCost'] ?? null,
                        'assemblyCost' => $result['assemblyCost'] ?? null,
                        'bigovkaCost' => $result['bigovkaCost'] ?? null,
                        'cornersCost' => $result['cornersCost'] ?? null,
                        'details' => [
                            'type' => $calendarType,
                            'size' => $size,
                            'quantity' => $quantity,
                            'printType' => $displayPrintType,
                            'pages' => $pages
                        ]
                    ];
                    
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

    /**
     * Расчет стоимости холстов - использует существующую функцию calculateCanvasPrice
     */
    private function calculateCanvas($width, $height, $includePodramnik)
    {
        $this->debug("calculateCanvas вызван", [
            'width' => $width,
            'height' => $height,
            'includePodramnik' => $includePodramnik
        ]);

        if (!function_exists('calculateCanvasPrice')) {
            $this->debug("Функция calculateCanvasPrice не найдена");
            return ['error' => 'Функция calculateCanvasPrice не найдена'];
        }
        
        // Валидация входных данных
        if ($width <= 0 || $height <= 0) {
            return ['error' => 'Ширина и высота должны быть больше нуля'];
        }
        
        try {
            $result = calculateCanvasPrice($width, $height, $includePodramnik);
            
            $this->debug("Результат calculateCanvasPrice", $result);
            
            if (!$result) {
                return ['error' => 'Ошибка выполнения расчета холста'];
            }
            
            if (isset($result['error'])) {
                return $result;
            }
            
            // Добавляем исходные параметры для отображения
            $result['originalWidth'] = $width;
            $result['originalHeight'] = $height;
            $result['includePodramnik'] = $includePodramnik;
            
            return $result;
            
        } catch (Exception $e) {
            $this->debug("Исключение в calculateCanvasPrice", $e->getMessage());
            return ['error' => 'Ошибка расчета холста: ' . $e->getMessage()];
        }
    }

    /**
     * Расчет стоимости каталогов
     */
    private function calculateCatalog($coverPaper, $coverPrintType, $innerPaper, $innerPrintType, $size, $pages, $quantity, $bindingType)
    {
        $this->debug("calculateCatalog вызван", [
            'coverPaper' => $coverPaper,
            'coverPrintType' => $coverPrintType,
            'innerPaper' => $innerPaper,
            'innerPrintType' => $innerPrintType,
            'size' => $size,
            'pages' => $pages,
            'quantity' => $quantity,
            'bindingType' => $bindingType
        ]);

        // Проверяем доступность функции
        if (!function_exists('calculateCatalogPrice')) {
            $this->debug("Функция calculateCatalogPrice не найдена");
            return ['error' => 'Функция calculateCatalogPrice не найдена'];
        }

        // Проверяем доступность конфигурации
        global $priceConfig;
        if (!isset($priceConfig) && isset($GLOBALS['priceConfig'])) {
            $priceConfig = $GLOBALS['priceConfig'];
        }
        
        if (!isset($priceConfig) || !is_array($priceConfig)) {
            $this->debug("priceConfig недоступна в calculateCatalog");
            return ['error' => 'Конфигурация цен недоступна'];
        }

        // Загружаем конфигурацию каталогов
        $calcConfig = $this->loadCalcConfig('catalog');
        if (!$calcConfig) {
            return ['error' => 'Конфигурация каталогов не найдена'];
        }

        // Валидация входных данных
        $errors = [];
        
        // Получаем параметры валидации из конфигурации
        $availableCoverPapers = array_keys($calcConfig['additional']['cover_paper_types'] ?? []);
        $availableInnerPapers = array_keys($calcConfig['additional']['inner_paper_types'] ?? []);
        $availableSizes = $calcConfig['additional']['available_sizes'] ?? [];
        $availablePages = $calcConfig['additional']['available_pages'] ?? [];
        $availableBindingTypes = array_keys($calcConfig['additional']['binding_types'] ?? []);
        
        if (!empty($availableCoverPapers) && !in_array($coverPaper, $availableCoverPapers)) {
            $errors[] = "Некорректная плотность бумаги обложки";
        }
        if (!empty($availableInnerPapers) && !in_array($innerPaper, $availableInnerPapers)) {
            $errors[] = "Некорректная плотность бумаги внутренних листов";
        }
        if (!empty($availableSizes) && !in_array($size, $availableSizes)) {
            $errors[] = "Некорректный размер каталога";
        }
        if (!empty($availablePages) && !in_array($pages, $availablePages)) {
            $errors[] = "Некорректное количество страниц";
        }
        if (!empty($availableBindingTypes) && !in_array($bindingType, $availableBindingTypes)) {
            $errors[] = "Некорректный тип сборки";
        }
        if ($quantity <= 0) {
            $errors[] = "Некорректный тираж";
        }
        
        if (!empty($errors)) {
            return ['error' => implode("<br>", $errors)];
        }

        try {
            // Выполняем расчет каталога
            $result = calculateCatalogPrice(
                $coverPaper,
                $coverPrintType,
                $innerPaper,
                $innerPrintType,
                $size,
                $pages,
                $quantity,
                $bindingType
            );
            
            $this->debug("Результат calculateCatalogPrice", $result);
            
            if (!$result) {
                return ['error' => 'Ошибка выполнения расчета каталога'];
            }
            
            if (isset($result['error'])) {
                return $result;
            }
            
            // Добавляем дополнительную информацию для отображения
            $result['bindingType'] = $bindingType;
            $result['coverPaper'] = $coverPaper;
            $result['innerPaper'] = $innerPaper;
            $result['size'] = $size;
            $result['pages'] = $pages;
            $result['quantity'] = $quantity;
            
            return $result;
            
        } catch (Exception $e) {
            $this->debug("Исключение в calculateCatalogPrice", $e->getMessage());
            return ['error' => 'Ошибка расчета каталога: ' . $e->getMessage()];
        }
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
     * Расчет стоимости кубариков
     */
    private function calculateKubaric($sheetsPerPack, $packsCount, $printType)
    {
        $this->debug("calculateKubaric вызван", [
            'sheetsPerPack' => $sheetsPerPack,
            'packsCount' => $packsCount,
            'printType' => $printType
        ]);

        // Загружаем конфигурацию кубариков
        $calcConfig = $this->loadCalcConfig('kubaric');
        if (!$calcConfig) {
            return ['error' => 'Конфигурация кубариков не найдена'];
        }

        // Валидация входных данных
        $errors = [];
        if ($sheetsPerPack <= 0) $errors[] = "Количество листов в пачке должно быть больше 0";
        if ($packsCount <= 0) $errors[] = "Количество пачек должно быть больше 0";
        
        // Проверяем допустимые варианты листов в пачке
        $allowedSheets = $calcConfig['additional']['sheets_per_pack_options'] ?? [100, 300, 500, 900];
        if (!in_array($sheetsPerPack, $allowedSheets)) {
            $errors[] = "Недопустимое количество листов в пачке";
        }

        if (!empty($errors)) {
            return ['error' => implode("<br>", $errors)];
        }

        // Вычисляем общее количество листов
        $totalSheets = $sheetsPerPack * $packsCount;

        // Фиксированные параметры для кубариков
        $paperType = 80.0; // Фиксированная плотность
        $size = "9X9";     // Фиксированный формат

        // Определяем тип печати для базового расчета
        $basePrintType = 'single';
        if (in_array($printType, ['1+1', '4+4'])) {
            $basePrintType = 'double';
        }

        try {
            // Выполняем базовый расчет через существующую функцию
            if (in_array($printType, ['1+0', '1+1'])) {
                // Ризография для ч/б печати
                $baseResult = calculateRizoPrice($paperType, $size, $totalSheets, $basePrintType);
            } else {
                // Обычная печать для цветной
                $baseResult = calculatePrice($paperType, $size, $totalSheets, $basePrintType);
            }

            if (!$baseResult || isset($baseResult['error'])) {
                return ['error' => 'Ошибка базового расчета: ' . ($baseResult['error'] ?? 'Неизвестная ошибка')];
            }

            // Применяем коэффициент наценки
            $multiplier = $calcConfig['additional']['price_multiplier'] ?? 1.3;
            $finalPrice = $baseResult['totalPrice'] * $multiplier;

            $result = [
                'sheetsPerPack' => $sheetsPerPack,
                'packsCount' => $packsCount,
                'totalSheets' => $totalSheets,
                'printType' => $printType,
                'paperType' => $paperType,
                'size' => $size,
                'basePrice' => $baseResult['totalPrice'],
                'multiplier' => $multiplier,
                'finalPrice' => $finalPrice,
                'printingType' => $baseResult['printingType'] ?? 'Неизвестно',
                // Технические детали для отладки
                'baseA3Sheets' => $baseResult['baseA3Sheets'] ?? 0,
                'printingCost' => $baseResult['printingCost'] ?? 0,
                'paperCost' => $baseResult['paperCost'] ?? 0,
                'plateCost' => $baseResult['plateCost'] ?? 0,
                'additionalCosts' => $baseResult['additionalCosts'] ?? 0
            ];

            $this->debug("Результат calculateKubaric", $result);

            return $result;

        } catch (Exception $e) {
            $this->debug("Исключение в calculateKubaric", $e->getMessage());
            return ['error' => 'Ошибка расчета кубариков: ' . $e->getMessage()];
        }
    }

    /**
     * Расчет стоимости блокнотов - ИСПРАВЛЕН
     */
    private function calculateNote($paperType, $size, $quantity, $printType, $bigovka, $cornerRadius, $perforation, $drill, $numbering)
    {
        $this->debug("calculateNote вызван", [
            'paperType' => $paperType,
            'size' => $size,
            'quantity' => $quantity
        ]);

        // Проверяем доступность конфигурации
        global $priceConfig;
        if (!isset($priceConfig) && isset($GLOBALS['priceConfig'])) {
            $priceConfig = $GLOBALS['priceConfig'];
        }
        
        if (!isset($priceConfig) || !is_array($priceConfig)) {
            $this->debug("priceConfig недоступна в calculateNote");
            return ['error' => 'Конфигурация цен недоступна'];
        }

        // Загружаем конфигурацию блокнотов
        $calcConfig = $this->loadCalcConfig('note');
        if (!$calcConfig) {
            return ['error' => 'Конфигурация блокнотов не найдена'];
        }

        // Получаем конфигурацию блокнотов из $priceConfig
        $noteConfig = $priceConfig['note'] ?? [];
        if (empty($noteConfig)) {
            return ['error' => 'Конфигурация блокнотов не найдена в системе'];
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

        $this->debug("Параметры блокнота", $params);

        // Выполняем расчет блокнота напрямую
        try {
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
            
            $coverResult = $this->calculateNoteComponent($coverParams);
            
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
            
            $backResult = $this->calculateNoteComponent($backParams);
            
            // 4. Расчет внутреннего блока
            $totalInnerSheets = $quantity * $innerPages;
            $innerPrintType = $params['inner_print'];
            
            if (strpos($innerPrintType, '1+') === 0) {
                // Ризография
                $innerResult = $this->calculateNoteRizoComponent([
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
                
                $innerResult = $this->calculateNoteComponent($innerParams);
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

            // Добавляем обработку ламинации если указана
            $laminationCost = 0;
            if (!empty($_POST['lamination_type'])) {
                $laminationCost = $this->calculateNoteLamination($_POST, $quantity);
                $totalPrice += $laminationCost;
            }
            
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

            if ($laminationCost > 0) {
                $result['laminationCost'] = $laminationCost;
                $result['laminationType'] = $_POST['lamination_type'];
                $result['laminationThickness'] = $_POST['lamination_thickness'] ?? '';
            }
            
            $this->debug("Результат calculateNote", $result);
            
            return $result;
            
        } catch (Exception $e) {
            $this->debug("Исключение в calculateNote", $e->getMessage());
            return ['error' => 'Ошибка расчета блокнота: ' . $e->getMessage()];
        }
    }

    /**
     * Вспомогательная функция для расчета компонентов блокнота
     */
    private function calculateNoteComponent($params)
    {
        global $priceConfig;
        
        // Если печати нет, возвращаем нулевую стоимость
        if ($params['printType'] === 'none') {
            return [
                'base' => ['totalPrice' => 0],
                'additional' => 0,
                'total' => 0
            ];
        }
        
        // Базовый расчет стоимости печати
        $baseResult = calculatePrice(
            $params['paperType'],
            $params['size'],
            $params['quantity'],
            $params['printType']
        );
        
        if (!$baseResult || isset($baseResult['error'])) {
            return [
                'base' => ['totalPrice' => 0],
                'additional' => 0,
                'total' => 0
            ];
        }
        
        // Добавляем дополнительные услуги
        $additionalCost = $this->calculateNoteAdditionalServices(
            $params['services'],
            $params['quantity']
        );
        
        return [
            'base' => $baseResult,
            'additional' => $additionalCost,
            'total' => $baseResult['totalPrice'] + $additionalCost
        ];
    }

    /**
     * Вспомогательная функция для расчета ризографии в блокноте
     */
    private function calculateNoteRizoComponent($params)
    {
        global $priceConfig;
        
        // Расчет ризографии
        $baseResult = calculateRizoPrice(
            $params['paperType'],
            $params['size'],
            $params['quantity'],
            $params['printType']
        );
        
        if (!$baseResult || isset($baseResult['error'])) {
            return [
                'base' => ['totalPrice' => 0],
                'additional' => 0,
                'total' => 0
            ];
        }
        
        // Добавляем дополнительные услуги
        $additionalCost = $this->calculateNoteAdditionalServices(
            $params['services'],
            $params['quantity']
        );
        
        return [
            'base' => $baseResult,
            'additional' => $additionalCost,
            'total' => $baseResult['totalPrice'] + $additionalCost
        ];
    }

    /**
     * Расчет сборки блокнота
     */
    private function calculateNoteBinding($params)
    {
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

    /**
     * Расчет дополнительных услуг для блокнота
     */
    private function calculateNoteAdditionalServices($services, $quantity)
    {
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
     * Расчет ламинации для блокнота
     */
    private function calculateNoteLamination($postData, $quantity)
    {
        global $priceConfig;
        
        if (!isset($priceConfig['lamination'])) {
            return 0;
        }

        $laminationType = $postData['lamination_type'] ?? '';
        $laminationThickness = $postData['lamination_thickness'] ?? '';
        
        if (empty($laminationType)) {
            return 0;
        }

        $laminationCost = 0;

        try {
            // Для блокнотов ламинация применяется только к обложке
            // Обычно это цифровая печать
            if (!empty($laminationThickness) && 
                isset($priceConfig['lamination']['digital'][$laminationThickness][$laminationType])) {
                $laminationCost = $quantity * $priceConfig['lamination']['digital'][$laminationThickness][$laminationType];
            }

        } catch (Exception $e) {
            $this->debug("Ошибка при добавлении ламинации к блокноту", $e->getMessage());
        }

        return $laminationCost;
    }

    /**
     * Расчет стоимости дорхендеров (6 шт/А3)
     */
    private function calculateDoorhanger($paperType, $quantity, $printType)
    {
        $this->debug("calculateDoorhanger вызван", [
            'paperType' => $paperType,
            'quantity' => $quantity,
            'printType' => $printType
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
            $this->debug("priceConfig недоступна в calculateDoorhanger");
            return ['error' => 'Конфигурация цен недоступна'];
        }

        // Загружаем конфигурацию дорхендеров
        $calcConfig = $this->loadCalcConfig('doorhanger');
        if (!$calcConfig) {
            return ['error' => 'Конфигурация дорхендеров не найдена'];
        }

        // Получаем параметры из конфигурации
        $itemsPerSheet = $calcConfig['additional']['items_per_sheet'] ?? 6;
        $availablePapers = $calcConfig['papers'] ?? [];
        $pricingRules = $calcConfig['additional']['pricing_rules'] ?? [];

        // Валидация входных данных
        $errors = [];
        
        if (!empty($availablePapers) && !in_array($paperType, $availablePapers)) {
            $errors[] = "Некорректный тип бумаги";
        }
        if ($quantity <= 0) {
            $errors[] = "Количество должно быть больше 0";
        }
        if ($quantity % $itemsPerSheet !== 0) {
            $errors[] = "Количество должно быть кратно {$itemsPerSheet}";
        }
        if (!in_array($printType, ['single', 'double'])) {
            $errors[] = "Некорректный тип печати";
        }
        
        if (!empty($errors)) {
            return ['error' => implode("<br>", $errors)];
        }

        try {
            // Расчет листов А3
            $a3Sheets = ceil($quantity / $itemsPerSheet);
            
            // Базовый расчет через существующую функцию
            $baseResult = calculatePrice(
                $paperType,
                'A3', // Фиксированный формат А3
                $quantity,
                $printType
            );
            
            $this->debug("Базовый результат calculatePrice", $baseResult);
            
            if (!$baseResult || isset($baseResult['error'])) {
                return ['error' => 'Ошибка базового расчета: ' . ($baseResult['error'] ?? 'Неизвестная ошибка')];
            }
            
            // Запоминаем базовую стоимость
            $basePrice = $baseResult['totalPrice'];
            
            // Применяем дополнительные наценки согласно логике оригинального кода
            $digitalFee = 0;
            $offsetFee = 0;
            
            if ($baseResult['printingType'] === 'Цифровая') {
                // Для цифровой печати - фиксированная наценка
                $digitalFee = $pricingRules['digital_fee'] ?? 1500;
                $baseResult['digital_fee'] = $digitalFee;
                $baseResult['totalPrice'] += $digitalFee;
            } else {
                // Для офсетной печати - зависит от количества листов
                if ($a3Sheets >= 200 && $a3Sheets <= 1000) {
                    $offsetFee = $pricingRules['offset_fee_200_1000'] ?? 3500;
                } elseif ($a3Sheets > 1000) {
                    $offsetFeePerSheet = $pricingRules['offset_fee_per_sheet'] ?? 3.5;
                    $offsetFee = $a3Sheets * $offsetFeePerSheet;
                }
                
                if ($offsetFee > 0) {
                    $baseResult['offset_fee'] = $offsetFee;
                    $baseResult['totalPrice'] += $offsetFee;
                }
            }
            
            // Добавляем дополнительную информацию для отображения
            $baseResult['basePrice'] = $basePrice;
            $baseResult['a3Sheets'] = $a3Sheets;
            $baseResult['quantity'] = $quantity;
            $baseResult['paperType'] = $paperType;
            $baseResult['printType'] = $printType;
            $baseResult['itemsPerSheet'] = $itemsPerSheet;
            
            $this->debug("Финальный результат calculateDoorhanger", $baseResult);
            
            return $baseResult;
            
        } catch (Exception $e) {
            $this->debug("Исключение в calculateDoorhanger", $e->getMessage());
            return ['error' => 'Ошибка расчета дорхендеров: ' . $e->getMessage()];
        }
    }

    /**
     * Расчет стоимости конвертов
     */
    private function calculateEnvelope($format, $quantity)
    {
        $this->debug("calculateEnvelope вызван", [
            'format' => $format,
            'quantity' => $quantity
        ]);

        // Загружаем конфигурацию конвертов
        $calcConfig = $this->loadCalcConfig('envelope');
        if (!$calcConfig) {
            return ['error' => 'Конфигурация конвертов не найдена'];
        }

        // Получаем таблицы цен из конфигурации
        $envelopePrices = $calcConfig['additional']['envelope_prices'] ?? [];
        $availableFormats = array_keys($calcConfig['additional']['available_formats'] ?? []);

        // Валидация входных данных
        $errors = [];
        
        if (empty($format) || !in_array($format, $availableFormats)) {
            $errors[] = "Некорректный формат конверта";
        }
        if ($quantity < 1) {
            $errors[] = "Количество должно быть больше 0";
        }
        if (empty($envelopePrices[$format])) {
            $errors[] = "Нет данных о ценах для формата {$format}";
        }
        
        if (!empty($errors)) {
            return ['error' => implode("<br>", $errors)];
        }

        try {
            // Поиск подходящего ценового диапазона
            $pricePerUnit = 0;
            $priceRanges = $envelopePrices[$format];
            
            foreach ($priceRanges as $range) {
                if ($quantity >= $range['min'] && $quantity <= $range['max']) {
                    $pricePerUnit = $range['price'];
                    break;
                }
            }
            
            if ($pricePerUnit === 0) {
                return ['error' => 'Не удалось определить цену для указанного количества'];
            }
            
            // Расчет общей стоимости
            $totalPrice = $quantity * $pricePerUnit;
            
            $result = [
                'format' => $format,
                'formatName' => $calcConfig['additional']['available_formats'][$format],
                'quantity' => $quantity,
                'pricePerUnit' => $pricePerUnit,
                'totalPrice' => $totalPrice,
                'priceRange' => $this->findPriceRangeDescription($quantity),
                'success' => true
            ];
            
            $this->debug("Результат calculateEnvelope", $result);
            
            return $result;
            
        } catch (Exception $e) {
            $this->debug("Исключение в calculateEnvelope", $e->getMessage());
            return ['error' => 'Ошибка расчета конвертов: ' . $e->getMessage()];
        }
    }

    /**
     * Определение описания ценового диапазона
     */
    private function findPriceRangeDescription($quantity)
    {
        if ($quantity <= 100) {
            return '1-100 шт (базовая цена)';
        } elseif ($quantity <= 300) {
            return '101-300 шт (скидка 10%)';
        } elseif ($quantity <= 500) {
            return '301-500 шт (скидка 20%)';
        } elseif ($quantity <= 1000) {
            return '501-1000 шт (скидка 30%)';
        } else {
            return '1001+ шт (максимальная скидка 40%)';
        }
    }

    private function addLogMessage($message) {
        $this->debug($message);
    }
}
?>