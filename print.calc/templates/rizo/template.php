<?php
/** Шаблон калькулятора ризографии */
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
            <strong>Ризография:</strong> экономичная черно-белая печать больших тиражей. При тираже более 499 листов A3 автоматически переключается на офсетную печать.<br>
            Спасибо за понимание!
        </p>
    </div>

    <h2><?= $arResult['DESCRIPTION'] ?? 'Калькулятор ризографической печати' ?></h2>



    <form id="<?= $calcType ?>CalcForm" class="calc-form">

        <?php if (!empty($arResult['PAPER_TYPES'])): ?>
        <!-- Тип бумаги -->
        <div class="form-group">
            <label class="form-label" for="paperType">Плотность бумаги:</label>
            <select name="paperType" id="paperType" class="form-control" required>
                <?php foreach ($arResult['PAPER_TYPES'] as $paper): ?>
                    <option value="<?= htmlspecialchars($paper['ID']) ?>"><?= htmlspecialchars($paper['NAME']) ?></option>
                <?php endforeach; ?>
            </select>
            <small class="text-muted">Для ризографии доступны только 80 и 120 г/м²</small>
        </div>
        <?php endif; ?>

        <?php if (!empty($arResult['FORMATS'])): ?>
        <!-- Формат -->
        <div class="form-group">
            <label class="form-label" for="size">Формат:</label>
            <select name="size" id="size" class="form-control" required>
                <?php foreach ($arResult['FORMATS'] as $format): ?>
                    <option value="<?= htmlspecialchars($format['ID']) ?>"><?= htmlspecialchars($format['NAME']) ?></option>
                <?php endforeach; ?>
            </select>
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
                   value="<?= $arResult['DEFAULT_QUANTITY'] ?? 500 ?>"
                   placeholder="Введите количество"
                   required>
            <small class="text-muted">При тираже более 499 листов A3 будет использована офсетная печать</small>
        </div>

        <!-- Тип печати -->
        <div class="form-group">
            <label class="form-label">Тип печати:</label>
            <div class="radio-group">
                <label class="radio-label">
                    <input type="radio" name="printType" value="single" checked>
                    1+0 (черно-белая с одной стороны)
                </label>
                <label class="radio-label">
                    <input type="radio" name="printType" value="double">
                    1+1 (черно-белая с двух сторон)
                </label>
            </div>
            <small class="text-muted">Ризография поддерживает только черно-белую печать</small>
        </div>

        <?php
        // Показываем дополнительные услуги только если они поддерживаются
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
        </div>
        <?php endif; ?>

        <input type="hidden" name="calcType" value="<?= $calcType ?>">
        <input type="hidden" name="sessid" value="<?= bitrix_sessid() ?>">

        <button id="calcBtn" type="button" class="calc-button">Рассчитать стоимость</button>
        <?php if (!empty($features['lamination'])): ?>
        <!-- Секция ламинации -->
        <div id="laminationSection" class="lamination-section" style="margin-top: 32px;">
            <h3>Дополнительная ламинация</h3>
            <div id="laminationControls"></div>
            <div id="laminationResult" class="lamination-result"></div>
        </div>
        <?php endif; ?>
        <div id="calcResult" class="calc-result"></div>
        <div class="calc-spacer"></div>
    </form>

    <?php include dirname(__DIR__) . '/_shared/order-modal.php'; ?>
</div>

<style>
/* Специфичные стили для ризографии */
.remove-lamination-btn {
    background: #dc3545;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    font-size: 14px;
    cursor: pointer;
    margin-left: 10px;
    transition: all 0.3s;
}
.remove-lamination-btn:hover {
    background: #c82333;
    transform: translateY(-1px);
}
.lamination-info-container {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 10px;
}
@media (max-width: 768px) {
    .lamination-info-container {
        flex-direction: column;
        align-items: stretch;
    }
    .remove-lamination-btn {
        margin-left: 0;
        width: 100%;
    }
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
    displayRizoResult(data, resultDiv);
};

// Инициализация
document.addEventListener('DOMContentLoaded', function() {
    initCalculator('Выполняется расчет ризографии...');
    initOrderModal();
    initializeDateTimeValidation();
});

// === УНИКАЛЬНАЯ ЛОГИКА РИЗОГРАФИИ ===

// Отображение результата ризографии
function displayRizoResult(result, resultDiv) {
    var totalPrice = Math.round((result.totalPrice || 0) * 10) / 10;

    var html = '<div class="result-success">';
    html += '<h3 class="result-title">Результат расчета ризографии</h3>';
    html += '<div class="result-price">' + totalPrice + ' <small>₽</small></div>';

    // Специальное отображение типа печати для ризографии
    if (result.printingType) {
        var isRizo = result.printingType === 'Ризографическая';
        var color = isRizo ? '#28a745' : '#007bff';
        var bgColor = isRizo ? '#f8fff8' : '#f8f9ff';

        html += '<div style="color: ' + color + '; background: ' + bgColor + '; padding: 10px; border-radius: 6px; border-left: 4px solid ' + color + '; margin-bottom: 15px;">';
        html += '<strong>Тип печати:</strong> ' + result.printingType;
        if (isRizo) {
            html += '<br><small>Экономичная черно-белая печать</small>';
        } else {
            html += '<br><small>Высокое качество для больших тиражей</small>';
        }
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
    if (result.adjustment && result.adjustment > 0) html += '<li>Приладочные листы: ' + result.adjustment + '</li>';
    if (result.additionalCosts && result.additionalCosts > 0) html += '<li>Дополнительные услуги: ' + Math.round(result.additionalCosts * 10) / 10 + ' ₽</li>';

    html += '</ul>';
    html += '</div>';
    html += '</details>';

    if (result.laminationCost && result.laminationCost > 0) {
        html += '<div class="lamination-info-container">';
        html += '<p class="lamination-info" style="margin: 0;"><strong>Ламинация включена:</strong> ' + Math.round(result.laminationCost * 10) / 10 + ' ₽</p>';
        html += '<button type="button" class="remove-lamination-btn" onclick="removeLamination()">Убрать ламинацию</button>';
        html += '</div>';
    }

    // Добавляем кнопку заказа
    html += '<button type="button" class="order-button" onclick="openOrderModal()">Заказать ризографию</button>';

    html += '</div>';

    resultDiv.innerHTML = html;
}

// Удаление ламинации и пересчет
function removeLamination() {
    var laminationSection = document.getElementById('laminationSection');
    var laminationControls = document.getElementById('laminationControls');
    var laminationResult = document.getElementById('laminationResult');

    if (laminationSection) {
        laminationSection.remove();
    }
    if (laminationControls) {
        laminationControls.remove();
    }
    if (laminationResult) {
        laminationResult.remove();
    }

    // Пересчитываем стоимость без ламинации
    var form = document.getElementById(calcConfig.type + 'CalcForm');
    var data = collectFormData(form);
    data.calcType = calcConfig.type;
    data.lamination = '0';

    var resultDiv = document.getElementById('calcResult');
    resultDiv.innerHTML = '<div class="loading">Выполняется расчет ризографии без ламинации...</div>';

    BX.ajax.runComponentAction(calcConfig.component, 'calc', {
        mode: 'class',
        data: data
    }).then(function(response) {
        handleResponse(response, resultDiv);
    }).catch(function(error) {
        resultDiv.innerHTML = '<div class="result-error">Ошибка соединения: ' +
            (error.message || 'Неизвестная ошибка') + '</div>';
    });
}

// Открытие модалки с данными заказа ризографии
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

    // Формируем данные заказа для ризографии
    var orderData = {
        product: 'Ризография',
        paperType: formData.paperType || 'Не указана',
        size: formData.size || 'Не указан',
        quantity: formData.quantity || 0,
        printType: formData.printType || 'single',
        totalPrice: totalPrice,
        calcType: 'rizo'
    };

    // Добавляем дополнительные услуги если выбраны
    if (formData.bigovka) orderData.bigovka = true;
    if (formData.perforation) orderData.perforation = true;
    if (formData.drill) orderData.drill = true;
    if (formData.numbering) orderData.numbering = true;
    if (formData.cornerRadius && formData.cornerRadius !== '0') {
        orderData.cornerRadius = formData.cornerRadius;
    }

    // Добавляем информацию о ламинации если выбрана
    if (formData.laminationType) {
        orderData.laminationType = formData.laminationType;
        if (formData.laminationThickness) {
            orderData.laminationThickness = formData.laminationThickness;
        }
    }

    orderDataInput.value = JSON.stringify(orderData);
    modal.style.display = 'block';
}
</script>
