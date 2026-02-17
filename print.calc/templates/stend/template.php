<?php
/** Шаблон калькулятора ПВХ стендов */
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
$pvcTypes = $arResult['pvc_types'] ?? [];
$pocketTypes = $arResult['pocket_types'] ?? [];
?>

<div class="calc-container">
    <!-- Информационный блок -->
    <div class="calc-disclaimer">
        <p>
            Данные, полученные при расчете на калькуляторе – являются ориентировочными в связи с регулярным изменением стоимости материалов.<br>
            Конечную стоимость заказа уточняйте у менеджера: <a href="tel:+78462060068">+7 (846) 206-00-68</a><br>
            <strong>ПВХ стенды:</strong> <?= $arResult['dimension_info'] ?? '' ?><br>
            Спасибо за понимание!
        </p>
    </div>

    <h2><?= $arResult['DESCRIPTION'] ?? 'Калькулятор ПВХ конструкций' ?></h2>

    <form id="<?= $calcType ?>CalcForm" class="calc-form">

        <!-- Размеры стенда -->
        <div class="form-group">
            <label class="form-label" for="width">Ширина стенда (см):</label>
            <input name="width"
                   id="width"
                   type="number"
                   class="form-control"
                   min="1"
                   step="1"
                   value="100"
                   placeholder="Например: 100"
                   required>
            <small class="text-muted">Размеры указываются в сантиметрах</small>
        </div>

        <div class="form-group">
            <label class="form-label" for="height">Высота стенда (см):</label>
            <input name="height"
                   id="height"
                   type="number"
                   class="form-control"
                   min="1"
                   step="1"
                   value="70"
                   placeholder="Например: 70"
                   required>
            <small class="text-muted">Размеры указываются в сантиметрах</small>
        </div>

        <!-- Тип ПВХ -->
        <div class="form-group">
            <label class="form-label" for="pvcType">Толщина ПВХ:</label>
            <select name="pvcType" id="pvcType" class="form-control" required>
                <?php if (!empty($pvcTypes)): ?>
                    <?php foreach ($pvcTypes as $key => $name): ?>
                        <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($name) ?></option>
                    <?php endforeach; ?>
                <?php else: ?>
                    <option value="3mm">3 мм</option>
                    <option value="5mm">5 мм</option>
                <?php endif; ?>
            </select>
        </div>

        <!-- Плоские карманы -->
        <div class="form-group">
            <label class="form-label">Плоские карманы:</label>
            <div class="pockets-grid">
                <div class="pocket-input">
                    <label class="form-label" for="flatA4">А4:</label>
                    <input name="flatA4"
                           id="flatA4"
                           type="number"
                           class="form-control"
                           min="0"
                           value="0"
                           placeholder="0">
                </div>
                <div class="pocket-input">
                    <label class="form-label" for="flatA5">А5:</label>
                    <input name="flatA5"
                           id="flatA5"
                           type="number"
                           class="form-control"
                           min="0"
                           value="0"
                           placeholder="0">
                </div>
            </div>
        </div>

        <!-- Объемные карманы -->
        <div class="form-group">
            <label class="form-label">Объемные карманы:</label>
            <div class="pockets-grid">
                <div class="pocket-input">
                    <label class="form-label" for="volumeA4">А4:</label>
                    <input name="volumeA4"
                           id="volumeA4"
                           type="number"
                           class="form-control"
                           min="0"
                           value="0"
                           placeholder="0">
                </div>
                <div class="pocket-input">
                    <label class="form-label" for="volumeA5">А5:</label>
                    <input name="volumeA5"
                           id="volumeA5"
                           type="number"
                           class="form-control"
                           min="0"
                           value="0"
                           placeholder="0">
                </div>
            </div>
        </div>

        <!-- Предварительный расчет -->
        <div class="form-group">
            <div id="previewCalc" class="preview-calc">
                <strong>Площадь стенда:</strong> <span id="areaPreview">0.70</span> м²<br>
                <span id="pointsWarning" class="points-warning" style="display: none;">На стенде не поместится столько карманов</span>
            </div>
        </div>

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
/* Специфичные стили для стендов */
.pockets-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-top: 10px;
}

.pocket-input {
    display: flex;
    flex-direction: column;
}

.pocket-input .form-label {
    margin-bottom: 5px;
    font-size: 13px;
    color: #666;
}

.preview-calc {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 15px;
    margin-top: 10px;
    font-size: 14px;
    color: #495057;
}

.preview-calc strong {
    color: #007bff;
}

.points-warning {
    color: #dc3545 !important;
    font-weight: bold;
    margin-top: 5px;
    display: block;
}

@media (max-width: 768px) {
    .pockets-grid {
        grid-template-columns: 1fr;
        gap: 10px;
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
    displayStendResult(data, resultDiv);
};

// Инициализация
document.addEventListener('DOMContentLoaded', function() {
    initCalculator('Выполняется расчет стенда...');
    setupStendPreview();
    initOrderModal();
    initializeDateTimeValidation();
});

// === УНИКАЛЬНАЯ ЛОГИКА СТЕНДОВ ===

// Элементы формы для предпросмотра
function setupStendPreview() {
    var widthInput = document.getElementById('width');
    var heightInput = document.getElementById('height');
    var flatA4Input = document.getElementById('flatA4');
    var flatA5Input = document.getElementById('flatA5');
    var volumeA4Input = document.getElementById('volumeA4');
    var volumeA5Input = document.getElementById('volumeA5');

    var inputs = [widthInput, heightInput, flatA4Input, flatA5Input, volumeA4Input, volumeA5Input];
    inputs.forEach(function(input) {
        if (input) {
            input.addEventListener('input', updatePreview);
        }
    });

    // Инициализируем предварительный расчет
    updatePreview();
}

// Обновление предварительного расчета
function updatePreview() {
    var widthInput = document.getElementById('width');
    var heightInput = document.getElementById('height');
    var flatA4Input = document.getElementById('flatA4');
    var flatA5Input = document.getElementById('flatA5');
    var volumeA4Input = document.getElementById('volumeA4');
    var volumeA5Input = document.getElementById('volumeA5');
    var areaPreview = document.getElementById('areaPreview');
    var pointsWarning = document.getElementById('pointsWarning');

    var width = parseFloat(widthInput.value) || 0;
    var height = parseFloat(heightInput.value) || 0;
    var flatA4 = parseInt(flatA4Input.value) || 0;
    var flatA5 = parseInt(flatA5Input.value) || 0;
    var volumeA4 = parseInt(volumeA4Input.value) || 0;
    var volumeA5 = parseInt(volumeA5Input.value) || 0;

    // Площадь в м²
    var area = (width * height) / 10000;
    areaPreview.textContent = area.toFixed(2);

    // Баллы карманов (А4 = 2 балла, А5 = 1 балл) - только для внутренних расчетов
    var usedPoints = (flatA4 + volumeA4) * 2 + (flatA5 + volumeA5) * 1;
    var maxPoints = Math.floor(area * 20); // 20 баллов на м²

    // Предупреждение о превышении лимита
    if (usedPoints > maxPoints) {
        pointsWarning.style.display = 'block';
    } else {
        pointsWarning.style.display = 'none';
    }
}

// Отображение результата ПВХ стенда
function displayStendResult(result, resultDiv) {
    var totalPrice = Math.round((result.totalPrice || 0) * 10) / 10;

    var html = '<div class="result-success">';
    html += '<h3 class="result-title">Результат расчета ПВХ стенда</h3>';
    html += '<div class="result-price">' + totalPrice + ' <small>₽</small></div>';

    // Показываем информацию о ламинации если она была добавлена
    if (result.laminationCost && result.laminationCost > 0) {
        html += '<div class="lamination-info-container" style="color: #28a745; background: #f8fff8; padding: 10px; border-radius: 6px; border-left: 4px solid #28a745; margin-bottom: 15px;">';
        html += '<div><strong>Ламинация добавлена:</strong> ' + Math.round(result.laminationCost * 10) / 10 + ' ₽</div>';
        html += '<button type="button" class="remove-lamination-btn" onclick="removeLamination()">Убрать ламинацию</button>';
        html += '</div>';
    }

    // Добавляем кнопку заказа
    html += '<button type="button" class="order-button" onclick="openOrderModal()">Заказать ПВХ стенд</button>';

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
    data.lamination = '0'; // Убираем ламинацию

    var resultDiv = document.getElementById('calcResult');
    resultDiv.innerHTML = '<div class="loading">Выполняется расчет ПВХ стенда без ламинации...</div>';

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

// Открытие модалки с данными заказа стенда
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

    // Формируем данные заказа для ПВХ стендов
    var orderData = {
        product: 'ПВХ стенд',
        width: formData.width || 'Не указан',
        height: formData.height || 'Не указан',
        pvcType: formData.pvcType || 'Не указан',
        flatA4: formData.flatA4 || '0',
        flatA5: formData.flatA5 || '0',
        volumeA4: formData.volumeA4 || '0',
        volumeA5: formData.volumeA5 || '0',
        totalPrice: totalPrice,
        calcType: 'stend'
    };

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