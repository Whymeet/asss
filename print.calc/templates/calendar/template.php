<?php
/** Шаблон калькулятора календарей */
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

// Подключаем общие стили
$this->addExternalCss($templateFolder.'/../.default/style.css');
// Подключаем специфичные стили для календарей (если есть)
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
$calendarTypes = $arResult['calendar_types'] ?? [];
$calendarInfo = $arResult['calendar_info'] ?? [];
$wallSizes = $arResult['wall_sizes'] ?? ['A4', 'A3'];
$printTypes = $arResult['print_types'] ?? [];
?>

<div class="calc-container">
    <!-- Информационный блок -->
    <div class="calc-disclaimer">
        <p>
            Данные, полученные при расчете на калькуляторе – являются ориентировочными в связи с регулярным изменением стоимости материалов.<br>
            Конечную стоимость заказа уточняйте у менеджера: <a href="tel:+78462060068">+7 (846) 206-00-68</a><br>
            <strong>Календари:</strong> <?= $arResult['assembly_info'] ?? 'Сборка включена в стоимость' ?><br>
            <?= $arResult['desktop_bigovka'] ?? '' ?><br>
            <?= $arResult['pocket_corners'] ?? '' ?><br>
            Спасибо за понимание!
        </p>
    </div>
    
    <h2><?= $arResult['DESCRIPTION'] ?? 'Калькулятор календарей' ?></h2>
    
    <form id="<?= $calcType ?>CalcForm" class="calc-form">
        
        <!-- Тип календаря -->
        <div class="form-group">
            <label class="form-label" for="calendarType">Тип календаря:</label>
            <select name="calendarType" id="calendarType" class="form-control" required>
                <?php foreach ($calendarTypes as $type => $name): ?>
                    <option value="<?= htmlspecialchars($type) ?>"><?= htmlspecialchars($name) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Размер (только для настенных) -->
        <div class="form-group" id="sizeGroup" style="display: none;">
            <label class="form-label" for="size">Размер:</label>
            <select name="size" id="size" class="form-control">
                <?php foreach ($wallSizes as $size): ?>
                    <option value="<?= htmlspecialchars($size) ?>"><?= htmlspecialchars($size) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Тип печати (скрыт для настольных) -->
        <div class="form-group" id="printTypeGroup">
            <label class="form-label">Тип печати:</label>
            <div class="radio-group">
                <label class="radio-label">
                    <input type="radio" name="printType" value="4+0" checked> 
                    4+0 (полноцветная печать с одной стороны)
                </label>
                <label class="radio-label">
                    <input type="radio" name="printType" value="4+4"> 
                    4+4 (полноцветная печать с двух сторон)
                </label>
            </div>
        </div>

        <!-- Тираж -->
        <div class="form-group">
            <label class="form-label" for="quantity">Тираж:</label>
            <input name="quantity" 
                   id="quantity" 
                   type="number" 
                   class="form-control" 
                   min="<?= $arResult['min_quantity'] ?? 1 ?>" 
                   max="<?= $arResult['max_quantity'] ?? 5000 ?>" 
                   value="<?= $arResult['default_quantity'] ?? 100 ?>" 
                   placeholder="Введите количество"
                   required>
        </div>

        <!-- Информационный блок о выбранном типе -->
        <div class="form-group">
            <div id="calendarTypeInfo" class="info-block">
                <p id="calendarTypeDescription"></p>
            </div>
        </div>

        <input type="hidden" name="calcType" value="<?= $calcType ?>">
        <input type="hidden" name="sessid" value="<?= bitrix_sessid() ?>">

        <button type="button" id="calcBtn" class="calc-button">Рассчитать стоимость</button>
        <div id="calcResult" class="calc-result"></div>
        <div class="calc-spacer"></div>
    </form>

    <div class="calc-thanks">
        <p>Спасибо, что Вы с нами!</p>
    </div>
</div>

<script>
// Блокируем внешние ошибки
window.addEventListener('error', function(e) {
    if (e.message && (
        e.message.includes('Cannot set properties of null') || 
        e.message.includes('Cannot read properties of null') ||
        e.message.includes('recaptcha') ||
        e.message.includes('mail.ru') ||
        e.message.includes('top-fwz1') ||
        e.message.includes('code.js')
    )) {
        console.log('Заблокирована внешняя ошибка:', e.message);
        e.preventDefault();
        e.stopPropagation();
        return true;
    }
});

window.addEventListener('unhandledrejection', function(e) {
    if (e.reason === null || (e.reason && e.reason.toString().includes('recaptcha'))) {
        console.log('Заблокирована ошибка Promise');
        e.preventDefault();
        return true;
    }
});

// Конфигурация для калькулятора календарей
const calcConfig = {
    type: '<?= $calcType ?>',
    features: <?= json_encode($features) ?>,
    component: 'my:print.calc'
};

// Информация о типах календарей
const calendarInfo = <?= json_encode($calendarInfo) ?>;

console.log('Конфигурация калькулятора:', calcConfig);

// Элементы формы
const calendarTypeSelect = document.getElementById('calendarType');
const sizeGroup = document.getElementById('sizeGroup');
const printTypeGroup = document.getElementById('printTypeGroup');
const calendarTypeDescription = document.getElementById('calendarTypeDescription');

// Обновление интерфейса при изменении типа календаря
function updateCalendarTypeInterface() {
    const selectedType = calendarTypeSelect.value;
    
    // Показываем/скрываем поля в зависимости от типа
    if (selectedType === 'wall') {
        // Настенный календарь - показываем размер
        sizeGroup.style.display = 'block';
    } else if (selectedType === 'desktop') {
        // Настольный календарь - скрываем размер
        sizeGroup.style.display = 'none';
    } else if (selectedType === 'pocket') {
        // Карманный календарь - скрываем размер
        sizeGroup.style.display = 'none';
    }
    
    // Всегда показываем выбор типа печати
    printTypeGroup.style.display = 'block';
    
    // Обновляем описание
    if (calendarInfo[selectedType]) {
        calendarTypeDescription.textContent = calendarInfo[selectedType];
    }
}

// Обработчик изменения типа календаря
calendarTypeSelect.addEventListener('change', updateCalendarTypeInterface);

// Функция ожидания BX
function waitForBX(callback, fallbackCallback, timeout = 3000) {
    const startTime = Date.now();
    
    function checkBX() {
        if (typeof BX !== 'undefined' && BX.ajax) {
            console.log('BX найден через', Date.now() - startTime, 'мс');
            callback();
        } else if (Date.now() - startTime < timeout) {
            setTimeout(checkBX, 50);
        } else {
            console.warn('BX не загрузился за', timeout, 'мс. Используем запасной вариант');
            fallbackCallback();
        }
    }
    
    checkBX();
}

// Основная инициализация с BX
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
        
        resultDiv.innerHTML = '<div class="loading">Выполняется расчет календаря...</div>';

        BX.ajax.runComponentAction(calcConfig.component, 'calc', {
            mode: 'class',
            data: data
        }).then(function(response) {
            handleResponse(response, resultDiv);
        }).catch(function(error) {
            console.error('Ошибка BX:', error);
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
        
        resultDiv.innerHTML = '<div class="loading">Выполняется расчет календаря...</div>';

        fetch('/bitrix/services/main/ajax.php?c=' + calcConfig.component + '&action=calc&mode=class', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams(data)
        })
        .then(response => {
            return response.json();
        })
        .then(response => {
            handleResponse(response, resultDiv);
        })
        .catch(error => {
            console.error('Ошибка fetch:', error);
            resultDiv.innerHTML = '<div class="result-error">Ошибка соединения: ' + 
                error.message + '</div>';
        });
    });
}

// Обработка ответа сервера
function handleResponse(response, resultDiv) {
    if (response && response.data) {
        if (response.data.error) {
            resultDiv.innerHTML = '<div class="result-error">Ошибка: ' + 
                response.data.error + '</div>';
        } else {
            displayCalendarResult(response.data, resultDiv);
        }
    } else {
        resultDiv.innerHTML = '<div class="result-error">Некорректный ответ сервера</div>';
        console.error('Неожиданная структура ответа:', response);
    }
}

// Отображение результата календаря
function displayCalendarResult(result, resultDiv) {
    const totalPrice = Math.round((result.totalPrice || 0) * 10) / 10;
    
    let html = '<div class="result-success">';
    html += '<h3 class="result-title">Результат расчета календаря</h3>';
    html += '<div class="result-price">' + totalPrice + ' <small>₽</small></div>';
    
    html += '<details class="result-details">';
    html += '<summary class="result-summary">Подробности расчета</summary>';
    html += '<div class="result-details-content">';
    html += '<ul>';
    
    // Детали расчета
    if (result.details) {
        html += '<li>Тип календаря: ' + result.details.type + '</li>';
        if (result.details.size) {
            html += '<li>Размер: ' + result.details.size + '</li>';
        }
        html += '<li>Тираж: ' + result.details.quantity + ' шт</li>';
        if (result.details.printType) {
            html += '<li>Тип печати: ' + result.details.printType + '</li>';
        }
    }
    
    // Компоненты стоимости
    if (result.printingCost) {
        html += '<li>Стоимость печати: ' + Math.round(result.printingCost * 10) / 10 + ' ₽</li>';
    }
    if (result.assemblyCost) {
        html += '<li>Стоимость сборки: ' + Math.round(result.assemblyCost * 10) / 10 + ' ₽</li>';
    }
    if (result.bigovkaCost) {
        html += '<li>Стоимость биговки: ' + Math.round(result.bigovkaCost * 10) / 10 + ' ₽</li>';
    }
    if (result.cornersCost) {
        html += '<li>Скругление углов: ' + Math.round(result.cornersCost * 10) / 10 + ' ₽</li>';
    }
    
    html += '</ul>';
    html += '</div>';
    html += '</details>';
    html += '</div>';
    
    resultDiv.innerHTML = html;
}

// Сбор данных формы
function collectFormData(form) {
    const formData = new FormData(form);
    const data = {};
    
    for (let [key, value] of formData.entries()) {
        data[key] = value;
    }
    
    console.log('Собранные данные формы:', data);
    return data;
}

// Запуск инициализации
console.log('Калькулятор:', calcConfig.type);
console.log('Время запуска:', new Date().toLocaleTimeString());

document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM загружен, ждем BX...');
    waitForBX(initWithBX, initWithoutBX);
    
    // Инициализация интерфейса
    updateCalendarTypeInterface();
});
</script>