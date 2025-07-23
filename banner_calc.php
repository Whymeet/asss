<?php
require_once __DIR__ . '/print.calc/class.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = [
        'width' => $_POST['width'] ?? '',
        'height' => $_POST['height'] ?? '',
        'material' => $_POST['material'] ?? '',
        'quantity' => $_POST['quantity'] ?? '',
        'post_print' => [
            'luvers' => $_POST['luvers'] ?? 0,
            'pocket' => $_POST['pocket'] ?? 0,
            'proklei' => $_POST['proklei'] ?? 0
        ]
    ];

    $calculator = new \App\Calculators\BannerCalculator();
    $result = $calculator->calculate($input);

    if ($result['success']) {
        echo "Стоимость заказа: " . number_format($result['cost'], 2) . " руб.\n";
        echo "\nДетали расчета:\n";
        echo "- Общая площадь: " . number_format($result['details']['total_area'], 2) . " м²\n";
        echo "- Стоимость материала: " . number_format($result['details']['material_cost'], 2) . " руб.\n";
        echo "- Стоимость печати: " . number_format($result['details']['print_cost'], 2) . " руб.\n";
        echo "- Стоимость постпечатной обработки: " . number_format($result['details']['post_print_cost'], 2) . " руб.\n";
        echo "- Цена за штуку: " . number_format($result['details']['price_per_item'], 2) . " руб.\n";
    } else {
        echo "Ошибка: " . implode(", ", $result['errors']);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Калькулятор баннеров</title>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
    <div class="calculator-form">
        <h1>Расчет стоимости баннеров</h1>
        <form method="post">
            <div class="form-group">
                <label for="width">Ширина (см):</label>
                <input type="number" step="0.1" id="width" name="width" required min="1">
            </div>

            <div class="form-group">
                <label for="height">Высота (см):</label>
                <input type="number" step="0.1" id="height" name="height" required min="1">
            </div>

            <div class="form-group">
                <label for="material">Материал:</label>
                <select id="material" name="material" required>
                    <option value="Banner440">Баннер 440 гр/м²</option>
                    <option value="Banner510">Баннер 510 гр/м²</option>
                    <option value="BannerLit">Литой баннер</option>
                </select>
            </div>

            <div class="form-group">
                <label for="quantity">Количество:</label>
                <input type="number" id="quantity" name="quantity" required min="1">
            </div>

            <div class="form-group">
                <h3>Постпечатная обработка:</h3>
                
                <label for="luvers">Количество люверсов:</label>
                <input type="number" id="luvers" name="luvers" min="0" value="0">
                
                <label for="pocket">Длина кармана (м):</label>
                <input type="number" step="0.1" id="pocket" name="pocket" min="0" value="0">
                
                <label for="proklei">Длина проклейки (м):</label>
                <input type="number" step="0.1" id="proklei" name="proklei" min="0" value="0">
            </div>

            <div class="form-group">
                <button type="submit">Рассчитать</button>
            </div>
        </form>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Автоматический расчет рекомендуемого количества люверсов
        function calculateLuvers() {
            const width = parseFloat(document.getElementById('width').value) || 0;
            const height = parseFloat(document.getElementById('height').value) || 0;
            
            // Расчет периметра в метрах
            const perimeter = ((width + height) * 2) / 100;
            
            // Рекомендуемое расстояние между люверсами - 40 см
            const recommendedLuvers = Math.ceil(perimeter / 0.4);
            
            document.getElementById('luvers').value = recommendedLuvers;
        }

        // Добавляем слушатели событий
        document.getElementById('width').addEventListener('change', calculateLuvers);
        document.getElementById('height').addEventListener('change', calculateLuvers);
    });
    </script>
</body>
</html>
