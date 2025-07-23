<?php

namespace PrintCalc\Calculators;

use PrintCalc\Core\AbstractCalculator;
use PrintCalc\Core\Constants\PrintTypes;
use PrintCalc\Core\Constants\PrintMethods;
use PrintCalc\Core\PrintCalculationTrait;

class RizoCalculator extends AbstractCalculator
{
    use PrintCalculationTrait;

    // Порог перехода на офсетную печать для ризографии
    private const RIZO_OFFSET_THRESHOLD = 500;

    /**
     * Расчет стоимости печати на ризографе
     *
     * @param array $params Параметры расчета:
     *  - string paperType: Тип бумаги (плотность)
     *  - string size: Размер (A3, A4, A5, A6)
     *  - int quantity: Тираж
     *  - string printType: Тип печати (single для 1+0 или double для 1+1)
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
        $totalA3Sheets = $baseA3Sheets;

        // Определяем метод печати (ризография или офсетная)
        $printMethod = $baseA3Sheets >= self::RIZO_OFFSET_THRESHOLD ? PrintMethods::OFFSET : PrintMethods::RIZO;
        $printingCost = 0;
        $plateCost = 0;
        $adjustment = 0;

        if ($printMethod === PrintMethods::OFFSET) {
            // Для офсетной печати
            foreach ($this->config->get('offset_rizo_prices') as $range) {
                if ($baseA3Sheets >= $range['min'] && $baseA3Sheets <= $range['max']) {
                    $priceColumn = ($printType === PrintTypes::DOUBLE) ? '1+1' : '1+0';
                    $printingCost = $range[$priceColumn];
                    $adjustment = $range['adjustment'];
                    break;
                }
            }

            $totalA3Sheets += $adjustment;
            $plateCost = $this->config->get('plate_price');

        } else {
            // Для ризографии
            $rizoPrice = 0;
            $priceColumn = ($printType === PrintTypes::DOUBLE) ? '1+1' : '1+0';

            foreach ($this->config->get('rizo_prices') as $max => $prices) {
                if ($baseA3Sheets <= $max) {
                    $rizoPrice = $prices[$priceColumn];
                    break;
                }
            }

            if ($rizoPrice === 0) {
                $lastPrice = end($this->config->get('rizo_prices'));
                $rizoPrice = $lastPrice[$priceColumn];
            }

            $printingCost = $baseA3Sheets * $rizoPrice;
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
            'adjustment' => $adjustment,
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
            $errors[] = "Неверный размер";
        }

        if (!in_array($params['printType'], [PrintTypes::SINGLE, PrintTypes::DOUBLE])) {
            $errors[] = "Неверный тип печати для ризографии (должен быть 1+0 или 1+1)";
        }

        // Проверка типа бумаги
        $paperType = $params['paperType'];
        if (!isset($this->config->get('paper')[$paperType])) {
            $errors[] = "Неверный тип бумаги";
        }

        // Проверка для ризографии - ограничения по плотности бумаги
        if (is_numeric($paperType) && floatval($paperType) > 200) {
            $errors[] = "Для ризографии максимальная плотность бумаги 200 г/м²";
        }

        // Проверка дополнительных параметров
        if (isset($params['cornerRadius']) && ($params['cornerRadius'] < 0 || $params['cornerRadius'] > 4)) {
            $errors[] = "Количество скругленных углов должно быть от 0 до 4";
        }

        return $errors;
    }
}
