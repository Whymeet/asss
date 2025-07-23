<?php
require_once __DIR__ . '/lib/Core/PriceConfig.php';
require_once __DIR__ . '/lib/Core/CalculatorInterface.php';
require_once __DIR__ . '/lib/Core/Constants/PrintConstants.php';
require_once __DIR__ . '/lib/Core/PrintCalculationTrait.php';
require_once __DIR__ . '/lib/Core/AbstractCalculator.php';
require_once __DIR__ . '/lib/Calculators/BookletCalculator.php';

use PrintCalc\Core\PriceConfig;
use PrintCalc\Core\Constants\PrintTypes;
use PrintCalc\Calculators\BookletCalculator;

// Создаем конфигурацию
$config = new PriceConfig();

// Создаем калькулятор
$calculator = new BookletCalculator($config);

// Параметры для расчета
$params = [
    'paperType' => '130.0',        // Плотность бумаги
    'size' => 'A4',                // Размер
    'quantity' => 1000,            // Тираж
    'printType' => PrintTypes::DOUBLE, // Двусторонняя печать
    'foldType' => 'double',        // Два сгиба
    'lamination' => true,          // С ламинацией
    'laminationType' => 'gloss',   // Глянцевая ламинация
    'bigovka' => true,             // С биговкой
    'cornerRadius' => 0,           // Без скругления углов
    'drill' => false              // Без сверления
];

// Получаем расчет
$result = $calculator->calculate($params);

// Выводим результат
echo "=== Результат расчета буклетов ===\n";
echo "Метод печати: {$result['printMethod']}\n";
echo "Количество листов A3: {$result['totalA3Sheets']}\n";
echo "Приладка: {$result['adjustment']} листов\n\n";

echo "=== Разбивка по стоимости ===\n";
foreach ($result['breakdown'] as $name => $cost) {
    echo "$name: " . number_format($cost, 2) . " руб.\n";
}

echo "\nОбщая стоимость: " . number_format($result['totalPrice'], 2) . " руб.\n";
echo "Цена за штуку: " . number_format($result['pricePerUnit'], 2) . " руб.\n";
