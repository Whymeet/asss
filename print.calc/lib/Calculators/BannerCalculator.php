<?php

namespace App\Calculators;

use App\Core\AbstractCalculator;
use App\Core\Constants\PrintConstants;
use App\Core\CalculatorInterface;

class BannerCalculator extends AbstractCalculator implements CalculatorInterface
{
    // Стоимость баннерной ткани за м²
    private const BANNER_MATERIAL_PRICES = [
        'Banner440' => 250,   // Баннер 440 гр/м²
        'Banner510' => 300,   // Баннер 510 гр/м²
        'BannerLit' => 400    // Литой баннер
    ];

    // Стоимость постпечатной обработки
    private const POST_PRINT_PRICES = [
        'luvers' => 15,       // Цена за люверс
        'pocket' => 100,      // Цена за погонный метр кармана
        'proklei' => 100      // Цена за погонный метр проклейки
    ];

    protected function validateInput(array $input): bool
    {
        // Проверяем наличие обязательных полей
        $requiredFields = ['width', 'height', 'material', 'quantity'];
        foreach ($requiredFields as $field) {
            if (!isset($input[$field])) {
                $this->errors[] = "Поле {$field} обязательно для заполнения";
                return false;
            }
        }

        // Проверяем размеры
        if (!is_numeric($input['width']) || $input['width'] <= 0 || 
            !is_numeric($input['height']) || $input['height'] <= 0) {
            $this->errors[] = "Размеры должны быть положительными числами";
            return false;
        }

        // Проверяем количество
        if (!is_numeric($input['quantity']) || $input['quantity'] <= 0) {
            $this->errors[] = "Количество должно быть положительным числом";
            return false;
        }

        // Проверяем материал
        if (!array_key_exists($input['material'], self::BANNER_MATERIAL_PRICES)) {
            $this->errors[] = "Неверно указан материал баннера";
            return false;
        }

        // Проверяем постпечатную обработку если указана
        if (isset($input['post_print'])) {
            if (isset($input['post_print']['luvers']) && !is_numeric($input['post_print']['luvers'])) {
                $this->errors[] = "Количество люверсов должно быть числом";
                return false;
            }
            if (isset($input['post_print']['pocket']) && !is_numeric($input['post_print']['pocket'])) {
                $this->errors[] = "Длина кармана должна быть числом";
                return false;
            }
            if (isset($input['post_print']['proklei']) && !is_numeric($input['post_print']['proklei'])) {
                $this->errors[] = "Длина проклейки должна быть числом";
                return false;
            }
        }

        return true;
    }

    public function calculate(array $input): array
    {
        if (!$this->validateInput($input)) {
            return ['success' => false, 'errors' => $this->errors];
        }

        $width = (float)$input['width'];
        $height = (float)$input['height'];
        $quantity = (int)$input['quantity'];
        $material = $input['material'];

        // Расчет площади в м²
        $area = ($width * $height) / 10000; // переводим из см² в м²

        // Стоимость материала
        $materialCost = $area * self::BANNER_MATERIAL_PRICES[$material];

        // Стоимость печати (базовая цена за м²)
        $printCost = $this->calculatePrintCost($area);

        // Расчет постпечатной обработки
        $postPrintCost = 0;
        if (isset($input['post_print'])) {
            if (isset($input['post_print']['luvers'])) {
                $postPrintCost += $input['post_print']['luvers'] * self::POST_PRINT_PRICES['luvers'];
            }
            if (isset($input['post_print']['pocket'])) {
                $postPrintCost += $input['post_print']['pocket'] * self::POST_PRINT_PRICES['pocket'];
            }
            if (isset($input['post_print']['proklei'])) {
                $postPrintCost += $input['post_print']['proklei'] * self::POST_PRINT_PRICES['proklei'];
            }
        }

        // Расчет общей стоимости
        $totalCostPerItem = $materialCost + $printCost + $postPrintCost;
        $totalCost = $totalCostPerItem * $quantity;

        return [
            'success' => true,
            'cost' => $totalCost,
            'details' => [
                'area' => $area,
                'material_cost' => $materialCost * $quantity,
                'print_cost' => $printCost * $quantity,
                'post_print_cost' => $postPrintCost * $quantity,
                'price_per_item' => $totalCost / $quantity,
                'total_area' => $area * $quantity
            ]
        ];
    }

    private function calculatePrintCost(float $area): float
    {
        // Базовая стоимость печати за м²
        $basePrintCost = 400;

        // Уменьшаем стоимость печати для больших площадей
        if ($area > 10) {
            $basePrintCost = 350;
        }
        if ($area > 50) {
            $basePrintCost = 300;
        }

        return $area * $basePrintCost;
    }
}
