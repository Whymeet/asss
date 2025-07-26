<?php
// Тест HTML-письма с ламинацией и толщиной

// Симулируем данные заказа с ламинацией и толщиной
$orderInfo = [
    'calcType' => 'list',
    'product' => 'Листовки',
    'size' => 'A4',
    'paperType' => '80 г/м²',
    'printType' => '4+4',
    'quantity' => 1000,
    'laminationType' => '1+1',
    'laminationThickness' => '125',
    'totalPrice' => 15000,
    'additionalServices' => 'Биговка, Скругление углов'
];

$name = 'Тестовый Клиент';
$phone = '+7 (999) 123-45-67';
$email = 'test@example.com';
$callTime = '25.07.2025 14:30';

// Воспроизводим логику из formatListOrderHTML
$html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Новый заказ листовок</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #28a745, #20c997); color: white; padding: 20px; border-radius: 8px 8px 0 0; text-align: center; }
        .content { background: #f8f9fa; padding: 20px; border: 1px solid #dee2e6; }
        .footer { background: #6c757d; color: white; padding: 15px; border-radius: 0 0 8px 8px; text-align: center; font-size: 14px; }
        .section { margin-bottom: 20px; }
        .section h3 { color: #28a745; margin-bottom: 10px; border-bottom: 2px solid #28a745; padding-bottom: 5px; }
        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { padding: 8px 12px; border-bottom: 1px solid #dee2e6; }
        .info-table td:first-child { font-weight: bold; background: #e8f5e8; width: 40%; }
        .price { font-size: 24px; font-weight: bold; color: #28a745; text-align: center; margin: 20px 0; }
        .client-info { background: white; padding: 15px; border-radius: 6px; border-left: 4px solid #28a745; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Новый заказ листовок</h1>
            <p>Заказ с калькулятора печати</p>
        </div>
        
        <div class="content">
            <div class="section">
                <h3>Информация о заказе</h3>
                <table class="info-table">
                    <tr><td>Продукт</td><td>' . htmlspecialchars($orderInfo['product'] ?? 'Листовки') . '</td></tr>
                    <tr><td>Формат</td><td>' . htmlspecialchars($orderInfo['size'] ?? 'Не указан') . '</td></tr>
                    <tr><td>Тип бумаги</td><td>' . htmlspecialchars($orderInfo['paperType'] ?? 'Не указан') . '</td></tr>
                    <tr><td>Тип печати</td><td>' . htmlspecialchars($orderInfo['printType'] ?? 'Не указан') . '</td></tr>
                    <tr><td>Тираж</td><td>' . number_format($orderInfo['quantity'] ?? 0, 0, ',', ' ') . ' шт.</td></tr>';

// Добавляем информацию о ламинации если есть
if (!empty($orderInfo['laminationType'])) {
    $laminationText = $orderInfo['laminationType'];
    
    // Преобразуем коды ламинации в понятные названия
    $laminationTypes = [
        '1+0' => 'Односторонняя',
        '1+1' => 'Двусторонняя'
    ];
    
    if (isset($laminationTypes[$laminationText])) {
        $laminationText = $laminationTypes[$laminationText];
    }
    
    // Добавляем толщину если указана
    if (!empty($orderInfo['laminationThickness'])) {
        $laminationText .= ' (' . $orderInfo['laminationThickness'] . ' мкм)';
    }
    
    $html .= '<tr><td>Ламинация</td><td>' . htmlspecialchars($laminationText) . '</td></tr>';
}

// Добавляем дополнительные услуги если есть
if (!empty($orderInfo['additionalServices'])) {
    $html .= '<tr><td>Дополнительные услуги</td><td>' . htmlspecialchars($orderInfo['additionalServices']) . '</td></tr>';
}

$html .= '</table>
            <div class="price">Итого: ' . number_format($orderInfo['totalPrice'] ?? 0, 0, ',', ' ') . ' руб.</div>
        </div>
        
        <div class="section">
            <h3>Информация о клиенте</h3>
            <div class="client-info">
                <p><strong>Имя:</strong> ' . htmlspecialchars($name) . '</p>
                <p><strong>Телефон:</strong> <a href="tel:' . htmlspecialchars($phone) . '">' . htmlspecialchars($phone) . '</a></p>';

if (!empty($email)) {
    $html .= '<p><strong>E-mail:</strong> <a href="mailto:' . htmlspecialchars($email) . '">' . htmlspecialchars($email) . '</a></p>';
}

if (!empty($callTime)) {
    $html .= '<p><strong>Удобное время для звонка:</strong> ' . htmlspecialchars($callTime) . '</p>';
}

$html .= '<p><strong>Дата заказа:</strong> ' . date('d.m.Y H:i:s') . '</p>
            </div>
        </div>
    </div>
    
    <div class="footer">
        <p>Заказ получен через калькулятор печати на сайте</p>
        <p>Время получения: ' . date('d.m.Y H:i:s') . '</p>
    </div>
</body>
</html>';

echo $html;
?>
