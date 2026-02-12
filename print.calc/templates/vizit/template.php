<?php
/** Шаблон калькулятора визиток */
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
$printTypes = $arResult['print_types'] ?? [];
$digitalRange = $arResult['digital_range'] ?? [];
$offsetRange = $arResult['offset_range'] ?? [];
$sideTypes = $arResult['side_types'] ?? [];
?>

<div class="calc-container">
    <!-- Информационный блок -->
    <div class="calc-disclaimer">
        <p>
            Данные, полученные при расчете на калькуляторе – являются ориентировочными в связи с регулярным изменением стоимости материалов.<br>
            Конечную стоимость заказа уточняйте у менеджера: <a href="tel:+78462060068">+7 (846) 206-00-68</a><br>
            <strong>Визитки:</strong> <?= $arResult['info_text'] ?? '' ?><br>
            Спасибо за понимание!
        </p>
    </div>

    <h2><?= $arResult['DESCRIPTION'] ?? 'Калькулятор печати визиток' ?></h2>

    <form id="<?= $calcType ?>CalcForm" class="calc-form">

        <!-- Тип печати -->
        <div class="form-group">
            <label class="form-label" for="printType">Тип печати:</label>
            <select name="printType" id="printType" class="form-control" required>
                <option value="digital">Цифровая печать (100-999 шт)</option>
                <option value="offset">Офсетная печать (от 1000 шт)</option>
            </select>
        </div>

        <!-- Цифровая печать -->
        <div class="form-group" id="digitalGroup">
            <label class="form-label" for="digitalQuantity">Тираж (цифровая печать):</label>
            <input name="digitalQuantity"
                   id="digitalQuantity"
                   type="number"
                   class="form-control"
                   min="<?= $digitalRange['min'] ?? 100 ?>"
                   max="<?= $digitalRange['max'] ?? 999 ?>"
                   value="<?= $arResult['DEFAULT_QUANTITY'] ?? 500 ?>"
                   placeholder="Введите количество (100-999)"
                   required>
            <small class="text-muted">Для цифровой печати: от <?= $digitalRange['min'] ?? 100 ?> до <?= $digitalRange['max'] ?? 999 ?> штук</small>

            <!-- Тип печати для цифровой -->
            <div style="margin-top: 15px;">
                <label class="form-label">Печать:</label>
                <div class="radio-group">
                    <label class="radio-label">
                        <input type="radio" name="sideType" value="single" checked>
                        Односторонняя (4+0)
                    </label>
                    <label class="radio-label">
                        <input type="radio" name="sideType" value="double">
                        Двусторонняя (4+4)
                    </label>
                </div>
            </div>
        </div>

        <!-- Офсетная печать -->
        <div class="form-group" id="offsetGroup" style="display: none;">
            <label class="form-label" for="offsetQuantity">Тираж (офсетная печать):</label>
            <select name="offsetQuantity" id="offsetQuantity" class="form-control">
                <?php if (!empty($offsetRange['available'])): ?>
                    <?php foreach ($offsetRange['available'] as $qty): ?>
                        <option value="<?= $qty ?>"><?= number_format($qty, 0, '', ' ') ?> шт</option>
                    <?php endforeach; ?>
                <?php else: ?>
                    <?php for ($i = 1000; $i <= 12000; $i += 1000): ?>
                        <option value="<?= $i ?>"><?= number_format($i, 0, '', ' ') ?> шт</option>
                    <?php endfor; ?>
                <?php endif; ?>
            </select>
            <small class="text-muted">Для офсетной печати: от 1000 до 12000 штук (кратно 1000)</small>
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

<script>
// Конфигурация калькулятора
var calcConfig = {
    type: '<?= $calcType ?>',
    component: 'my:print.calc'
};

// Hook для отображения результата (вызывается из shared.js)
window.displayResult = function(data, resultDiv) {
    displayVizitResult(data, resultDiv);
};

// Инициализация
document.addEventListener('DOMContentLoaded', function() {
    initCalculator('Выполняется расчет визиток...');
    setupFormLogic();
    initOrderModal();
    initializeDateTimeValidation();
});

// === УНИКАЛЬНАЯ ЛОГИКА ВИЗИТОК ===

// Настройка логики формы (цифровая/офсетная печать)
function setupFormLogic() {
    var printTypeSelect = document.getElementById('printType');
    var digitalGroup = document.getElementById('digitalGroup');
    var offsetGroup = document.getElementById('offsetGroup');
    var digitalQuantity = document.getElementById('digitalQuantity');
    var offsetQuantity = document.getElementById('offsetQuantity');

    if (printTypeSelect && digitalGroup && offsetGroup) {
        printTypeSelect.addEventListener('change', function() {
            updateFormDisplay(printTypeSelect, digitalGroup, offsetGroup, digitalQuantity, offsetQuantity);
        });
        updateFormDisplay(printTypeSelect, digitalGroup, offsetGroup, digitalQuantity, offsetQuantity);
    }
}

// Управление отображением полей в зависимости от типа печати
function updateFormDisplay(printTypeSelect, digitalGroup, offsetGroup, digitalQuantity, offsetQuantity) {
    var isOffset = printTypeSelect.value === 'offset';

    if (isOffset) {
        digitalGroup.style.display = 'none';
        offsetGroup.style.display = 'block';
        digitalQuantity.required = false;
        offsetQuantity.required = true;
    } else {
        digitalGroup.style.display = 'block';
        offsetGroup.style.display = 'none';
        digitalQuantity.required = true;
        offsetQuantity.required = false;
    }
}

// Вспомогательная функция для форматирования чисел
function number_format(number, decimals, dec_point, thousands_sep) {
    number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
    var n = !isFinite(+number) ? 0 : +number,
        prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
        sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
        dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
        s = '',
        toFixedFix = function(n, prec) {
            var k = Math.pow(10, prec);
            return '' + Math.round(n * k) / k;
        };
    s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
    if (s[0].length > 3) {
        s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
    }
    if ((s[1] || '').length < prec) {
        s[1] = s[1] || '';
        s[1] += new Array(prec - s[1].length + 1).join('0');
    }
    return s.join(dec);
}

// Отображение результата визиток
function displayVizitResult(result, resultDiv) {
    var totalPrice = Math.round((result.totalPrice || 0) * 10) / 10;

    var html = '<div class="result-success">';
    html += '<h3 class="result-title">Результат расчета визиток</h3>';
    html += '<div class="result-price">' + totalPrice + ' <small>₽</small></div>';

    // Количество
    if (result.quantity) {
        html += '<p><strong>Количество:</strong> ' + number_format(result.quantity, 0, '', ' ') + ' шт</p>';
    }

    // Добавляем кнопку заказа
    html += '<button type="button" class="order-button" onclick="openOrderModal()">Заказать печать</button>';

    html += '</div>';

    resultDiv.innerHTML = html;
}

// Открытие модалки с данными заказа визиток
function openOrderModal() {
    var modal = document.getElementById('orderModal');
    var orderDataInput = document.getElementById('orderData');
    var form = document.getElementById(calcConfig.type + 'CalcForm');
    var formData = collectFormData(form);
    var resultDiv = document.getElementById('calcResult');

    var priceElement = resultDiv.querySelector('.result-price');
    var totalPrice = priceElement ? priceElement.textContent.replace(/[^\d.,]/g, '') : '0';

    // Формируем данные заказа для визиток
    var printType = formData.printType;
    var quantity;
    var sideType;

    if (printType === 'offset') {
        quantity = parseInt(formData.offsetQuantity) || 0;
        sideType = 'single';
    } else {
        quantity = parseInt(formData.digitalQuantity) || 0;
        var sideTypeRadio = form.querySelector('input[name="sideType"]:checked');
        sideType = sideTypeRadio ? sideTypeRadio.value : 'single';
    }

    var orderData = {
        calcType: calcConfig.type,
        product: 'Визитки',
        printType: printType === 'digital' ? 'Цифровая печать' : 'Офсетная печать',
        quantity: quantity,
        sideType: sideType === 'single' ? 'Односторонняя (4+0)' : 'Двусторонняя (4+4)',
        size: '90x50 мм (стандартный)',
        totalPrice: parseFloat(String(totalPrice).replace(',', '.')) || 0
    };

    orderDataInput.value = JSON.stringify(orderData);
    modal.style.display = 'block';
}
</script>
