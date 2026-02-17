<?php
/** Шаблон калькулятора конвертов */
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
?>

<div class="calc-container">
    <!-- Информационный блок -->
    <div class="calc-disclaimer">
        <p>
            Данные, полученные при расчете на калькуляторе – являются ориентировочными в связи с регулярным изменением стоимости материалов.<br>
            Конечную стоимость заказа уточняйте у менеджера: <a href="tel:+78462060068">+7 (846) 206-00-68</a><br>
            <strong>Конверты:</strong> <?= $arResult['pricing_info'] ?? 'Цена зависит от формата и тиража' ?><br>
            <?= $arResult['format_info'] ?? 'Доступны стандартные форматы конвертов' ?><br>
            Спасибо за понимание!
        </p>
    </div>

    <h2><?= $arResult['DESCRIPTION'] ?? 'Калькулятор конвертов' ?></h2>

    <form id="<?= $calcType ?>CalcForm" class="calc-form">

        <!-- Формат конверта -->
        <div class="form-group">
            <label class="form-label" for="format">Формат конверта:</label>
            <select name="format" id="format" class="form-control" required>
                <?php if (!empty($arResult['available_formats'])): ?>
                    <?php foreach ($arResult['available_formats'] as $key => $name): ?>
                        <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($name) ?></option>
                    <?php endforeach; ?>
                <?php else: ?>
                    <option value="Евро">Евро</option>
                    <option value="A5">A5</option>
                    <option value="A4">A4</option>
                <?php endif; ?>
            </select>
        </div>

        <!-- Тираж -->
        <div class="form-group">
            <label class="form-label" for="quantity">Тираж (шт):</label>
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

        <input type="hidden" name="calcType" value="<?= $calcType ?>">
        <input type="hidden" name="sessid" value="<?= bitrix_sessid() ?>">

        <button id="calcBtn" type="button" class="calc-button">Рассчитать стоимость</button>

        <div id="calcResult" class="calc-result"></div>

        <div class="calc-spacer"></div>
    </form>

    <?php include dirname(__DIR__) . '/_shared/order-modal.php'; ?>
</div>

<style>
/* Специфичные стили для конвертов */
.format-info {
    background: #e3f2fd;
    border: 1px solid #2196f3;
    border-radius: 6px;
    padding: 15px;
    margin: 15px 0;
    color: #1565c0;
}

.envelope-breakdown {
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 20px;
    margin: 15px 0;
}

.envelope-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #f5f5f5;
}

.envelope-item:last-child {
    border-bottom: none;
    font-weight: bold;
    font-size: 16px;
    color: #2e7d32;
    margin-top: 10px;
    padding-top: 15px;
    border-top: 2px solid #4caf50;
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
    displayEnvelopeResult(data, resultDiv);
};

// Инициализация
document.addEventListener('DOMContentLoaded', function() {
    initCalculator('Выполняется расчет конвертов...');
    initOrderModal();
    initializeDateTimeValidation();
});

// === УНИКАЛЬНАЯ ЛОГИКА КОНВЕРТОВ ===

// Отображение результата конвертов
function displayEnvelopeResult(result, resultDiv) {
    var totalPrice = formatPrice(result.totalPrice);
    var pricePerUnit = formatPrice(result.pricePerUnit);
    var formatName = result.formatName || result.format || 'Не указан';
    var quantity = parseInt(result.quantity, 10) || 0;
    var priceRange = result.priceRange || '';

    var html = '<div class="result-success">';
    html += '<h3 class="result-title">Результат расчета конвертов</h3>';
    html += '<div class="result-price">' + totalPrice + ' <small>₽</small></div>';

    // Информация о формате
    if (formatName) {
        html += '<div class="format-info">';
        html += '<strong>Формат конверта:</strong> ' + formatName;
        html += '</div>';
    }

    // Детализация стоимости
    html += '<div class="envelope-breakdown">';
    html += '<h4>Детализация стоимости:</h4>';

    html += '<div class="envelope-item">';
    html += '<span>Формат:</span>';
    html += '<span>' + formatName + '</span>';
    html += '</div>';

    html += '<div class="envelope-item">';
    html += '<span>Тираж:</span>';
    html += '<span>' + quantity.toLocaleString('ru-RU') + ' шт</span>';
    html += '</div>';

    html += '<div class="envelope-item">';
    html += '<span>Цена за штуку:</span>';
    html += '<span>' + pricePerUnit + ' ₽</span>';
    html += '</div>';

    html += '<div class="envelope-item">';
    html += '<span>Итого:</span>';
    html += '<span>' + totalPrice + ' ₽</span>';
    html += '</div>';

    html += '</div>';

    html += '<details class="result-details">';
    html += '<summary class="result-summary">Информация о ценообразовании</summary>';
    html += '<div class="result-details-content">';

    if (priceRange) {
        html += '<p><strong>Текущий ценовой диапазон:</strong> ' + priceRange + '</p>';
    }

    html += '<p>Цена за конверт зависит от выбранного формата и объема заказа. ';
    html += 'Чем больше тираж, тем ниже цена за единицу.</p>';
    html += '<ul>';
    html += '<li>1-100 шт: максимальная цена</li>';
    html += '<li>101-300 шт: скидка при среднем тираже</li>';
    html += '<li>301-500 шт: дополнительная скидка</li>';
    html += '<li>501-1000 шт: скидка при большом тираже</li>';
    html += '<li>1001+ шт: минимальная цена</li>';
    html += '</ul>';
    html += '</div>';
    html += '</details>';

    // Добавляем кнопку заказа
    html += '<button type="button" class="order-button" onclick="openOrderModal()">Заказать печать</button>';

    html += '</div>';

    resultDiv.innerHTML = html;
}

// Открытие модалки с данными заказа конвертов
function openOrderModal() {
    var modal = document.getElementById('orderModal');
    var orderDataInput = document.getElementById('orderData');

    // Собираем данные расчета
    var form = document.getElementById(calcConfig.type + 'CalcForm');
    var formData = collectFormData(form);
    var formatSelect = form.querySelector('select[name="format"]');
    var selectedFormatName = formatSelect ? formatSelect.options[formatSelect.selectedIndex].text : (formData.format || 'Не указан');

    // Получаем результат расчета
    var resultDiv = document.getElementById('calcResult');
    var priceElement = resultDiv.querySelector('.result-price');
    var totalPrice = priceElement ? priceElement.textContent.replace(/[^\d.,]/g, '') : '0';

    // Формируем данные заказа для конвертов
    var orderData = {
        product: 'Конверты',
        format: selectedFormatName,
        formatCode: formData.format || '',
        quantity: parseInt(formData.quantity, 10) || 0,
        totalPrice: parseFloat(String(totalPrice).replace(',', '.')) || 0,
        calcType: calcConfig.type
    };

    orderDataInput.value = JSON.stringify(orderData);
    modal.style.display = 'block';
}
</script>
