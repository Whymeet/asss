<?php
require_once __DIR__ . '/print.calc/class.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = [
        'quantity' => $_POST['quantity'] ?? '',
        'paper_type' => $_POST['paper_type'] ?? '',
        'print_type' => $_POST['print_type'] ?? '',
    ];

    $calculator = new \App\Calculators\AvtovizCalculator();
    $result = $calculator->calculate($input);

    if ($result['success']) {
        echo "Стоимость заказа: " . number_format($result['cost'], 2) . " руб.\n";
        echo "Детали расчета:\n";
        echo "- Стоимость материалов: " . number_format($result['details']['materials_cost'], 2) . " руб.\n";
        echo "- Стоимость печати: " . number_format($result['details']['print_cost'], 2) . " руб.\n";
        echo "- Стоимость резки: " . number_format($result['details']['cutting_cost'], 2) . " руб.\n";
        echo "- Необходимо листов: " . $result['details']['sheets_needed'] . "\n";
        echo "- Цена за штуку: " . number_format($result['details']['price_per_item'], 2) . " руб.\n";
    } else {
        echo "Ошибка: " . implode(", ", $result['errors']);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Калькулятор автовизиток</title>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
    <div class="calculator-form">
        <h1>Расчет стоимости автовизиток</h1>
        <form method="post">
            <div class="form-group">
                <label for="quantity">Количество:</label>
                <input type="number" id="quantity" name="quantity" required min="1">
            </div>

            <div class="form-group">
                <label for="paper_type">Тип бумаги:</label>
                <select id="paper_type" name="paper_type" required>
                    <option value="Самоклейка">Самоклейка</option>
                </select>
            </div>

            <div class="form-group">
                <label for="print_type">Тип печати:</label>
                <select id="print_type" name="print_type" required>
                    <option value="4+0">4+0 (односторонняя)</option>
                    <option value="4+4">4+4 (двусторонняя)</option>
                </select>
            </div>

            <div class="form-group">
                <button type="submit">Рассчитать</button>
            </div>
        </form>
    </div>
</body>
</html>
