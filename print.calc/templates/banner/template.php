<?php
/** Шаблон калькулятора баннеров */
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
$bannerTypes = $arResult['banner_types'] ?? [];
$validationRules = $arResult['validation_rules'] ?? [];
?>

<div class="calc-container">
    <!-- Информационный блок -->
    <div class="calc-disclaimer">
        <p>
            Данные, полученные при расчете на калькуляторе – являются ориентировочными в связи с регулярным изменением стоимости материалов.<br>
            Конечную стоимость заказа уточняйте у менеджера: <a href="tel:+78462060068">+7 (846) 206-00-68</a><br>
            <strong>Баннеры:</strong> <?= $arResult['dimension_info'] ?? '' ?><br>
            <?= $arResult['area_info'] ?? '' ?><br>
            <?= $arResult['hemming_info'] ?? '' ?><br>
            <?= $arResult['grommets_info'] ?? '' ?><br>
            Спасибо за понимание!
        </p>
    </div>

    <h2><?= $arResult['DESCRIPTION'] ?? 'Калькулятор стоимости баннеров' ?></h2>

    <form id="<?= $calcType ?>CalcForm" class="calc-form">

        <!-- Размеры баннера -->
        <div class="form-group">
            <label class="form-label" for="length">Длина (м):</label>
            <input name="length"
                   id="length"
                   type="number"
                   class="form-control"
                   min="<?= $validationRules['min_length'] ?? 0.1 ?>"
                   max="<?= $validationRules['max_length'] ?? 50 ?>"
                   step="0.01"
                   value="1.0"
                   placeholder="Введите длину в метрах"
                   required>
            <small class="text-muted">Минимум: <?= $validationRules['min_length'] ?? 0.1 ?> м, максимум: <?= $validationRules['max_length'] ?? 50 ?> м</small>
        </div>

        <div class="form-group">
            <label class="form-label" for="width">Ширина (м):</label>
            <input name="width"
                   id="width"
                   type="number"
                   class="form-control"
                   min="<?= $validationRules['min_width'] ?? 0.1 ?>"
                   max="<?= $validationRules['max_width'] ?? 50 ?>"
                   step="0.01"
                   value="1.0"
                   placeholder="Введите ширину в метрах"
                   required>
            <small class="text-muted">Минимум: <?= $validationRules['min_width'] ?? 0.1 ?> м, максимум: <?= $validationRules['max_width'] ?? 50 ?> м</small>
        </div>

        <!-- Тип баннера -->
        <div class="form-group">
            <label class="form-label" for="bannerType">Тип баннера:</label>
            <select name="bannerType" id="bannerType" class="form-control" required>
                <?php if (!empty($bannerTypes)): ?>
                    <?php foreach ($bannerTypes as $name => $price): ?>
                        <option value="<?= htmlspecialchars($name) ?>"><?= htmlspecialchars($name) ?> (<?= $price ?> руб/м²)</option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
            <small class="text-muted">Выберите тип баннерной ткани</small>
        </div>

        <!-- Дополнительные услуги -->
        <div class="form-group">
            <label class="form-label">Дополнительные услуги:</label>
            <div class="checkbox-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="hemming" id="hemmingCheckbox"> Проклейка (90 руб/м периметра)
                </label>
                <label class="checkbox-label">
                    <input type="checkbox" name="grommets" id="grommetsCheckbox"> Люверсы (30 руб/шт)
                </label>
            </div>

            <div id="grommetStepField" style="display: none; margin-top: 15px;">
                <label class="form-label" for="grommetStep">Шаг люверсов (м):</label>
                <input name="grommetStep"
                       id="grommetStep"
                       type="number"
                       class="form-control"
                       min="<?= $validationRules['min_grommet_step'] ?? 0.1 ?>"
                       max="<?= $validationRules['max_grommet_step'] ?? 10 ?>"
                       step="0.01"
                       value="0.5"
                       placeholder="Расстояние между люверсами">
                <small class="text-muted">Чем меньше шаг, тем больше люверсов потребуется</small>
            </div>
        </div>

        <!-- Скрытые поля -->
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
    displayBannerResult(data, resultDiv);
};

// Инициализация
document.addEventListener('DOMContentLoaded', function() {
    initCalculator('Выполняется расчет баннера...');
    setupFormLogic();
    initOrderModal();
    initializeDateTimeValidation();
});

// === УНИКАЛЬНАЯ ЛОГИКА БАННЕРОВ ===

// Настройка логики формы (люверсы/проклейка)
function setupFormLogic() {
    var grommetsCheckbox = document.getElementById('grommetsCheckbox');
    var hemmingCheckbox = document.getElementById('hemmingCheckbox');
    var grommetStepField = document.getElementById('grommetStepField');

    if (grommetsCheckbox && hemmingCheckbox && grommetStepField) {
        grommetsCheckbox.addEventListener('change', function() {
            grommetStepField.style.display = this.checked ? 'block' : 'none';
            if (this.checked) {
                hemmingCheckbox.checked = true;
            }
        });
    }
}

// Отображение результата расчета баннера
function displayBannerResult(result, resultDiv) {
    var totalPrice = Math.round((result.totalPrice || 0) * 10) / 10;

    var html = '<div class="result-success">';
    html += '<h3 class="result-title">Результат расчета баннера</h3>';
    html += '<div class="result-price">' + totalPrice + ' <small>₽</small></div>';

    // Детали расчета
    html += '<details class="result-details">';
    html += '<summary>Детали расчета</summary>';
    html += '<div class="result-details-content">';

    if (result.dimensions) {
        var dimensions = result.dimensions.length && result.dimensions.width
            ? result.dimensions.length + ' × ' + result.dimensions.width + ' м'
            : result.dimensions;
        html += '<div class="detail-item"><span class="detail-label">Размеры:</span><span class="detail-value">' + dimensions + '</span></div>';
    }

    if (result.area) {
        html += '<div class="detail-item"><span class="detail-label">Площадь баннера:</span><span class="detail-value">' + result.area + ' м²</span></div>';
    }

    if (result.bannerType) {
        html += '<div class="detail-item"><span class="detail-label">Тип материала:</span><span class="detail-value">' + result.bannerType + '</span></div>';
    }

    if (result.bannerCost) {
        html += '<div class="detail-item"><span class="detail-label">Стоимость полотна:</span><span class="detail-value">' + formatPrice(result.bannerCost) + ' ₽</span></div>';
    }

    if (result.perimeter && result.hemmingCost > 0) {
        html += '<div class="detail-item"><span class="detail-label">Проклейка краев (' + result.perimeter + ' м):</span><span class="detail-value">' + formatPrice(result.hemmingCost) + ' ₽</span></div>';
    }

    if (result.grommetCount > 0) {
        html += '<div class="detail-item"><span class="detail-label">Люверсы (' + result.grommetCount + ' шт):</span><span class="detail-value">' + formatPrice(result.grommetCost) + ' ₽</span></div>';
    }

    html += '</div>';
    html += '</details>';

    // Кнопка заказа
    html += '<button type="button" class="order-button" onclick="openOrderModal()">Заказать баннер</button>';

    html += '</div>';

    resultDiv.innerHTML = html;
}

// Открытие модалки с данными заказа баннера
function openOrderModal() {
    var modal = document.getElementById('orderModal');
    var orderDataInput = document.getElementById('orderData');

    var form = document.getElementById(calcConfig.type + 'CalcForm');
    var formData = collectFormData(form);

    var resultDiv = document.getElementById('calcResult');
    var priceElement = resultDiv.querySelector('.result-price');
    var totalPrice = priceElement ? priceElement.textContent.replace(/[^\d.,]/g, '') : '0';

    var orderData = {
        product: 'Баннер',
        length: formData.length || '',
        width: formData.width || '',
        bannerType: formData.bannerType || '',
        hemming: formData.hemming || false,
        grommets: formData.grommets || false,
        grommetStep: formData.grommetStep || '',
        totalPrice: totalPrice,
        calcType: 'banner'
    };

    orderDataInput.value = JSON.stringify(orderData);
    modal.style.display = 'block';
}
</script>