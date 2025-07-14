<?php
/** Шаблон калькулятора ПВХ стендов */
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

// Подключаем общие стили
$this->addExternalCss($templateFolder.'/../.default/style.css');
// Подключаем специфичные стили для стендов (если есть)
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
                <span id="pointsWarning" class="points-warning" style="display: none;">⚠️ Превышен лимит карманов!</span>
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

// Конфигурация для калькулятора стендов
const calcConfig = {
    type: '<?= $calcType ?>',
    features: <?= json_encode($features) ?>,
    component: 'my:print.calc'
};

// Элементы формы
const widthInput = document.getElementById('width');
const heightInput = document.getElementById('height');
const flatA4Input = document.getElementById('flatA4');
const flatA5Input = document.getElementById('flatA5');
const volumeA4Input = document.getElementById('volumeA4');
const volumeA5Input = document.getElementById('volumeA5');
const areaPreview = document.getElementById('areaPreview');
const pointsWarning = document.getElementById('pointsWarning');

// Обновление предварительного расчета
function updatePreview() {
    const width = parseFloat(widthInput.value) || 0;
    const height = parseFloat(heightInput.value) || 0;
    const flatA4 = parseInt(flatA4Input.value) || 0;
    const flatA5 = parseInt(flatA5Input.value) || 0;
    const volumeA4 = parseInt(volumeA4Input.value) || 0;
    const volumeA5 = parseInt(volumeA5Input.value) || 0;
    
    // Площадь в м²
    const area = (width * height) / 10000;
    areaPreview.textContent = area.toFixed(2);
    
    // Баллы карманов (А4 = 2 балла, А5 = 1 балл) - только для внутренних расчетов
    const usedPoints = (flatA4 + volumeA4) * 2 + (flatA5 + volumeA5) * 1;
    const maxPoints = Math.floor(area * 20); // 20 баллов на м²
    
    // Предупреждение о превышении лимита
    if (usedPoints > maxPoints) {
        pointsWarning.style.display = 'block';
    } else {
        pointsWarning.style.display = 'none';
    }
}

// Добавляем обработчики для обновления предварительного расчета
[widthInput, heightInput, flatA4Input, flatA5Input, volumeA4Input, volumeA5Input].forEach(input => {
    input.addEventListener('input', updatePreview);
});

// Инициализируем предварительный расчет
updatePreview();

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
        
        resultDiv.innerHTML = '<div class="loading">Выполняется расчет ПВХ стенда...</div>';

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
        
        resultDiv.innerHTML = '<div class="loading">Выполняется расчет ПВХ стенда...</div>';

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
            displayStendResult(response.data, resultDiv);
        }
    } else {
        resultDiv.innerHTML = '<div class="result-error">Некорректный ответ сервера</div>';
    }
}

// Отображение результата ПВХ стенда
function displayStendResult(result, resultDiv) {
    const totalPrice = Math.round((result.totalPrice || 0) * 10) / 10;
    
    let html = '<div class="result-success">';
    html += '<h3 class="result-title">Результат расчета ПВХ стенда</h3>';
    html += '<div class="result-price">' + totalPrice + ' <small>₽</small></div>';
    
    // Информация о типе ПВХ
    if (result.pvcType) {
        const pvcTypeNames = {
            '3mm': '3 мм',
            '5mm': '5 мм'
        };
        
        const typeName = pvcTypeNames[result.pvcType] || result.pvcType;
        
        html += '<div style="color: #007bff; background: #f8f9ff; padding: 10px; border-radius: 6px; border-left: 4px solid #007bff; margin-bottom: 15px;">';
        html += '<strong>Толщина ПВХ:</strong> ' + typeName;
        html += '</div>';
    }
    
    html += '<details class="result-details">';
    html += '<summary class="result-summary">Подробности расчета</summary>';
    html += '<div class="result-details-content">';
    html += '<ul>';
    
    if (result.width && result.height) {
        html += '<li>Размер стенда: ' + result.width + ' × ' + result.height + ' см</li>';
    }
    if (result.area) {
        html += '<li>Площадь стенда: ' + Math.round(result.area * 100) / 100 + ' м²</li>';
    }
    if (result.pvcCost) {
        html += '<li>Стоимость ПВХ: ' + Math.round(result.pvcCost * 10) / 10 + ' ₽</li>';
    }
    if (result.pocketsCost && result.pocketsCost > 0) {
        html += '<li>Стоимость карманов: ' + Math.round(result.pocketsCost * 10) / 10 + ' ₽</li>';
    }
    
    // Детали карманов
    if (result.pockets) {
        const pockets = result.pockets;
        if (pockets.flatA4 > 0) html += '<li>Плоских карманов А4: ' + pockets.flatA4 + '</li>';
        if (pockets.flatA5 > 0) html += '<li>Плоских карманов А5: ' + pockets.flatA5 + '</li>';
        if (pockets.volumeA4 > 0) html += '<li>Объемных карманов А4: ' + pockets.volumeA4 + '</li>';
        if (pockets.volumeA5 > 0) html += '<li>Объемных карманов А5: ' + pockets.volumeA5 + '</li>';
    }
    
    html += '</ul>';
    html += '</div>';
    html += '</details>';
    html += '</div>';
    
    resultDiv.innerHTML = html;
}

// Сбор данных формы для стендов
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