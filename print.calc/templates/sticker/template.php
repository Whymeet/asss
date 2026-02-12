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
        transform: translateY(0);
    }
    to {
        opacity: 0;
        transform: translateY(-10px);
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
        padding: 14px 20px;
    }
    
    /* Стили для мобильных устройств */
    .form-group input[type="date"],
    .form-group input[type="time"] {
        padding: 12px;
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
    }
    
    // Добавляем кнопку заказа
    html += '<button type="button" class="order-button" onclick="openOrderModal()">Заказать печать</button>';
    
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
    
    // Инициализация модального окна
    initOrderModal();
});

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
    
    // Формируем данные заказа для наклеек
    const orderData = {
        product: 'Наклейки',
        length: formData.length || 'Не указана',
        width: formData.width || 'Не указана',
        quantity: formData.quantity || 0,
        stickerType: formData.stickerType || 'simple_print',
        totalPrice: totalPrice,
        calcType: 'sticker'
    };
    
    orderDataInput.value = JSON.stringify(orderData);
    modal.style.display = 'block';
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
    
    // Закрытие по клику на X
    closeBtn.onclick = closeOrderModal;
    
    // Закрытие по клику вне модального окна
    window.onclick = function(event) {
        if (event.target === modal) {
            closeOrderModal();
        }
    };
    
    // Добавляем обработчики для очистки ошибок при фокусе
    const formFields = form.querySelectorAll('input[type="text"], input[type="tel"], input[type="email"], input[type="date"], input[type="time"]');
    formFields.forEach(field => {
        field.addEventListener('focus', function() {
            clearFieldError(this);
        });
        
        // Также очищаем ошибки при вводе текста
        if (field.type === 'text' || field.type === 'tel' || field.type === 'email') {
            field.addEventListener('input', function() {
                clearFieldError(this);
            });
        }
    });
    
    // Обработчик отправки формы
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Сначала валидируем форму
        if (!validateOrderForm()) {
            return;
        }
        
        const formData = new FormData(form);
        const date = formData.get('callDate');
        const time = formData.get('callTime');
        
        // Формируем строку времени для отправки
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
        
        const clientData = {
            name: formData.get('clientName'),
            phone: formData.get('clientPhone'),
            email: formData.get('clientEmail'),
            callTime: callTimeString,
            orderData: formData.get('orderData')
        };
        
        // Отправляем данные на сервер
        sendOrderEmail(clientData);
    });
}

function sendOrderEmail(clientData) {
    const submitBtn = document.querySelector('#orderForm button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Отправляем...';
    submitBtn.disabled = true;
    
    // Парсим данные заказа
    const orderData = JSON.parse(clientData.orderData);
    
    // Формируем правильные данные для отправки на сервер
    const serverData = {
        clientName: clientData.name,
        clientPhone: clientData.phone,
        clientEmail: clientData.email || '',
        callDate: '',
        callTime: '',
        clientComment: '',
        orderDetails: clientData.orderData
    };
    
    // Если есть время звонка, разбираем его на дату и время
    if (clientData.callTime) {
        // callTime приходит в формате "31.07.2025, 10:10"
        const parts = clientData.callTime.split(', ');
        if (parts.length === 2) {
            const datePart = parts[0]; // "31.07.2025"
            const timePart = parts[1]; // "10:10"
            
            // Преобразуем дату из dd.mm.yyyy в yyyy-mm-dd
            const dateComponents = datePart.split('.');
            if (dateComponents.length === 3) {
                serverData.callDate = `${dateComponents[2]}-${dateComponents[1]}-${dateComponents[0]}`;
                serverData.callTime = timePart;
            }
        }
    }
    
    // Используем BX.ajax если доступен, иначе fetch
    if (typeof BX !== 'undefined' && BX.ajax) {
        BX.ajax.runComponentAction(calcConfig.component, 'sendOrderEmail', {
            mode: 'class',
            data: serverData
        }).then(function(response) {
            handleOrderResponse(response, submitBtn, originalText);
        }).catch(function(error) {
            console.error('Ошибка отправки заказа:', error);
            handleOrderError(submitBtn, originalText);
        });
    } else {
        fetch('/bitrix/services/main/ajax.php?c=' + calcConfig.component + '&action=sendOrderEmail&mode=class', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams(serverData)
        })
        .then(response => response.json())
        .then(response => {
            handleOrderResponse(response, submitBtn, originalText);
        })
        .catch(error => {
            console.error('Ошибка отправки заказа:', error);
            handleOrderError(submitBtn, originalText);
        });
    }
}

function handleOrderResponse(response, submitBtn, originalText) {
    if (response && response.data && response.data.success) {
        closeOrderModal();
    }
    
    submitBtn.textContent = originalText;
    submitBtn.disabled = false;
}

function handleOrderError(submitBtn, originalText) {
    submitBtn.textContent = originalText;
    submitBtn.disabled = false;
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
        
        // Валидация даты и времени
        if (date && time) {
            const selectedDate = new Date(date);
            const now = new Date();
            const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
            const selectedDay = new Date(selectedDate.getFullYear(), selectedDate.getMonth(), selectedDate.getDate());
            
            // Проверяем, что дата не в прошлом
            if (selectedDay < today) {
                showFieldError(dateField, 'Нельзя выбрать дату в прошлом');
                hasErrors = true;
            }
            
            // Проверяем, что дата не более чем на год вперед (динамически)
            const oneYearFromNow = new Date();
            oneYearFromNow.setFullYear(oneYearFromNow.getFullYear() + 1);
            if (selectedDate > oneYearFromNow) {
                showFieldError(dateField, 'Нельзя выбрать дату более чем на год вперед');
                hasErrors = true;
            }
            
            // Валидация времени (с 9:00 до 20:00)
            const timeParts = time.split(':');
            const hours = parseInt(timeParts[0], 10);
            const minutes = parseInt(timeParts[1], 10);
            
            if (hours < 9 || hours > 20 || (hours === 20 && minutes > 0)) {
                showFieldError(timeField, 'Время должно быть между 9:00 и 20:00');
                hasErrors = true;
            }
            
            // Проверяем, что дата и время не в прошлом (для сегодняшнего дня)
            if (selectedDay.getTime() === today.getTime()) {
                const selectedDateTime = new Date(date + 'T' + time);
                if (selectedDateTime < now) {
                    showFieldError(timeField, 'Нельзя выбрать время в прошлом');
                    hasErrors = true;
                }
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
</script>