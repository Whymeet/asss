<?php

namespace PrintCalc\Calculators;

use PrintCalc\Core\AbstractCalculator;
use PrintCalc\Core\Constants\PrintTypes;
use PrintCalc\Core\Constants\PrintMethods;
use PrintCalc\Core\PrintCalculationTrait;

class BookletCalculator extends AbstractCalculator
{
    use PrintCalculationTrait;

    /**
     * Типы фальцовки
     */
    private const FOLD_TYPES = [
        'no' => 0,      // Без фальцовки
        'single' => 1,   // Один сгиб
        'double' => 2,   // Два сгиба
        'triple' => 3    // Три сгиба
    ];

    /**
     * Расчет стоимости буклетов
     *
     * @param array $params Параметры расчета:
     *  - string paperType: Тип бумаги (плотность)
     *  - string size: Размер (A3, A4, A5, A6)
     *  - int quantity: Тираж
     *  - string printType: Тип печати (single для 4+0 или double для 4+4)
     *  - string foldType: Тип фальцовки (no, single, double, triple)
     *  - bool lamination: Нужна ли ламинация
     *  - string laminationType: Тип ламинации (глянцевая/матовая)
     *  - bool bigovka: Нужна ли биговка
     *  - int cornerRadius: Количество скругленных углов (0-4)
     *  - bool drill: Нужно ли сверление
     * @return array
     */
    public function calculate(array $params): array
    {
        // Валидация параметров
        $errors = $this->validate($params);
        if (!empty($errors)) {
            return ['error' => implode(", ", $errors)];
        }

        // Получаем и нормализуем параметры
        $paperType = $params['paperType'];
        $size = mb_convert_case($params['size'], MB_CASE_UPPER, "UTF-8");
        $quantity = (int)$params['quantity'];
        $printType = $params['printType'];
        $foldType = $params['foldType'] ?? 'no';
        $lamination = $params['lamination'] ?? false;
        $laminationType = $params['laminationType'] ?? null;
        
        // Дополнительные услуги
        $bigovka = $params['bigovka'] ?? false;
        $cornerRadius = $params['cornerRadius'] ?? 0;
        $drill = $params['drill'] ?? false;

        // Расчет базовых значений
        $sizeCoefficient = $this->config->get("size_coefficients.$size");
        $baseA3Sheets = ceil($quantity / $sizeCoefficient);
        
        // Определяем метод печати
        $printMethod = $this->determinePrintMethod($baseA3Sheets);
        $totalA3Sheets = $baseA3Sheets;
        $printingCost = 0;
        $plateCost = 0;

        // Расчет стоимости печати
        if ($printMethod === PrintMethods::OFFSET) {
            // Определяем колонку для стоимости офсетной печати
            $priceColumn = ($printType === PrintTypes::DOUBLE) 
                ? ($size === 'A3' ? '4+4' : 'custom') 
                : '4+0';

            // Находим стоимость печати в таблице офсетных цен
            foreach ($this->config->get('offset_prices') as $range) {
                if ($totalA3Sheets >= $range['min'] && $totalA3Sheets <= $range['max']) {
                    $printingCost = $range[$priceColumn];
                    break;
                }
            }

            // Добавляем приладку
            $adjustment = $this->calculateAdjustmentSheets($baseA3Sheets);
            $totalA3Sheets += $adjustment;

            // Считаем стоимость пластин
            $plateCost = $this->calculatePlateCost($size, $printType);

        } else { // Цифровая печать
            $digitalPrice = $this->findDigitalPrice($baseA3Sheets, $printType);
            $printingCost = $baseA3Sheets * $digitalPrice;
            $adjustment = 0;
        }

        // Стоимость бумаги
        $paperCost = $totalA3Sheets * $this->config->get("paper.$paperType");

        // Стоимость фальцовки
        $foldCost = 0;
        if ($foldType !== 'no') {
            $foldsCount = self::FOLD_TYPES[$foldType];
            $foldCost = $foldsCount * ($size === 'A3' ? 0.2 : 0.4) * $quantity;
        }

        // Стоимость ламинации
        $laminationCost = 0;
        if ($lamination && $laminationType) {
            $laminationPrices = $this->config->get('lamination.' . ($printMethod === PrintMethods::OFFSET ? 'offset' : 'digital'));
            $laminationColumn = ($printType === PrintTypes::DOUBLE) ? '1+1' : '1+0';
            
            if ($printMethod === PrintMethods::OFFSET) {
                $laminationCost = $quantity * $laminationPrices[$laminationColumn];
            } else {
                // Для цифровой печати учитываем плотность пленки
                foreach ($laminationPrices as $thickness => $prices) {
                    if ($paperType <= $thickness) {
                        $laminationCost = $quantity * $prices[$laminationColumn];
                        break;
                    }
                }
            }
        }

        // Базовая стоимость
        $totalPrice = $paperCost + $printingCost + $plateCost + $foldCost + $laminationCost;

        // Добавляем стоимость дополнительных услуг
        $additionalCosts = $this->calculateAdditionalCosts(
            $bigovka,
            $cornerRadius,
            false, // perforation
            $drill,
            false, // numbering
            $quantity
        );

        $totalPrice += $additionalCosts;

        // Формируем результат
        return [
            'printMethod' => $printMethod,
            'baseA3Sheets' => $baseA3Sheets,
            'adjustment' => $adjustment ?? 0,
            'totalA3Sheets' => $totalA3Sheets,
            'printingCost' => $printingCost,
            'plateCost' => $plateCost,
            'paperCost' => $paperCost,
            'foldCost' => $foldCost,
            'laminationCost' => $laminationCost,
            'additionalCosts' => $additionalCosts,
            'totalPrice' => $totalPrice,
            'pricePerUnit' => round($totalPrice / $quantity, 2),
            'breakdown' => [
                'Бумага' => $paperCost,
                'Печать' => $printingCost,
                'Пластины' => $plateCost,
                'Фальцовка' => $foldCost,
                'Ламинация' => $laminationCost,
                'Дополнительные услуги' => $additionalCosts
            ]
        ];
    }

    /**
     * Валидация входных параметров
     */
    public function validate(array $params): array
    {
        $errors = [];

        // Проверка обязательных параметров
        $requiredParams = ['paperType', 'size', 'quantity', 'printType'];
        foreach ($requiredParams as $param) {
            if (!isset($params[$param])) {
                $errors[] = "Не указан параметр $param";
            }
        }

        if (!empty($errors)) {
            return $errors;
        }

        // Проверка корректности значений
        if ($params['quantity'] <= 0) {
            $errors[] = "Количество должно быть больше 0";
        }

        $size = mb_convert_case($params['size'], MB_CASE_UPPER, "UTF-8");
        if (!isset($this->config->get('size_coefficients')[$size])) {
            $errors[] = "Неверный размер буклета";
        }

        if (!in_array($params['printType'], [PrintTypes::SINGLE, PrintTypes::DOUBLE])) {
            $errors[] = "Неверный тип печати";
        }

        // Проверка типа бумаги
        $paperType = $params['paperType'];
        if (!isset($this->config->get('paper')[$paperType])) {
            $errors[] = "Неверный тип бумаги";
        }

        // Проверка типа фальцовки
        if (isset($params['foldType']) && !isset(self::FOLD_TYPES[$params['foldType']])) {
            $errors[] = "Неверный тип фальцовки";
        }

        // Проверка ламинации
        if (isset($params['lamination']) && $params['lamination']) {
            if (!isset($params['laminationType'])) {
                $errors[] = "Не указан тип ламинации";
            }
        }

        // Проверка дополнительных параметров
        if (isset($params['cornerRadius']) && ($params['cornerRadius'] < 0 || $params['cornerRadius'] > 4)) {
            $errors[] = "Количество скругленных углов должно быть от 0 до 4";
        }

        return $errors;
    }
}
