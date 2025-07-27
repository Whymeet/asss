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
            ],
            'sendOrder' => [
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
                    $result = $this->calculateList($paperType, $size, $quantity, $printType, $bigovka, $cornerRadius, $perforation, $drill, $numbering);
                    
                    // Добавляем обработку ламинации если указана
                    if (!empty($_POST['laminationType']) || !empty($_POST['lamination_type'])) {
                        // Преобразуем данные ламинации в нужный формат
                        $postData = $_POST;
                        if (!empty($_POST['laminationType'])) {
                            $postData['lamination_type'] = $_POST['laminationType'];
                        }
                        if (!empty($_POST['laminationThickness'])) {
                            $postData['lamination_thickness'] = $_POST['laminationThickness'];
                        }
                        $result = $this->addLamination($result, $postData, $quantity);
                    }
                    
                    return $result;
                    
                case 'booklet':
                    $foldingCount = (int)($_POST['foldingCount'] ?? 0);
                    $result = $this->calculateBooklet($paperType, $size, $quantity, $printType, $foldingCount, $bigovka, $cornerRadius, $perforation, $drill, $numbering);
                    
                    // Добавляем обработку ламинации если указана
                    if (!empty($_POST['laminationType']) || !empty($_POST['lamination_type'])) {
                        // Преобразуем данные ламинации в нужный формат
                        $postData = $_POST;
                        if (!empty($_POST['laminationType'])) {
                            $postData['lamination_type'] = $_POST['laminationType'];
                        }
                        if (!empty($_POST['laminationThickness'])) {
                            $postData['lamination_thickness'] = $_POST['laminationThickness'];
                        }
                        $result = $this->addLamination($result, $postData, $quantity);
                    }
                    
                    return $result;
                    
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

                case 'banner':
                    $length = (float)($_POST['length'] ?? 0);
                    $width = (float)($_POST['width'] ?? 0);
                    $bannerType = $_POST['bannerType'] ?? '';
                    $hasHemming = filter_var($_POST['hemming'] ?? false, FILTER_VALIDATE_BOOLEAN);
                    $hasGrommets = filter_var($_POST['grommets'] ?? false, FILTER_VALIDATE_BOOLEAN);
                    $grommetStep = (float)($_POST['grommetStep'] ?? 0.5);
                    
                    return $this->calculateBanner($length, $width, $bannerType, $hasHemming, $hasGrommets, $grommetStep);

                case 'avtoviz':
                    return $this->calculateAvtoviz($paperType, $size, $quantity, $printType, $bigovka, $cornerRadius, $perforation, $drill, $numbering);

                case 'card':
                    return $this->calculateCard($paperType, $size, $quantity, $printType, $bigovka, $cornerRadius, $perforation, $drill, $numbering);
                    
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

    /**
     * Расчет стоимости открыток
     */
    private function calculateCard($paperType, $size, $quantity, $printType, $bigovka, $cornerRadius, $perforation, $drill, $numbering)
    {
        $this->debug("calculateCard вызван", [
            'paperType' => $paperType,
            'size' => $size,
            'quantity' => $quantity,
            'printType' => $printType
        ]);

        global $priceConfig;
        
        if (!function_exists('calculatePrice')) {
            return ['error' => 'Функция calculatePrice не найдена'];
        }

        try {
            // Для открыток используем фиксированную плотность 300.0
            $paperType = 300.0;
            
            // Вызываем функцию расчета из Calculator.php
            $result = calculatePrice($paperType, $size, $quantity, $printType, 0, $bigovka, $cornerRadius, $perforation, $drill, $numbering);
            
            $this->debug("Результат расчета открыток", $result);
            
            return $result;
        } catch (Exception $e) {
            $this->debug("Ошибка расчета открыток: " . $e->getMessage());
            return ['error' => 'Ошибка расчета: ' . $e->getMessage()];
        }
    }

    private function addLogMessage($message) {
        $this->debug($message);
    }

    /**
     * Расчет стоимости баннеров
     */
    private function calculateBanner($length, $width, $bannerType, $hasHemming = false, $hasGrommets = false, $grommetStep = 0.5)
    {
        $this->debug("calculateBanner вызван", [
            'length' => $length,
            'width' => $width,
            'bannerType' => $bannerType,
            'hasHemming' => $hasHemming,
            'hasGrommets' => $hasGrommets,
            'grommetStep' => $grommetStep
        ]);

        global $priceConfig;
        
        if (!function_exists('calculateBanner')) {
            return ['error' => 'Функция calculateBanner не найдена'];
        }

        try {
            // Вызываем функцию расчета из Calculator.php
            $result = calculateBanner($length, $width, $bannerType, $hasHemming, $hasGrommets, $grommetStep);
            
            $this->debug("Результат расчета баннера", $result);
            
            return $result;
        } catch (Exception $e) {
            $this->debug("Ошибка расчета баннера: " . $e->getMessage());
            return ['error' => 'Ошибка расчета: ' . $e->getMessage()];
        }
    }

    /**
     * Расчет стоимости автовизиток
     */
    private function calculateAvtoviz($paperType, $size, $quantity, $printType, $bigovka, $cornerRadius, $perforation, $drill, $numbering)
    {
        $this->debug("calculateAvtoviz вызван", [
            'paperType' => $paperType,
            'size' => $size,
            'quantity' => $quantity,
            'printType' => $printType,
            'bigovka' => $bigovka,
            'cornerRadius' => $cornerRadius,
            'perforation' => $perforation,
            'drill' => $drill,
            'numbering' => $numbering
        ]);

        global $priceConfig;
        
        if (!function_exists('calculatePrice')) {
            return ['error' => 'Функция calculatePrice не найдена'];
        }

        try {
            // Принудительно устанавливаем размер Евро
            $size = 'Евро';
            
            // Вызываем основную функцию расчета из Calculator.php
            $result = calculatePrice($paperType, $size, $quantity, $printType, 0, $bigovka, $cornerRadius, $perforation, $drill, $numbering);
            
            // Добавляем специфичную информацию для автовизиток
            if (!isset($result['error'])) {
                $result['productType'] = 'Автовизитки';
                $result['format'] = 'Евро (99×210 мм)';
                $result['paperType'] = $paperType;
                $result['quantity'] = $quantity;
            }
            
            $this->debug("Результат расчета автовизиток", $result);
            
            return $result;
        } catch (Exception $e) {
            $this->debug("Ошибка расчета автовизиток: " . $e->getMessage());
            return ['error' => 'Ошибка расчета: ' . $e->getMessage()];
        }
    }

    /**
     * Метод для отправки заказа по email
     */
    public function sendOrderAction($name = '', $phone = '', $email = '', $callTime = '', $orderData = '')
    {
        $this->debug("sendOrderAction вызван", [
            'name' => $name,
            'phone' => $phone, 
            'email' => $email,
            'callTime' => $callTime,
            'orderData' => $orderData
        ]);

        // Валидация обязательных полей
        if (empty($name) || empty($phone)) {
            return ['error' => 'Не заполнены обязательные поля: имя и телефон'];
        }

        // Дополнительная валидация
        if (strlen($name) < 2) {
            return ['error' => 'Имя должно содержать минимум 2 символа'];
        }

        // Валидация телефона
        $cleanPhone = preg_replace('/[^\d+]/', '', $phone);
        if (strlen($cleanPhone) < 10) {
            return ['error' => 'Некорректный номер телефона'];
        }

        // Валидация email (если указан)
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['error' => 'Некорректный email адрес'];
        }

        try {
            // Декодируем данные заказа
            $orderInfo = json_decode($orderData, true);
            if (!$orderInfo) {
                return ['error' => 'Некорректные данные заказа'];
            }

            // Формируем сообщение для email
            $message = $this->formatOrderMessage($orderInfo, $name, $phone, $email, $callTime);
            
            // Отправляем email через событие Битрикса
            $success = $this->sendEmailNotification($message, $orderInfo, $name, $phone, $email);
            
            if ($success) {
                $this->debug("Email успешно отправлен");
                return ['success' => true, 'message' => 'Заказ успешно отправлен'];
            } else {
                $this->debug("Ошибка отправки email");
                return ['error' => 'Ошибка при отправке заказа'];
            }
            
        } catch (Exception $e) {
            $this->debug("Исключение при отправке заказа: " . $e->getMessage());
            return ['error' => 'Техническая ошибка при отправке заказа'];
        }
    }

    /**
     * Форматирует сообщение для email
     */
    private function formatOrderMessage($orderInfo, $name, $phone, $email, $callTime)
    {
        // Для листовок создаем красивое HTML-письмо
        if ($orderInfo['calcType'] === 'list') {
            return $this->formatListOrderHTML($orderInfo, $name, $phone, $email, $callTime);
        }
        
        // Для буклетов создаем красивое HTML-письмо
        if ($orderInfo['calcType'] === 'booklet') {
            return $this->formatBookletOrderHTML($orderInfo, $name, $phone, $email, $callTime);
        }
        
        // Для визиток создаем красивое HTML-письмо
        if ($orderInfo['calcType'] === 'vizit') {
            return $this->formatVizitOrderHTML($orderInfo, $name, $phone, $email, $callTime);
        }
        
        // Для ПВХ стендов создаем красивое HTML-письмо
        if ($orderInfo['calcType'] === 'stend') {
            return $this->formatStendOrderHTML($orderInfo, $name, $phone, $email, $callTime);
        }
        
        // Для блокнотов создаем красивое HTML-письмо
        if ($orderInfo['calcType'] === 'note') {
            return $this->formatNoteOrderHTML($orderInfo, $name, $phone, $email, $callTime);
        }
        
        // Для остальных калькуляторов - старый текстовый формат
        $message = "=== НОВЫЙ ЗАКАЗ ИЗ КАЛЬКУЛЯТОРА ===\n\n";
        
        $message .= "Информация о заказе:\n";
        $message .= "Продукт: " . ($orderInfo['product'] ?? 'Не указан') . "\n";
        
        // Для листовок (БСО)
        if ($orderInfo['calcType'] === 'list') {
            $message .= "Формат бланка: " . ($orderInfo['size'] ?? 'Не указан') . "\n";
            $message .= "Печать: " . ($orderInfo['printType'] ?? 'Не указан') . "\n";
            $message .= "Тираж: " . ($orderInfo['quantity'] ?? 'Не указан') . "\n";
            $message .= "Количество слоёв: " . ($orderInfo['layers'] ?? 'Не указан') . "\n";
            $message .= "Нумерация: " . ($orderInfo['numbering'] ?? 'Не указана') . "\n";
            $message .= "Одинаковые слои или разные?: " . ($orderInfo['layersSame'] ?? 'Не указано') . "\n";
            
            if (!empty($orderInfo['additionalServices'])) {
                $message .= "Дополнительные услуги: " . $orderInfo['additionalServices'] . "\n";
            }
        }
        
        $message .= "Итого: " . ($orderInfo['totalPrice'] ?? '0') . " руб.\n\n";
        
        $message .= "Клиент:\n";
        $message .= "Имя: " . $name . "\n";
        $message .= "Телефон: " . $phone . "\n";
        
        if (!empty($email)) {
            $message .= "E-mail: " . $email . "\n";
        }
        
        if (!empty($callTime)) {
            // Если время уже отформатировано (содержит точки), используем как есть
            if (strpos($callTime, '.') !== false) {
                $message .= "Удобное время для звонка: " . $callTime . "\n";
            } else {
                // Иначе пытаемся отформатировать
                $callTimeFormatted = date('d.m.Y H:i', strtotime($callTime));
                $message .= "Удобное время для звонка: " . $callTimeFormatted . "\n";
            }
        }
        
        $message .= "\nДата заказа: " . date('d.m.Y H:i:s') . "\n";
        
        return $message;
    }

    /**
     * Создает HTML-письмо для заказа листовок
     */
    private function formatListOrderHTML($orderInfo, $name, $phone, $email, $callTime)
    {
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Новый заказ листовок</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #28a745, #20c997); color: white; padding: 20px; border-radius: 8px 8px 0 0; text-align: center; }
        .content { background: #f8f9fa; padding: 20px; border: 1px solid #dee2e6; }
        .footer { background: #6c757d; color: white; padding: 15px; border-radius: 0 0 8px 8px; text-align: center; font-size: 14px; }
        .section { margin-bottom: 20px; }
        .section h3 { color: #28a745; margin-bottom: 10px; border-bottom: 2px solid #28a745; padding-bottom: 5px; }
        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { padding: 8px 12px; border-bottom: 1px solid #dee2e6; }
        .info-table td:first-child { font-weight: bold; background: #e8f5e8; width: 40%; }
        .price { font-size: 24px; font-weight: bold; color: #28a745; text-align: center; margin: 20px 0; }
        .client-info { background: white; padding: 15px; border-radius: 6px; border-left: 4px solid #28a745; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Новый заказ листовок</h1>
            <p>Заказ с калькулятора печати</p>
        </div>
        
        <div class="content">
            <div class="section">
                <h3>Информация о заказе</h3>
                <table class="info-table">
                    <tr><td>Продукт</td><td>' . htmlspecialchars($orderInfo['product'] ?? 'Листовки') . '</td></tr>
                    <tr><td>Формат</td><td>' . htmlspecialchars($orderInfo['size'] ?? 'Не указан') . '</td></tr>
                    <tr><td>Тип бумаги</td><td>' . htmlspecialchars($orderInfo['paperType'] ?? 'Не указан') . '</td></tr>
                    <tr><td>Тип печати</td><td>' . htmlspecialchars($orderInfo['printType'] ?? 'Не указан') . '</td></tr>
                    <tr><td>Тираж</td><td>' . number_format($orderInfo['quantity'] ?? 0, 0, ',', ' ') . ' шт.</td></tr>';
        
        // Добавляем информацию о ламинации если есть
        if (!empty($orderInfo['laminationType'])) {
            $laminationText = $orderInfo['laminationType'];
            
            // Преобразуем коды ламинации в понятные названия
            $laminationTypes = [
                '1+0' => 'Односторонняя',
                '1+1' => 'Двусторонняя'
            ];
            
            if (isset($laminationTypes[$laminationText])) {
                $laminationText = $laminationTypes[$laminationText];
            }
            
            // Добавляем толщину если указана
            if (!empty($orderInfo['laminationThickness'])) {
                $laminationText .= ' (' . $orderInfo['laminationThickness'] . ' мкм)';
            }
            
            $html .= '<tr><td>Ламинация</td><td>' . htmlspecialchars($laminationText) . '</td></tr>';
        }
        
        // Добавляем дополнительные услуги если есть
        if (!empty($orderInfo['additionalServices'])) {
            $html .= '<tr><td>Дополнительные услуги</td><td>' . htmlspecialchars($orderInfo['additionalServices']) . '</td></tr>';
        }
        
        $html .= '</table>
                <div class="price">Итого: ' . number_format($orderInfo['totalPrice'] ?? 0, 0, ',', ' ') . ' руб.</div>
            </div>
            
            <div class="section">
                <h3>Информация о клиенте</h3>
                <div class="client-info">
                    <p><strong>Имя:</strong> ' . htmlspecialchars($name) . '</p>
                    <p><strong>Телефон:</strong> <a href="tel:' . htmlspecialchars($phone) . '">' . htmlspecialchars($phone) . '</a></p>';
        
        if (!empty($email)) {
            $html .= '<p><strong>E-mail:</strong> <a href="mailto:' . htmlspecialchars($email) . '">' . htmlspecialchars($email) . '</a></p>';
        }
        
        if (!empty($callTime)) {
            $callTimeFormatted = $callTime;
            if (strpos($callTime, '.') === false && strtotime($callTime)) {
                $callTimeFormatted = date('d.m.Y H:i', strtotime($callTime));
            }
            $html .= '<p><strong>Удобное время для звонка:</strong> ' . htmlspecialchars($callTimeFormatted) . '</p>';
        }
        
        $html .= '<p><strong>Дата заказа:</strong> ' . date('d.m.Y H:i:s') . '</p>
                </div>
            </div>
        </div>
        
        <div class="footer">
            <p>Заказ получен через калькулятор печати на сайте</p>
            <p>Время получения: ' . date('d.m.Y H:i:s') . '</p>
        </div>
    </div>
</body>
</html>';
        
        return $html;
    }

    /**
     * Создает HTML-письмо для заказа буклетов
     */
    private function formatBookletOrderHTML($orderInfo, $name, $phone, $email, $callTime)
    {
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Новый заказ буклетов</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #17a2b8, #20c997); color: white; padding: 20px; border-radius: 8px 8px 0 0; text-align: center; }
        .content { background: #f8f9fa; padding: 20px; border: 1px solid #dee2e6; }
        .footer { background: #6c757d; color: white; padding: 15px; border-radius: 0 0 8px 8px; text-align: center; font-size: 14px; }
        .section { margin-bottom: 20px; }
        .section h3 { color: #17a2b8; margin-bottom: 10px; border-bottom: 2px solid #17a2b8; padding-bottom: 5px; }
        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { padding: 8px 12px; border-bottom: 1px solid #dee2e6; }
        .info-table td:first-child { font-weight: bold; background: #e8f4f8; width: 40%; }
        .price { font-size: 24px; font-weight: bold; color: #17a2b8; text-align: center; margin: 20px 0; }
        .client-info { background: white; padding: 15px; border-radius: 6px; border-left: 4px solid #17a2b8; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Новый заказ буклетов</h1>
            <p>Заказ с калькулятора печати</p>
        </div>
        
        <div class="content">
            <div class="section">
                <h3>Информация о заказе</h3>
                <table class="info-table">
                    <tr><td>Продукт</td><td>' . htmlspecialchars($orderInfo['product'] ?? 'Буклеты') . '</td></tr>
                    <tr><td>Формат</td><td>' . htmlspecialchars($orderInfo['size'] ?? 'Не указан') . '</td></tr>
                    <tr><td>Тип бумаги</td><td>' . htmlspecialchars($orderInfo['paperType'] ?? 'Не указан') . '</td></tr>
                    <tr><td>Тип печати</td><td>' . htmlspecialchars($orderInfo['printType'] ?? 'Не указан') . '</td></tr>
                    <tr><td>Тираж</td><td>' . number_format($orderInfo['quantity'] ?? 0, 0, ',', ' ') . ' шт.</td></tr>';
        
        // Всегда добавляем информацию о сложениях
        if (isset($orderInfo['foldingDescription'])) {
            $html .= '<tr><td>Данные сложений</td><td>' . htmlspecialchars($orderInfo['foldingDescription']) . '</td></tr>';
        } elseif (isset($orderInfo['foldingCount'])) {
            // Фоллбэк для старых данных
            $foldingText = $orderInfo['foldingCount'] > 0 ? $orderInfo['foldingCount'] . ' сложение' . ($orderInfo['foldingCount'] > 1 ? 'я' : '') : 'Нет сложений';
            $html .= '<tr><td>Данные сложений</td><td>' . htmlspecialchars($foldingText) . '</td></tr>';
        } else {
            // Если данных о сложениях нет вообще
            $html .= '<tr><td>Данные сложений</td><td>Нет сложений</td></tr>';
        }
        
        // Добавляем информацию о ламинации если есть
        if (!empty($orderInfo['laminationType'])) {
            $laminationText = $orderInfo['laminationType'];
            
            // Преобразуем коды ламинации в понятные названия
            $laminationTypes = [
                '1+0' => 'Односторонняя',
                '1+1' => 'Двусторонняя'
            ];
            
            if (isset($laminationTypes[$laminationText])) {
                $laminationText = $laminationTypes[$laminationText];
            }
            
            // Добавляем толщину если указана
            if (!empty($orderInfo['laminationThickness'])) {
                $laminationText .= ' (' . $orderInfo['laminationThickness'] . ' мкм)';
            }
            
            $html .= '<tr><td>Ламинация</td><td>' . htmlspecialchars($laminationText) . '</td></tr>';
        }
        
        // Добавляем дополнительные услуги если есть
        if (!empty($orderInfo['additionalServices'])) {
            $html .= '<tr><td>Дополнительные услуги</td><td>' . htmlspecialchars($orderInfo['additionalServices']) . '</td></tr>';
        }
        
        $html .= '</table>
                <div class="price">Итого: ' . number_format($orderInfo['totalPrice'] ?? 0, 0, ',', ' ') . ' руб.</div>
            </div>
            
            <div class="section">
                <h3>Информация о клиенте</h3>
                <div class="client-info">
                    <p><strong>Имя:</strong> ' . htmlspecialchars($name) . '</p>
                    <p><strong>Телефон:</strong> <a href="tel:' . htmlspecialchars($phone) . '">' . htmlspecialchars($phone) . '</a></p>';
        
        if (!empty($email)) {
            $html .= '<p><strong>E-mail:</strong> <a href="mailto:' . htmlspecialchars($email) . '">' . htmlspecialchars($email) . '</a></p>';
        }
        
        if (!empty($callTime)) {
            $callTimeFormatted = $callTime;
            if (strpos($callTime, '.') === false && strtotime($callTime)) {
                $callTimeFormatted = date('d.m.Y H:i', strtotime($callTime));
            }
            $html .= '<p><strong>Удобное время для звонка:</strong> ' . htmlspecialchars($callTimeFormatted) . '</p>';
        }
        
        $html .= '<p><strong>Дата заказа:</strong> ' . date('d.m.Y H:i:s') . '</p>
                </div>
            </div>
        </div>
        
        <div class="footer">
            <p>Заказ получен через калькулятор печати на сайте</p>
            <p>Время получения: ' . date('d.m.Y H:i:s') . '</p>
        </div>
    </div>
</body>
</html>';
        
        return $html;
    }

    /**
     * Форматирование HTML-письма для заказа визиток
     */
    private function formatVizitOrderHTML($orderInfo, $name, $phone, $email, $callTime)
    {
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Новый заказ визиток</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .section {
            margin-bottom: 25px;
            padding: 20px;
            background: #f8f9ff;
            border-radius: 8px;
            border-left: 4px solid #007bff;
        }
        .section h2 {
            color: #007bff;
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 18px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }
        .info-item {
            background: white;
            padding: 15px;
            border-radius: 6px;
            border: 1px solid #e3f2fd;
        }
        .info-item strong {
            color: #0056b3;
            display: block;
            margin-bottom: 5px;
        }
        .price-highlight {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            margin: 20px 0;
        }
        .client-info {
            background: #fff3cd;
            border-left-color: #ffc107;
        }
        .client-info h2 {
            color: #856404;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            color: #6c757d;
            font-size: 14px;
        }
        @media (max-width: 600px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Новый заказ визиток</h1>
        </div>
        
        <div class="section">
            <h2>Параметры заказа</h2>
            <div class="info-grid">';
        
        // Тип печати
        if (!empty($orderInfo['printType'])) {
            $printTypeDisplay = '';
            if ($orderInfo['printType'] === 'digital') {
                $printTypeDisplay = 'Цифровая печать';
            } elseif ($orderInfo['printType'] === 'offset') {
                $printTypeDisplay = 'Офсетная печать';
            } else {
                $printTypeDisplay = htmlspecialchars($orderInfo['printType']);
            }
            
            $html .= '<div class="info-item">
                        <strong>Тип печати:</strong>
                        ' . $printTypeDisplay . '
                      </div>';
        }
        
        // Количество
        if (!empty($orderInfo['quantity'])) {
            $html .= '<div class="info-item">
                        <strong>Тираж:</strong>
                        ' . number_format($orderInfo['quantity'], 0, '', ' ') . ' шт
                      </div>';
        }
        
        // Тип печати (односторонняя/двусторонняя)
        if (!empty($orderInfo['sideType'])) {
            $sideTypeDisplay = '';
            if ($orderInfo['sideType'] === 'single') {
                $sideTypeDisplay = 'Односторонняя (4+0)';
            } elseif ($orderInfo['sideType'] === 'double') {
                $sideTypeDisplay = 'Двусторонняя (4+4)';
            } else {
                $sideTypeDisplay = htmlspecialchars($orderInfo['sideType']);
            }
            
            $html .= '<div class="info-item">
                        <strong>Печать:</strong>
                        ' . $sideTypeDisplay . '
                      </div>';
        }
        
        // Размер (стандартный для визиток)
        $html .= '<div class="info-item">
                    <strong>Размер:</strong>
                    90x50 мм (стандартный)
                  </div>';
        
        $html .= '</div>';
        
        // Стоимость
        if (!empty($orderInfo['totalPrice'])) {
            $html .= '<div class="price-highlight">
                        Стоимость: ' . number_format($orderInfo['totalPrice'], 2, ',', ' ') . ' ₽
                      </div>';
        }
        
        $html .= '</div>';
        
        // Информация о клиенте
        $html .= '<div class="section client-info">
                    <h2>Информация о клиенте</h2>
                    <div class="info-grid">
                        <div class="info-item">
                            <strong>Имя:</strong>
                            ' . htmlspecialchars($name) . '
                        </div>
                        <div class="info-item">
                            <strong>Телефон:</strong>
                            <a href="tel:' . htmlspecialchars($phone) . '">' . htmlspecialchars($phone) . '</a>
                        </div>';
        
        if (!empty($email)) {
            $html .= '<div class="info-item">
                        <strong>Email:</strong>
                        <a href="mailto:' . htmlspecialchars($email) . '">' . htmlspecialchars($email) . '</a>
                      </div>';
        }
        
        $html .= '</div>';
        
        // Предпочтительное время звонка
        if (!empty($callTime)) {
            $callTimeFormatted = $callTime;
            try {
                $dateTime = new DateTime($callTime);
                $callTimeFormatted = $dateTime->format('d.m.Y в H:i');
            } catch (Exception $e) {
                // Если не удалось распарсить дату, оставляем как есть
            }
            $html .= '<p><strong>Удобное время для звонка:</strong> ' . htmlspecialchars($callTimeFormatted) . '</p>';
        }
        
        $html .= '<p><strong>Дата заказа:</strong> ' . date('d.m.Y H:i:s') . '</p>
                </div>
            </div>
        </div>
        
        <div class="footer">
            <p>Заказ получен через калькулятор визиток на сайте</p>
            <p>Время получения: ' . date('d.m.Y H:i:s') . '</p>
        </div>
    </div>
</body>
</html>';
        
        return $html;
    }

    /**
     * Создает HTML-письмо для заказа блокнотов
     */
    private function formatNoteOrderHTML($orderInfo, $name, $phone, $email, $callTime)
    {
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Новый заказ блокнотов</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #6f42c1, #e83e8c); color: white; padding: 20px; border-radius: 8px 8px 0 0; text-align: center; }
        .content { background: #f8f9fa; padding: 20px; border: 1px solid #dee2e6; }
        .footer { background: #6c757d; color: white; padding: 15px; border-radius: 0 0 8px 8px; text-align: center; font-size: 14px; }
        .section { margin-bottom: 20px; }
        .section h3 { color: #6f42c1; margin-bottom: 10px; border-bottom: 2px solid #6f42c1; padding-bottom: 5px; }
        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { padding: 8px 12px; border-bottom: 1px solid #dee2e6; }
        .info-table td:first-child { font-weight: bold; background: #f3e8ff; width: 40%; }
        .price { font-size: 24px; font-weight: bold; color: #6f42c1; text-align: center; margin: 20px 0; }
        .client-info { background: white; padding: 15px; border-radius: 6px; border-left: 4px solid #6f42c1; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Новый заказ блокнотов</h1>
            <p>Заказ с калькулятора печати</p>
        </div>
        
        <div class="content">
            <div class="section">
                <h3>Информация о заказе</h3>
                <table class="info-table">
                    <tr><td>Продукт</td><td>' . htmlspecialchars($orderInfo['product'] ?? 'Блокноты') . '</td></tr>
                    <tr><td>Формат</td><td>' . htmlspecialchars($orderInfo['size'] ?? 'Не указан') . '</td></tr>
                    <tr><td>Тираж</td><td>' . number_format($orderInfo['quantity'] ?? 0, 0, ',', ' ') . ' шт.</td></tr>
                    <tr><td>Листов в блоке</td><td>' . htmlspecialchars($orderInfo['inner_pages'] ?? 'Не указано') . '</td></tr>
                    <tr><td>Печать обложки</td><td>' . htmlspecialchars($orderInfo['cover_print'] ?? 'Не указано') . '</td></tr>
                    <tr><td>Печать задника</td><td>' . htmlspecialchars($orderInfo['back_print'] ?? 'Не указано') . '</td></tr>
                    <tr><td>Печать внутреннего блока</td><td>' . htmlspecialchars($orderInfo['inner_print'] ?? 'Не указано') . '</td></tr>';
        
        // Добавляем информацию о ламинации если есть
        if (!empty($orderInfo['laminationType'])) {
            $laminationText = $orderInfo['laminationType'];
            
            // Преобразуем коды ламинации в понятные названия
            $laminationTypes = [
                '1+0' => 'Односторонняя',
                '1+1' => 'Двусторонняя'
            ];
            
            if (isset($laminationTypes[$laminationText])) {
                $laminationText = $laminationTypes[$laminationText];
            }
            
            // Добавляем толщину если указана
            if (!empty($orderInfo['laminationThickness'])) {
                $laminationText .= ' (' . $orderInfo['laminationThickness'] . ' мкм)';
            }
            
            $html .= '<tr><td>Ламинация обложки</td><td>' . htmlspecialchars($laminationText) . '</td></tr>';
        }
        
        // Добавляем дополнительные услуги если есть
        if (!empty($orderInfo['additionalServices'])) {
            $html .= '<tr><td>Дополнительные услуги</td><td>' . htmlspecialchars($orderInfo['additionalServices']) . '</td></tr>';
        }
        
        $html .= '</table>
                <div class="price">Итого: ' . number_format($orderInfo['totalPrice'] ?? 0, 0, ',', ' ') . ' руб.</div>
            </div>
            
            <div class="section">
                <h3>Информация о клиенте</h3>
                <div class="client-info">
                    <p><strong>Имя:</strong> ' . htmlspecialchars($name) . '</p>
                    <p><strong>Телефон:</strong> <a href="tel:' . htmlspecialchars($phone) . '">' . htmlspecialchars($phone) . '</a></p>';
        
        if (!empty($email)) {
            $html .= '<p><strong>E-mail:</strong> <a href="mailto:' . htmlspecialchars($email) . '">' . htmlspecialchars($email) . '</a></p>';
        }
        
        if (!empty($callTime)) {
            $callTimeFormatted = $callTime;
            if (strpos($callTime, '.') === false && strtotime($callTime)) {
                $callTimeFormatted = date('d.m.Y H:i', strtotime($callTime));
            }
            $html .= '<p><strong>Удобное время для звонка:</strong> ' . htmlspecialchars($callTimeFormatted) . '</p>';
        }
        
        $html .= '<p><strong>Дата заказа:</strong> ' . date('d.m.Y H:i:s') . '</p>
                </div>
            </div>
        </div>
        
        <div class="footer">
            <p>Заказ получен через калькулятор печати на сайте</p>
            <p>Время получения: ' . date('d.m.Y H:i:s') . '</p>
        </div>
    </div>
</body>
</html>';
        
        return $html;
    }

    /**
     * Отправляет email уведомление через событие Битрикса
     */
    private function sendEmailNotification($message, $orderInfo, $name, $phone, $email)
    {
        $this->debug("sendEmailNotification вызвана", [
            'calcType' => $orderInfo['calcType'] ?? 'unknown',
            'messageLength' => strlen($message),
            'name' => $name,
            'phone' => $phone,
            'email' => $email
        ]);
        
        if (!CModule::IncludeModule("main")) {
            $this->debug("Модуль main не подключен");
            return false;
        }

        // Для листовок, буклетов, визиток, стендов и блокнотов отправляем письмо напрямую
        if (in_array($orderInfo['calcType'], ['list', 'booklet', 'vizit', 'stend', 'note'])) {
            $this->debug("Отправляем HTML-письмо для типа: " . $orderInfo['calcType']);
            // Для стендов пока отправляем текстовую версию
            if ($orderInfo['calcType'] === 'stend') {
                return $this->sendTextEmail($message, $orderInfo, $name, $phone, $email);
            }
            // Для остальных HTML
            return $this->sendHtmlEmail($message, $orderInfo, $name, $phone, $email);
        }

        // Для остальных калькуляторов используем стандартный способ через события Bitrix
        $arEventFields = [
            "CALC_TYPE" => $orderInfo['calcType'] ?? 'list',
            "ORDER_INFO" => $message,
            "CLIENT_NAME" => $name,
            "CLIENT_PHONE" => $phone,
            "CLIENT_EMAIL" => $email,
            "DATE_CREATE" => date('d.m.Y H:i:s'),
            // Дополнительные поля для совместимости
            "ORDER_TEXT" => $message,
            "PRODUCT_TYPE" => $orderInfo['product'] ?? '',
            "TOTAL_PRICE" => $orderInfo['totalPrice'] ?? '0',
            "ORDER_DATE" => date('d.m.Y H:i:s')
        ];

        // Отправляем событие
        $this->debug("Отправка события CALC_ORDER_REQUEST с полями:", $arEventFields);
        
        $result = CEvent::Send("CALC_ORDER_REQUEST", SITE_ID, $arEventFields);
        
        $this->debug("Результат отправки события CALC_ORDER_REQUEST", [
            'result' => $result,
            'SITE_ID' => SITE_ID,
            'eventFields' => $arEventFields
        ]);
        
        // Дополнительная проверка лога почты
        if ($result) {
            $this->debug("Событие отправлено успешно, проверяем последние записи почтового лога");
        } else {
            $this->debug("ОШИБКА: Событие не отправлено!");
        }
        
        return $result;
    }

    /**
     * Отправляет HTML-письмо
     */
    private function sendHtmlEmail($htmlMessage, $orderInfo, $name, $phone, $email)
    {
        try {
            // Определяем тип заказа для темы письма
            $productType = 'заказ';
            $calcType = $orderInfo['calcType'] ?? '';
            
            switch ($calcType) {
                case 'list':
                    $productType = 'листовки';
                    break;
                case 'stend':
                    $productType = 'ПВХ стенд';
                    break;
                case 'vizit':
                    $productType = 'визитки';
                    break;
                case 'booklet':
                    $productType = 'буклеты';
                    break;
                case 'note':
                    $productType = 'блокноты';
                    break;
                default:
                    $productType = $orderInfo['product'] ?? 'заказ';
                    break;
            }
            
            $this->debug("Отправка HTML-письма", [
                'to' => 'matvey.turkin.97@mail.ru',
                'subject' => "Новый заказ: {$productType}",
                'calcType' => $calcType,
                'messageLength' => strlen($htmlMessage),
                'messagePreview' => substr(strip_tags($htmlMessage), 0, 100) . '...'
            ]);

            // Пробуем использовать встроенную в Bitrix функцию отправки почты
            $to = "matvey.turkin.97@mail.ru";
            $subject = "Новый заказ: {$productType} - " . number_format($orderInfo['totalPrice'] ?? 0, 0, ',', ' ') . ' руб.';
            $message = $htmlMessage;
            
            // Создаем текстовую версию письма для совместимости
            $textMessage = $this->htmlToText($htmlMessage);
            
            // Генерируем boundary для multipart
            $boundary = "boundary_" . md5(uniqid(time()));
            
            // Заголовки для multipart HTML-письма
            $headers = [
                "MIME-Version: 1.0",
                "Content-Type: multipart/alternative; boundary=\"{$boundary}\"",
                "From: info@mir-pechati.su",
                "Reply-To: " . ($email ?: "info@mir-pechati.su"),
                "X-Mailer: PHP/" . phpversion()
            ];
            
            // Создаем multipart сообщение
            $multipartMessage = "--{$boundary}\r\n";
            $multipartMessage .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $multipartMessage .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
            $multipartMessage .= $textMessage . "\r\n\r\n";
            
            $multipartMessage .= "--{$boundary}\r\n";
            $multipartMessage .= "Content-Type: text/html; charset=UTF-8\r\n";
            $multipartMessage .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
            $multipartMessage .= $htmlMessage . "\r\n\r\n";
            
            $multipartMessage .= "--{$boundary}--";
            
            $this->debug("Заголовки письма", $headers);
            
            // Отправляем через стандартную функцию Bitrix
            $result = bxmail($to, $subject, $multipartMessage, implode("\r\n", $headers));
            
            if ($result) {
                $this->debug("HTML-письмо успешно отправлено через bxmail");
                return true;
            } else {
                $this->debug("Ошибка отправки через bxmail, пробуем альтернативный способ");
                
                // Fallback: используем стандартный PHP mail()
                $result = mail($to, $subject, $multipartMessage, implode("\r\n", $headers));
                
                if ($result) {
                    $this->debug("HTML-письмо успешно отправлено через mail()");
                    return true;
                } else {
                    $this->debug("Ошибка отправки через mail()");
                    return false;
                }
            }
            
        } catch (Exception $e) {
            $this->debug("Исключение при отправке HTML-письма: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Конвертирует HTML в простой текст для текстовой версии письма
     */
    private function htmlToText($html)
    {
        // Убираем теги стилей и скриптов
        $html = preg_replace('/<style\b[^<]*(?:(?!<\/style>)<[^<]*)*<\/style>/mi', '', $html);
        $html = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $html);
        
        // Заменяем основные HTML теги на текстовые эквиваленты
        $html = str_replace(['<br>', '<br/>', '<br />'], "\n", $html);
        $html = str_replace(['</p>', '</div>', '</h1>', '</h2>', '</h3>', '</h4>', '</h5>', '</h6>'], "\n\n", $html);
        $html = str_replace(['</tr>'], "\n", $html);
        $html = str_replace(['</td>'], " | ", $html);
        $html = str_replace(['</li>'], "\n- ", $html);
        
        // Убираем все остальные HTML теги
        $html = strip_tags($html);
        
        // Убираем лишние пробелы и переносы строк
        $html = preg_replace('/\n\s*\n/', "\n\n", $html);
        $html = trim($html);
        
        return $html;
    }

    /**
     * Создает текстовое письмо для заказа ПВХ стендов
     */
    private function formatStendOrderHTML($orderInfo, $name, $phone, $email, $callTime)
    {
        $text = "=== НОВЫЙ ЗАКАЗ ПВХ СТЕНДА ===\n\n";
        
        $text .= "Информация о заказе:\n";
        $text .= "Продукт: " . ($orderInfo['product'] ?? 'ПВХ стенд') . "\n";
        $text .= "Ширина стенда: " . ($orderInfo['width'] ?? 'Не указана') . " см\n";
        $text .= "Высота стенда: " . ($orderInfo['height'] ?? 'Не указана') . " см\n";
        
        // Рассчитываем площадь если есть размеры
        if (!empty($orderInfo['width']) && !empty($orderInfo['height'])) {
            $area = ((float)$orderInfo['width'] * (float)$orderInfo['height']) / 10000;
            $text .= "Площадь стенда: " . number_format($area, 2, ',', ' ') . " м²\n";
        }
        
        // Тип ПВХ
        $pvcTypeText = $orderInfo['pvcType'] ?? 'Не указан';
        if ($pvcTypeText === '3mm') $pvcTypeText = '3 мм';
        if ($pvcTypeText === '5mm') $pvcTypeText = '5 мм';
        $text .= "Толщина ПВХ: " . $pvcTypeText . "\n";
        
        // Карманы
        $text .= "\nКарманы для документов:\n";
        $hasAnyPockets = false;
        
        if (!empty($orderInfo['flatA4']) && (int)$orderInfo['flatA4'] > 0) {
            $text .= "- Плоских карманов А4: " . (int)$orderInfo['flatA4'] . "\n";
            $hasAnyPockets = true;
        }
        if (!empty($orderInfo['flatA5']) && (int)$orderInfo['flatA5'] > 0) {
            $text .= "- Плоских карманов А5: " . (int)$orderInfo['flatA5'] . "\n";
            $hasAnyPockets = true;
        }
        if (!empty($orderInfo['volumeA4']) && (int)$orderInfo['volumeA4'] > 0) {
            $text .= "- Объемных карманов А4: " . (int)$orderInfo['volumeA4'] . "\n";
            $hasAnyPockets = true;
        }
        if (!empty($orderInfo['volumeA5']) && (int)$orderInfo['volumeA5'] > 0) {
            $text .= "- Объемных карманов А5: " . (int)$orderInfo['volumeA5'] . "\n";
            $hasAnyPockets = true;
        }
        
        if (!$hasAnyPockets) {
            $text .= "- Без карманов\n";
        }
        
        // Ламинация если есть
        if (!empty($orderInfo['laminationType'])) {
            $laminationText = $orderInfo['laminationType'];
            
            // Преобразуем коды ламинации в понятные названия
            $laminationTypes = [
                '1+0' => 'Односторонняя',
                '1+1' => 'Двусторонняя'
            ];
            
            if (isset($laminationTypes[$laminationText])) {
                $laminationText = $laminationTypes[$laminationText];
            }
            
            // Добавляем толщину если указана
            if (!empty($orderInfo['laminationThickness'])) {
                $thicknessNames = [
                    '80' => '80 мкм',
                    '125' => '125 мкм',
                    '175' => '175 мкм'
                ];
                
                $thicknessText = $thicknessNames[$orderInfo['laminationThickness']] ?? $orderInfo['laminationThickness'];
                $laminationText .= ' (' . $thicknessText . ')';
            }
            
            $text .= "\nЛаминация: " . $laminationText . "\n";
        }
        
        // Цена
        $text .= "\nИТОГО: " . ($orderInfo['totalPrice'] ?? '0') . " руб.\n";
        
        // Информация о клиенте
        $text .= "\n=== КОНТАКТНАЯ ИНФОРМАЦИЯ ===\n";
        $text .= "Имя: " . $name . "\n";
        $text .= "Телефон: " . $phone . "\n";
        
        if (!empty($email)) {
            $text .= "E-mail: " . $email . "\n";
        }
        
        if (!empty($callTime)) {
            $text .= "Удобное время для звонка: " . $callTime . "\n";
        }
        
        $text .= "\nДата заказа: " . date('d.m.Y H:i:s') . "\n";
        $text .= "Автоматическое уведомление от калькулятора печати\n";
        
        return $text;
    }

    /**
     * Отправляет обычное текстовое письмо
     */
    private function sendTextEmail($textMessage, $orderInfo, $name, $phone, $email)
    {
        try {
            // Определяем тип заказа для темы письма
            $productType = 'заказ';
            $calcType = $orderInfo['calcType'] ?? '';
            
            switch ($calcType) {
                case 'stend':
                    $productType = 'ПВХ стенд';
                    break;
                default:
                    $productType = $orderInfo['product'] ?? 'заказ';
                    break;
            }
            
            $this->debug("Отправка текстового письма", [
                'to' => 'matvey.turkin.97@mail.ru',
                'subject' => "Новый заказ: {$productType}",
                'calcType' => $calcType
            ]);

            // Пробуем использовать встроенную в Bitrix функцию отправки почты
            $to = "matvey.turkin.97@mail.ru";
            $subject = "Новый заказ: {$productType} - " . number_format($orderInfo['totalPrice'] ?? 0, 0, ',', ' ') . ' руб.';
            
            // Конвертируем HTML в текст если нужно
            $message = strip_tags($textMessage);
            $message = html_entity_decode($message, ENT_QUOTES, 'UTF-8');
            
            // Заголовки для текстового письма
            $headers = [
                "Content-Type: text/plain; charset=UTF-8",
                "From: info@mir-pechati.su",
                "Reply-To: " . ($email ?: "info@mir-pechati.su")
            ];
            
            $this->debug("Заголовки письма", $headers);
            $this->debug("Содержимое письма", substr($message, 0, 200) . '...');
            
            // Отправляем через стандартную функцию Bitrix
            $result = bxmail($to, $subject, $message, implode("\r\n", $headers));
            
            if ($result) {
                $this->debug("Текстовое письмо успешно отправлено через bxmail");
                return true;
            } else {
                $this->debug("Ошибка отправки через bxmail, пробуем альтернативный способ");
                
                // Fallback: используем стандартный PHP mail()
                $result = mail($to, $subject, $message, implode("\r\n", $headers));
                
                if ($result) {
                    $this->debug("Текстовое письмо успешно отправлено через mail()");
                    return true;
                } else {
                    $this->debug("Ошибка отправки через mail()");
                    return false;
                }
            }
            
        } catch (Exception $e) {
            $this->debug("Исключение при отправке текстового письма: " . $e->getMessage());
            return false;
        }
    }
}
?>