<?php
/** Шаблон калькулятора наклеек */
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

// Подключаем общие стили
$this->addExternalCss($templateFolder.'/../.default/style.css');
// Подключаем специфичные стили для наклеек (если есть)
if (file_exists($templateFolder.'/style.css')) {
    $this->addExternalCss($templateFolder.'/style.css');
}

// Проверяем, что конфигурация загружена
if (!$arResult['CONFIG_LOADED']) {
    echo '<div class="result-error">Ошибка: Конфигурация калькулятора не загружена</div>';
    return;
}

// Принудительно подключаем основные скрипты Битрикса
CJSCore::Init(['ajax', 'window']);

$calcType = $arResult['CALC_TYPE'];
$features = $arResult['FEATURES'] ?? [];
$stickerTypes = $arResult['sticker_types'] ?? [];
?>

<div class="calc-container">
    <!-- Информационный блок -->
    <div class="calc-disclaimer">
        <p>
            Данные, полученные при расчете на калькуляторе – являются ориентировочными в связи с регулярным изменением стоимости материалов.<br>
            Конечную стоимость заказа уточняйте у менеджера: <a href="tel:+78462060068">+7 (846) 206-00-68</a><br>
            <strong>Наклейки:</strong> <?= $arResult['dimension_info'] ?? '' ?><br>
            <?= $arResult['area_info'] ?? '' ?><br>
            Спасибо за понимание!
        </p>
    </div>
    
    <h2><?= $arResult['DESCRIPTION'] ?? 'Калькулятор печати наклеек' ?></h2>
    
    <form id="<?= $calcType ?>CalcForm" class="calc-form">
        
        <!-- Размеры наклейки -->
        <div class="form-group">
            <label class="form-label" for="length">Длина наклейки (м):</label>
            <input name="length" 
                   id="length" 
                   type="number" 
                   class="form-control" 
                   min="0.001"
                   step="0.001"
                   value="0.1" 
                   placeholder="Например: 0.1 (= 10 см)"
                   required>
            <small class="text-muted">Указывайте размеры в метрах (0.1 м = 10 см)</small>
        </div>

        <div class="form-group">
            <label class="form-label" for="width">Ширина наклейки (м):</label>
            <input name="width" 
                   id="width" 
                   type="number" 
                   class="form-control" 
                   min="0.001"
                   step="0.001"
                   value="0.1" 
                   placeholder="Например: 0.1 (= 10 см)"
                   required>
            <small class="text-muted">Указывайте размеры в метрах (0.1 м = 10 см)</small>
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
            <small class="text-muted">Цена зависит от общей площади всех наклеек</small>
        </div>

        <!-- Тип наклейки -->
        <div class="form-group">
            <label class="form-label" for="stickerType">Тип наклейки:</label>
            <select name="stickerType" id="stickerType" class="form-control" required>
                <?php if (!empty($stickerTypes)): ?>
                    <?php foreach ($stickerTypes as $key => $name): ?>
                        <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($name) ?></option>
                    <?php endforeach; ?>
                <?php else: ?>
                    <option value="simple_print">Просто печать СМУК</option>
                    <option value="print_cut">Печать + контурная резка</option>
                    <option value="print_white">Печать смук + белый</option>
                    <option value="print_white_cut">Печать смук + белый + контурная резка</option>
                    <option value="print_white_varnish">Печать смук + белый + лак</option>
                    <option value="print_white_varnish_cut">Печать смук + белый + лак + контурная резка</option>
                    <option value="print_varnish">Печать смук+лак</option>
                    <option value="print_varnish_cut">Печать смук+лак+резка</option>
                <?php endif; ?>
            </select>
        </div>

        <!-- Предварительный расчет площади -->
        <div class="form-group">
            <div id="areaPreview" class="area-preview">
                <strong>Площадь одной наклейки:</strong> <span id="singleArea">0.01</span> м²<br>
                <strong>Общая площадь:</strong> <span id="totalArea">1</span> м²
            </div>
        </div>

        <input type="hidden" name="calcType" value="<?= $calcType ?>">
        <input type="hidden" name="sessid" value="<?= bitrix_sessid() ?>">

        <button id="calcBtn" type="button" class="calc-button">Рассчитать стоимость</button>
        
        <div id="calcResult" class="calc-result"></div>
        
        <!-- Отступ между результатом и ламинацией -->
        <div class="calc-spacer"></div>
    </form>

    <div class="calc-thanks">
        <p>Спасибо, что Вы с нами!</p>
    </div>
</div>

<style>
.area-preview {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 15px;
    margin-top: 10px;
    font-size: 14px;
    color: #495057;
}

.area-preview strong {
    color: #007bff;
}

.dimension-input {
    position: relative;
}

.dimension-input::after {
    content: 'м';
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
    pointer-events: none;
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

// Конфигурация для калькулятора наклеек
const calcConfig = {
    type: '<?= $calcType ?>',
    features: <?= json_encode($features) ?>,
    component: 'my:print.calc'
};

// Элементы формы
const lengthInput = document.getElementById('length');
const widthInput = document.getElementById('width');
const quantityInput = document.getElementById('quantity');
const singleAreaSpan = document.getElementById('singleArea');
const totalAreaSpan = document.getElementById('totalArea');

// Обновление предварительного расчета площади
function updateAreaPreview() {
    const length = parseFloat(lengthInput.value) || 0;
    const width = parseFloat(widthInput.value) || 0;
    const quantity = parseInt(quantityInput.value) || 0;
    
    const singleArea = length * width;
    const totalArea = singleArea * quantity;
    
    singleAreaSpan.textContent = singleArea.toFixed(4);
    totalAreaSpan.textContent = totalArea.toFixed(4);
}

// Добавляем обработчики для обновления предварительного расчета
lengthInput.addEventListener('input', updateAreaPreview);
widthInput.addEventListener('input', updateAreaPreview);
quantityInput.addEventListener('input', updateAreaPreview);

// Инициализируем предварительный расчет
updateAreaPreview();

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
        
        resultDiv.innerHTML = '<div class="loading">Выполняется расчет наклеек...</div>';

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
        
        resultDiv.innerHTML = '<div class="loading">Выполняется расчет наклеек...</div>';

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
            displayStickerResult(response.data, resultDiv);
        }
    } else {
        resultDiv.innerHTML = '<div class="result-error">Некорректный ответ сервера</div>';
    }
}

// Отображение результата наклеек
function displayStickerResult(result, resultDiv) {
    const totalPrice = Math.round((result.totalPrice || 0) * 10) / 10;
    
    let html = '<div class="result-success">';
    html += '<h3 class="result-title">Результат расчета наклеек</h3>';
    html += '<div class="result-price">' + totalPrice + ' <small>₽</small></div>';
    
    // Информация о типе наклейки
    if (result.stickerType) {
        const stickerTypeNames = {
            'simple_print': 'Просто печать СМУК',
            'print_cut': 'Печать + контурная резка',
            'print_white': 'Печать смук + белый',
            'print_white_cut': 'Печать смук + белый + контурная резка',
            'print_white_varnish': 'Печать смук + белый + лак',
            'print_white_varnish_cut': 'Печать смук + белый + лак + контурная резка',
            'print_varnish': 'Печать смук+лак',
            'print_varnish_cut': 'Печать смук+лак+резка'
        };
        
        const typeName = stickerTypeNames[result.stickerType] || result.stickerType;
        
        html += '<div style="color: #007bff; background: #f8f9ff; padding: 10px; border-radius: 6px; border-left: 4px solid #007bff; margin-bottom: 15px;">';
        html += '<strong>Тип наклейки:</strong> ' + typeName;
        html += '</div>';
    }
    
    html += '<details class="result-details">';
    html += '<summary class="result-summary">Подробности расчета</summary>';
    html += '<div class="result-details-content">';
    html += '<ul>';
    
    if (result.length && result.width) {
        html += '<li>Размер одной наклейки: ' + result.length + ' × ' + result.width + ' м</li>';
    }
    if (result.quantity) {
        html += '<li>Количество: ' + number_format(result.quantity, 0, '', ' ') + ' шт</li>';
    }
    if (result.areaPerSticker) {
        html += '<li>Площадь одной наклейки: ' + Math.round(result.areaPerSticker * 10000) / 10000 + ' м²</li>';
    }
    if (result.totalArea) {
        html += '<li>Общая площадь: ' + Math.round(result.totalArea * 10000) / 10000 + ' м²</li>';
    }
    if (result.pricePerM2) {
        html += '<li>Стоимость за м²: ' + Math.round(result.pricePerM2 * 10) / 10 + ' ₽</li>';
    }
    
    html += '</ul>';
    html += '</div>';
    html += '</details>';
    html += '</div>';
    
    resultDiv.innerHTML = html;
}

// Сбор данных формы для наклеек
function collectFormData(form) {
    const formData = new FormData(form);
    const data = {};
    
    // Собираем все поля формы
    for (let [key, value] of formData.entries()) {
        data[key] = value;
    }

    return data;
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

// Запуск инициализации
document.addEventListener('DOMContentLoaded', function() {
    waitForBX(initWithBX, initWithoutBX, 3000);
});
</script>