<?php
/** Шаблон калькулятора дорхендеров */
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

// Подключаем общие стили и скрипты
$this->addExternalCss($templateFolder.'/../.default/style.css');
if (file_exists($templateFolder.'/style.css')) {
    $this->addExternalCss($templateFolder.'/style.css');
}
$this->addExternalJs($templateFolder.'/../_shared/shared.js');

// Проверяем, что конфигурация загружена
if (!$arResult['CONFIG_LOADED']) {
    echo '<div class="result-error">Ошибка: Конфигурация калькулятора не загружена</div>';
    return;
}

// Принудительно подключаем основные скрипты Битрикса
CJSCore::Init(['ajax', 'window']);

$calcType = $arResult['CALC_TYPE'];
$features = $arResult['FEATURES'] ?? [];
$itemsPerSheet = $arResult['items_per_sheet'] ?? 6;
?>

<div class="calc-container">
    <!-- Информационный блок -->
    <div class="calc-disclaimer">
        <p>
            Данные, полученные при расчете на калькуляторе – являются ориентировочными в связи с регулярным изменением стоимости материалов.<br>
            Конечную стоимость заказа уточняйте у менеджера: <a href="tel:+78462060068">+7 (846) 206-00-68</a><br>
            Спасибо за понимание!
        </p>
    </div>

    <h2><?= $arResult['DESCRIPTION'] ?? 'Калькулятор дорхендеров (6 шт/А3)' ?></h2>

    <form id="<?= $calcType ?>CalcForm" class="calc-form">

        <?php if (!empty($arResult['PAPER_TYPES'])): ?>
        <!-- Тип бумаги -->
        <div class="form-group">
            <label class="form-label" for="paperType">Тип бумаги:</label>
            <select name="paperType" id="paperType" class="form-control" required>
                <?php foreach ($arResult['PAPER_TYPES'] as $paper): ?>
                    <option value="<?= htmlspecialchars($paper['ID']) ?>"><?= htmlspecialchars($paper['NAME']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>

        <!-- Количество листов А3 -->
        <div class="form-group">
            <label class="form-label" for="sheetsInput">Количество листов А3:</label>
            <input id="sheetsInput"
                   type="number"
                   class="form-control"
                   min="1"
                   value="1"
                   placeholder="Введите количество листов"
                   required>
            <span id="itemsHint" class="text-muted">= <?= $itemsPerSheet ?> штук</span>
            <input type="hidden" name="quantity" id="quantity">
        </div>

        <!-- Тип печати -->
        <div class="form-group">
            <label class="form-label">Тип печати:</label>
            <div class="radio-group">
                <label class="radio-label">
                    <input type="radio" name="printType" value="single" checked>
                    Односторонняя
                </label>
                <label class="radio-label">
                    <input type="radio" name="printType" value="double">
                    Двусторонняя
                </label>
            </div>
        </div>

        <input type="hidden" name="calcType" value="<?= $calcType ?>">
        <input type="hidden" name="sessid" value="<?= bitrix_sessid() ?>">

        <button id="calcBtn" type="button" class="calc-button">Рассчитать стоимость</button>

        <div id="calcResult" class="calc-result"></div>
        <div class="calc-spacer"></div>
    </form>

    <?php include dirname(__DIR__) . '/_shared/order-modal.php'; ?>
</div>

<script>
// Конфигурация калькулятора
var calcConfig = {
    type: '<?= $calcType ?>',
    features: <?= json_encode($features) ?>,
    component: 'my:print.calc',
    itemsPerSheet: <?= $itemsPerSheet ?>
};

// Hook для отображения результата (вызывается из shared.js)
window.displayResult = function(data, resultDiv) {
    displayDoorhangerResult(data, resultDiv);
};

// Пересчёт штук при вводе листов
function updateItemsHint() {
    var sheets = parseInt(document.getElementById('sheetsInput').value) || 0;
    var items = sheets * calcConfig.itemsPerSheet;
    document.getElementById('itemsHint').textContent = '= ' + items + ' штук';
    document.getElementById('quantity').value = items;
}

// Инициализация
document.addEventListener('DOMContentLoaded', function() {
    initCalculator('Выполняется расчет дорхендеров...');
    initOrderModal();
    var __m = document.getElementById("orderModal"); if (__m && __m.parentElement !== document.body) document.body.appendChild(__m);
    initializeDateTimeValidation();

    var sheetsInput = document.getElementById('sheetsInput');
    sheetsInput.addEventListener('input', updateItemsHint);
    updateItemsHint();
});

// === УНИКАЛЬНАЯ ЛОГИКА ДОРХЕНДЕРОВ ===

// Отображение результата
function displayDoorhangerResult(result, resultDiv) {
    var totalPrice = formatPrice(result.totalPrice);

    var html = '<div class="result-success">';
    html += '<h3 class="result-title">Результат расчета</h3>';
    html += '<div class="result-price">' + totalPrice + ' <small>₽</small></div>';

    // Кнопка заказа
    html += '<button type="button" class="order-button" onclick="openOrderModal()">Заказать дорхендеры</button>';
    html += '</div>';

    resultDiv.innerHTML = html;
}

// Открытие модалки с данными заказа дорхендеров
function openOrderModal() {
    var modal = document.getElementById('orderModal');
    var orderDataInput = document.getElementById('orderData');

    var form = document.getElementById(calcConfig.type + 'CalcForm');
    var formData = collectFormData(form);

    var resultDiv = document.getElementById('calcResult');
    var priceElement = resultDiv.querySelector('.result-price');
    var totalPrice = priceElement ? priceElement.textContent.replace(/[^\d.,]/g, '') : '0';

    var sheets = parseInt(document.getElementById('sheetsInput').value) || 0;

    // Данные заказа дорхендеров
    var orderData = {
        calcType: 'doorhanger',
        product: 'Дорхендеры',
        quantity: formData.quantity || 0,
        sheetsA3: sheets,
        paperType: formData.paperType || 'Не указан',
        printType: formData.printType === 'single' ? 'Односторонняя' : 'Двусторонняя',
        totalPrice: totalPrice
    };

    orderDataInput.value = JSON.stringify(orderData);
    modal.style.display = 'block';
}
</script>
