<?php
require_once __DIR__ . '/lib/Core/PriceConfig.php';
require_once __DIR__ . '/lib/Core/CalculatorInterface.php';
require_once __DIR__ . '/lib/Core/Constants/PrintConstants.php';
require_once __DIR__ . '/lib/Core/PrintCalculationTrait.php';
require_once __DIR__ . '/lib/Core/AbstractCalculator.php';
require_once __DIR__ . '/lib/Calculators/RizoCalculator.php';

use PrintCalc\Core\PriceConfig;
use PrintCalc\Core\Constants\PrintTypes;
use PrintCalc\Calculators\RizoCalculator;

// Создаем конфигурацию
$config = new PriceConfig();

// Создаем калькулятор
$calculator = new RizoCalculator($config);

// Параметры для расчета
$params = [
    'paperType' => '80.0',         // Плотность бумаги (для ризо лучше легкая бумага)
    'size' => 'A4',                // Размер
    'quantity' => 400,             // Тираж
    'printType' => PrintTypes::SINGLE, // Односторонняя печать
    'bigovka' => false,            // Без биговки
    'cornerRadius' => 0,           // Без скругления углов
    'perforation' => false,        // Без перфорации
    'drill' => false,              // Без сверления
    'numbering' => false           // Без нумерации
];

// Получаем расчет
$result = $calculator->calculate($params);

// Выводим результат
echo "=== Результат расчета ризографии ===\n";
echo "Метод печати: {$result['printMethod']}\n";
echo "Количество листов A3: {$result['totalA3Sheets']}\n";
if ($result['adjustment'] > 0) {
    echo "Приладка: {$result['adjustment']} листов\n";
}
echo "\n=== Разбивка по стоимости ===\n";
foreach ($result['breakdown'] as $name => $cost) {
    echo "$name: " . number_format($cost, 2) . " руб.\n";
}

echo "\nОбщая стоимость: " . number_format($result['totalPrice'], 2) . " руб.\n";
echo "Цена за штуку: " . number_format($result['pricePerUnit'], 2) . " руб.\n";
