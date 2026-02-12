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

    <!-- Модальное окно для заказа -->
    <div id="orderModal" class="order-modal" style="display: none;">
        <div class="order-modal-content">
            <span class="order-modal-close">&times;</span>
            <h3>Оформить заказ</h3>
            <form id="orderForm" class="order-form">
                <div class="form-group">
                    <label class="form-label" for="clientName">Имя <span class="required">*</span>:</label>
                    <input type="text" id="clientName" name="clientName" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="clientPhone">Телефон <span class="required">*</span>:</label>
                    <input type="tel" id="clientPhone" name="clientPhone" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="clientEmail">E-mail:</label>
                    <input type="email" id="clientEmail" name="clientEmail" class="form-control">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="callDate">Удобная дата для звонка:</label>
                    <input type="date" id="callDate" name="callDate" class="form-control">
                </div>

                <div class="form-group">
                    <label class="form-label" for="callTime">Удобное время для звонка:</label>
                    <input type="time" id="callTime" name="callTime" class="form-control">
                </div>
                
                <div class="modal-buttons">
                    <button type="button" class="calc-button calc-button-secondary" onclick="closeOrderModal()">Отмена</button>
                    <button type="submit" class="calc-button calc-button-success">Отправить заказ</button>
                </div>
                
                <input type="hidden" id="orderData" name="orderData">
            </form>
        </div>
    </div>

    <div class="calc-thanks">
        <p>Спасибо, что Вы с нами!</p>
    </div>
</div>

<style>
/* Стили для модального окна заказа */
.order-modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.4);
    backdrop-filter: blur(3px);
}

.order-modal-content {
    background-color: #fefefe;
    margin: 5% auto;
    padding: 30px;
    border: none;
    border-radius: 12px;
    width: 90%;
    max-width: 500px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    position: relative;
    animation: modalSlideIn 0.3s ease-out;
}

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: translateY(-50px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.order-modal-close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    position: absolute;
    top: 15px;
    right: 20px;
    cursor: pointer;
    transition: color 0.3s;
}

.order-modal-close:hover,
.order-modal-close:focus {
    color: #000;
}

.order-form h3 {
    margin: 0 0 25px 0;
    color: #333;
    font-size: 24px;
    text-align: center;
}

.required {
    color: #dc3545;
}

.modal-buttons {
    display: flex;
    gap: 15px;
    margin-top: 25px;
    justify-content: center;
}

.calc-button-secondary {
    background: #6c757d;
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 16px;
    transition: all 0.3s;
}

.calc-button-secondary:hover {
    background: #5a6268;
    transform: translateY(-1px);
}

.calc-button-success {
    background: #28a745;
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 16px;
    transition: all 0.3s;
}

.calc-button-success:hover {
    background: #218838;
    transform: translateY(-1px);
}

/* Кнопка заказа в результатах */
.order-button {
    background: linear-gradient(45deg, #28a745, #20c997);
    color: white;
    border: none;
    padding: 15px 30px;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    margin-top: 15px;
    width: 100%;
    transition: all 0.3s;
    box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
}

.order-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
    background: linear-gradient(45deg, #218838, #1ea085);
}

.order-button:active {
    transform: translateY(0);
}

/* Стили для полей даты и времени */
.form-group input[type="date"],
.form-group input[type="time"] {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
    box-sizing: border-box;
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
}

.form-group input[type="date"]:focus,
.form-group input[type="time"]:focus {
    border-color: #007bff;
    outline: none;
    box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
}

.form-group input[type="date"]:invalid,
.form-group input[type="time"]:invalid {
    border-color: #dc3545;
}

/* Стили для ошибок валидации */
.form-group.error input,
.form-group.error select {
    border-color: #dc3545 !important;
    box-shadow: 0 0 0 2px rgba(220, 53, 69, 0.25) !important;
    animation: shakeError 0.5s ease-in-out;
}

.error-message {
    color: #dc3545;
    font-size: 14px;
    margin-top: 5px;
    padding: 8px 12px;
    background: rgba(220, 53, 69, 0.1);
    border: 1px solid rgba(220, 53, 69, 0.3);
    border-radius: 4px;
    animation: slideDown 0.3s ease-out;
    display: block;
}

@keyframes shakeError {
    0%, 100% { transform: translateX(0); }
    10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
    20%, 40%, 60%, 80% { transform: translateX(5px); }
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fadeOut {
    from {
        opacity: 1;
    }
    to {
        opacity: 0;
    }
}

@media (max-width: 768px) {
    .order-modal-content {
        margin: 10% auto;
        padding: 20px;
        width: 95%;
    }
    
    .modal-buttons {
        flex-direction: column;
        gap: 10px;
    }
    
    .calc-button-secondary,
    .calc-button-success {
        width: 100%;
    }
    
    /* Стили для мобильных устройств */
    .form-group input[type="date"],
    .form-group input[type="time"] {
        font-size: 16px;
    }
}
</style>

<script>
// Блокируем внешние ошибки
window.addEventListener('error', function(e) {
    if (e.message && (
        e.message.includes('Cannot set properties of null') || 
        e.message.includes('Cannot read properties of null') ||
        e.message.includes('recaptcha') ||
        e.message.includes('mail.ru') ||
        e.message.includes('top-fwz1') ||
        e.message.includes('code.js') ||
        e.message.includes('Attestation check') ||
        e.filename && (
            e.filename.includes('recaptcha') ||
            e.filename.includes('mail.ru') ||
            e.filename.includes('top-fwz1')
        )
    )) {
        console.log('Заблокирована внешняя ошибка:', e.message);
        e.preventDefault();
        e.stopPropagation();
        return true;
    }
});

window.addEventListener('unhandledrejection', function(e) {
    if (e.reason === null || (e.reason && (
        e.reason.toString().includes('recaptcha') ||
        e.reason.toString().includes('mail.ru') ||
        e.reason.toString().includes('top-fwz1') ||
        e.reason.toString().includes('Attestation check')
    ))) {
        console.log('Заблокирована ошибка Promise:', e.reason);
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
    
    // Добавляем кнопку заказа
    html += '<button type="button" class="order-button" onclick="openOrderModal()">Заказать календарь</button>';
    
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
    
    // Инициализация модального окна
    initOrderModal();
    
    // Инициализация валидации даты и времени
    initializeDateTimeValidation();
});

// Функции для работы с модальным окном заказа
function openOrderModal() {
    const modal = document.getElementById('orderModal');
    const orderDataInput = document.getElementById('orderData');
    
    // Проверяем наличие всех необходимых элементов
    if (!modal) {
        console.error('Модальное окно не найдено');
        return;
    }
    
    if (!orderDataInput) {
        console.error('Поле orderData не найдено');
        return;
    }
    
    // Собираем данные расчета
    const form = document.getElementById(calcConfig.type + 'CalcForm');
    if (!form) {
        console.error('Форма калькулятора не найдена');
        return;
    }
    
    const formData = collectFormData(form);
    
    // Получаем результат расчета
    const resultDiv = document.getElementById('calcResult');
    const priceElement = resultDiv ? resultDiv.querySelector('.result-price') : null;
    const totalPrice = priceElement ? priceElement.textContent.replace(/[^\d.,]/g, '') : '0';
    
    // Формируем данные заказа для календарей
    const orderData = {
        calcType: 'calendar',
        type: 'calendar',
        description: 'Заказ календарей',
        calendarType: formData.calendarType || '',
        size: formData.size || '',
        printType: formData.printType || '',
        quantity: formData.quantity || '',
        totalPrice: totalPrice,
        timestamp: new Date().toLocaleString('ru-RU')
    };
    
    try {
        orderDataInput.value = JSON.stringify(orderData);
        modal.style.display = 'block';
        console.log('Модальное окно открыто с данными:', orderData);
        console.log('Данные для отправки в JSON:', JSON.stringify(orderData));
    } catch (error) {
        console.error('Ошибка при открытии модального окна:', error);
    }
}

function closeOrderModal() {
    const modal = document.getElementById('orderModal');
    const form = document.getElementById('orderForm');
    
    if (modal) {
        modal.style.display = 'none';
    }
    
    // Очищаем форму и все ошибки
    if (form) {
        form.reset();
        clearAllFieldErrors();
    }
}

function initOrderModal() {
    const modal = document.getElementById('orderModal');
    const closeBtn = modal ? modal.querySelector('.order-modal-close') : null;
    const form = document.getElementById('orderForm');
    
    // Проверяем наличие всех необходимых элементов
    if (!modal) {
        console.error('Модальное окно не найдено при инициализации');
        return;
    }
    
    if (!closeBtn) {
        console.error('Кнопка закрытия модального окна не найдена');
        return;
    }
    
    if (!form) {
        console.error('Форма заказа не найдена');
        return;
    }
    
    // Закрытие по клику на X
    closeBtn.onclick = closeOrderModal;
    
    // Закрытие по клику вне модального окна
    window.onclick = function(event) {
        if (event.target == modal) {
            closeOrderModal();
        }
    };
    
    // Обработка отправки формы
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!validateOrderForm()) {
            return;
        }
        
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.textContent = 'Отправляем...';
        submitBtn.disabled = true;
        
        const formData = new FormData(form);
        const clientData = {};
        for (let [key, value] of formData.entries()) {
            clientData[key] = value;
        }
        
        sendOrderEmail(clientData)
            .then((response) => handleOrderResponse(response, submitBtn, originalText))
            .catch((error) => handleOrderError(submitBtn, originalText, error));
    });
}

// Функция инициализации валидации даты и времени
function initializeDateTimeValidation() {
    const dateInput = document.getElementById('callDate');
    const timeInput = document.getElementById('callTime');
    
    if (dateInput) {
        const today = new Date().toISOString().split('T')[0];
        dateInput.setAttribute('min', today);
        
        const maxDate = new Date();
        maxDate.setFullYear(maxDate.getFullYear() + 1);
        dateInput.setAttribute('max', maxDate.toISOString().split('T')[0]);
        
        dateInput.addEventListener('change', function() {
            validateDateField(this);
        });
    }
    
    if (timeInput) {
        timeInput.addEventListener('change', function() {
            validateTimeField(this);
        });
    }
}

// Функция валидации поля даты
function validateDateField(dateField) {
    clearFieldError(dateField);
    
    const dateValue = dateField.value;
    if (!dateValue) return true;
    
    const selectedDate = new Date(dateValue);
    const now = new Date();
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    const selectedDay = new Date(selectedDate.getFullYear(), selectedDate.getMonth(), selectedDate.getDate());
    
    if (isNaN(selectedDate.getTime())) {
        showFieldError(dateField, 'Пожалуйста, введите корректную дату');
        return false;
    }
    
    if (selectedDay < today) {
        showFieldError(dateField, 'Дата не может быть в прошлом');
        return false;
    }
    
    const oneYearFromNow = new Date();
    oneYearFromNow.setFullYear(oneYearFromNow.getFullYear() + 1);
    if (selectedDate > oneYearFromNow) {
        showFieldError(dateField, 'Дата не может быть более чем на год вперед');
        return false;
    }
    
    return true;
}

// Функция валидации поля времени
function validateTimeField(timeField) {
    clearFieldError(timeField);
    
    const timeValue = timeField.value;
    if (!timeValue) return true;
    
    const timeParts = timeValue.split(':');
    const hours = parseInt(timeParts[0], 10);
    const minutes = parseInt(timeParts[1], 10);
    
    if (isNaN(hours) || isNaN(minutes)) {
        showFieldError(timeField, 'Пожалуйста, введите корректное время');
        return false;
    }
    
    if (hours < 9 || hours > 20 || (hours === 20 && minutes > 0)) {
        showFieldError(timeField, 'Время должно быть в промежутке с 9:00 до 20:00');
        return false;
    }
    
    const dateField = document.getElementById('callDate');
    if (dateField && dateField.value) {
        const selectedDate = new Date(dateField.value);
        const today = new Date();
        
        if (selectedDate.toDateString() === today.toDateString()) {
            const currentTime = new Date();
            const selectedTime = new Date(today.getFullYear(), today.getMonth(), today.getDate(), hours, minutes);
            
            if (selectedTime < currentTime) {
                showFieldError(timeField, 'Время не может быть в прошлом для сегодняшнего дня');
                return false;
            }
        }
    }
    
    return true;
}

// Функция валидации формы заказа
function validateOrderForm() {
    const nameField = document.getElementById('clientName');
    const phoneField = document.getElementById('clientPhone');
    const emailField = document.getElementById('clientEmail');
    const dateField = document.getElementById('callDate');
    const timeField = document.getElementById('callTime');
    
    const name = nameField.value.trim();
    const phone = phoneField.value.trim();
    const email = emailField.value.trim();
    const date = dateField.value;
    const time = timeField.value;
    
    let hasErrors = false;
    
    // Очищаем все предыдущие ошибки
    clearAllFieldErrors();
    
    // Валидация имени
    if (!name) {
        showFieldError(nameField, 'Пожалуйста, введите ваше имя');
        hasErrors = true;
    } else if (name.length < 2) {
        showFieldError(nameField, 'Имя должно содержать не менее 2 символов');
        hasErrors = true;
    }
    
    // Валидация телефона
    if (!phone) {
        showFieldError(phoneField, 'Пожалуйста, введите номер телефона');
        hasErrors = true;
    } else {
        const phonePattern = /^[\+]?[0-9\s\-\(\)]{10,}$/;
        if (!phonePattern.test(phone)) {
            showFieldError(phoneField, 'Пожалуйста, введите корректный номер телефона');
            hasErrors = true;
        }
    }
    
    // Валидация email (если указан)
    if (email) {
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailPattern.test(email)) {
            showFieldError(emailField, 'Пожалуйста, введите корректный email адрес');
            hasErrors = true;
        }
    }
    
    // Валидация даты и времени (если указаны)
    if (date || time) {
        if (date && !validateDateField(dateField)) hasErrors = true;
        if (time && !validateTimeField(timeField)) hasErrors = true;
        
        if ((date && !time) || (!date && time)) {
            if (!time) showFieldError(timeField, 'Пожалуйста, укажите время звонка');
            if (!date) showFieldError(dateField, 'Пожалуйста, укажите дату звонка');
            hasErrors = true;
        }
    }
    
    return !hasErrors;
}

// Функция показа ошибки для конкретного поля
function showFieldError(field, message) {
    const formGroup = field.closest('.form-group');
    if (!formGroup) return;
    
    // Добавляем класс ошибки
    formGroup.classList.add('error');
    
    // Удаляем предыдущее сообщение об ошибке, если есть
    const existingError = formGroup.querySelector('.error-message');
    if (existingError) {
        existingError.remove();
    }
    
    // Создаем новое сообщение об ошибке
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.textContent = message;
    
    // Добавляем сообщение после поля
    field.parentNode.insertBefore(errorDiv, field.nextSibling);
    
    // Автоматически убираем ошибку через 5 секунд
    setTimeout(() => {
        clearFieldError(field);
    }, 5000);
}

// Функция очистки ошибки для поля
function clearFieldError(field) {
    const formGroup = field.closest('.form-group');
    if (!formGroup) return;
    
    formGroup.classList.remove('error');
    
    const errorMessage = formGroup.querySelector('.error-message');
    if (errorMessage) {
        errorMessage.style.animation = 'fadeOut 0.3s ease-out';
        setTimeout(() => {
            if (errorMessage.parentNode) {
                errorMessage.remove();
            }
        }, 300);
    }
}

// Функция очистки всех ошибок в форме
function clearAllFieldErrors() {
    const formGroups = document.querySelectorAll('#orderForm .form-group');
    formGroups.forEach(group => {
        group.classList.remove('error');
        const errorMessage = group.querySelector('.error-message');
        if (errorMessage) {
            errorMessage.remove();
        }
    });
}

function sendOrderEmail(clientData) {
    const orderData = JSON.parse(clientData.orderData);
    
    console.log('Отправка заказа календаря:', {
        clientData: clientData,
        orderData: orderData
    });
    
    const sessidElement = document.querySelector('input[name="sessid"]');
    const sessid = sessidElement ? sessidElement.value : '';
    
    const emailData = {
        action: 'sendOrderEmail',
        clientName: clientData.clientName || '',
        clientPhone: clientData.clientPhone || '',
        clientEmail: clientData.clientEmail || '',
        callDate: clientData.callDate || '',
        callTime: clientData.callTime || '',
        clientComment: '',
        orderDetails: JSON.stringify(orderData),
        sessid: sessid
    };
    
    console.log('Данные для отправки на сервер:', emailData);
    
    if (typeof BX !== 'undefined' && BX.ajax) {
        return BX.ajax.runComponentAction(calcConfig.component, 'sendOrderEmail', {
            mode: 'class',
            data: emailData
        });
    } else {
        return fetch('/bitrix/services/main/ajax.php?c=' + calcConfig.component + '&action=sendOrderEmail&mode=class', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams(emailData)
        }).then(response => response.json());
    }
}

function handleOrderResponse(response, submitBtn, originalText) {
    if (response && (response.success || (response.data && response.data.success))) {
        closeOrderModal();
        console.log('Заказ успешно отправлен!');
    } else {
        const errorMsg = response && response.data && response.data.error ? 
            response.data.error : 
            'Произошла ошибка при отправке заказа. Пожалуйста, свяжитесь с нами по телефону.';
        console.error('Ошибка заказа:', errorMsg);
    }
    
    submitBtn.textContent = originalText;
    submitBtn.disabled = false;
}

function handleOrderError(submitBtn, originalText, error) {
    console.error('Ошибка отправки заказа:', error);
    
    submitBtn.textContent = originalText;
    submitBtn.disabled = false;
}
</script>