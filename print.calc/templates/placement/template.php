<?php
/** Шаблон калькулятора размещения */
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
            <strong>Размещение:</strong> <?= $arResult['format_info'] ?? '' ?><br>
            <?= $arResult['paper_info'] ?? '' ?><br>
            Спасибо за понимание!
        </p>
    </div>

    <h2><?= $arResult['DESCRIPTION'] ?? 'Калькулятор печати размещения' ?></h2>

    <form id="<?= $calcType ?>CalcForm" class="calc-form">

        <?php if (!empty($arResult['PAPER_TYPES'])): ?>
        <!-- Тип бумаги (ограниченный выбор) -->
        <div class="form-group">
            <label class="form-label" for="paperType">Тип бумаги:</label>
            <select name="paperType" id="paperType" class="form-control" required>
                <?php foreach ($arResult['PAPER_TYPES'] as $paper): ?>
                    <option value="<?= htmlspecialchars($paper['ID']) ?>"><?= htmlspecialchars($paper['NAME']) ?></option>
                <?php endforeach; ?>
            </select>
            <small class="text-muted">Ограниченный выбор типов бумаги для размещения</small>
        </div>
        <?php endif; ?>

        <?php if (!empty($arResult['FORMATS'])): ?>
        <!-- Формат (только А3) -->
        <div class="form-group">
            <label class="form-label" for="size">Формат:</label>
            <select name="size" id="size" class="form-control" required>
                <?php foreach ($arResult['FORMATS'] as $format): ?>
                    <option value="<?= htmlspecialchars($format['ID']) ?>"><?= htmlspecialchars($format['NAME']) ?></option>
                <?php endforeach; ?>
            </select>
            <small class="text-muted">Доступен только формат А3</small>
        </div>
        <?php endif; ?>

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

        <?php
        // Показываем дополнительные услуги
        $showAdditionalServices = false;
        $supportedServices = [];

        if (!empty($features['bigovka'])) {
            $supportedServices[] = ['name' => 'bigovka', 'label' => 'Биговка'];
            $showAdditionalServices = true;
        }
        if (!empty($features['perforation'])) {
            $supportedServices[] = ['name' => 'perforation', 'label' => 'Перфорация'];
            $showAdditionalServices = true;
        }
        if (!empty($features['drill'])) {
            $supportedServices[] = ['name' => 'drill', 'label' => 'Сверление Ø5мм'];
            $showAdditionalServices = true;
        }
        if (!empty($features['numbering'])) {
            $supportedServices[] = ['name' => 'numbering', 'label' => 'Нумерация'];
            $showAdditionalServices = true;
        }

        if ($showAdditionalServices): ?>
        <!-- Дополнительные услуги -->
        <div class="form-group">
            <label class="form-label">Дополнительные услуги:</label>
            <div class="checkbox-group">
                <?php foreach ($supportedServices as $service): ?>
                <label class="checkbox-label">
                    <input type="checkbox" name="<?= $service['name'] ?>"> <?= $service['label'] ?>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($features['corner_radius'])): ?>
        <!-- Скругление углов -->
        <div class="form-group">
            <label class="form-label" for="cornerRadius">Количество углов для скругления:</label>
            <select name="cornerRadius" id="cornerRadius" class="form-control">
                <option value="0">Без скругления</option>
                <option value="1">1 угол</option>
                <option value="2">2 угла</option>
                <option value="3">3 угла</option>
                <option value="4">4 угла</option>
            </select>
            <small class="text-muted">Максимум <?= $arResult['corner_radius_max'] ?? 4 ?> угла</small>
        </div>
        <?php endif; ?>

        <input type="hidden" name="calcType" value="<?= $calcType ?>">
        <input type="hidden" name="sessid" value="<?= bitrix_sessid() ?>">

        <?php if (!empty($features['lamination'])): ?>
        <!-- Секция ламинации -->
        <div id="laminationSection" class="lamination-section" style="display: none; margin-top: 20px; margin-bottom: 20px;">
            <h3>Дополнительная ламинация</h3>
            <div id="laminationControls"></div>
            <div id="laminationResult" class="lamination-result"></div>
        </div>
        <?php endif; ?>

        <button id="calcBtn" type="button" class="calc-button mt-4" style="margin-top:32px;">Рассчитать стоимость</button>

        <div id="calcResult" class="calc-result"></div>
        <div class="calc-spacer"></div>
    </form>

    <?php include dirname(__DIR__) . '/_shared/order-modal.php'; ?>
</div>


<script>
// Конфигурация калькулятора размещения
var calcConfig = {
    type: '<?= $calcType ?>',
    features: <?= json_encode($features) ?>,
    component: 'my:print.calc'
};

// Сохраняем исходный результат без ламинации
var originalResultWithoutLamination = null;
var currentPrintingType = null;

// Hook для отображения результата (вызывается из shared.js)
window.displayResult = function(data, resultDiv) {
    // Сохраняем исходный результат без ламинации
    if (!data.laminationCost) {
        originalResultWithoutLamination = JSON.parse(JSON.stringify(data));
        currentPrintingType = data.printingType;
    }

    displayPlacementResult(data, resultDiv);

    // Показываем секцию ламинации если доступна
    if (calcConfig.features.lamination && data.printingType) {
        showLaminationSection(data);
    }
};

// Инициализация
document.addEventListener('DOMContentLoaded', function() {
    initCalculator('Выполняется расчет размещения...');
    initOrderModal();
    initializeDateTimeValidation();
});

// === УНИКАЛЬНАЯ ЛОГИКА РАЗМЕЩЕНИЯ ===

// Отображение результата размещения
function displayPlacementResult(result, resultDiv) {
    var totalPrice = Math.round((result.totalPrice || 0) * 10) / 10;
    var hasLamination = result.laminationCost && result.laminationCost > 0;

    var html = '<div class="result-success">';
    html += '<h3 class="result-title">Результат расчета размещения</h3>';
    html += '<div class="result-price">' + totalPrice + ' <small>₽</small></div>';

    // Информация о типе печати
    if (result.printingType) {
        var isOffset = result.printingType === 'Офсетная';
        var color = isOffset ? '#28a745' : '#007bff';
        var bgColor = isOffset ? '#f8fff8' : '#f8f9ff';

        html += '<div style="color: ' + color + '; background: ' + bgColor + '; padding: 10px; border-radius: 6px; border-left: 4px solid ' + color + '; margin-bottom: 15px;">';
        html += '<strong>Тип печати:</strong> ' + result.printingType;
        if (isOffset) {
            html += '<br><small>Высокое качество для больших тиражей</small>';
        } else {
            html += '<br><small>Быстрая печать малых тиражей</small>';
        }
        html += '</div>';
    }

    // Показываем информацию о ламинации если она была добавлена
    if (hasLamination) {
        html += '<div class="lamination-info-container">';
        html += '<p class="lamination-info" style="margin: 0;"><strong>Ламинация включена:</strong> ' + Math.round(result.laminationCost * 10) / 10 + ' ₽</p>';
        html += '<button type="button" class="remove-lamination-btn" onclick="removeLamination()">Убрать ламинацию</button>';
        html += '</div>';
    }

    html += '<details class="result-details">';
    html += '<summary class="result-summary">Подробности расчета</summary>';
    html += '<div class="result-details-content">';
    html += '<ul>';

    if (result.baseA3Sheets) html += '<li>Листов A3: ' + result.baseA3Sheets + '</li>';
    if (result.printingCost) html += '<li>Стоимость печати: ' + Math.round(result.printingCost * 10) / 10 + ' ₽</li>';
    if (result.paperCost) html += '<li>Стоимость бумаги: ' + Math.round(result.paperCost * 10) / 10 + ' ₽</li>';
    if (result.plateCost && result.plateCost > 0) html += '<li>Стоимость пластин: ' + Math.round(result.plateCost * 10) / 10 + ' ₽</li>';
    if (result.additionalCosts && result.additionalCosts > 0) html += '<li>Дополнительные услуги: ' + Math.round(result.additionalCosts * 10) / 10 + ' ₽</li>';
    if (result.laminationCost && result.laminationCost > 0) html += '<li>Ламинация: ' + Math.round(result.laminationCost * 10) / 10 + ' ₽</li>';

    html += '</ul>';
    html += '</div>';
    html += '</details>';

    // Добавляем кнопку заказа
    html += '<button type="button" class="order-button" onclick="openOrderModal()">Заказать печать</button>';

    html += '</div>';

    resultDiv.innerHTML = html;
}

// Функция показа секции ламинации
function showLaminationSection(result) {
    showStandardLaminationSection({
        enabled: !!calcConfig.features.lamination,
        result: result,
        printingType: currentPrintingType || result.printingType,
        onCalculate: function() {
            calculateLamination(result);
        }
    });
}

// Функция расчета с ламинацией (клиентская)
function calculateLamination(originalResult) {
    var resultDiv = document.getElementById('calcResult');
    var form = document.getElementById(calcConfig.type + 'CalcForm');
    var baseResult = originalResultWithoutLamination || originalResult;
    var quantityInput = form ? form.querySelector('input[name="quantity"]') : null;
    var quantity = quantityInput ? parseInt(quantityInput.value, 10) : 0;

    var newResult = applyStandardLamination({
        scope: form || document,
        baseResult: baseResult,
        printingType: currentPrintingType || baseResult.printingType,
        quantity: quantity,
        laminationResult: document.getElementById('laminationResult')
    });

    if (!newResult) {
        return;
    }

    displayPlacementResult(newResult, resultDiv);
}

// Функция для удаления ламинации
function removeLamination() {
    var resultDiv = document.getElementById('calcResult');
    var form = document.getElementById(calcConfig.type + 'CalcForm');

    if (originalResultWithoutLamination) {
        displayPlacementResult(originalResultWithoutLamination, resultDiv);
        resetStandardLaminationSelection(form || document, 'laminationResult');
    }
}

// Открытие модалки с данными заказа размещения
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

    // Формируем данные заказа для размещения
    var orderData = {
        product: 'Размещение',
        paperType: formData.paperType || 'Не указан',
        size: formData.size || 'Не указан',
        printType: formData.printType === 'single' ? '1+0' : '1+1',
        quantity: formData.quantity || 0,
        totalPrice: totalPrice,
        calcType: 'placement'
    };

    // Добавляем дополнительные услуги
    var additionalServices = [];
    if (formData.bigovka) additionalServices.push('Биговка');
    if (formData.perforation) additionalServices.push('Перфорация');
    if (formData.drill) additionalServices.push('Сверление');
    if (formData.numbering) additionalServices.push('Нумерация');
    if (formData.cornerRadius && formData.cornerRadius > 0) additionalServices.push('Скругление ' + formData.cornerRadius + ' углов');
    if (additionalServices.length > 0) {
        orderData.additionalServices = additionalServices.join(', ');
    }

    // Добавляем информацию о ламинации если выбрана
    var laminationRadio = document.querySelector('input[name="laminationType"]:checked');
    if (laminationRadio) {
        orderData.laminationType = laminationRadio.value;
        var laminationThickness = document.querySelector('select[name="laminationThickness"]');
        if (laminationThickness) {
            orderData.laminationThickness = laminationThickness.value;
        }
    }

    orderDataInput.value = JSON.stringify(orderData);
    modal.style.display = 'block';
}
</script>
