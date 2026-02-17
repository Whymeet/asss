<?php
/** Шаблон калькулятора холстов */
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
            <strong>Холсты:</strong> <?= $arResult['dimension_info'] ?? 'Размеры указываются в сантиметрах' ?><br>
            <?= $arResult['rounding_info'] ?? 'Размеры до 100 см округляются до стандартных значений' ?><br>
            Спасибо за понимание!
        </p>
    </div>

    <h2><?= $arResult['DESCRIPTION'] ?? 'Калькулятор печати на холсте' ?></h2>

    <form id="<?= $calcType ?>CalcForm" class="calc-form">

        <!-- Размеры холста -->
        <div class="form-group">
            <label class="form-label" for="width">Ширина (см):</label>
            <input name="width"
                   id="width"
                   type="number"
                   class="form-control"
                   min="1"
                   step="1"
                   value="30"
                   placeholder="Например: 30"
                   required>
            <small class="text-muted">Размеры указываются в сантиметрах</small>
        </div>

        <div class="form-group">
            <label class="form-label" for="height">Высота (см):</label>
            <input name="height"
                   id="height"
                   type="number"
                   class="form-control"
                   min="1"
                   step="1"
                   value="30"
                   placeholder="Например: 40"
                   required>
            <small class="text-muted">Размеры указываются в сантиметрах</small>
        </div>

        <!-- Подрамник -->
        <div class="form-group">
            <div class="checkbox-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="includePodramnik" id="includePodramnik">
                    Включить подрамник
                </label>
            </div>
            <small class="text-muted"><?= $arResult['podramnik_info'] ?? 'Подрамник можно добавить к любому размеру холста' ?></small>
        </div>

        <!-- Предварительная информация -->
        <div class="form-group">
            <div id="sizePreview" class="size-preview">
                <span id="previewText">Размер: 30×30 см (стандартный)</span><br>
                <span id="areaText" style="display: none;">Площадь: 0.09 м²</span>
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
    component: 'my:print.calc'
};

// Стандартные размеры
var standardSizes = <?= json_encode($arResult['standard_sizes'] ?? [30, 40, 50, 60, 70, 80, 90, 100]) ?>;
var maxStandardSize = <?= $arResult['max_standard_size'] ?? 100 ?>;

// Hook для отображения результата (вызывается из shared.js)
window.displayResult = function(data, resultDiv) {
    displayCanvasResult(data, resultDiv);
};

// Инициализация
document.addEventListener('DOMContentLoaded', function() {
    initCalculator('Выполняется расчет холста...');
    setupCanvasPreview();
    initOrderModal();
    initializeDateTimeValidation();
});

// === УНИКАЛЬНАЯ ЛОГИКА ХОЛСТОВ ===

// Настройка предпросмотра размеров
function setupCanvasPreview() {
    var widthInput = document.getElementById('width');
    var heightInput = document.getElementById('height');

    if (widthInput) widthInput.addEventListener('input', updateSizePreview);
    if (heightInput) heightInput.addEventListener('input', updateSizePreview);

    updateSizePreview();
}

// Функция округления до ближайшего стандартного размера
function ceilToNearest(value, allowed) {
    var maxAllowed = Math.max.apply(null, allowed);

    if (value > maxAllowed) {
        return value;
    }

    allowed.sort(function(a, b) { return a - b; });

    for (var i = 0; i < allowed.length; i++) {
        if (allowed[i] >= value) {
            return allowed[i];
        }
    }

    return value;
}

// Обновление предварительного просмотра
function updateSizePreview() {
    var widthInput = document.getElementById('width');
    var heightInput = document.getElementById('height');
    var previewText = document.getElementById('previewText');
    var areaText = document.getElementById('areaText');

    var width = parseFloat(widthInput.value) || 0;
    var height = parseFloat(heightInput.value) || 0;

    if (width <= 0 || height <= 0) {
        previewText.textContent = 'Укажите размеры холста';
        areaText.style.display = 'none';
        return;
    }

    if (width > maxStandardSize || height > maxStandardSize) {
        var area = (width * height) / 10000;
        previewText.textContent = 'Размер: ' + width + '×' + height + ' см (большой)';
        areaText.textContent = 'Площадь: ' + area.toFixed(4) + ' м²';
        areaText.style.display = 'block';
    } else {
        var roundedWidth = ceilToNearest(width, standardSizes);
        var roundedHeight = ceilToNearest(height, standardSizes);

        if (roundedWidth !== width || roundedHeight !== height) {
            previewText.textContent = 'Размер: ' + width + '×' + height + ' см → ' + roundedWidth + '×' + roundedHeight + ' см (округлено)';
        } else {
            previewText.textContent = 'Размер: ' + width + '×' + height + ' см (стандартный)';
        }
        areaText.style.display = 'none';
    }
}

// Отображение результата холста
function displayCanvasResult(result, resultDiv) {
    var totalPrice = Math.round((result.totalPrice || 0) * 10) / 10;

    var html = '<div class="result-success">';
    html += '<h3 class="result-title">Результат расчета холста</h3>';
    html += '<div class="result-price">' + totalPrice + ' <small>₽</small></div>';

    // Кнопка заказа
    html += '<button type="button" class="order-button" onclick="openOrderModal()">Заказать печать</button>';

    html += '</div>';

    resultDiv.innerHTML = html;
}

// Открытие модалки с данными заказа холста
function openOrderModal() {
    var modal = document.getElementById('orderModal');
    var orderDataInput = document.getElementById('orderData');

    var resultDiv = document.getElementById('calcResult');
    var priceElement = resultDiv.querySelector('.result-price');
    var totalPrice = priceElement ? priceElement.textContent.replace(/[^\d.,]/g, '') : '0';

    var orderData = {
        product: 'Печать на холсте',
        width: document.getElementById('width').value,
        height: document.getElementById('height').value,
        includePodramnik: document.getElementById('includePodramnik').checked,
        totalPrice: totalPrice,
        calcType: 'canvas'
    };

    orderDataInput.value = JSON.stringify(orderData);
    modal.style.display = 'block';
}
</script>