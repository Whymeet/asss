<?php
/** Шаблон калькулятора буклетов */
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
            Спасибо за понимание!
        </p>
    </div>

    <h2><?= $arResult['DESCRIPTION'] ?? 'Калькулятор печати буклетов' ?></h2>

    <form id="<?= $calcType ?>CalcForm" class="calc-form">

        <?php if (!empty($arResult['PAPER_TYPES'])): ?>
        <!-- Тип бумаги -->
        <div class="form-group">
            <label class="form-label" for="paperType">Тип бумаги:</label>
            <select name="paperType" id="paperType" class="form-control" required>
                <?php foreach ($arResult['PAPER_TYPES'] as $paper): ?>
                    <option value="<?= htmlspecialchars($paper['ID']) ?>"><?= htmlspecialchars($paper['NAME']) ?></option>
                <?php endforeach; ?>
            </select>
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
                   value="<?= $arResult['DEFAULT_QUANTITY'] ?? 1000 ?>"
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

        <?php if (isset($arResult['MAX_FOLDING'])): ?>
        <!-- Количество сложений для буклетов -->
        <div class="form-group">
            <label class="form-label" for="foldingCount">Количество сложений:</label>
            <select name="foldingCount" id="foldingCount" class="form-control">
                <option value="0">Нет сложений</option>
                <?php for ($i = 1; $i <= $arResult['MAX_FOLDING']; $i++): ?>
                    <option value="<?= $i ?>"><?= $i ?> сложение<?= $i > 1 ? 'я' : '' ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <?php endif; ?>

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

        <?php if (!empty($features['lamination'])): ?>
        <!-- Секция ламинации -->
        <div id="laminationSection" class="lamination-section" style="display: none; margin-top: 20px; margin-bottom: 20px;">
            <h3>Дополнительная ламинация</h3>
            <div id="laminationControls"></div>
            <div id="laminationResult" class="lamination-result"></div>
        </div>
        <?php endif; ?>

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

    displayBookletResult(data, resultDiv);

    // Показываем секцию ламинации если доступна
    if (calcConfig.features.lamination && (data.laminationAvailable || data.printingType)) {
        showLaminationSection(data);
    }
};

// Инициализация
document.addEventListener('DOMContentLoaded', function() {
    initCalculator('Выполняется расчет буклетов...');
    initOrderModal();
    initializeDateTimeValidation();
});

// === УНИКАЛЬНАЯ ЛОГИКА БУКЛЕТОВ ===

// Отображение результата
function displayBookletResult(result, resultDiv) {
    // Формируем описание сложений
    var foldingDescription = 'Без сложений';
    if (result.foldingCount && result.foldingCount > 0) {
        foldingDescription = result.foldingCount + ' сложение' + (result.foldingCount > 1 ? 'я' : '');
    }

    var totalPrice = formatPrice(result.totalPrice);
    var hasLamination = result.laminationCost && result.laminationCost > 0;

    var html = '<div class="result-success">';
    html += '<h3 class="result-title">Результат расчета</h3>';
    html += '<div class="result-price">' + totalPrice + ' <small>₽</small></div>';

    // Информация о ламинации с кнопкой удаления
    if (hasLamination) {
        html += '<div class="lamination-info-container">';
        html += '<p class="lamination-info" style="margin: 0;"><strong>Ламинация включена:</strong> ' + formatPrice(result.laminationCost) + ' ₽</p>';
        html += '<button type="button" class="remove-lamination-btn" onclick="removeLamination()">Убрать ламинацию</button>';
        html += '</div>';
    }

    // Кнопка заказа
    html += '<button type="button" class="order-button" onclick="openOrderModal()">Заказать буклеты</button>';
    html += '</div>';

    resultDiv.innerHTML = html;
}

// Показ секции ламинации
function showLaminationSection(result) {
    var laminationSection = document.getElementById('laminationSection');
    var controlsDiv = document.getElementById('laminationControls');

    if (!laminationSection || !controlsDiv || !calcConfig.features.lamination) {
        return;
    }

    var printingType = currentPrintingType || result.printingType;

    var html = '<div class="lamination-content">';
    html += '<p class="lamination-title">Добавить ламинацию к заказу:</p>';

    if (printingType === 'Офсетная') {
        html += '<div class="lamination-options">';
        html += '<div class="radio-group">';
        html += '<label class="radio-label"><input type="radio" name="laminationType" value="1+0"> Односторонняя (7 руб/лист)</label>';
        html += '<label class="radio-label"><input type="radio" name="laminationType" value="1+1"> Двусторонняя (14 руб/лист)</label>';
        html += '</div>';
        html += '</div>';
    } else {
        html += '<div class="lamination-options">';
        html += '<div class="form-group">';
        html += '<label class="form-label">Толщина ламинации:';
        html += '<select name="laminationThickness" class="form-control">';
        html += '<option value="32">32 мкм</option>';
        html += '<option value="75">75 мкм</option>';
        html += '<option value="125">125 мкм</option>';
        html += '<option value="250">250 мкм</option>';
        html += '</select></label>';
        html += '</div>';
        html += '<div class="radio-group">';
        html += '<label class="radio-label"><input type="radio" name="laminationType" value="1+0"> Односторонняя</label>';
        html += '<label class="radio-label"><input type="radio" name="laminationType" value="1+1"> Двусторонняя</label>';
        html += '</div>';
        html += '</div>';
    }

    html += '<div class="lamination-button-container">';
    html += '<button type="button" id="laminationBtn" class="calc-button calc-button-success">Пересчитать с ламинацией</button>';
    html += '</div>';
    html += '</div>';

    controlsDiv.innerHTML = html;
    laminationSection.style.display = 'block';

    // Обработчик кнопки ламинации
    var laminationBtn = document.getElementById('laminationBtn');
    if (laminationBtn) {
        laminationBtn.addEventListener('click', function() {
            calculateLamination(result);
        });
    }

    // Убираем ошибку при выборе радио
    var radioButtons = controlsDiv.querySelectorAll('input[name="laminationType"]');
    radioButtons.forEach(function(radio) {
        radio.addEventListener('change', function() {
            var laminationResult = document.getElementById('laminationResult');
            if (laminationResult && laminationResult.innerHTML.indexOf('Выберите тип ламинации') !== -1) {
                laminationResult.innerHTML = '';
            }
        });
    });
}

// Расчёт с ламинацией
function calculateLamination(originalResult) {
    var laminationType = document.querySelector('input[name="laminationType"]:checked');
    var laminationThickness = document.querySelector('select[name="laminationThickness"]');
    var resultDiv = document.getElementById('calcResult');
    var laminationResult = document.getElementById('laminationResult');

    if (!laminationType) {
        laminationResult.innerHTML = '<div class="result-error">Выберите тип ламинации</div>';
        return;
    }

    var form = document.getElementById(calcConfig.type + 'CalcForm');
    var quantity = parseInt(form.querySelector('input[name="quantity"]').value);

    var baseResult = originalResultWithoutLamination || originalResult;
    var printingType = currentPrintingType || baseResult.printingType;

    var laminationCost = 0;
    var laminationDescription = '';

    if (printingType === 'Офсетная') {
        if (laminationType.value === '1+0') {
            laminationCost = quantity * 7;
            laminationDescription = 'Односторонняя (7 руб/лист)';
        } else {
            laminationCost = quantity * 14;
            laminationDescription = 'Двусторонняя (14 руб/лист)';
        }
    } else {
        var thickness = laminationThickness ? laminationThickness.value : '32';
        var rates = {
            '32': { '1+0': 40, '1+1': 80 },
            '75': { '1+0': 60, '1+1': 120 },
            '125': { '1+0': 80, '1+1': 160 },
            '250': { '1+0': 90, '1+1': 180 }
        };

        laminationCost = quantity * rates[thickness][laminationType.value];
        var laminationName = laminationType.value === '1+0' ? 'Односторонняя' : 'Двусторонняя';
        laminationDescription = laminationName + ' ' + thickness + ' мкм (' + rates[thickness][laminationType.value] + ' руб/лист)';
    }

    var newResult = JSON.parse(JSON.stringify(baseResult));
    newResult.totalPrice = baseResult.totalPrice + laminationCost;
    newResult.laminationCost = laminationCost;
    newResult.laminationDescription = laminationDescription;

    displayBookletResult(newResult, resultDiv);
    laminationResult.innerHTML = '';
}

// Удаление ламинации
function removeLamination() {
    var resultDiv = document.getElementById('calcResult');

    if (originalResultWithoutLamination) {
        displayBookletResult(originalResultWithoutLamination, resultDiv);
        var laminationRadios = document.querySelectorAll('input[name="laminationType"]');
        laminationRadios.forEach(function(radio) { radio.checked = false; });
    }
}

// Открытие модалки с данными заказа буклетов
function openOrderModal() {
    var modal = document.getElementById('orderModal');
    var orderDataInput = document.getElementById('orderData');

    var form = document.getElementById(calcConfig.type + 'CalcForm');
    var formData = collectFormData(form);

    var resultDiv = document.getElementById('calcResult');
    var priceElement = resultDiv.querySelector('.result-price');
    var totalPrice = priceElement ? priceElement.textContent.replace(/[^\d.,]/g, '') : '0';

    // Данные заказа буклетов
    var orderData = {
        calcType: 'booklet',
        product: 'Буклеты',
        quantity: formData.quantity || 0,
        size: formData.size || 'Не указан',
        paperType: formData.paperType || 'Не указан',
        printType: formData.printType === 'single' ? 'Односторонняя' : 'Двусторонняя',
        totalPrice: totalPrice,
        foldingCount: formData.foldingCount || 0
    };

    // Описание сложений
    if (formData.foldingCount && formData.foldingCount > 0) {
        orderData.foldingDescription = formData.foldingCount + ' сложение' + (formData.foldingCount > 1 ? 'я' : '');
    } else {
        orderData.foldingDescription = 'Без сложений';
    }

    // Дополнительные услуги
    var additionalServices = [];
    if (formData.bigovka) additionalServices.push('Биговка');
    if (formData.perforation) additionalServices.push('Перфорация');
    if (formData.drill) additionalServices.push('Сверление');
    if (formData.numbering) additionalServices.push('Нумерация');
    if (additionalServices.length > 0) {
        orderData.additionalServices = additionalServices.join(', ');
    }

    // Данные ламинации
    var laminationRadio = document.querySelector('input[name="laminationType"]:checked');
    var laminationThicknessSelect = document.querySelector('select[name="laminationThickness"]');

    if (laminationRadio || formData.laminationType) {
        var lamType = laminationRadio ? laminationRadio.value : formData.laminationType;
        var lamThickness = laminationThicknessSelect ? laminationThicknessSelect.value : formData.laminationThickness;

        orderData.laminationType = lamType;
        if (lamThickness) {
            orderData.laminationThickness = lamThickness;
        }

        var laminationInfo = resultDiv.querySelector('.lamination-info');
        if (laminationInfo) {
            var laminationCostMatch = laminationInfo.textContent.match(/(\d+(?:\.\d+)?)/);
            if (laminationCostMatch) {
                orderData.laminationCost = parseFloat(laminationCostMatch[1]);
            }
        }

        var lamDescription = lamType;
        if (lamThickness) {
            lamDescription += ' ' + lamThickness + ' мкм';
        }
        orderData.laminationDescription = lamDescription;
    }

    orderDataInput.value = JSON.stringify(orderData);
    modal.style.display = 'block';
}
</script>
