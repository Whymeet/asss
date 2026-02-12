<?php
/** Шаблон калькулятора открыток */
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
?>

<div class="calc-container">
    <!-- Информационный блок -->
    <div class="calc-disclaimer">
        <p>
            Данные, полученные при расчете на калькуляторе – являются ориентировочными в связи с регулярным изменением стоимости материалов.<br>
            Конечную стоимость заказа уточняйте у менеджера: <a href="tel:+78462060068">+7 (846) 206-00-68</a><br>
            <strong>Открытки:</strong> <?= $arResult['format_info'] ?? '' ?><br>
            <?= $arResult['paper_info'] ?? '' ?><br>
            Спасибо за понимание!
        </p>
    </div>

    <h2><?= $arResult['DESCRIPTION'] ?? 'Калькулятор печати открыток' ?></h2>

    <form id="<?= $calcType ?>CalcForm" class="calc-form">

        <!-- Формат открытки -->
        <div class="form-group">
            <label class="form-label" for="size">Формат открытки:</label>
            <select name="size" id="size" class="form-control" required>
                <?php if (!empty($arResult['available_sizes'])): ?>
                    <?php foreach ($arResult['available_sizes'] as $s): ?>
                        <option value="<?= htmlspecialchars($s) ?>"><?= htmlspecialchars($s) ?></option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
            <small class="text-muted">Выберите формат открытки</small>
        </div>

        <!-- Тираж -->
        <div class="form-group">
            <label class="form-label" for="quantity">Тираж:</label>
            <input name="quantity"
                   id="quantity"
                   type="number"
                   class="form-control"
                   min="<?= $arResult['min_quantity'] ?? 1 ?>"
                   max="<?= $arResult['max_quantity'] ?? 50000 ?>"
                   value="<?= $arResult['default_quantity'] ?? 100 ?>"
                   placeholder="Введите количество экземпляров"
                   required>
            <small class="text-muted">Минимальный тираж: <?= $arResult['min_quantity'] ?? 1 ?> шт.</small>
        </div>

        <!-- Тип печати -->
        <div class="form-group">
            <label class="form-label">Тип печати:</label>
            <div class="radio-group">
                <label class="radio-label">
                    <input type="radio" name="printType" value="single" checked>
                    Односторонняя печать
                </label>
                <label class="radio-label">
                    <input type="radio" name="printType" value="double">
                    Двусторонняя печать
                </label>
            </div>
        </div>

        <!-- Дополнительные услуги -->
        <div class="form-group">
            <label class="form-label">Дополнительные услуги:</label>
            <div class="checkbox-group">

                <?php if ($features['bigovka'] ?? false): ?>
                <label class="checkbox-label">
                    <input type="checkbox" name="bigovka">
                    Биговка
                </label>
                <?php endif; ?>

                <?php if ($features['perforation'] ?? false): ?>
                <label class="checkbox-label">
                    <input type="checkbox" name="perforation">
                    Перфорация
                </label>
                <?php endif; ?>

                <?php if ($features['drill'] ?? false): ?>
                <label class="checkbox-label">
                    <input type="checkbox" name="drill">
                    Сверление диаметром 5мм
                </label>
                <?php endif; ?>

                <?php if ($features['numbering'] ?? false): ?>
                <label class="checkbox-label">
                    <input type="checkbox" name="numbering">
                    Нумерация
                </label>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($features['corner_radius'] ?? false): ?>
        <!-- Скругление углов -->
        <div class="form-group">
            <label class="form-label" for="cornerRadius">Количество скругленных углов:</label>
            <input name="cornerRadius"
                   id="cornerRadius"
                   type="number"
                   class="form-control"
                   min="0"
                   max="<?= $arResult['corner_radius_max'] ?? 4 ?>"
                   value="0"
                   placeholder="0">
            <small class="text-muted">Максимум <?= $arResult['corner_radius_max'] ?? 4 ?> угла</small>
        </div>
        <?php endif; ?>

        <!-- Скрытые поля -->
        <input type="hidden" name="paperType" value="300.0">
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
    component: 'my:print.calc'
};

// Hook для отображения результата (вызывается из shared.js)
window.displayResult = function(data, resultDiv) {
    displayCardResult(data, resultDiv);
};

// Инициализация
document.addEventListener('DOMContentLoaded', function() {
    initCalculator('Выполняется расчет открыток...');
    initOrderModal();
    initializeDateTimeValidation();
});

// === УНИКАЛЬНАЯ ЛОГИКА ОТКРЫТОК ===

// Отображение результата расчета открыток
function displayCardResult(result, resultDiv) {
    var totalPrice = Math.round((result.totalPrice || 0) * 10) / 10;

    var html = '<div class="result-success">';
    html += '<h3 class="result-title">Результат расчета открыток</h3>';
    html += '<div class="result-price">' + formatPrice(totalPrice) + ' <small>₽</small></div>';

    // Детали расчета
    html += '<details class="result-details">';
    html += '<summary class="result-summary">Подробности расчета</summary>';
    html += '<div class="result-details-content">';
    html += '<ul>';

    if (result.printingType) {
        html += '<li>Тип печати: <strong>' + result.printingType + '</strong></li>';
    }

    if (result.baseA3Sheets) {
        html += '<li>Базовые листы A3: <strong>' + result.baseA3Sheets + '</strong></li>';
    }

    if (result.adjustment) {
        html += '<li>Приладочные листы: <strong>' + result.adjustment + '</strong></li>';
    }

    if (result.totalA3Sheets) {
        html += '<li>Всего листов A3: <strong>' + result.totalA3Sheets + '</strong></li>';
    }

    if (result.printingCost) {
        html += '<li>Стоимость печати: <strong>' + formatPrice(result.printingCost) + ' ₽</strong></li>';
    }

    if (result.plateCost) {
        html += '<li>Стоимость пластины: <strong>' + formatPrice(result.plateCost) + ' ₽</strong></li>';
    }

    if (result.paperCost) {
        html += '<li>Стоимость бумаги: <strong>' + formatPrice(result.paperCost) + ' ₽</strong></li>';
    }

    if (result.additionalCosts) {
        html += '<li>Дополнительные услуги: <strong>' + formatPrice(result.additionalCosts) + ' ₽</strong></li>';
    }

    html += '</ul>';
    html += '</div>';
    html += '</details>';

    // Добавляем кнопку заказа
    html += '<button type="button" class="order-button" onclick="openOrderModal()">Заказать печать</button>';

    html += '</div>';

    resultDiv.innerHTML = html;
}

// Открытие модалки с данными заказа открыток
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

    // Тип печати
    var printTypeRadio = form.querySelector('input[name="printType"]:checked');
    var printType = printTypeRadio ? printTypeRadio.value : 'single';

    // Формируем данные заказа для открыток
    var orderData = {
        calcType: calcConfig.type,
        product: 'Открытки',
        size: formData.size || 'Не указан',
        quantity: formData.quantity || 0,
        printType: printType === 'single' ? 'Односторонняя печать' : 'Двусторонняя печать',
        paperType: '300 г/м²',
        bigovka: formData.bigovka || false,
        perforation: formData.perforation || false,
        drill: formData.drill || false,
        numbering: formData.numbering || false,
        cornerRadius: formData.cornerRadius || 0,
        totalPrice: parseFloat(String(totalPrice).replace(',', '.')) || 0
    };

    orderDataInput.value = JSON.stringify(orderData);
    modal.style.display = 'block';
}
</script>