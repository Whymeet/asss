<?php

namespace App\Calculators;

use App\Core\AbstractCalculator;
use App\Core\Constants\PrintConstants;
use App\Core\CalculatorInterface;

class AvtovizCalculator extends AbstractCalculator implements CalculatorInterface
{
    protected function validateInput(array $input): bool
    {
        // Проверяем наличие обязательных полей
        $requiredFields = ['quantity', 'paper_type', 'print_type'];
        foreach ($requiredFields as $field) {
            if (!isset($input[$field])) {
                $this->errors[] = "Поле {$field} обязательно для заполнения";
                return false;
            }
        }

        // Проверяем корректность количества
        if (!is_numeric($input['quantity']) || $input['quantity'] <= 0) {
            $this->errors[] = "Количество должно быть положительным числом";
            return false;
        }

        // Проверка типа печати
        $allowedPrintTypes = ['4+0', '4+4'];
        if (!in_array($input['print_type'], $allowedPrintTypes)) {
            $this->errors[] = "Недопустимый тип печати. Разрешены: " . implode(', ', $allowedPrintTypes);
            return false;
        }

        // Проверка типа бумаги
        $allowedPaperTypes = ['Самоклейка'];
        if (!in_array($input['paper_type'], $allowedPaperTypes)) {
            $this->errors[] = "Недопустимый тип бумаги для автовизиток. Разрешена только самоклейка.";
            return false;
        }

        return true;
    }

    public function calculate(array $input): array
    {
        if (!$this->validateInput($input)) {
            return ['success' => false, 'errors' => $this->errors];
        }

        $quantity = (int)$input['quantity'];
        $printType = $input['print_type'];
        
        // Определяем базовую стоимость печати
        $printCost = $this->calculatePrintCost($quantity, $printType);
        
        // Стоимость бумаги (самоклейка)
        $paperCost = $this->getPaperCost('Самоклейка');
        
        // Количество листов на тираж (на А3 помещается 24 визитки)
        $sheetsNeeded = ceil($quantity / 24);
        
        // Расчет стоимости материалов
        $materialsCost = $paperCost * $sheetsNeeded;
        
        // Расчет стоимости печати
        $totalPrintCost = $printCost * $sheetsNeeded;

        // Стоимость резки (0.3 руб за визитку)
        $cuttingCost = $quantity * 0.3;

        // Расчет полной стоимости
        $totalCost = $materialsCost + $totalPrintCost + $cuttingCost;

        return [
            'success' => true,
            'cost' => $totalCost,
            'details' => [
                'materials_cost' => $materialsCost,
                'print_cost' => $totalPrintCost,
                'cutting_cost' => $cuttingCost,
                'sheets_needed' => $sheetsNeeded,
                'price_per_item' => $totalCost / $quantity
            ]
        ];
    }

    private function calculatePrintCost(int $quantity, string $printType): float
    {
        // Определяем цену за лист в зависимости от тиража
        $pricePerSheet = 0;
        
        if ($quantity < 200) {
            // Используем цифровую печать
            $digitalPrices = $this->config['digital_prices'][min(array_keys($this->config['digital_prices']))];
            $pricePerSheet = $digitalPrices[$printType];
        } else {
            // Используем офсетную печать
            foreach ($this->config['offset_prices'] as $range) {
                if ($quantity >= $range['min'] && $quantity <= $range['max']) {
                    $pricePerSheet = $range[$printType] / 100; // Переводим в цену за лист
                    break;
                }
            }
        }

        return $pricePerSheet;
    }

    private function getPaperCost(string $paperType): float
    {
        return $this->config['paper'][$paperType];
    }
}
