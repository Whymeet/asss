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
        max-height: 0;
    }
    to {
        opacity: 1;
        transform: translateY(0);
        max-height: 50px;
    }
}

@keyframes fadeOut {
    from {
        opacity: 1;
        transform: translateY(0);
        max-height: 50px;
    }
    to {
        opacity: 0;
        transform: translateY(-10px);
        max-height: 0;
    }
}

@keyframes slideInRight {
    from {
        opacity: 0;
        transform: translateX(300px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes slideOutRight {
    from {
        opacity: 1;
        transform: translateX(0);
    }
    to {
        opacity: 0;
        transform: translateX(300px);
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
        font-size: 16px; /* Предотвращает зум на iOS */
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
    
    // Количество
    if (result.quantity) {
        html += '<p><strong>Количество:</strong> ' + number_format(result.quantity, 0, '', ' ') + ' шт</p>';
    }
    
    // Добавляем кнопку заказа
    html += '<button type="button" class="order-button" onclick="openOrderModal()">Заказать печать</button>';
    
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
        
        dateInput.addEventListener('change', function() {
            clearFieldError(this);
        });
    }
    
    if (timeInput) {
        timeInput.addEventListener('change', function() {
            clearFieldError(this);
        });
    }
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
        showFieldError(nameField, 'Имя должно содержать минимум 2 символа');
        hasErrors = true;
    }
    
    // Валидация телефона
    if (!phone) {
        showFieldError(phoneField, 'Пожалуйста, введите номер телефона');
        hasErrors = true;
    } else {
        // Простая валидация телефона (российские номера)
        const phoneRegex = /^(\+7|8)?[\s\-]?\(?[0-9]{3}\)?[\s\-]?[0-9]{3}[\s\-]?[0-9]{2}[\s\-]?[0-9]{2}$/;
        if (!phoneRegex.test(phone.replace(/\s/g, ''))) {
            showFieldError(phoneField, 'Пожалуйста, введите корректный номер телефона');
            hasErrors = true;
        }
    }
    
    // Валидация email (если указан)
    if (email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            showFieldError(emailField, 'Пожалуйста, введите корректный email адрес');
            hasErrors = true;
        }
    }
    
    // Валидация даты и времени (если указаны)
    if (date || time) {
        if (!date) {
            showFieldError(dateField, 'Если указываете время, пожалуйста, выберите дату');
            hasErrors = true;
        }
        if (!time) {
            showFieldError(timeField, 'Если указываете дату, пожалуйста, выберите время');
            hasErrors = true;
        }
        
        // Проверяем, что дата не в прошлом
        if (date && time) {
            const selectedDateTime = new Date(date + 'T' + time);
            const now = new Date();
            
            if (selectedDateTime < now) {
                showFieldError(timeField, 'Нельзя выбрать время в прошлом');
                hasErrors = true;
            }
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
    
    // Собираем данные расчета
    const form = document.getElementById(calcConfig.type + 'CalcForm');
    const formData = collectFormData(form);
    
    // Получаем результат расчета
    const resultDiv = document.getElementById('calcResult');
    const priceElement = resultDiv.querySelector('.result-price');
    const totalPrice = priceElement ? priceElement.textContent.replace(/[^\d.,]/g, '') : '0';
    
    // Формируем данные заказа для визиток
    const orderData = {
        product: 'Визитки',
        printType: formData.printType === 'digital' ? 'Цифровая печать' : 'Офсетная печать',
        quantity: formData.quantity || 0,
        sideType: formData.sideType === 'single' ? 'Односторонняя (4+0)' : 'Двусторонняя (4+4)',
        size: '90x50 мм (стандартный)',
        totalPrice: totalPrice,
        calcType: 'vizit'
    };
    
    orderDataInput.value = JSON.stringify(orderData);
    modal.style.display = 'block';
    
    // Очищаем форму
    const orderForm = document.getElementById('orderForm');
    orderForm.reset();
    clearAllFieldErrors();
    
    // Фокус на первое поле
    setTimeout(() => {
        document.getElementById('clientName').focus();
    }, 100);
}

function closeOrderModal() {
    const modal = document.getElementById('orderModal');
    modal.style.display = 'none';
    
    // Очищаем форму и все ошибки
    const form = document.getElementById('orderForm');
    form.reset();
    clearAllFieldErrors();
}

function initOrderModal() {
    const modal = document.getElementById('orderModal');
    const closeBtn = modal.querySelector('.order-modal-close');
    const form = document.getElementById('orderForm');
    const orderDataInput = document.getElementById('orderData');
    
    // Закрытие по клику на X
    closeBtn.onclick = closeOrderModal;
    
    // Закрытие по клику вне модального окна
    window.onclick = function(event) {
        if (event.target === modal) {
            closeOrderModal();
        }
    };
    
    // Добавляем обработчики для автоочистки ошибок
    const nameField = document.getElementById('clientName');
    const phoneField = document.getElementById('clientPhone');
    const emailField = document.getElementById('clientEmail');
    const dateField = document.getElementById('callDate');
    const timeField = document.getElementById('callTime');
    
    if (nameField) {
        nameField.addEventListener('input', () => clearFieldError(nameField));
    }
    if (phoneField) {
        phoneField.addEventListener('input', () => clearFieldError(phoneField));
    }
    if (emailField) {
        emailField.addEventListener('input', () => clearFieldError(emailField));
    }
    if (dateField) {
        dateField.addEventListener('change', () => clearFieldError(dateField));
    }
    if (timeField) {
        timeField.addEventListener('change', () => clearFieldError(timeField));
    }
    
    // Обработка отправки формы
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!validateOrderForm()) {
            return;
        }
        
        // Собираем данные клиента
        const clientData = {
            name: nameField.value.trim(),
            phone: phoneField.value.trim(),
            email: emailField.value.trim(),
            callDate: dateField.value,
            callTime: timeField.value
        };
        
        // Проверяем, что есть результат расчета
        const resultDiv = document.getElementById('calcResult');
        if (!resultDiv || !resultDiv.innerHTML.includes('result-success')) {
            alert('Сначала выполните расчет стоимости визиток');
            return;
        }
        
        sendOrderEmail(clientData);
    });
}

function sendOrderEmail(clientData) {
    const submitBtn = document.querySelector('#orderForm button[type="submit"]');
    const originalText = submitBtn.textContent;
    
    submitBtn.disabled = true;
    submitBtn.textContent = 'Отправка...';
    
    // Получаем данные заказа из скрытого поля
    const orderDataInput = document.getElementById('orderData');
    let orderInfo = {};
    
    try {
        orderInfo = JSON.parse(orderDataInput.value);
    } catch (e) {
        console.error('Ошибка парсинга данных заказа:', e);
        handleOrderError(submitBtn, originalText);
        return;
    }
    
    // Формируем данные для отправки
    const date = clientData.callDate;
    const time = clientData.callTime;
    let callTimeString = '';
    
    if (date && time) {
        const dateObj = new Date(date + 'T' + time);
        callTimeString = dateObj.toLocaleString('ru-RU', {
            day: '2-digit',
            month: '2-digit', 
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
    
    const data = {
        name: clientData.name,
        phone: clientData.phone,
        email: clientData.email,
        callTime: callTimeString,
        orderData: JSON.stringify(orderInfo)
    };
    
    // Используем BX или fetch
    if (typeof BX !== 'undefined' && BX.ajax) {
        BX.ajax.runComponentAction(calcConfig.component, 'sendOrder', {
            mode: 'class',
            data: data
        }).then(function(response) {
            handleOrderResponse(response, submitBtn, originalText);
        }).catch(function(error) {
            handleOrderError(submitBtn, originalText);
        });
    } else {
        fetch('/bitrix/services/main/ajax.php?c=' + calcConfig.component + '&action=sendOrder&mode=class', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams(data)
        })
        .then(response => response.json())
        .then(response => {
            handleOrderResponse(response, submitBtn, originalText);
        })
        .catch(error => {
            handleOrderError(submitBtn, originalText);
        });
    }
}

function handleOrderResponse(response, submitBtn, originalText) {
    submitBtn.disabled = false;
    submitBtn.textContent = originalText;
    
    if (response && response.data && response.data.success) {
        closeOrderModal();
        
        // Показываем уведомление об успехе
        const successMessage = document.createElement('div');
        successMessage.innerHTML = `
            <div style="position: fixed; top: 20px; right: 20px; background: #28a745; color: white; 
                        padding: 15px 20px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); 
                        z-index: 1001; max-width: 350px; animation: slideInRight 0.3s ease-out;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="font-size: 20px;">✓</span>
                    <div>
                        <div style="font-weight: bold; margin-bottom: 5px;">Заказ отправлен!</div>
                        <div style="font-size: 14px; opacity: 0.9;">Мы свяжемся с вами в ближайшее время</div>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(successMessage);
        
        // Удаляем уведомление через 5 секунд
        setTimeout(() => {
            if (successMessage.parentNode) {
                successMessage.style.animation = 'slideOutRight 0.3s ease-in';
                setTimeout(() => {
                    if (successMessage.parentNode) {
                        successMessage.remove();
                    }
                }, 300);
            }
        }, 5000);
        
    } else {
        alert('Ошибка при отправке заказа. Пожалуйста, попробуйте еще раз или свяжитесь с нами по телефону.');
    }
}

function handleOrderError(submitBtn, originalText) {
    submitBtn.disabled = false;
    submitBtn.textContent = originalText;
    alert('Ошибка соединения. Пожалуйста, попробуйте еще раз или свяжитесь с нами по телефону.');
}
</script>