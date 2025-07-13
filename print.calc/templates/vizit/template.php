<?php
/** Шаблон калькулятора визиток */
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

// Подключаем общие стили
$this->addExternalCss($templateFolder.'/../.default/style.css');
// Подключаем специфичные стили для визиток (если есть)
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
$printTypes = $arResult['print_types'] ?? [];
$digitalRange = $arResult['digital_range'] ?? [];
$offsetRange = $arResult['offset_range'] ?? [];
$sideTypes = $arResult['side_types'] ?? [];
?>

<div class="calc-container">
    <!-- Информационный блок -->
    <div class="calc-disclaimer">
        <p>
            Данные, полученные при расчете на калькуляторе – являются ориентировочными в связи с регулярным изменением стоимости материалов.<br>
            Конечную стоимость заказа уточняйте у менеджера: <a href="tel:+78462060068">+7 (846) 206-00-68</a><br>
            <strong>Визитки:</strong> <?= $arResult['info_text'] ?? '' ?><br>
            Спасибо за понимание!
        </p>
    </div>
    
    <h2><?= $arResult['DESCRIPTION'] ?? 'Калькулятор печати визиток' ?></h2>
    
    <form id="<?= $calcType ?>CalcForm" class="calc-form">
        
        <!-- Тип печати -->
        <div class="form-group">
            <label class="form-label" for="printType">Тип печати:</label>
            <select name="printType" id="printType" class="form-control" required>
                <option value="digital">Цифровая печать (100-999 шт)</option>
                <option value="offset">Офсетная печать (от 1000 шт)</option>
            </select>
        </div>

        <!-- Цифровая печать -->
        <div class="form-group" id="digitalGroup">
            <label class="form-label" for="digitalQuantity">Тираж (цифровая печать):</label>
            <input name="digitalQuantity" 
                   id="digitalQuantity" 
                   type="number" 
                   class="form-control" 
                   min="<?= $digitalRange['min'] ?? 100 ?>" 
                   max="<?= $digitalRange['max'] ?? 999 ?>" 
                   value="<?= $arResult['DEFAULT_QUANTITY'] ?? 500 ?>" 
                   placeholder="Введите количество (100-999)"
                   required>
            <small class="text-muted">Для цифровой печати: от <?= $digitalRange['min'] ?? 100 ?> до <?= $digitalRange['max'] ?? 999 ?> штук</small>
            
            <!-- Тип печати для цифровой -->
            <div style="margin-top: 15px;">
                <label class="form-label">Печать:</label>
                <div class="radio-group">
                    <label class="radio-label">
                        <input type="radio" name="sideType" value="single" checked> 
                        Односторонняя (4+0)
                    </label>
                    <label class="radio-label">
                        <input type="radio" name="sideType" value="double"> 
                        Двусторонняя (4+4)
                    </label>
                </div>
            </div>
        </div>

        <!-- Офсетная печать -->
        <div class="form-group" id="offsetGroup" style="display: none;">
            <label class="form-label" for="offsetQuantity">Тираж (офсетная печать):</label>
            <select name="offsetQuantity" id="offsetQuantity" class="form-control">
                <?php if (!empty($offsetRange['available'])): ?>
                    <?php foreach ($offsetRange['available'] as $qty): ?>
                        <option value="<?= $qty ?>"><?= number_format($qty, 0, '', ' ') ?> шт</option>
                    <?php endforeach; ?>
                <?php else: ?>
                    <?php for ($i = 1000; $i <= 12000; $i += 1000): ?>
                        <option value="<?= $i ?>"><?= number_format($i, 0, '', ' ') ?> шт</option>
                    <?php endfor; ?>
                <?php endif; ?>
            </select>
            <small class="text-muted">Для офсетной печати: от 1000 до 12000 штук (кратно 1000)</small>
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

// Конфигурация для калькулятора визиток
const calcConfig = {
    type: '<?= $calcType ?>',
    features: <?= json_encode($features) ?>,
    component: 'my:print.calc'
};

// Управление отображением полей в зависимости от типа печати
const printTypeSelect = document.getElementById('printType');
const digitalGroup = document.getElementById('digitalGroup');
const offsetGroup = document.getElementById('offsetGroup');
const digitalQuantity = document.getElementById('digitalQuantity');
const offsetQuantity = document.getElementById('offsetQuantity');

function updateFormDisplay() {
    const isOffset = printTypeSelect.value === 'offset';
    
    if (isOffset) {
        digitalGroup.style.display = 'none';
        offsetGroup.style.display = 'block';
        digitalQuantity.required = false;
        offsetQuantity.required = true;
    } else {
        digitalGroup.style.display = 'block';
        offsetGroup.style.display = 'none';
        digitalQuantity.required = true;
        offsetQuantity.required = false;
    }
}

printTypeSelect.addEventListener('change', updateFormDisplay);
updateFormDisplay(); // Инициализация

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
        
        resultDiv.innerHTML = '<div class="loading">Выполняется расчет визиток...</div>';

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
        
        resultDiv.innerHTML = '<div class="loading">Выполняется расчет визиток...</div>';

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
            displayVizitResult(response.data, resultDiv);
        }
    } else {
        resultDiv.innerHTML = '<div class="result-error">Некорректный ответ сервера</div>';
    }
}

// Отображение результата визиток
function displayVizitResult(result, resultDiv) {
    const totalPrice = Math.round((result.totalPrice || 0) * 10) / 10;
    
    let html = '<div class="result-success">';
    html += '<h3 class="result-title">Результат расчета визиток</h3>';
    html += '<div class="result-price">' + totalPrice + ' <small>₽</small></div>';
    
    // Информация о типе печати
    if (result.printType) {
        const isDigital = result.printType === 'Цифровая';
        const color = isDigital ? '#007bff' : '#28a745';
        const bgColor = isDigital ? '#f8f9ff' : '#f8fff8';
        
        html += '<div style="color: ' + color + '; background: ' + bgColor + '; padding: 10px; border-radius: 6px; border-left: 4px solid ' + color + '; margin-bottom: 15px;">';
        html += '<strong>Тип печати:</strong> ' + result.printType;
        if (isDigital) {
            html += '<br><small>Быстрая печать малых тиражей</small>';
        } else {
            html += '<br><small>Экономичная печать больших тиражей</small>';
        }
        html += '</div>';
    }
    
    // Количество
    if (result.quantity) {
        html += '<p><strong>Количество:</strong> ' + number_format(result.quantity, 0, '', ' ') + ' шт</p>';
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
    
    html += '</ul>';
    html += '</div>';
    html += '</details>';
    html += '</div>';
    
    resultDiv.innerHTML = html;
}

// Сбор данных формы для визиток
function collectFormData(form) {
    const formData = new FormData(form);
    const data = {};
    
    // Собираем основные данные
    const printType = form.querySelector('select[name="printType"]').value;
    data.printType = printType;
    
    // В зависимости от типа печати собираем количество
    if (printType === 'offset') {
        data.quantity = parseInt(form.querySelector('select[name="offsetQuantity"]').value);
        data.sideType = 'single'; // Для офсета не важно
    } else {
        data.quantity = parseInt(form.querySelector('input[name="digitalQuantity"]').value);
        const sideTypeRadio = form.querySelector('input[name="sideType"]:checked');
        data.sideType = sideTypeRadio ? sideTypeRadio.value : 'single';
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