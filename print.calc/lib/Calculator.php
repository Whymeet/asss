<?php
/**
 * Главный файл системы расчетов - ПОЛНАЯ ВЕРСИЯ
 * Версия 2.0 - полный рефакторинг
 * 
 * ВАЖНО: Вся логика расчетов сохранена из оригинального кода
 * Изменена только структура - расчеты работают идентично
 */

// Загружаем конфигурацию цен
$priceConfig = include __DIR__ . '/config/prices.php';

require_once __DIR__ . '/CalculatorInterface.php';
require_once __DIR__ . '/ParameterValidationTrait.php';
require_once __DIR__ . '/CalculatorConstants.php';

/**
 * Базовый класс калькулятора с общей логикой
 * Содержит методы, которые используются во всех калькуляторах
 */
require_once __DIR__ . '/PrintCalculationTrait.php';

abstract class BaseCalculator implements CalculatorInterface {
    use ParameterValidationTrait;
    use PrintCalculationTrait;
    
    protected $priceConfig;
    protected $result = [];
    
    public function __construct($priceConfig) {
        $this->priceConfig = $priceConfig;
    }
    
    /**
     * Основной метод расчета - должен быть реализован в наследниках
     */
    abstract public function calculate(array $params): array;
    
    /**
     * ОСНОВНАЯ ФУНКЦИЯ РАСЧЕТА ПЕЧАТИ
     * Копия оригинальной calculatePrice с сохранением всей логики
     */
    private const ADJUSTMENT_BASE = 100;
    private const ADJUSTMENT_STEP = 50;
    private const DEFAULT_PAPER_PRICE = 1.5;

    /**
     * Рассчитывает базовую стоимость печати
     * @throws InvalidArgumentException
     */
    protected function calculateBasePrintCost($paperType, $size, $quantity, $printType, $foldingCount = 0) {
        $this->validatePrintParameters($size, $quantity);

        // Расчёт базовых значений
        $sizeCoefficient = $this->priceConfig['size_coefficients'][$size];
        $baseA3Sheets = ceil($quantity / $sizeCoefficient);
        $totalA3Sheets = $baseA3Sheets;
        
        // Добавление листов на приладку для фальцовки
        if ($foldingCount > 0) {
            $adjustment = ($foldingCount == 1) 
                ? self::ADJUSTMENT_BASE 
                : self::ADJUSTMENT_BASE + ($foldingCount - 1) * self::ADJUSTMENT_STEP;
            $totalA3Sheets += $adjustment;
        }
        
        // Определение типа печати
        $printingType = $this->determinePrintingType($baseA3Sheets);
        
        // Расчет стоимости бумаги
        $paperCost = $this->calculatePaperCost($paperType, $totalA3Sheets);
        
        if ($printingType === 'Офсетная') {
            return $this->calculateOffsetPrinting($totalA3Sheets, $baseA3Sheets, $size, $printType, $paperCost, $adjustment ?? 0);
        } else {
            return $this->calculateDigitalPrinting($totalA3Sheets, $baseA3Sheets, $size, $printType, $paperCost);
        }
    }
    
    /**
     * Расчет офсетной печати - ТОЧНАЯ КОПИЯ оригинальной логики
     */
    protected function calculateOffsetPrinting($totalSheets, $baseSheets, $size, $printType, $paperCost, $adjustment) {
        // Определение колонки для стоимости - как в оригинале
        $priceColumn = ($printType === 'double') ? ($size === 'A3' ? '4+4' : 'custom') : '4+0';
        $printingCost = 0;
        
        // Поиск стоимости печати по диапазонам - без изменений
        foreach ($this->priceConfig['offset_prices'] as $range) {
            if ($totalSheets >= $range['min'] && $totalSheets <= $range['max']) {
                $printingCost = $range[$priceColumn];
                break;
            }
        }
        
        // Стоимость пластины - логика сохранена
        $plateCost = ($size === 'A3' && $printType === 'double') 
            ? $this->priceConfig['plate_costs']['A3_double'] 
            : $this->priceConfig['plate_costs']['standard'];
        
        // Итоговый расчет - формула не изменена
        $totalPrice = $paperCost + ($printingCost * $totalSheets) + $plateCost;
        
        return [
            'printingType' => 'Офсетная',
            'baseA3Sheets' => $baseSheets,
            'totalA3Sheets' => $totalSheets,
            'adjustment' => $adjustment,
            'paperCost' => $paperCost,
            'printingCost' => $printingCost * $totalSheets,
            'plateCost' => $plateCost,
            'totalPrice' => $totalPrice
        ];
    }
    
    /**
     * Расчет цифровой печати - без изменений в логике
     */
    protected function calculateDigitalPrinting($totalSheets, $baseSheets, $size, $printType, $paperCost) {
        // Получение цены за лист - как было
        $priceKey = ($printType === 'double') ? 'double' : 'single';
        $printingCost = $this->priceConfig['digital_prices'][$size][$priceKey] ?? 0;
        
        // Итоговая стоимость печати
        $totalPrintingCost = $printingCost * $totalSheets;
        $totalPrice = $paperCost + $totalPrintingCost;
        
        return [
            'printingType' => 'Цифровая',
            'baseA3Sheets' => $baseSheets,
            'totalA3Sheets' => $totalSheets,
            'adjustment' => 0,
            'paperCost' => $paperCost,
            'printingCost' => $totalPrintingCost,
            'plateCost' => 0,
            'totalPrice' => $totalPrice
        ];
    }
    
    /**
     * Расчет дополнительных услуг - СОХРАНЕНА вся логика
     */
    protected function calculateAdditionalServices($services, $quantity, $size = null) {
        $cost = 0;
        $details = [];
        
        // Биговка - расчет не изменен
        if (!empty($services['bigovka'])) {
            $bigovkaPrice = $this->priceConfig['additional_services']['bigovka'] ?? 0.20;
            $bigovkaCost = $quantity * $bigovkaPrice;
            $cost += $bigovkaCost;
            $details['bigovka'] = $bigovkaCost;
        }
        
        // Перфорация
        if (!empty($services['perforation'])) {
            $perforationPrice = $this->priceConfig['additional_services']['perforation'] ?? 0.25;
            $perforationCost = $quantity * $perforationPrice;
            $cost += $perforationCost;
            $details['perforation'] = $perforationCost;
        }
        
        // Сверление отверстий
        if (!empty($services['drill'])) {
            $drillPrice = $this->priceConfig['additional_services']['drill'] ?? 0.35;
            $drillCost = $quantity * $drillPrice;
            $cost += $drillCost;
            $details['drill'] = $drillCost;
        }
        
        // Нумерация
        if (!empty($services['numbering'])) {
            $numberingPrice = $this->priceConfig['additional_services']['numbering'] ?? 0.60;
            $numberingCost = $quantity * $numberingPrice;
            $cost += $numberingCost;
            $details['numbering'] = $numberingCost;
        }
        
        // Скругление углов - особая логика для разного количества углов
        if (!empty($services['cornerRadius']) && $services['cornerRadius'] > 0) {
            $cornerCount = (int)$services['cornerRadius'];
            $cornerPrice = $this->priceConfig['additional_services']['corner_radius'] ?? 0.10;
            
            // Цена зависит от количества углов
            $pricePerCorner = $cornerPrice * $cornerCount;
            $cornerCost = $quantity * $pricePerCorner;
            $cost += $cornerCost;
            $details['cornerRadius'] = $cornerCost;
        }
        
        return [
            'total' => $cost,
            'details' => $details
        ];
    }
    
    /**
     * Расчет ламинации - ТОЧНАЯ КОПИЯ оригинальной логики
     */
    protected function calculateLamination($type, $thickness, $quantity, $printingType) {
        if (empty($type)) {
            return ['cost' => 0];
        }
        
        $cost = 0;
        
        if ($printingType === 'Офсетная') {
            // Офсетная ламинация - простой тариф
            $cost = $quantity * ($this->priceConfig['lamination']['offset'][$type] ?? 0);
        } else {
            // Цифровая ламинация - зависит от толщины
            if (!empty($thickness)) {
                $cost = $quantity * ($this->priceConfig['lamination']['digital'][$thickness][$type] ?? 0);
            }
        }
        
        return [
            'cost' => $cost,
            'type' => $type,
            'thickness' => $thickness
        ];
    }
    
    /**
     * Расчет ризографической печати - используется в блокнотах
     */
    protected function calculateRizoPrinting($paperType, $size, $quantity, $printType) {
        // Проверка корректности
        if ($quantity <= 0 || !isset($this->priceConfig['size_coefficients'][$size])) {
            throw new Exception('Некорректные данные для ризографии');
        }
        
        // Коэффициент размера
        $sizeCoefficient = $this->priceConfig['size_coefficients'][$size];
        $baseA3Sheets = ceil($quantity / $sizeCoefficient);
        
        // Стоимость бумаги
        $paperCost = $baseA3Sheets * ($this->priceConfig['paper'][$paperType] ?? 1.5);
        
        // Цены на ризографию из конфигурации
        $rizoConfig = $this->priceConfig['rizo'] ?? [];
        
        // Определяем стоимость печати
        $printCost = 0;
        if (isset($rizoConfig['prices'][$size])) {
            $priceType = ($printType === 'double') ? 'double' : 'single';
            $printCost = $baseA3Sheets * ($rizoConfig['prices'][$size][$priceType] ?? 0);
        } else {
            // Запасной вариант
            $printCost = $baseA3Sheets * 2.5; // Базовая цена
        }
        
        return [
            'printingType' => 'Ризография',
            'baseA3Sheets' => $baseA3Sheets,
            'paperCost' => $paperCost,
            'printingCost' => $printCost,
            'totalPrice' => $paperCost + $printCost
        ];
    }
}

/**
 * КАЛЬКУЛЯТОР ЛИСТОВОК
 * Базовый калькулятор для листовок, флаеров
 */
class ListCalculator extends BaseCalculator {
    
    public function calculate(array $params): array {
        // Валидация обязательных параметров
        $this->validateRequired($params, ['paperType', 'size', 'quantity']);
        $this->validateNumeric($params, ['quantity']);
        
        // Извлечение параметров
        $paperType = $params['paperType'];
        $size = $params['size'];
        $quantity = (int)$params['quantity'];
        $printType = $params['printType'] ?? 'single';
        
        // Базовый расчет печати
        $result = $this->calculateBasePrintCost($paperType, $size, $quantity, $printType);
        
        // Сбор дополнительных услуг
        $services = [
            'bigovka' => !empty($params['bigovka']),
            'perforation' => !empty($params['perforation']),
            'drill' => !empty($params['drill']),
            'numbering' => !empty($params['numbering']),
            'cornerRadius' => (int)($params['cornerRadius'] ?? 0)
        ];
        
        // Расчет стоимости доп. услуг
        $additionalServices = $this->calculateAdditionalServices($services, $quantity);
        $result['additionalServicesCost'] = $additionalServices['total'];
        $result['additionalServicesDetails'] = $additionalServices['details'];
        $result['totalPrice'] += $additionalServices['total'];
        
        // Расчет ламинации если указана
        if (!empty($params['lamination_type'])) {
            $lamination = $this->calculateLamination(
                $params['lamination_type'],
                $params['lamination_thickness'] ?? '',
                $quantity,
                $result['printingType']
            );
            
            $result['laminationCost'] = $lamination['cost'];
            $result['laminationType'] = $lamination['type'];
            $result['laminationThickness'] = $lamination['thickness'];
            $result['totalPrice'] += $lamination['cost'];
        }
        
        // Проверка доступности ламинации
        $result['laminationAvailable'] = true;
        
        return $result;
    }
}

/**
 * КАЛЬКУЛЯТОР БУКЛЕТОВ
 * Добавляет фальцовку к базовому расчету
 */
class BookletCalculator extends BaseCalculator {
    
    public function calculate(array $params): array {
        // Валидация обязательных параметров
        $this->validateRequired($params, ['paperType', 'size', 'quantity']);
        $this->validateNumeric($params, ['quantity']);
        
        $paperType = $params['paperType'];
        $size = $params['size'];
        $quantity = (int)$params['quantity'];
        $foldingCount = (int)($params['foldingCount'] ?? 1);
        
        // Буклеты всегда двусторонние
        $result = $this->calculateBasePrintCost($paperType, $size, $quantity, 'double', $foldingCount);
        
        // Расчет стоимости фальцовки - ОРИГИНАЛЬНАЯ ЛОГИКА
        $foldingCost = $this->calculateFoldingCost($quantity, $foldingCount);
        $result['foldingCost'] = $foldingCost;
        $result['foldingCount'] = $foldingCount;
        $result['totalPrice'] += $foldingCost;
        
        // Дополнительные услуги
        $services = [
            'bigovka' => !empty($params['bigovka']),
            'perforation' => !empty($params['perforation']),
            'drill' => !empty($params['drill']),
            'numbering' => !empty($params['numbering']),
            'cornerRadius' => (int)($params['cornerRadius'] ?? 0)
        ];
        
        $additionalServices = $this->calculateAdditionalServices($services, $quantity);
        if ($additionalServices['total'] > 0) {
            $result['additionalServicesCost'] = $additionalServices['total'];
            $result['totalPrice'] += $additionalServices['total'];
        }
        
        return $result;
    }
    
    /**
     * Расчет стоимости фальцовки - как в оригинале
     */
    protected function calculateFoldingCost($quantity, $foldingCount) {
        $baseRate = $this->priceConfig['folding']['base_rate'] ?? 0.5;
        
        // Прогрессивная стоимость за каждый дополнительный фальц
        $cost = 0;
        for ($i = 1; $i <= $foldingCount; $i++) {
            $rate = $baseRate * $i; // Увеличение стоимости с каждым фальцем
            $cost += $quantity * $rate;
        }
        
        return $cost;
    }
}

/**
 * КАЛЬКУЛЯТОР ВИЗИТОК
 * Особая логика для цифровой и офсетной печати
 */
class VizitCalculator extends BaseCalculator {
    
    public function calculate(array $params): array {
        // Валидация обязательных параметров
        $this->validateRequired($params, ['quantity']);
        $this->validateNumeric($params, ['quantity']);
        
        $printType = $params['printType'] ?? CalculatorConstants::PRINT_TYPE_DIGITAL;
        $quantity = (int)$params['quantity'];
        $sideType = $params['sideType'] ?? CalculatorConstants::PRINT_TYPE_SINGLE;
        
        if ($printType === 'digital') {
            // Для цифровой печати используем переданное количество
            $digitalQuantity = (int)($params['digitalQuantity'] ?? $quantity);
            return $this->calculateDigitalVizit($digitalQuantity, $sideType);
        } else {
            // Для офсетной - фиксированные тиражи
            $offsetQuantity = (int)($params['offsetQuantity'] ?? $quantity);
            return $this->calculateOffsetVizit($offsetQuantity, $sideType);
        }
    }
    
    /**
     * Расчет цифровой печати визиток
     */
    protected function calculateDigitalVizit($quantity, $sideType) {
        // Цены за штуку из конфигурации
        $prices = $this->priceConfig['vizit']['digital'] ?? [];
        $pricePerUnit = $prices[$sideType] ?? 0;
        
        // Для цифровой печати может быть прогрессивная скидка
        if ($quantity > 500) {
            $pricePerUnit *= 0.9; // Скидка 10% при тираже больше 500
        }
        
        $totalPrice = $quantity * $pricePerUnit;
        
        return [
            'printingType' => 'Цифровая печать визиток',
            'quantity' => $quantity,
            'pricePerUnit' => $pricePerUnit,
            'sideType' => $sideType,
            'totalPrice' => $totalPrice
        ];
    }
    
    /**
     * Расчет офсетной печати визиток - фиксированные тиражи
     */
    protected function calculateOffsetVizit($quantity, $sideType) {
        // Для офсета - готовые цены за тираж
        $prices = $this->priceConfig['vizit']['offset'][$sideType] ?? [];
        
        // Ищем подходящий тираж
        if (!isset($prices[$quantity])) {
            // Если точного тиража нет, ищем ближайший больший
            $availableQuantities = array_keys($prices);
            sort($availableQuantities);
            
            foreach ($availableQuantities as $availQty) {
                if ($availQty >= $quantity) {
                    $quantity = $availQty;
                    break;
                }
            }
        }
        
        $totalPrice = $prices[$quantity] ?? 0;
        
        return [
            'printingType' => 'Офсетная печать визиток',
            'quantity' => $quantity,
            'sideType' => $sideType,
            'totalPrice' => $totalPrice
        ];
    }
}

/**
 * КАЛЬКУЛЯТОР БЛОКНОТОВ
 * Сложный расчет: обложка + задник + внутренний блок + сборка
 */
class NoteCalculator extends BaseCalculator {
    
    public function calculate(array $params): array {
        // Валидация обязательных параметров
        $this->validateRequired($params, ['size', 'quantity']);
        $this->validateNumeric($params, ['quantity', 'innerPages']);
        
        $size = $params['size'];
        $quantity = (int)$params['quantity'];
        $innerPages = (int)($params['innerPages'] ?? 50);
        
        // КОМПОНЕНТ 1: Обложка (цветная, плотная бумага)
        $coverResult = $this->calculateNoteCover($size, $quantity);
        
        // КОМПОНЕНТ 2: Задник (такой же как обложка)
        $backResult = $this->calculateNoteBack($size, $quantity);
        
        // КОМПОНЕНТ 3: Внутренний блок (ч/б печать, тонкая бумага)
        $innerResult = $this->calculateNoteInner($size, $quantity, $innerPages);
        
        // КОМПОНЕНТ 4: Сборка на пружину
        $bindingCost = $this->calculateNoteBinding($size, $quantity);
        
        // Сбор дополнительных услуг если есть
        $additionalCost = 0;
        if (!empty($params['bigovka']) || !empty($params['perforation'])) {
            $services = [
                'bigovka' => !empty($params['bigovka']),
                'perforation' => !empty($params['perforation']),
                'drill' => !empty($params['drill']),
                'numbering' => !empty($params['numbering']),
                'cornerRadius' => (int)($params['cornerRadius'] ?? 0)
            ];
            $additional = $this->calculateAdditionalServices($services, $quantity);
            $additionalCost = $additional['total'];
        }
        
        // Итоговая сборка результата
        $totalPrice = $coverResult['totalPrice'] + 
                     $backResult['totalPrice'] + 
                     $innerResult['totalPrice'] + 
                     $bindingCost + 
                     $additionalCost;
        
        return [
            'coverCost' => $coverResult['totalPrice'],
            'backCost' => $backResult['totalPrice'],
            'innerCost' => $innerResult['totalPrice'],
            'bindingCost' => $bindingCost,
            'additionalCost' => $additionalCost,
            'totalPrice' => $totalPrice,
            'details' => [
                'cover' => $coverResult,
                'back' => $backResult,
                'inner' => $innerResult,
                'pages' => $innerPages,
                'binding' => ['cost' => $bindingCost, 'type' => 'spiral']
            ]
        ];
    }
    
    /**
     * Расчет обложки блокнота - цветная печать на плотной бумаге
     */
    protected function calculateNoteCover($size, $quantity) {
        // Обложка: 300г бумага, цветная двусторонняя печать
        return $this->calculateBasePrintCost(300, $size, $quantity, 'double');
    }
    
    /**
     * Расчет задника блокнота - обычно как обложка
     */
    protected function calculateNoteBack($size, $quantity) {
        // Задник: 300г бумага, может быть односторонняя печать
        return $this->calculateBasePrintCost(300, $size, $quantity, 'single');
    }
    
    /**
     * Расчет внутреннего блока - ризография для экономии
     */
    protected function calculateNoteInner($size, $quantity, $pages) {
        // Общее количество листов внутреннего блока
        $totalInnerSheets = $quantity * $pages;
        
        // Для внутреннего блока используем ризографию (дешевле)
        return $this->calculateRizoPrinting(80, $size, $totalInnerSheets, 'single');
    }
    
    /**
     * Расчет стоимости сборки на пружину
     */
    protected function calculateNoteBinding($size, $quantity) {
        // Цены на сборку зависят от формата и тиража
        $bindingConfig = $this->priceConfig['note']['binding']['spiral'][$size] ?? [];
        
        // Определяем цену за единицу по тиражу
        if ($quantity <= 100) {
            $pricePerUnit = $bindingConfig[100] ?? 18;
        } elseif ($quantity <= 500) {
            $pricePerUnit = $bindingConfig[500] ?? 16;
        } elseif ($quantity <= 1000) {
            $pricePerUnit = $bindingConfig[1000] ?? 14;
        } else {
            $pricePerUnit = $bindingConfig['max'] ?? 13;
        }
        
        return $quantity * $pricePerUnit;
    }
}

/**
 * КАЛЬКУЛЯТОР КАТАЛОГОВ
 * Многостраничные изделия с различными типами сборки
 */
class CatalogCalculator extends BaseCalculator {
    
    public function calculate(array $params): array {
        // Валидация обязательных параметров
        $this->validateRequired($params, ['quantity']);
        $this->validateNumeric($params, ['quantity', 'pages']);
        
        $pages = (int)($params['pages'] ?? CalculatorConstants::MIN_CATALOG_PAGES);
        $quantity = (int)$params['quantity'];
        $bindingType = $params['bindingType'] ?? CalculatorConstants::BINDING_STAPLE;
        $coverPaper = $params['coverPaper'] ?? $params['blockPaper'] ?? 170;
        $blockPaper = $params['blockPaper'] ?? 170;
        
        // Проверка кратности страниц
        if ($pages % 4 !== 0) {
            return ['error' => 'Количество страниц должно быть кратно 4'];
        }
        
        // Расчет количества листов А3 для блока
        $a3SheetsPerCatalog = $pages / 4; // 4 страницы А4 на одном листе А3
        $totalA3Sheets = $a3SheetsPerCatalog * $quantity;
        
        // Определяем одинаковая ли бумага для обложки и блока
        $samePaper = ($coverPaper == $blockPaper);
        
        if ($samePaper) {
            // Если бумага одинаковая - считаем все вместе
            $result = $this->calculateBasePrintCost($blockPaper, 'A3', $totalA3Sheets, 'double');
            $result['samePaper'] = true;
        } else {
            // Если разная - считаем отдельно обложку и блок
            $coverSheets = $quantity; // 1 лист А3 на обложку
            $blockSheets = ($a3SheetsPerCatalog - 1) * $quantity; // Остальные листы
            
            $coverResult = $this->calculateBasePrintCost($coverPaper, 'A3', $coverSheets, 'double');
            $blockResult = $this->calculateBasePrintCost($blockPaper, 'A3', $blockSheets, 'double');
            
            $result = [
                'printingType' => $coverResult['printingType'], // Берем тип печати от обложки
                'coverCost' => $coverResult['totalPrice'],
                'blockCost' => $blockResult['totalPrice'],
                'totalPrice' => $coverResult['totalPrice'] + $blockResult['totalPrice'],
                'samePaper' => false
            ];
        }
        
        // Добавляем стоимость сборки
        $bindingCost = $this->calculateCatalogBinding($bindingType, $quantity, $pages);
        $result['bindingCost'] = $bindingCost;
        $result['bindingType'] = $bindingType;
        $result['totalPrice'] += $bindingCost;
        
        // Сохраняем информацию о параметрах
        $result['pages'] = $pages;
        $result['quantity'] = $quantity;
        
        return $result;
    }
    
    /**
     * Расчет стоимости сборки каталога
     */
    protected function calculateCatalogBinding($type, $quantity, $pages) {
        $bindingPrices = $this->priceConfig['catalog']['binding'] ?? [];
        
        switch ($type) {
            case 'staple': // Скрепка
                $basePrice = $bindingPrices['staple'] ?? 2;
                // Цена может зависеть от количества страниц
                if ($pages > 20) {
                    $basePrice *= 1.5; // Доплата за толстые каталоги
                }
                break;
                
            case 'spring': // Пружина
                $basePrice = $bindingPrices['spring'] ?? 15;
                break;
                
            case 'glue': // Клеевое скрепление
                $basePrice = $bindingPrices['glue'] ?? 25;
                break;
                
            default:
                $basePrice = 2;
        }
        
        return $quantity * $basePrice;
    }
}

/**
 * КАЛЬКУЛЯТОР АВТОВИЗИТОК
 * Фиксированный формат Евро
 */
class AvtovizCalculator extends BaseCalculator {
    
    public function calculate(array $params): array {
        // Автовизитки всегда формата Евро
        $params['size'] = CalculatorConstants::SIZE_EURO;
        
        // Используем стандартный калькулятор листовок
        $listCalculator = new ListCalculator($this->priceConfig);
        $result = $listCalculator->calculate($params);
        
        // Добавляем специфичную информацию
        $result['productType'] = 'Автовизитка';
        
        return $result;
    }
}

/**
 * КАЛЬКУЛЯТОР ОТКРЫТОК
 * Плотная бумага, возможна ламинация
 */
class CardCalculator extends BaseCalculator {
    
    public function calculate(array $params): array {
        // Открытки всегда на плотной бумаге
        if (empty($params['paperType']) || $params['paperType'] < CalculatorConstants::PAPER_TYPE_300) {
            $params['paperType'] = CalculatorConstants::PAPER_TYPE_300;
        }
        
        // Используем стандартный расчет
        $listCalculator = new ListCalculator($this->priceConfig);
        $result = $listCalculator->calculate($params);
        
        // Добавляем информацию о продукте
        $result['productType'] = 'Открытка';
        $result['recommendedLamination'] = true;
        
        return $result;
    }
}

/**
 * КАЛЬКУЛЯТ