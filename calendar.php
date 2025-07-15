<?php
include 'header.php';
include 'print_calculator.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $type = $_POST["type"] ?? '';
    $size = $_POST["size"] ?? 'A4';
    $quantity = (int)($_POST["quantity"] ?? 0);
    $printType = $_POST["printType"] ?? '4+0';

    $result = calculateCalendarPrice($type, $size, $quantity, $printType);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Калькулятор календарей</title>
</head>
<body>
    <div class="content">
        <h1>Калькулятор календарей</h1>
        
        <form method="POST">
            <label>Тип календаря:
                <select name="type" id="typeSelect" required>
                    <option value="desktop">Настольный</option>
                    <option value="pocket">Карманный</option>
                    <option value="wall">Настенный перекидной</option>
                </select>
            </label><br>

            <div id="sizeField" style="display:none;">
                <label>Размер:
                    <select name="size">
                        <option value="A4">A4</option>
                        <option value="A3">A3</option>
                    </select>
                </label><br>
            </div>

            <label>Тираж: <input type="number" name="quantity" min="1" required></label><br>

            <div id="printTypeField">
                <label>Тип печати:
                    <select name="printType">
                        <option value="4+0">4+0</option>
                        <option value="4+4">4+4</option>
                    </select>
                </label><br>
            </div>

            <button type="submit">Рассчитать</button>
        </form>

        <?php if (isset($result)): ?>
            <?php if (isset($result['error'])): ?>
                <div class="error"><?= $result['error'] ?></div>
            <?php else: ?>
                <h2>Результаты расчета:</h2>
                <?php if ($type === 'wall'): ?>
                    <p>Стоимость печати: <?= number_format($result['printingCost'], 2) ?> ₽</p>
                    <p>Стоимость сборки: <?= number_format($result['assemblyCost'], 2) ?> ₽</p>
                <?php endif; ?>
                <p>Итого: <strong><?= number_format($result['totalPrice'], 2) ?> ₽</strong></p>
            <?php endif; ?>
        <?php endif; ?>

        <script>
            // Показываем/скрываем поля в зависимости от типа
            document.getElementById('typeSelect').addEventListener('change', function() {
                const type = this.value;
                document.getElementById('sizeField').style.display = 
                    (type === 'wall') ? 'block' : 'none';
                document.getElementById('printTypeField').style.display = 
                    (type === 'desktop') ? 'none' : 'block';
            });
        </script>
    </div>
</body>
</html>