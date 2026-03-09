<?php
/** Шаблон калькулятора наклеек */
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
$stickerTypes = $arResult['sticker_types'] ?? [];
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

    <h2><?= $arResult['DESCRIPTION'] ?? 'Калькулятор печати наклеек' ?></h2>

    <form id="<?= $calcType ?>CalcForm" class="calc-form">

        <!-- Размеры наклейки -->
        <div class="form-group">
            <label class="form-label" for="length">Длина наклейки (м):</label>
            <input name="length"
                   id="length"
                   type="number"
                   class="form-control"
                   min="0.001"
                   step="0.001"
                   value="0.1"
                   placeholder="Например: 0.1 (= 10 см)"
                   required>
        </div>

        <div class="form-group">
            <label class="form-label" for="width">Ширина наклейки (м):</label>
            <input name="width"
                   id="width"
                   type="number"
                   class="form-control"
                   min="0.001"
                   step="0.001"
                   value="0.1"
                   placeholder="Например: 0.1 (= 10 см)"
                   required>
        </div>

        <!-- Тираж -->
        <div class="form-group">
            <label class="form-label" for="quantity">Тираж:</label>
            <input name="quantity"
                   id="quantity"
                   type="number"
                   class="form-control"
                   min="<?= $arResult['MIN_QUANTITY'] ?? 1 ?>"
                   max="<?= $arResult['MAX_QUANTITY'] ?? '' ?>"
                   value="<?= $arResult['DEFAULT_QUANTITY'] ?? 100 ?>"
                   placeholder="Введите количество"
                   required>
        </div>

        <!-- Тип наклейки -->
        <div class="form-group">
            <label class="form-label" for="stickerType">Тип наклейки:</label>
            <select name="stickerType" id="stickerType" class="form-control" required>
                <?php foreach ($stickerTypes as $key => $name): ?>
                    <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($name) ?></option>
                <?php endforeach; ?>
            </select>
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
    component: 'my:print.calc'
};

// Hook для отображения результата (вызывается из shared.js)
window.displayResult = function(data, resultDiv) {
    displayStickerResult(data, resultDiv);
};

// Инициализация
document.addEventListener('DOMContentLoaded', function() {
    initCalculator('Выполняется расчет наклеек...');
    initOrderModal();
    var __m = document.getElementById("orderModal"); if (__m && __m.parentElement !== document.body) document.body.appendChild(__m);
    initializeDateTimeValidation();
});

// === УНИКАЛЬНАЯ ЛОГИКА НАКЛЕЕК ===

// Отображение результата
function displayStickerResult(result, resultDiv) {
    var totalPrice = formatPrice(result.totalPrice);

    var html = '<div class="result-success">';
    html += '<h3 class="result-title">Результат расчета</h3>';
    html += '<div class="result-price">' + totalPrice + ' <small>₽</small></div>';

    // Кнопка заказа
    html += '<button type="button" class="order-button" onclick="openOrderModal()">Заказать наклейки</button>';
    html += '</div>';

    resultDiv.innerHTML = html;
}

// Открытие модалки с данными заказа наклеек
function openOrderModal() {
    var modal = document.getElementById('orderModal');
    var orderDataInput = document.getElementById('orderData');

    var form = document.getElementById(calcConfig.type + 'CalcForm');
    var formData = collectFormData(form);

    var resultDiv = document.getElementById('calcResult');
    var priceElement = resultDiv.querySelector('.result-price');
    var totalPrice = priceElement ? priceElement.textContent.replace(/[^\d.,]/g, '') : '0';

    var stickerSelect = document.getElementById('stickerType');
    var stickerTypeName = stickerSelect ? stickerSelect.options[stickerSelect.selectedIndex].text : '';

    // Данные заказа наклеек
    var orderData = {
        calcType: 'sticker',
        product: 'Наклейки',
        quantity: formData.quantity || 0,
        length: formData.length || 0,
        width: formData.width || 0,
        stickerType: stickerTypeName,
        totalPrice: totalPrice
    };

    orderDataInput.value = JSON.stringify(orderData);
    modal.style.display = 'block';
}
</script>
