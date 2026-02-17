<?php
/** Шаблон калькулятора блокнотов */
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
            <strong>Блокноты:</strong> <?= $arResult['binding_info'] ?? 'Спиральная сборка включена в стоимость' ?><br>
            <?= $arResult['lamination_info'] ?? 'Ламинация применяется только к обложке' ?><br>
            Спасибо за понимание!
        </p>
    </div>

    <h2><?= $arResult['DESCRIPTION'] ?? 'Калькулятор блокнотов' ?></h2>

    <form id="<?= $calcType ?>CalcForm" class="calc-form">

        <!-- Формат -->
        <div class="form-group">
            <label class="form-label" for="size">Формат блокнота:</label>
            <select name="size" id="size" class="form-control" required>
                <?php if (!empty($arResult['available_sizes'])): ?>
                    <?php foreach ($arResult['available_sizes'] as $s): ?>
                        <option value="<?= htmlspecialchars($s) ?>"><?= htmlspecialchars($s) ?></option>
                    <?php endforeach; ?>
                <?php else: ?>
                    <option value="A4">A4</option>
                    <option value="A5">A5</option>
                    <option value="A6">A6</option>
                <?php endif; ?>
            </select>
        </div>

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

        <!-- Количество страниц -->
        <div class="form-group">
            <label class="form-label" for="inner_pages">Листов в блоке:</label>
            <select name="inner_pages" id="inner_pages" class="form-control" required>
                <?php if (!empty($arResult['inner_pages_options'])): ?>
                    <?php foreach ($arResult['inner_pages_options'] as $pages): ?>
                        <option value="<?= $pages ?>"><?= $pages ?> листов</option>
                    <?php endforeach; ?>
                <?php else: ?>
                    <option value="40">40 листов</option>
                    <option value="50">50 листов</option>
                <?php endif; ?>
            </select>
        </div>

        <!-- Печать обложки -->
        <div class="form-group">
            <label class="form-label" for="cover_print">Печать обложки:</label>
            <select name="cover_print" id="cover_print" class="form-control" required>
                <?php if (!empty($arResult['cover_print_types'])): ?>
                    <?php foreach ($arResult['cover_print_types'] as $key => $name): ?>
                        <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($name) ?></option>
                    <?php endforeach; ?>
                <?php else: ?>
                    <option value="4+0">4+0 (полноцвет с одной стороны)</option>
                    <option value="4+4">4+4 (полноцвет с двух сторон)</option>
                <?php endif; ?>
            </select>
            <small class="text-muted"><?= $arResult['paper_info']['cover'] ?? 'Обложка: 300 г/м²' ?></small>
        </div>

        <!-- Печать задника -->
        <div class="form-group">
            <label class="form-label" for="back_print">Печать задника:</label>
            <select name="back_print" id="back_print" class="form-control" required>
                <?php if (!empty($arResult['back_print_types'])): ?>
                    <?php foreach ($arResult['back_print_types'] as $key => $name): ?>
                        <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($name) ?></option>
                    <?php endforeach; ?>
                <?php else: ?>
                    <option value="0+0">0+0 (без печати)</option>
                    <option value="4+0">4+0 (полноцвет с одной стороны)</option>
                    <option value="4+4">4+4 (полноцвет с двух сторон)</option>
                <?php endif; ?>
            </select>
            <small class="text-muted"><?= $arResult['paper_info']['back'] ?? 'Задник: 300 г/м²' ?></small>
        </div>

        <!-- Печать внутреннего блока -->
        <div class="form-group">
            <label class="form-label" for="inner_print">Печать внутреннего блока:</label>
            <select name="inner_print" id="inner_print" class="form-control" required>
                <?php if (!empty($arResult['inner_print_types'])): ?>
                    <?php foreach ($arResult['inner_print_types'] as $key => $name): ?>
                        <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($name) ?></option>
                    <?php endforeach; ?>
                <?php else: ?>
                    <option value="0+0">0+0 (без печати)</option>
                    <option value="1+0">1+0 (ризография с одной стороны)</option>
                    <option value="1+1">1+1 (ризография с двух сторон)</option>
                    <option value="4+0">4+0 (полноцвет с одной стороны)</option>
                    <option value="4+4">4+4 (полноцвет с двух сторон)</option>
                <?php endif; ?>
            </select>
            <small class="text-muted"><?= $arResult['paper_info']['inner'] ?? 'Внутренний блок: 80 г/м²' ?></small>
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

        <?php if (!empty($features['lamination'])): ?>
        <!-- Секция ламинации -->
        <div id="laminationSection" class="lamination-section">
            <h3>Дополнительная ламинация обложки</h3>
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
// Конфигурация калькулятора блокнотов
var calcConfig = {
    type: '<?= $calcType ?>',
    features: <?= json_encode($features) ?>,
    component: 'my:print.calc'
};

// Сохраняем исходный результат без ламинации
var originalResultWithoutLamination = null;

// Hook для отображения результата (вызывается из shared.js)
window.displayResult = function(data, resultDiv) {
    // Сохраняем оригинальный результат без ламинации
    if (!data.laminationCost) {
        originalResultWithoutLamination = JSON.parse(JSON.stringify(data));
    }

    displayNoteResult(data, resultDiv);

    // Показываем секцию ламинации если доступна и еще не добавлена
    if (calcConfig.features.lamination && data.components && !data.laminationCost) {
        showLaminationSection(data);
    }
};

// Инициализация
document.addEventListener('DOMContentLoaded', function() {
    initCalculator('Выполняется расчет блокнота...');
    initOrderModal();
    initializeDateTimeValidation();
});

// === УНИКАЛЬНАЯ ЛОГИКА БЛОКНОТОВ ===

// Отображение результата блокнота
function displayNoteResult(result, resultDiv) {
    var totalPrice = Math.round((result.total || 0) * 10) / 10;

    var html = '<div class="result-success">';
    html += '<h3 class="result-title">Результат расчета блокнота</h3>';
    html += '<div class="result-price">' + totalPrice + ' <small>₽</small></div>';

    // Показываем информацию о ламинации если она была добавлена
    if (result.laminationCost && result.laminationCost > 0) {
        html += '<div class="lamination-info-container" style="color: #28a745; background: #f8fff8; padding: 10px; border-radius: 6px; border-left: 4px solid #28a745; margin-bottom: 15px;">';
        html += '<div><strong>Ламинация обложки добавлена:</strong> ' + Math.round(result.laminationCost * 10) / 10 + ' ₽</div>';
        html += '<button type="button" class="remove-lamination-btn" onclick="removeLamination()">Убрать ламинацию</button>';
        html += '</div>';
    }

    // Добавляем кнопку заказа
    html += '<button type="button" class="order-button" onclick="openOrderModal()">Заказать печать</button>';

    html += '</div>';

    resultDiv.innerHTML = html;
}

// Функция показа секции ламинации
function showLaminationSection(result) {
    var laminationSection = document.getElementById('laminationSection');
    if (!laminationSection || !calcConfig.features.lamination) {
        return;
    }

    // Если ламинация уже добавлена, не показываем секцию повторно
    if (result.laminationCost && result.laminationCost > 0) {
        laminationSection.style.display = 'none';
        return;
    }

    // Определяем тип печати обложки для выбора ламинации
    var coverPrintingType = 'Цифровая';
    if (result.components && result.components.cover && result.components.cover.base) {
        coverPrintingType = result.components.cover.base.printingType || 'Цифровая';
    }
    showStandardLaminationSection({
        enabled: !!calcConfig.features.lamination,
        result: result,
        printingType: coverPrintingType,
        titleText: 'Добавить ламинацию к обложке:',
        onCalculate: function() {
            calculateWithLamination();
        }
    });
}

// Функция расчета с ламинацией (серверный пересчет)
function calculateWithLamination() {
    var laminationType = document.querySelector('input[name="laminationType"]:checked');
    var laminationThickness = document.querySelector('select[name="laminationThickness"]');
    var resultDiv = document.getElementById('calcResult');
    var laminationResult = document.getElementById('laminationResult');

    if (!laminationType) {
        laminationResult.innerHTML = '<div class="result-error">Выберите тип ламинации</div>';
        return;
    }

    var form = document.getElementById(calcConfig.type + 'CalcForm');
    var data = collectFormData(form);
    data.calcType = calcConfig.type;
    data.lamination_type = laminationType.value;
    if (laminationThickness) {
        data.lamination_thickness = laminationThickness.value;
    }

    resultDiv.innerHTML = '<div class="loading">Пересчитываем с ламинацией...</div>';

    if (typeof BX !== 'undefined' && BX.ajax) {
        BX.ajax.runComponentAction(calcConfig.component, 'calc', {
            mode: 'class',
            data: data
        }).then(function(response) {
            handleResponse(response, resultDiv);
        }).catch(function(error) {
            resultDiv.innerHTML = '<div class="result-error">Ошибка соединения: ' + (error.message || 'Неизвестная ошибка') + '</div>';
        });
    } else {
        fetch('/bitrix/services/main/ajax.php?c=' + calcConfig.component + '&action=calc&mode=class', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams(data)
        })
        .then(function(response) { return response.json(); })
        .then(function(response) { handleResponse(response, resultDiv); })
        .catch(function(error) {
            resultDiv.innerHTML = '<div class="result-error">Ошибка соединения: ' + error.message + '</div>';
        });
    }
}

// Функция удаления ламинации
function removeLamination() {
    var resultDiv = document.getElementById('calcResult');
    var form = document.getElementById(calcConfig.type + 'CalcForm');

    if (originalResultWithoutLamination) {
        displayNoteResult(originalResultWithoutLamination, resultDiv);
        resetStandardLaminationSelection(form || document, 'laminationResult');
    }
}

// Открытие модалки с данными заказа блокнотов
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

    // Формируем данные заказа для блокнотов
    var orderData = {
        calcType: 'note',
        product: 'Блокноты',
        size: formData.size || 'Не указан',
        quantity: formData.quantity || 0,
        inner_pages: formData.inner_pages || 'Не указано',
        cover_print: formData.cover_print || 'Не указано',
        back_print: formData.back_print || 'Не указано',
        inner_print: formData.inner_print || 'Не указано',
        totalPrice: totalPrice
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
