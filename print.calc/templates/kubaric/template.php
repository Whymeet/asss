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



        <input type="hidden" name="calcType" value="<?= $calcType ?>">
        <input type="hidden" name="sessid" value="<?= bitrix_sessid() ?>">

        <button id="calcBtn" type="button" class="calc-button">Рассчитать стоимость</button>
        
        <div id="calcResult" class="calc-result"></div>
        
        <!-- Отступ между результатом и ламинацией -->
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
    
    .form-group input[type="date"],
    .form-group input[type="time"] {
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
        e.message.includes('code.js') ||
        e.message.includes('yandex.com') ||
        e.message.includes('mc.yandex.com')
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
    
    // Добавляем кнопку заказа
    html += '<button type="button" class="order-button" onclick="openOrderModal()">Заказать печать</button>';
    
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
    
    // Инициализация модального окна
    initOrderModal();
    
    // Инициализация валидации даты и времени
    initializeDateTimeValidation();
});

// Функция инициализации валидации даты и времени
function initializeDateTimeValidation() {
    const dateInput = document.getElementById('callDate');
    const timeInput = document.getElementById('callTime');
    
    if (dateInput) {
        // Устанавливаем минимальную дату как сегодня
        const today = new Date().toISOString().split('T')[0];
        dateInput.setAttribute('min', today);
        
        // Устанавливаем максимальную дату как год вперед
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
    
    // Проверяем время для сегодняшнего дня
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

// Функция показа ошибки для конкретного поля
function showFieldError(field, message) {
    const formGroup = field.closest('.form-group');
    if (!formGroup) return;
    
    formGroup.classList.add('error');
    
    const existingError = formGroup.querySelector('.error-message');
    if (existingError) {
        existingError.remove();
    }
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.textContent = message;
    
    field.parentNode.insertBefore(errorDiv, field.nextSibling);
    
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
    
    clearAllFieldErrors();
    
    if (!name) {
        showFieldError(nameField, 'Пожалуйста, введите ваше имя');
        hasErrors = true;
    } else if (name.length < 2) {
        showFieldError(nameField, 'Имя должно содержать не менее 2 символов');
        hasErrors = true;
    }
    
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
    
    if (email) {
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailPattern.test(email)) {
            showFieldError(emailField, 'Пожалуйста, введите корректный email адрес');
            hasErrors = true;
        }
    }
    
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

// Функции для работы с модальным окном заказа
function openOrderModal() {
    const modal = document.getElementById('orderModal');
    const orderDataInput = document.getElementById('orderData');
    
    if (!modal || !orderDataInput) {
        console.error('Ошибка инициализации формы заказа');
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
    
    // Формируем данные заказа для кубариков
    const orderData = {
        calcType: 'kubaric', // Главный идентификатор типа калькулятора
        type: 'kubaric',
        description: 'Заказ печати кубариков',
        sheetsPerPack: formData.sheetsPerPack || '',
        packsCount: formData.packsCount || '',
        totalSheets: (parseInt(formData.sheetsPerPack) || 0) * (parseInt(formData.packsCount) || 0),
        printType: formData.printType || '',
        format: '9×9 см',
        paperDensity: '80 г/м²',
        totalPrice: totalPrice,
        timestamp: new Date().toLocaleString('ru-RU')
    };
    
    orderDataInput.value = JSON.stringify(orderData);
    modal.style.display = 'block';
}

function closeOrderModal() {
    const modal = document.getElementById('orderModal');
    modal.style.display = 'none';
    
    const form = document.getElementById('orderForm');
    form.reset();
    clearAllFieldErrors();
}

function initOrderModal() {
    const modal = document.getElementById('orderModal');
    const closeBtn = modal.querySelector('.order-modal-close');
    const form = document.getElementById('orderForm');
    
    closeBtn.onclick = closeOrderModal;
    
    window.onclick = function(event) {
        if (event.target == modal) {
            closeOrderModal();
        }
    };
    
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

function sendOrderEmail(clientData) {
    const orderData = JSON.parse(clientData.orderData);
    
    // Безопасно получаем sessid
    const sessidElement = document.querySelector('input[name="sessid"]');
    const sessid = sessidElement ? sessidElement.value : '';
    
    const emailData = {
        action: 'sendOrderEmail',
        clientName: clientData.clientName || '',
        clientPhone: clientData.clientPhone || '',
        clientEmail: clientData.clientEmail || '',
        callDate: clientData.callDate || '',
        callTime: clientData.callTime || '',
        clientComment: '', // Для кубариков комментарий не используется
        orderDetails: JSON.stringify(orderData), // Преобразуем в строку для передачи
        sessid: sessid
    };
    
    if (typeof BX !== 'undefined' && BX.ajax) {
        console.log('Отправка заказа через BX.ajax:', emailData);
        return BX.ajax.runComponentAction(calcConfig.component, 'sendOrderEmail', {
            mode: 'class',
            data: emailData
        });
    } else {
        console.log('Отправка заказа через fetch:', emailData);
        return fetch('/bitrix/services/main/ajax.php?c=' + calcConfig.component + '&action=sendOrderEmail&mode=class', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams(emailData)
        }).then(response => {
            console.log('Ответ сервера (raw):', response);
            return response.json();
        }).then(data => {
            console.log('Ответ сервера (parsed):', data);
            return data;
        });
    }
}

function handleOrderResponse(response, submitBtn, originalText) {
    console.log('Обработка ответа заказа:', response);
    
    if (response && (response.success || (response.data && response.data.success))) {
        console.log('Заказ успешно отправлен');
        closeOrderModal();
    } else {
        const errorMsg = response && response.data && response.data.error ? 
            response.data.error : 
            'Произошла ошибка при отправке заказа. Пожалуйста, свяжитесь с нами по телефону.';
        console.error('Ошибка отправки заказа:', errorMsg);
        console.error('Полный ответ сервера:', response);
    }
    
    submitBtn.textContent = originalText;
    submitBtn.disabled = false;
}

function handleOrderError(submitBtn, originalText, error) {
    console.error('Критическая ошибка отправки заказа:', error);
    console.error('Детали ошибки:', {
        message: error.message,
        stack: error.stack,
        name: error.name
    });
    
    submitBtn.textContent = originalText;
    submitBtn.disabled = false;
}
</script>