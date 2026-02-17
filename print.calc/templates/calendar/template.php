<?php
/** Шаблон калькулятора календарей */
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
$calendarTypes = $arResult['calendar_types'] ?? [];
$calendarInfo = $arResult['calendar_info'] ?? [];
$wallSizes = $arResult['wall_sizes'] ?? ['A4', 'A3'];
$printTypes = $arResult['print_types'] ?? [];
?>

<div class="calc-container">
    <!-- Информационный блок -->
    <div class="calc-disclaimer">
        <p>
            Данные, полученные при расчете на калькуляторе – являются ориентировочными в связи с регулярным изменением стоимости материалов.<br>
            Конечную стоимость заказа уточняйте у менеджера: <a href="tel:+78462060068">+7 (846) 206-00-68</a><br>
            <strong>Календари:</strong> <?= $arResult['assembly_info'] ?? 'Сборка включена в стоимость' ?><br>
            <?= $arResult['desktop_bigovka'] ?? '' ?><br>
            <?= $arResult['pocket_corners'] ?? '' ?><br>
            Спасибо за понимание!
        </p>
    </div>

    <h2><?= $arResult['DESCRIPTION'] ?? 'Калькулятор календарей' ?></h2>

    <form id="<?= $calcType ?>CalcForm" class="calc-form">

        <!-- Тип календаря -->
        <div class="form-group">
            <label class="form-label" for="calendarType">Тип календаря:</label>
            <select name="calendarType" id="calendarType" class="form-control" required>
                <?php foreach ($calendarTypes as $type => $name): ?>
                    <option value="<?= htmlspecialchars($type) ?>"><?= htmlspecialchars($name) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Размер (только для настенных) -->
        <div class="form-group" id="sizeGroup" style="display: none;">
            <label class="form-label" for="size">Размер:</label>
            <select name="size" id="size" class="form-control">
                <?php foreach ($wallSizes as $size): ?>
                    <option value="<?= htmlspecialchars($size) ?>"><?= htmlspecialchars($size) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Тип печати -->
        <div class="form-group" id="printTypeGroup">
            <label class="form-label">Тип печати:</label>
            <div class="radio-group">
                <label class="radio-label">
                    <input type="radio" name="printType" value="4+0" checked>
                    4+0 (полноцветная печать с одной стороны)
                </label>
                <label class="radio-label">
                    <input type="radio" name="printType" value="4+4">
                    4+4 (полноцветная печать с двух сторон)
                </label>
            </div>
        </div>

        <!-- Тираж -->
        <div class="form-group">
            <label class="form-label" for="quantity">Тираж:</label>
            <input name="quantity"
                   id="quantity"
                   type="number"
                   class="form-control"
                   min="<?= $arResult['min_quantity'] ?? 1 ?>"
                   max="<?= $arResult['max_quantity'] ?? 5000 ?>"
                   value="<?= $arResult['default_quantity'] ?? 100 ?>"
                   placeholder="Введите количество"
                   required>
        </div>

        <!-- Информационный блок о выбранном типе -->
        <div class="form-group">
            <div id="calendarTypeInfo" class="info-block">
                <p id="calendarTypeDescription"></p>
            </div>
        </div>

        <input type="hidden" name="calcType" value="<?= $calcType ?>">
        <input type="hidden" name="sessid" value="<?= bitrix_sessid() ?>">

        <button type="button" id="calcBtn" class="calc-button">Рассчитать стоимость</button>
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

// Информация о типах календарей
var calendarInfo = <?= json_encode($calendarInfo) ?>;

// Hook для отображения результата (вызывается из shared.js)
window.displayResult = function(data, resultDiv) {
    displayCalendarResult(data, resultDiv);
};

// Инициализация
document.addEventListener('DOMContentLoaded', function() {
    initCalculator('Выполняется расчет календаря...');
    setupCalendarTypeInterface();
    initOrderModal();
    initializeDateTimeValidation();
});

// === УНИКАЛЬНАЯ ЛОГИКА КАЛЕНДАРЕЙ ===

// Настройка интерфейса типа календаря
function setupCalendarTypeInterface() {
    var calendarTypeSelect = document.getElementById('calendarType');
    if (calendarTypeSelect) {
        calendarTypeSelect.addEventListener('change', updateCalendarTypeInterface);
        updateCalendarTypeInterface();
    }
}

// Обновление интерфейса при изменении типа календаря
function updateCalendarTypeInterface() {
    var calendarTypeSelect = document.getElementById('calendarType');
    var sizeGroup = document.getElementById('sizeGroup');
    var printTypeGroup = document.getElementById('printTypeGroup');
    var calendarTypeDescription = document.getElementById('calendarTypeDescription');

    var selectedType = calendarTypeSelect.value;

    // Показываем/скрываем поле размера
    if (selectedType === 'wall') {
        sizeGroup.style.display = 'block';
    } else {
        sizeGroup.style.display = 'none';
    }

    // Всегда показываем выбор типа печати
    printTypeGroup.style.display = 'block';

    // Обновляем описание
    if (calendarInfo[selectedType]) {
        calendarTypeDescription.textContent = calendarInfo[selectedType];
    }
}

// Отображение результата календаря
function displayCalendarResult(result, resultDiv) {
    var totalPrice = Math.round((result.totalPrice || 0) * 10) / 10;

    var html = '<div class="result-success">';
    html += '<h3 class="result-title">Результат расчета календаря</h3>';
    html += '<div class="result-price">' + totalPrice + ' <small>₽</small></div>';

    // Кнопка заказа
    html += '<button type="button" class="order-button" onclick="openOrderModal()">Заказать календарь</button>';

    html += '</div>';

    resultDiv.innerHTML = html;
}

// Открытие модалки с данными заказа календаря
function openOrderModal() {
    var modal = document.getElementById('orderModal');
    var orderDataInput = document.getElementById('orderData');

    var form = document.getElementById(calcConfig.type + 'CalcForm');
    var formData = collectFormData(form);

    var resultDiv = document.getElementById('calcResult');
    var priceElement = resultDiv.querySelector('.result-price');
    var totalPrice = priceElement ? priceElement.textContent.replace(/[^\d.,]/g, '') : '0';

    var orderData = {
        product: 'Календарь',
        calendarType: formData.calendarType || '',
        size: formData.size || '',
        printType: formData.printType || '',
        quantity: formData.quantity || '',
        totalPrice: totalPrice,
        calcType: 'calendar'
    };

    orderDataInput.value = JSON.stringify(orderData);
    modal.style.display = 'block';
}
</script>