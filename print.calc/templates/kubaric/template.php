<?php
/** Шаблон калькулятора кубариков */
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

// Подключаем общие стили
$this->addExternalCss($templateFolder.'/../.default/style.css');
// Подключаем специфичные стили для кубариков (если есть)
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
$sheetsPerPackOptions = $arResult['sheets_per_pack_options'] ?? [];
$printTypes = $arResult['print_types'] ?? [];
?>

<div class="calc-container">
    <!-- Информационный блок -->
    <div class="calc-disclaimer">
        <p>
            Данные, полученные при расчете на калькуляторе – являются ориентировочными в связи с регулярным изменением стоимости материалов.<br>
            Конечную стоимость заказа уточняйте у менеджера: <a href="tel:+78462060068">+7 (846) 206-00-68</a><br>
            <strong>Кубарики:</strong> <?= $arResult['format_info'] ?? '' ?><br>
            <?= $arResult['paper_info'] ?? '' ?><br>
            <?= $arResult['multiplier_info'] ?? '' ?><br>
            Спасибо за понимание!
        </p>
    </div>
    
    <h2><?= $arResult['DESCRIPTION'] ?? 'Калькулятор кубариков' ?></h2>
    
    <form id="<?= $calcType ?>CalcForm" class="calc-form">
        
        <!-- Листов в пачке -->
        <div class="form-group">
            <label class="form-label" for="sheetsPerPack">Листов в пачке:</label>
            <select name="sheetsPerPack" id="sheetsPerPack" class="form-control" required>
                <?php if (!empty($sheetsPerPackOptions)): ?>
                    <?php foreach ($sheetsPerPackOptions as $count): ?>
                        <option value="<?= $count ?>" <?= $count == 100 ? 'selected' : '' ?>><?= $count ?> листов</option>
                    <?php endforeach; ?>
                <?php else: ?>
                    <option value="100" selected>100 листов</option>
                    <option value="300">300 листов</option>
                    <option value="500">500 листов</option>
                    <option value="900">900 листов</option>
                <?php endif; ?>
            </select>
            <small class="text-muted">Стандартные варианты упаковки кубариков</small>
        </div>

        <!-- Количество пачек -->
        <div class="form-group">
            <label class="form-label" for="packsCount">Количество пачек:</label>
            <input name="packsCount" 
                   id="packsCount" 
                   type="number" 
                   class="form-control" 
                   min="1" 
                   value="1" 
                   placeholder="Введите количество пачек"
                   required>
            <small class="text-muted">Общее количество листов будет рассчитано автоматически</small>
        </div>

        <!-- Тип печати -->
        <div class="form-group">
            <label class="form-label" for="printType">Тип печати:</label>
            <select name="printType" id="printType" class="form-control" required>
                <?php if (!empty($printTypes)): ?>
                    <?php foreach ($printTypes as $key => $name): ?>
                        <option value="<?= htmlspecialchars($key) ?>" <?= $key == '4+0' ? 'selected' : '' ?>><?= htmlspecialchars($name) ?></option>
                    <?php endforeach; ?>
                <?php else: ?>
                    <option value="1+0">1+0 (Односторонняя ч/б)</option>
                    <option value="1+1">1+1 (Двусторонняя ч/б)</option>
                    <option value="4+0" selected>4+0 (Цветная с одной стороны)</option>
                    <option value="4+4">4+4 (Цветная с двух сторон)</option>
                <?php endif; ?>
            </select>
        </div>

        <!-- Предварительный расчет -->
        <div class="form-group">
            <div id="totalPreview" class="total-preview">
                <strong>Общее количество листов:</strong> <span id="totalSheets">100</span> шт<br>
                <strong>Формат:</strong> 9×9 см<br>
                <strong>Плотность:</strong> 80 г/м²
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
.total-preview {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 15px;
    margin-top: 10px;
    font-size: 14px;
    color: #495057;
}

.total-preview strong {
    color: #007bff;
}

.kubaric-info {
    background: #e3f2fd;
    border: 1px solid #2196f3;
    border-radius: 6px;
    padding: 15px;
    margin: 20px 0;
    color: #1565c0;
}

.kubaric-info h4 {
    margin: 0 0 10px 0;
    color: #0d47a1;
}

.price-breakdown {
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 20px;
    margin: 15px 0;
}

.price-step {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #f5f5f5;
}

.price-step:last-child {
    border-bottom: none;
    font-weight: bold;
    font-size: 16px;
    color: #2e7d32;
}

.multiplier-highlight {
    background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%);
    border: 1px solid #ff9800;
    border-radius: 6px;
    padding: 10px;
    margin: 10px 0;
    color: #ef6c00;
    font-weight: 600;
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

// Конфигурация для калькулятора кубариков
const calcConfig = {
    type: '<?= $calcType ?>',
    features: <?= json_encode($features) ?>,
    component: 'my:print.calc'
};

// Элементы формы
const sheetsPerPackSelect = document.getElementById('sheetsPerPack');
const packsCountInput = document.getElementById('packsCount');
const totalSheetsSpan = document.getElementById('totalSheets');

// Обновление предварительного расчета
function updateTotalPreview() {
    const sheetsPerPack = parseInt(sheetsPerPackSelect.value) || 0;
    const packsCount = parseInt(packsCountInput.value) || 0;
    const totalSheets = sheetsPerPack * packsCount;
    
    totalSheetsSpan.textContent = totalSheets.toLocaleString();
}

// Добавляем обработчики для обновления предварительного расчета
sheetsPerPackSelect.addEventListener('change', updateTotalPreview);
packsCountInput.addEventListener('input', updateTotalPreview);

// Инициализируем предварительный расчет
updateTotalPreview();

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
        
        resultDiv.innerHTML = '<div class="loading">Выполняется расчет кубариков...</div>';

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
        
        resultDiv.innerHTML = '<div class="loading">Выполняется расчет кубариков...</div>';

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
            displayKubaricResult(response.data, resultDiv);
        }
    } else {
        resultDiv.innerHTML = '<div class="result-error">Некорректный ответ сервера</div>';
    }
}

// Отображение результата кубариков
function displayKubaricResult(result, resultDiv) {
    const finalPrice = Math.round((result.finalPrice || 0) * 10) / 10;
    const basePrice = Math.round((result.basePrice || 0) * 10) / 10;
    const multiplier = result.multiplier || 1.3;
    
    let html = '<div class="result-success">';
    html += '<h3 class="result-title">Результат расчета кубариков</h3>';
    html += '<div class="result-price">' + finalPrice + ' <small>₽</small></div>';
    
    // Информация о кубариках
    html += '<div class="kubaric-info">';
    html += '<h4>Параметры заказа:</h4>';
    html += '<p><strong>Формат:</strong> 9×9 см</p>';
    html += '<p><strong>Плотность бумаги:</strong> 80 г/м²</p>';
    if (result.sheetsPerPack && result.packsCount) {
        html += '<p><strong>Упаковка:</strong> ' + result.packsCount + ' пачек по ' + result.sheetsPerPack + ' листов</p>';
    }
    if (result.totalSheets) {
        html += '<p><strong>Общее количество:</strong> ' + result.totalSheets.toLocaleString() + ' листов</p>';
    }
    html += '</div>';
    
    // Детализация расчета
    html += '<div class="price-breakdown">';
    html += '<h4>Детализация стоимости:</h4>';
    
    if (result.basePrice) {
        html += '<div class="price-step">';
        html += '<span>Базовая стоимость производства:</span>';
        html += '<span>' + basePrice + ' ₽</span>';
        html += '</div>';
    }
    
    html += '<div class="price-step">';
    html += '<span>Применяем коэффициент × ' + multiplier + ':</span>';
    html += '<span>' + basePrice + ' × ' + multiplier + '</span>';
    html += '</div>';
    
    html += '<div class="price-step">';
    html += '<span>Итоговая стоимость:</span>';
    html += '<span>' + finalPrice + ' ₽</span>';
    html += '</div>';
    
    html += '</div>';
    
    // Дополнительная информация
    if (result.printingType) {
        html += '<div class="multiplier-highlight">';
        html += '<strong>Тип печати:</strong> ' + result.printingType;
        html += '</div>';
    }
    
    html += '<details class="result-details">';
    html += '<summary class="result-summary">Техническая информация</summary>';
    html += '<div class="result-details-content">';
    html += '<ul>';
    
    if (result.baseA3Sheets) html += '<li>Эквивалент листов A3: ' + result.baseA3Sheets + '</li>';
    if (result.printingCost) html += '<li>Стоимость печати: ' + Math.round(result.printingCost * 10) / 10 + ' ₽</li>';
    if (result.paperCost) html += '<li>Стоимость бумаги: ' + Math.round(result.paperCost * 10) / 10 + ' ₽</li>';
    if (result.plateCost && result.plateCost > 0) html += '<li>Стоимость пластин: ' + Math.round(result.plateCost * 10) / 10 + ' ₽</li>';
    
    html += '</ul>';
    html += '</div>';
    html += '</details>';
    html += '</div>';
    
    resultDiv.innerHTML = html;
}

// Сбор данных формы для кубариков
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