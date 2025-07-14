<?php
/** Шаблон калькулятора холстов */
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
?>

<div class="calc-container">
    <!-- Информационный блок -->
    <div class="calc-disclaimer">
        <p>
            Данные, полученные при расчете на калькуляторе – являются ориентировочными в связи с регулярным изменением стоимости материалов.<br>
            Конечную стоимость заказа уточняйте у менеджера: <a href="tel:+78462060068">+7 (846) 206-00-68</a><br>
            <strong>Холсты:</strong> <?= $arResult['dimension_info'] ?? 'Размеры указываются в сантиметрах' ?><br>
            <?= $arResult['rounding_info'] ?? 'Размеры до 100 см округляются до стандартных значений' ?><br>
            Спасибо за понимание!
        </p>
    </div>
    
    <h2><?= $arResult['DESCRIPTION'] ?? 'Калькулятор печати на холсте' ?></h2>
    
    <form id="<?= $calcType ?>CalcForm" class="calc-form">
        
        <!-- Размеры холста -->
        <div class="form-section">
            <h3 class="section-title">Размеры холста</h3>
            
            <div class="form-group">
                <label class="form-label" for="width">Ширина (см):</label>
                <input name="width" 
                       id="width" 
                       type="number" 
                       class="form-control" 
                       min="1"
                       step="1"
                       value="30" 
                       placeholder="Например: 30"
                       required>
                <small class="text-muted">Размеры указываются в сантиметрах</small>
            </div>

            <div class="form-group">
                <label class="form-label" for="height">Высота (см):</label>
                <input name="height" 
                       id="height" 
                       type="number" 
                       class="form-control" 
                       min="1"
                       step="1"
                       value="30" 
                       placeholder="Например: 40"
                       required>
                <small class="text-muted">Размеры указываются в сантиметрах</small>
            </div>
        </div>

        <!-- Опции -->
        <div class="form-section">
            <h3 class="section-title">Дополнительные опции</h3>
            
            <div class="form-group">
                <div class="checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="includePodramnik" id="includePodramnik"> 
                        Включить подрамник
                    </label>
                </div>
                <small class="text-muted"><?= $arResult['podramnik_info'] ?? 'Подрамник можно добавить к любому размеру холста' ?></small>
            </div>
        </div>

        <!-- Предварительная информация -->
        <div class="form-group">
            <div id="sizePreview" class="size-preview">
                <strong>Предварительный расчет:</strong><br>
                <span id="previewText">Размер: 30×30 см (стандартный)</span><br>
                <span id="areaText" style="display: none;">Площадь: 0.09 м²</span>
            </div>
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
.form-section {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.section-title {
    margin: 0 0 15px 0;
    color: #495057;
    font-size: 18px;
    font-weight: 600;
    padding-bottom: 8px;
    border-bottom: 2px solid #007bff;
}

.size-preview {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 15px;
    margin-top: 10px;
    font-size: 14px;
    color: #495057;
}

.size-preview strong {
    color: #007bff;
}

.canvas-info {
    background: #e3f2fd;
    border: 1px solid #2196f3;
    border-radius: 6px;
    padding: 15px;
    margin: 15px 0;
    color: #1565c0;
}

.rounded-info {
    background: #fff3e0;
    border: 1px solid #ff9800;
    border-radius: 6px;
    padding: 10px;
    margin: 10px 0;
    color: #ef6c00;
    font-size: 14px;
}

.large-size-info {
    background: #f3e5f5;
    border: 1px solid #9c27b0;
    border-radius: 6px;
    padding: 10px;
    margin: 10px 0;
    color: #7b1fa2;
    font-size: 14px;
}

@media (max-width: 768px) {
    .form-section {
        padding: 15px;
        margin-bottom: 15px;
    }
    
    .section-title {
        font-size: 16px;
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

// Конфигурация для калькулятора холстов
const calcConfig = {
    type: '<?= $calcType ?>',
    features: <?= json_encode($features) ?>,
    component: 'my:print.calc'
};

// Стандартные размеры
const standardSizes = <?= json_encode($arResult['standard_sizes'] ?? [30, 40, 50, 60, 70, 80, 90, 100]) ?>;
const maxStandardSize = <?= $arResult['max_standard_size'] ?? 100 ?>;

// Элементы формы
const widthInput = document.getElementById('width');
const heightInput = document.getElementById('height');
const previewText = document.getElementById('previewText');
const areaText = document.getElementById('areaText');

// Функция округления до ближайшего стандартного размера
function ceilToNearest(value, allowed) {
    const maxAllowed = Math.max(...allowed);
    
    if (value > maxAllowed) {
        return value;
    }
    
    allowed.sort((a, b) => a - b);
    
    for (let size of allowed) {
        if (size >= value) {
            return size;
        }
    }
    
    return value;
}

// Обновление предварительного просмотра
function updateSizePreview() {
    const width = parseFloat(widthInput.value) || 0;
    const height = parseFloat(heightInput.value) || 0;
    
    if (width <= 0 || height <= 0) {
        previewText.textContent = 'Укажите размеры холста';
        areaText.style.display = 'none';
        return;
    }
    
    if (width > maxStandardSize || height > maxStandardSize) {
        // Большой размер - расчет по площади
        const area = (width * height) / 10000; // см² в м²
        previewText.textContent = `Размер: ${width}×${height} см (большой)`;
        areaText.textContent = `Площадь: ${area.toFixed(4)} м²`;
        areaText.style.display = 'block';
    } else {
        // Стандартный размер - округление
        const roundedWidth = ceilToNearest(width, standardSizes);
        const roundedHeight = ceilToNearest(height, standardSizes);
        
        if (roundedWidth !== width || roundedHeight !== height) {
            previewText.textContent = `Размер: ${width}×${height} см → ${roundedWidth}×${roundedHeight} см (округлено)`;
        } else {
            previewText.textContent = `Размер: ${width}×${height} см (стандартный)`;
        }
        areaText.style.display = 'none';
    }
}

// Добавляем обработчики для обновления предварительного просмотра
widthInput.addEventListener('input', updateSizePreview);
heightInput.addEventListener('input', updateSizePreview);

// Инициализируем предварительный просмотр
updateSizePreview();

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
        
        resultDiv.innerHTML = '<div class="loading">Выполняется расчет холста...</div>';

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
        
        resultDiv.innerHTML = '<div class="loading">Выполняется расчет холста...</div>';

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
            displayCanvasResult(response.data, resultDiv);
        }
    } else {
        resultDiv.innerHTML = '<div class="result-error">Некорректный ответ сервера</div>';
    }
}

// Отображение результата холста
function displayCanvasResult(result, resultDiv) {
    const totalPrice = Math.round((result.totalPrice || 0) * 10) / 10;
    const canvasPrice = Math.round((result.canvasPrice || 0) * 10) / 10;
    const podramnikPrice = Math.round((result.podramnikPrice || 0) * 10) / 10;
    
    let html = '<div class="result-success">';
    html += '<h3 class="result-title">Результат расчета холста</h3>';
    html += '<div class="result-price">' + totalPrice + ' <small>₽</small></div>';
    
    // Информация о размере
    if (result.roundedWidth && result.roundedHeight) {
        const isLarge = result.roundedWidth > maxStandardSize || result.roundedHeight > maxStandardSize;
        const wasRounded = result.roundedWidth !== parseFloat(widthInput.value) || result.roundedHeight !== parseFloat(heightInput.value);
        
        if (isLarge) {
            html += '<div class="large-size-info">';
            html += '<strong>Большой размер:</strong> ' + result.roundedWidth + '×' + result.roundedHeight + ' см<br>';
            if (result.area) {
                html += 'Площадь: ' + Math.round(result.area * 10000) / 10000 + ' м²';
            }
            html += '</div>';
        } else if (wasRounded) {
            html += '<div class="rounded-info">';
            html += '<strong>Размер округлен:</strong> ' + parseFloat(widthInput.value) + '×' + parseFloat(heightInput.value) + ' см → ' + result.roundedWidth + '×' + result.roundedHeight + ' см';
            html += '</div>';
        } else {
            html += '<div class="canvas-info">';
            html += '<strong>Размер холста:</strong> ' + result.roundedWidth + '×' + result.roundedHeight + ' см';
            html += '</div>';
        }
    }
    
    // Детализация стоимости
    html += '<div class="price-breakdown">';
    html += '<h4>Детализация стоимости:</h4>';
    
    html += '<div class="price-item">';
    html += '<span>Печать на холсте:</span>';
    html += '<span>' + canvasPrice + ' ₽</span>';
    html += '</div>';
    
    if (podramnikPrice > 0) {
        html += '<div class="price-item">';
        html += '<span>Подрамник:</span>';
        html += '<span>' + podramnikPrice + ' ₽</span>';
        html += '</div>';
    }
    
    html += '<div class="price-item">';
    html += '<span>Итого:</span>';
    html += '<span>' + totalPrice + ' ₽</span>';
    html += '</div>';
    
    html += '</div>';
    
    html += '<details class="result-details">';
    html += '<summary class="result-summary">Техническая информация</summary>';
    html += '<div class="result-details-content">';
    html += '<ul>';
    
    if (result.roundedWidth && result.roundedHeight) {
        html += '<li>Финальный размер: ' + result.roundedWidth + '×' + result.roundedHeight + ' см</li>';
    }
    if (result.area) {
        html += '<li>Площадь: ' + Math.round(result.area * 10000) / 10000 + ' м²</li>';
    }
    html += '<li>Подрамник: ' + (podramnikPrice > 0 ? 'Включен' : 'Не включен') + '</li>';
    
    html += '</ul>';
    html += '</div>';
    html += '</details>';
    html += '</div>';
    
    resultDiv.innerHTML = html;
}

// Сбор данных формы для холстов
function collectFormData(form) {
    const formData = new FormData(form);
    const data = {};
    
    // Собираем все поля формы
    for (let [key, value] of formData.entries()) {
        data[key] = value;
    }
    
    // Добавляем чекбокс для подрамника
    const includePodramnik = form.querySelector('input[name="includePodramnik"]');
    if (includePodramnik) {
        data.includePodramnik = includePodramnik.checked;
    }

    return data;
}

// Запуск инициализации
document.addEventListener('DOMContentLoaded', function() {
    waitForBX(initWithBX, initWithoutBX, 3000);
});
</script>