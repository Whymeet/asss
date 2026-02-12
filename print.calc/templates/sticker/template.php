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
            <strong>Наклейки:</strong> <?= $arResult['dimension_info'] ?? '' ?><br>
            <?= $arResult['area_info'] ?? '' ?><br>
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
            <small class="text-muted">Указывайте размеры в метрах (0.1 м = 10 см)</small>
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
            <small class="text-muted">Указывайте размеры в метрах (0.1 м = 10 см)</small>
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
            <small class="text-muted">Цена зависит от общей площади всех наклеек</small>
        </div>

        <!-- Тип наклейки -->
        <div class="form-group">
            <label class="form-label" for="stickerType">Тип наклейки:</label>
            <select name="stickerType" id="stickerType" class="form-control" required>
                <?php if (!empty($stickerTypes)): ?>
                    <?php foreach ($stickerTypes as $key => $name): ?>
                        <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($name) ?></option>
                    <?php endforeach; ?>
                <?php else: ?>
                    <option value="simple_print">Просто печать СМУК</option>
                    <option value="print_cut">Печать + контурная резка</option>
                    <option value="print_white">Печать смук + белый</option>
                    <option value="print_white_cut">Печать смук + белый + контурная резка</option>
                    <option value="print_white_varnish">Печать смук + белый + лак</option>
                    <option value="print_white_varnish_cut">Печать смук + белый + лак + контурная резка</option>
                    <option value="print_varnish">Печать смук+лак</option>
                    <option value="print_varnish_cut">Печать смук+лак+резка</option>
                <?php endif; ?>
            </select>
        </div>

        <input type="hidden" name="calcType" value="<?= $calcType ?>">
        <input type="hidden" name="sessid" value="<?= bitrix_sessid() ?>">

        <button id="calcBtn" type="button" class="calc-button">Рассчитать стоимость</button>

        <div id="calcResult" class="calc-result"></div>

        <!-- Отступ между результатом и ламинацией -->
        <div class="calc-spacer"></div>
    </form>

    <?php include dirname(__DIR__) . '/_shared/order-modal.php'; ?>
</div>

<style>
/* Специфичные стили для наклеек */
.dimension-input {
    position: relative;
}

.dimension-input::after {
    content: 'м';
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
    pointer-events: none;
}
</style>

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
    initializeDateTimeValidation();
});

// === УНИКАЛЬНАЯ ЛОГИКА НАКЛЕЕК ===

// Отображение результата наклеек
function displayStickerResult(result, resultDiv) {
    var totalPrice = Math.round((result.totalPrice || 0) * 10) / 10;

    var html = '<div class="result-success">';
    html += '<h3 class="result-title">Результат расчета наклеек</h3>';
    html += '<div class="result-price">' + totalPrice + ' <small>₽</small></div>';

    // Информация о типе наклейки
    if (result.stickerType) {
        var stickerTypeNames = {
            'simple_print': 'Просто печать СМУК',
            'print_cut': 'Печать + контурная резка',
            'print_white': 'Печать смук + белый',
            'print_white_cut': 'Печать смук + белый + контурная резка',
            'print_white_varnish': 'Печать смук + белый + лак',
            'print_white_varnish_cut': 'Печать смук + белый + лак + контурная резка',
            'print_varnish': 'Печать смук+лак',
            'print_varnish_cut': 'Печать смук+лак+резка'
        };

        var typeName = stickerTypeNames[result.stickerType] || result.stickerType;
    }

    // Добавляем кнопку заказа
    html += '<button type="button" class="order-button" onclick="openOrderModal()">Заказать печать</button>';

    html += '</div>';

    resultDiv.innerHTML = html;
}

// Открытие модалки с данными заказа наклеек
function openOrderModal() {
    var modal = document.getElementById('orderModal');
    var orderDataInput = document.getElementById('orderData');

    // Собираем данные расчета
    var form = document.getElementById(calcConfig.type + 'CalcForm');
    var formData = collectFormData(form);

    // Получаем результат расчета
    var resultDiv = document.getElementById('calcResult');
    var priceElement = resultDiv.querySelector('.result-price');
    var totalPrice = priceElement ? priceElement.textContent.replace(/[^\d.,]/g, '') : '0';

    // Формируем данные заказа для наклеек
    var orderData = {
        product: 'Наклейки',
        length: formData.length || 'Не указана',
        width: formData.width || 'Не указана',
        quantity: formData.quantity || 0,
        stickerType: formData.stickerType || 'simple_print',
        totalPrice: totalPrice,
        calcType: 'sticker'
    };

    orderDataInput.value = JSON.stringify(orderData);
    modal.style.display = 'block';
}
</script>