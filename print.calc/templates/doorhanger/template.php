<?php
/** Шаблон калькулятора дорхендеров */
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

// Подключаем общие стили
$this->addExternalCss($templateFolder.'/../.default/style.css');

// Проверяем, что конфигурация загружена
if (!$arResult['CONFIG_LOADED']) {
    echo '<div class="result-error">Ошибка: Конфигурация калькулятора не загружена</div>';
    return;
}

// Принудительно подключаем основные скрипты Битрикса
CJSCore::Init(['ajax', 'window']);

$calcType = $arResult['CALC_TYPE'];
$features = $arResult['FEATURES'] ?? [];
$itemsPerSheet = $arResult['items_per_sheet'] ?? 6;
?>

<div class="calc-container">
    <!-- Информационный блок -->
    <div class="calc-disclaimer">
        <p>
            Данные, полученные при расчете на калькуляторе – являются ориентировочными в связи с регулярным изменением стоимости материалов.<br>
            Конечную стоимость заказа уточняйте у менеджера: <a href="tel:+78462060068">+7 (846) 206-00-68</a><br>
            <strong>Дорхендеры:</strong> <?= $arResult['layout_info'] ?? 'Размещение 6 штук на листе А3' ?><br>
            <?= $arResult['format_info'] ?? 'Фиксированный формат А3' ?><br>
            Спасибо за понимание!
        </p>
    </div>
    
    <h2><?= $arResult['DESCRIPTION'] ?? 'Калькулятор дорхендеров (6 шт/А3)' ?></h2>
    
    <form id="<?= $calcType ?>CalcForm" class="calc-form">
        
        <!-- Информация о раскладке -->
        <div class="layout-info">
            <h3>Особенности расчета</h3>
            <p>Расчет осуществляется для продукции, размещенной по <strong><?= $itemsPerSheet ?> штук</strong> на листе А3</p>
            <p>Количество должно быть кратно <?= $itemsPerSheet ?> (1 лист А3 = <?= $itemsPerSheet ?> штук)</p>
        </div>

        <!-- Тип бумаги -->
        <?php if (!empty($arResult['PAPER_TYPES'])): ?>
        <div class="form-group">
            <label class="form-label" for="paperType">Тип бумаги:</label>
            <select name="paperType" id="paperType" class="form-control" required>
                <?php foreach ($arResult['PAPER_TYPES'] as $paper): ?>
                    <option value="<?= htmlspecialchars($paper['ID']) ?>"><?= htmlspecialchars($paper['NAME']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>

        <!-- Количество штук -->
        <div class="form-group">
            <label class="form-label" for="quantity">Количество штук:</label>
            <input name="quantity" 
                   id="quantity" 
                   type="number" 
                   class="form-control" 
                   min="<?= $arResult['MIN_QUANTITY'] ?? 6 ?>" 
                   max="<?= $arResult['MAX_QUANTITY'] ?? '' ?>" 
                   step="<?= $arResult['quantity_step'] ?? 6 ?>"
                   value="<?= $arResult['DEFAULT_QUANTITY'] ?? 6 ?>" 
                   placeholder="Введите количество (кратно <?= $itemsPerSheet ?>)"
                   required>
            <small class="text-muted">Количество должно быть кратно <?= $itemsPerSheet ?> (1 лист А3 = <?= $itemsPerSheet ?> штук)</small>
        </div>

        <!-- Тип печати -->
        <div class="form-group">
            <label class="form-label">Тип печати:</label>
            <div class="radio-group">
                <?php if (!empty($arResult['print_types'])): ?>
                    <?php $first = true; foreach ($arResult['print_types'] as $key => $name): ?>
                        <label class="radio-label">
                            <input type="radio" name="printType" value="<?= htmlspecialchars($key) ?>" <?= $first ? 'checked' : '' ?>> 
                            <?= htmlspecialchars($name) ?>
                        </label>
                        <?php $first = false; endforeach; ?>
                <?php else: ?>
                    <label class="radio-label">
                        <input type="radio" name="printType" value="single" checked> 
                        Односторонняя
                    </label>
                    <label class="radio-label">
                        <input type="radio" name="printType" value="double"> 
                        Двусторонняя
                    </label>
                <?php endif; ?>
            </div>
        </div>

        <!-- Предварительный расчет -->
        <div class="form-group">
            <div id="sheetPreview" class="sheet-preview">
                <strong>Предварительный расчет:</strong><br>
                <span id="sheetsCount">Листов А3: 1</span><br>
                <span id="itemsCount">Штук: <?= $itemsPerSheet ?></span>
            </div>
        </div>

        <!-- Информация о наценках -->
        <div class="pricing-info">
            <h4>Дополнительные наценки:</h4>
            <ul>
                <?php if (!empty($arResult['fee_info'])): ?>
                    <?php foreach ($arResult['fee_info'] as $fee): ?>
                        <li><?= htmlspecialchars($fee) ?></li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li>Наценка за цифровую печать: 1500 ₽</li>
                    <li>Наценка за офсет (200-1000 листов): 3500 ₽</li>
                    <li>Наценка за офсет (>1000 листов): 3.5 ₽/лист</li>
                <?php endif; ?>
            </ul>
        </div>

        <input type="hidden" name="calcType" value="<?= $calcType ?>">
        <input type="hidden" name="sessid" value="<?= bitrix_sessid() ?>">

        <button id="calcBtn" type="button" class="calc-button">Рассчитать стоимость</button>
        
        <div id="calcResult" class="calc-result"></div>
        
        <div class="calc-spacer"></div>
    </form>

    <div class="calc-thanks">
        <p>Спасибо, что Вы с нами!</p>
    </div>
</div>

<style>
.layout-info {
    background: #e3f2fd;
    border: 1px solid #2196f3;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 25px;
    color: #1565c0;
}

.layout-info h3 {
    margin: 0 0 15px 0;
    color: #0d47a1;
    font-size: 18px;
}

.layout-info p {
    margin: 8px 0;
    font-size: 14px;
}

.sheet-preview {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 15px;
    margin-top: 10px;
    font-size: 14px;
    color: #495057;
}

.sheet-preview strong {
    color: #007bff;
}

.pricing-info {
    background: #fff3e0;
    border: 1px solid #ff9800;
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
    color: #ef6c00;
}

.pricing-info h4 {
    margin: 0 0 15px 0;
    color: #e65100;
    font-size: 16px;
}

.pricing-info ul {
    margin: 0;
    padding-left: 20px;
}

.pricing-info li {
    margin: 8px 0;
    font-size: 14px;
}

.fee-highlight {
    background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%);
    border: 1px solid #ff9800;
    border-radius: 6px;
    padding: 15px;
    margin: 15px 0;
    color: #ef6c00;
}

.fee-breakdown {
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 20px;
    margin: 15px 0;
}

.fee-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #f5f5f5;
}

.fee-item:last-child {
    border-bottom: none;
    font-weight: bold;
    font-size: 16px;
    color: #2e7d32;
    margin-top: 10px;
    padding-top: 15px;
    border-top: 2px solid #4caf50;
}

@media (max-width: 768px) {
    .layout-info {
        padding: 15px;
        margin-bottom: 20px;
    }
    
    .layout-info h3 {
        font-size: 16px;
    }
    
    .pricing-info {
        padding: 15px;
    }
}
</style>

<script>
// Блокировка внешних ошибок
window.addEventListener('error', function(e) {
    if (e.message && (
        e.message.includes('Cannot set properties of null') || 
        e.message.includes('Cannot read properties of null') ||
        e.message.includes('recaptcha') ||
        e.message.includes('mail.ru') ||
        e.message.includes('top-fwz1') ||
        e.message.includes('code.js')
    )) {
        e.preventDefault();
        e.stopPropagation();
        return true;
    }
});

window.addEventListener('unhandledrejection', function(e) {
    if (e.reason === null || (e.reason && e.reason.toString().includes('recaptcha'))) {
        e.preventDefault();
        return true;
    }
});

// Конфигурация для калькулятора дорхендеров
const calcConfig = {
    type: '<?= $calcType ?>',
    features: <?= json_encode($features) ?>,
    component: 'my:print.calc',
    itemsPerSheet: <?= $itemsPerSheet ?>
};

// Элементы формы
const quantityInput = document.getElementById('quantity');
const sheetsCountSpan = document.getElementById('sheetsCount');
const itemsCountSpan = document.getElementById('itemsCount');

// Обновление предварительного расчета
function updateSheetPreview() {
    const quantity = parseInt(quantityInput.value) || 0;
    const sheets = Math.ceil(quantity / calcConfig.itemsPerSheet);
    
    sheetsCountSpan.textContent = `Листов А3: ${sheets}`;
    itemsCountSpan.textContent = `Штук: ${quantity}`;
    
    // Проверка кратности
    if (quantity > 0 && quantity % calcConfig.itemsPerSheet !== 0) {
        quantityInput.style.borderColor = '#dc3545';
        quantityInput.style.backgroundColor = '#fff5f5';
    } else {
        quantityInput.style.borderColor = '#e9ecef';
        quantityInput.style.backgroundColor = '#fff';
    }
}

// Добавляем обработчик для обновления предварительного расчета
quantityInput.addEventListener('input', updateSheetPreview);

// Инициализируем предварительный расчет
updateSheetPreview();

// Функция ожидания BX
function waitForBX(callback, fallbackCallback, timeout = 3000) {
    const startTime = Date.now();
    
    function checkBX() {
        if (typeof BX !== 'undefined' && BX.ajax) {
            callback();
        } else if (Date.now() - startTime < timeout) {
            setTimeout(checkBX, 50);
        } else {
            fallbackCallback();
        }
    }
    checkBX();
}

// Инициализация с BX
function initWithBX() {
    const form = document.getElementById(calcConfig.type + 'CalcForm');
    const resultDiv = document.getElementById('calcResult');
    const calcBtn = document.getElementById('calcBtn');
    
    if (!form || !resultDiv || !calcBtn) {
        console.error('Элементы формы не найдены');
        return;
    }

    calcBtn.addEventListener('click', function() {
        const data = collectFormData(form);
        data.calcType = calcConfig.type;
        
        // Проверяем кратность
        const quantity = parseInt(data.quantity) || 0;
        if (quantity % calcConfig.itemsPerSheet !== 0) {
            resultDiv.innerHTML = '<div class="result-error">Количество должно быть кратно ' + calcConfig.itemsPerSheet + '</div>';
            return;
        }
        
        resultDiv.innerHTML = '<div class="loading">Выполняется расчет дорхендеров...</div>';

        BX.ajax.runComponentAction(calcConfig.component, 'calc', {
            mode: 'class',
            data: data
        }).then(function(response) {
            handleResponse(response, resultDiv);
        }).catch(function(error) {
            resultDiv.innerHTML = '<div class="result-error">Ошибка соединения: ' + 
                (error.message || 'Неизвестная ошибка') + '</div>';
        });
    });
}

// Запасной вариант без BX
function initWithoutBX() {
    const form = document.getElementById(calcConfig.type + 'CalcForm');
    const resultDiv = document.getElementById('calcResult');
    const calcBtn = document.getElementById('calcBtn');
    
    if (!form || !resultDiv || !calcBtn) {
        console.error('Элементы формы не найдены');
        return;
    }

    calcBtn.addEventListener('click', function() {
        const data = collectFormData(form);
        data.calcType = calcConfig.type;
        
        // Проверяем кратность
        const quantity = parseInt(data.quantity) || 0;
        if (quantity % calcConfig.itemsPerSheet !== 0) {
            resultDiv.innerHTML = '<div class="result-error">Количество должно быть кратно ' + calcConfig.itemsPerSheet + '</div>';
            return;
        }
        
        resultDiv.innerHTML = '<div class="loading">Выполняется расчет дорхендеров...</div>';

        fetch('/bitrix/services/main/ajax.php?c=' + calcConfig.component + '&action=calc&mode=class', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams(data)
        })
        .then(response => response.json())
        .then(response => {
            handleResponse(response, resultDiv);
        })
        .catch(error => {
            resultDiv.innerHTML = '<div class="result-error">Ошибка соединения: ' + error.message + '</div>';
        });
    });
}

// Обработка ответа сервера
function handleResponse(response, resultDiv) {
    if (response && response.data) {
        if (response.data.error) {
            resultDiv.innerHTML = '<div class="result-error">Ошибка: ' + response.data.error + '</div>';
        } else {
            displayDoorhangerResult(response.data, resultDiv);
        }
    } else {
        resultDiv.innerHTML = '<div class="result-error">Некорректный ответ сервера</div>';
    }
}

// Отображение результата дорхендеров
function displayDoorhangerResult(result, resultDiv) {
    const totalPrice = Math.round((result.totalPrice || 0) * 10) / 10;
    const basePrice = Math.round((result.basePrice || 0) * 10) / 10;
    const fee = result.digital_fee || result.offset_fee || 0;
    
    let html = '<div class="result-success">';
    html += '<h3 class="result-title">Результат расчета дорхендеров</h3>';
    html += '<div class="result-price">' + totalPrice + ' <small>₽</small></div>';
    
    // Информация о листах
    if (result.a3Sheets) {
        html += '<div class="layout-info">';
        html += '<h4>Раскладка:</h4>';
        html += '<p><strong>Листов А3:</strong> ' + result.a3Sheets + '</p>';
        html += '<p><strong>Штук на листе:</strong> ' + calcConfig.itemsPerSheet + '</p>';
        html += '<p><strong>Общее количество:</strong> ' + (result.quantity || 0) + ' шт</p>';
        html += '</div>';
    }
    
    // Детализация стоимости
    html += '<div class="fee-breakdown">';
    html += '<h4>Детализация стоимости:</h4>';
    
    html += '<div class="fee-item">';
    html += '<span>Базовая стоимость:</span>';
    html += '<span>' + basePrice + ' ₽</span>';
    html += '</div>';
    
    if (fee > 0) {
        let feeDescription = '';
        if (result.digital_fee) {
            feeDescription = 'Наценка за цифровую печать';
        } else if (result.offset_fee) {
            if (result.a3Sheets > 1000) {
                feeDescription = `Наценка за офсет (${result.a3Sheets} листов × 3.5 ₽)`;
            } else {
                feeDescription = 'Наценка за офсет (фиксированная)';
            }
        }
        
        html += '<div class="fee-item">';
        html += '<span>' + feeDescription + ':</span>';
        html += '<span>' + Math.round(fee * 10) / 10 + ' ₽</span>';
        html += '</div>';
    }
    
    html += '<div class="fee-item">';
    html += '<span>Итого:</span>';
    html += '<span>' + totalPrice + ' ₽</span>';
    html += '</div>';
    
    html += '</div>';
    
    // Тип печати
    if (result.printingType) {
        html += '<div class="fee-highlight">';
        html += '<strong>Тип печати:</strong> ' + result.printingType;
        html += '</div>';
    }
    
    html += '<details class="result-details">';
    html += '<summary class="result-summary">Техническая информация</summary>';
    html += '<div class="result-details-content">';
    html += '<ul>';
    
    if (result.baseA3Sheets) html += '<li>Базовых листов A3: ' + result.baseA3Sheets + '</li>';
    if (result.printingCost) html += '<li>Стоимость печати: ' + Math.round(result.printingCost * 10) / 10 + ' ₽</li>';
    if (result.paperCost) html += '<li>Стоимость бумаги: ' + Math.round(result.paperCost * 10) / 10 + ' ₽</li>';
    if (result.plateCost && result.plateCost > 0) html += '<li>Стоимость пластин: ' + Math.round(result.plateCost * 10) / 10 + ' ₽</li>';
    
    html += '</ul>';
    html += '</div>';
    html += '</details>';
    html += '</div>';
    
    resultDiv.innerHTML = html;
}

// Сбор данных формы для дорхендеров
function collectFormData(form) {
    const formData = new FormData(form);
    const data = {};
    
    // Собираем все поля формы
    for (let [key, value] of formData.entries()) {
        data[key] = value;
    }

    return data;
}

// Запуск инициализации
document.addEventListener('DOMContentLoaded', function() {
    waitForBX(initWithBX, initWithoutBX, 3000);
});
</script>