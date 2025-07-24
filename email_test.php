<?php
require $_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php';

$APPLICATION->SetTitle("Тест отправки email");

echo "<h1>Диагностика email системы Bitrix</h1>";

// Проверяем подключение модуля
if (!CModule::IncludeModule("main")) {
    echo "<div style='color: red;'>Ошибка: Модуль main не подключен</div>";
} else {
    echo "<div style='color: green;'>✓ Модуль main подключен</div>";
}

// Проверяем настройки почты
echo "<h2>Настройки почты:</h2>";

// Проверяем типы событий
echo "<h2>Проверка доступных типов событий:</h2>";

// Проверяем CALC_ORDER_REQUEST более правильно
$rsEventType = CEventType::GetList([], ['EVENT_NAME' => 'CALC_ORDER_REQUEST']);
if ($arEventType = $rsEventType->Fetch()) {
    echo "<div style='color: green;'>✓ Тип события CALC_ORDER_REQUEST найден</div>";
    echo "<p>ID: " . $arEventType['ID'] . "</p>";
    echo "<p>Название: " . htmlspecialchars($arEventType['NAME']) . "</p>";
    echo "<p>Описание: " . htmlspecialchars($arEventType['DESCRIPTION']) . "</p>";
    echo "<pre>";
    print_r($arEventType);
    echo "</pre>";
} else {
    echo "<div style='color: red;'>✗ Тип события CALC_ORDER_REQUEST НЕ найден</div>";
    
    // Попробуем найти все события с похожим названием
    $rsAllEvents = CEventType::GetList(['SORT' => 'ASC'], []);
    echo "<h3>Все доступные типы событий:</h3>";
    $eventCount = 0;
    while ($arEvent = $rsAllEvents->Fetch() && $eventCount < 10) {
        if (strpos($arEvent['EVENT_NAME'], 'CALC') !== false || 
            strpos($arEvent['EVENT_NAME'], 'ORDER') !== false ||
            strpos($arEvent['EVENT_NAME'], 'FEEDBACK') !== false) {
            echo "<p>📧 " . $arEvent['EVENT_NAME'] . " - " . htmlspecialchars($arEvent['NAME']) . "</p>";
        }
        $eventCount++;
    }
}

// Проверяем альтернативные события
$alternativeEvents = ['FEEDBACK', 'FORM_FILL_SIMPLE', 'FORM_FILL', 'MAIN_MAIL_CONFIRM_CODE'];
foreach ($alternativeEvents as $eventType) {
    $rsAltEvent = CEventType::GetList([], ['EVENT_NAME' => $eventType]);
    if ($arAltEvent = $rsAltEvent->Fetch()) {
        echo "<div style='color: blue;'>📧 Найдено альтернативное событие: $eventType</div>";
    }
}

echo "<p><strong>Рекомендация:</strong> Создайте тип события CALC_ORDER_REQUEST в админке или используйте FEEDBACK</p>";
echo "<ul>";
echo "<li>Идите в админку: Настройки → Почтовые события → Типы почтовых событий</li>";
echo "<li>Добавьте новый тип: CALC_ORDER_REQUEST</li>";
echo "<li>Название: Заказ из калькулятора печати</li>";
echo "<li>Описание: Уведомление о новом заказе из калькулятора</li>";
echo "</ul>";

// Проверяем почтовые шаблоны
echo "<h2>Почтовые шаблоны для CALC_ORDER_REQUEST:</h2>";
$rsEventMessage = CEventMessage::GetList($by="ID", $order="ASC", ["TYPE" => "CALC_ORDER_REQUEST"]);
$templatesFound = false;
while ($arEventMessage = $rsEventMessage->Fetch()) {
    $templatesFound = true;
    echo "<div style='color: green;'>✓ Найден шаблон ID: " . $arEventMessage['ID'] . "</div>";
    echo "<p><strong>Тема:</strong> " . htmlspecialchars($arEventMessage['SUBJECT']) . "</p>";
    echo "<p><strong>Email получателя:</strong> " . htmlspecialchars($arEventMessage['EMAIL_TO']) . "</p>";
    echo "<p><strong>Активен:</strong> " . ($arEventMessage['ACTIVE'] == 'Y' ? 'Да' : 'Нет') . "</p>";
    echo "<p><strong>От кого:</strong> " . htmlspecialchars($arEventMessage['EMAIL_FROM']) . "</p>";
    if (!empty($arEventMessage['BODY_TYPE']) && $arEventMessage['BODY_TYPE'] == 'html') {
        echo "<p><strong>Тип:</strong> HTML</p>";
    } else {
        echo "<p><strong>Тип:</strong> Текст</p>";
    }
    echo "<hr>";
}

if (!$templatesFound) {
    echo "<div style='color: red;'>✗ Почтовые шаблоны для CALC_ORDER_REQUEST НЕ найдены</div>";
    echo "<p><strong>⚠️ ВАЖНО:</strong> Нужно создать почтовый шаблон!</p>";
    echo "<ol>";
    echo "<li>Идите в админку: <strong>Настройки → Почтовые события → Почтовые шаблоны</strong></li>";
    echo "<li>Нажмите <strong>\"Добавить шаблон\"</strong></li>";
    echo "<li>Выберите тип события: <strong>CALC_ORDER_REQUEST</strong></li>";
    echo "<li>Укажите email получателя: <strong>matvey.turkin.97@mail.ru</strong></li>";
    echo "<li>Настройте тему и текст письма</li>";
    echo "</ol>";
} else {
    echo "<div style='color: green;'>✅ Шаблоны найдены! Это хорошо.</div>";
}

// Тестовая отправка
if ($_POST['test_send']) {
    echo "<h2>Результат тестовой отправки:</h2>";
    
    $arEventFields = [
        "ORDER_TEXT" => "Тестовое сообщение из диагностики",
        "CLIENT_NAME" => "Тестовый клиент",
        "CLIENT_PHONE" => "+7 999 123-45-67",
        "CLIENT_EMAIL" => "test@example.com",
        "PRODUCT_TYPE" => "БСО",
        "TOTAL_PRICE" => "1000",
        "ORDER_DATE" => date('d.m.Y H:i:s'),
        "MESSAGE" => "Тест отправки заказа из калькулятора"
    ];
    
    // Пробуем сначала CALC_ORDER_REQUEST
    $result1 = CEvent::Send("CALC_ORDER_REQUEST", SITE_ID, $arEventFields);
    if ($result1) {
        echo "<div style='color: green;'>✓ CALC_ORDER_REQUEST отправлено успешно</div>";
    } else {
        echo "<div style='color: red;'>✗ CALC_ORDER_REQUEST - ошибка отправки</div>";
        
        // Пробуем FEEDBACK как запасной вариант
        $result2 = CEvent::Send("FEEDBACK", SITE_ID, $arEventFields);
        if ($result2) {
            echo "<div style='color: orange;'>⚠️ FEEDBACK отправлено успешно (запасной вариант)</div>";
            echo "<p><strong>Рекомендация:</strong> Измените в коде CALC_ORDER_REQUEST на FEEDBACK</p>";
        } else {
            echo "<div style='color: red;'>✗ FEEDBACK тоже не работает</div>";
        }
    }
    
    // Проверяем логи
    $rsEventLog = CEventLog::GetList([], ["AUDIT_TYPE_ID" => "EMAIL_SEND"], [], false, ["ID", "TIMESTAMP_X", "DESCRIPTION", "AUDIT_TYPE_ID"]);
    echo "<h3>Последние записи лога email:</h3>";
    $logCount = 0;
    while ($arEventLog = $rsEventLog->Fetch() && $logCount < 5) {
        echo "<p><strong>" . $arEventLog['TIMESTAMP_X'] . ":</strong> " . htmlspecialchars($arEventLog['DESCRIPTION']) . "</p>";
        $logCount++;
    }
}

?>

<form method="post">
    <h2>Тестовая отправка</h2>
    <button type="submit" name="test_send" value="1" style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 4px;">
        Отправить тестовое письмо
    </button>
</form>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    h1, h2, h3 { color: #333; }
    div { margin: 10px 0; padding: 5px; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 4px; }
</style>

<?php
require $_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php';
?>
