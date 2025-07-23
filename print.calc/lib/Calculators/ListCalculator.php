<?php

namespace PrintCalc\Calculators;

use PrintCalc\Core\AbstractCalculator;
use PrintCalc\Core\Constants\PrintTypes;
use PrintCalc\Core\Constants\PrintMethods;
use PrintCalc\Core\PrintCalculationTrait;

class ListCalculator extends AbstractCalculator
{
    use PrintCalculationTrait;

    /**
     * Расчет стоимости листовок
     *
     * @param array $params Параметры расчета:
     *  - string paperType: Тип бумаги (плотность или название)
     *  - string size: Размер (A3, A4, A5, A6 и т.д.)
     *  - int quantity: Тираж
     *  - string printType: Тип печати (single для 4+0 или double для 4+4)
     *  - bool bigovka: Нужна ли биговка
     *  - int cornerRadius: Количество скругленных углов (0-4)
     *  - bool perforation: Нужна ли перфорация
     *  - bool drill: Нужно ли сверление
     *  - bool numbering: Нужна ли нумерация
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
        
        // Дополнительные услуги
        $bigovka = $params['bigovka'] ?? false;
        $cornerRadius = $params['cornerRadius'] ?? 0;
        $perforation = $params['perforation'] ?? false;
        $drill = $params['drill'] ?? false;
        $numbering = $params['numbering'] ?? false;

        // Расчет базовых значений
        $sizeCoefficient = $this->config->get("size_coefficients.$size");
        $baseA3Sheets = ceil($quantity / $sizeCoefficient);
        
        // Определяем метод печати
        $printMethod = $this->determinePrintMethod($baseA3Sheets);
        $totalA3Sheets = $baseA3Sheets;
        $printingCost = 0;
        $plateCost = 0;

        // Расчет в зависимости от метода печати
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
        
        // Базовая стоимость
        $totalPrice = $paperCost + $printingCost + $plateCost;

        // Добавляем стоимость дополнительных услуг
        $additionalCosts = $this->calculateAdditionalCosts(
            $bigovka,
            $cornerRadius,
            $perforation,
            $drill,
            $numbering,
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
            'additionalCosts' => $additionalCosts,
            'totalPrice' => $totalPrice,
            'pricePerUnit' => round($totalPrice / $quantity, 2),
            'breakdown' => [
                'Бумага' => $paperCost,
                'Печать' => $printingCost,
                'Пластины' => $plateCost,
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
            $errors[] = "Неверный размер листовки";
        }

        if (!in_array($params['printType'], [PrintTypes::SINGLE, PrintTypes::DOUBLE])) {
            $errors[] = "Неверный тип печати";
        }

        // Проверка типа бумаги
        $paperType = $params['paperType'];
        if (!isset($this->config->get('paper')[$paperType])) {
            $errors[] = "Неверный тип бумаги";
        }

        // Проверка дополнительных параметров
        if (isset($params['cornerRadius']) && ($params['cornerRadius'] < 0 || $params['cornerRadius'] > 4)) {
            $errors[] = "Количество скругленных углов должно быть от 0 до 4";
        }

        return $errors;
    }
}
