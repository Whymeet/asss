<?php
/** Шаблон калькулятора баннеров */
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

// Подключаем общие стили
$this->addExternalCss($templateFolder.'/../.default/style.css');
// Подключаем специфичные стили для баннеров (если есть)
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
$bannerTypes = $arResult['banner_types'] ?? [];
$validationRules = $arResult['validation_rules'] ?? [];
?>

<div class="calc-container">
    <!-- Информационный блок -->
    <div class="calc-disclaimer">
        <p>
            Данные, полученные при расчете на калькуляторе – являются ориентировочными в связи с регулярным изменением стоимости материалов.<br>
            Конечную стоимость заказа уточняйте у менеджера: <a href="tel:+78462060068">+7 (846) 206-00-68</a><br>
            <strong>Баннеры:</strong> <?= $arResult['dimension_info'] ?? '' ?><br>
            <?= $arResult['area_info'] ?? '' ?><br>
            <?= $arResult['hemming_info'] ?? '' ?><br>
            <?= $arResult['grommets_info'] ?? '' ?><br>
            Спасибо за понимание!
        </p>
    </div>
    
    <h2><?= $arResult['DESCRIPTION'] ?? 'Калькулятор стоимости баннеров' ?></h2>
    
    <form id="<?= $calcType ?>CalcForm" class="calc-form">
        
        <!-- Размеры баннера -->
        <div class="form-section">
            <h3 class="section-title">Размеры баннера</h3>
            
            <div class="form-group">
                <label class="form-label" for="length">Длина (м):</label>
                <input name="length" 
                       id="length" 
                       type="number" 
                       class="form-control" 
                       min="<?= $validationRules['min_length'] ?? 0.1 ?>"
                       max="<?= $validationRules['max_length'] ?? 50 ?>"
                       step="0.01"
                       value="1.0" 
                       placeholder="Введите длину в метрах"
                       required>
                <small class="text-muted">Минимум: <?= $validationRules['min_length'] ?? 0.1 ?> м, максимум: <?= $validationRules['max_length'] ?? 50 ?> м</small>
            </div>

            <div class="form-group">
                <label class="form-label" for="width">Ширина (м):</label>
                <input name="width" 
                       id="width" 
                       type="number" 
                       class="form-control" 
                       min="<?= $validationRules['min_width'] ?? 0.1 ?>"
                       max="<?= $validationRules['max_width'] ?? 50 ?>"
                       step="0.01"
                       value="1.0" 
                       placeholder="Введите ширину в метрах"
                       required>
                <small class="text-muted">Минимум: <?= $validationRules['min_width'] ?? 0.1 ?> м, максимум: <?= $validationRules['max_width'] ?? 50 ?> м</small>
            </div>
        </div>

        <!-- Тип баннера -->
        <div class="form-section">
            <h3 class="section-title">Тип материала</h3>
            
            <div class="form-group">
                <label class="form-label" for="bannerType">Тип баннера:</label>
                <select name="bannerType" id="bannerType" class="form-control" required>
                    <?php if (!empty($bannerTypes)): ?>
                        <?php foreach ($bannerTypes as $name => $price): ?>
                            <option value="<?= htmlspecialchars($name) ?>"><?= htmlspecialchars($name) ?> (<?= $price ?> руб/м²)</option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <small class="text-muted">Выберите тип баннерной ткани</small>
            </div>
        </div>

        <!-- Дополнительные услуги -->
        <div class="form-section">
            <h3 class="section-title">Дополнительные услуги</h3>
            
            <div class="form-group">
                <label class="form-label">Дополнительные услуги:</label>
                <div class="checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="hemming" id="hemmingCheckbox"> Проклейка (90 руб/м периметра)
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="grommets" id="grommetsCheckbox"> Люверсы (30 руб/шт)
                    </label>
                </div>
                
                <div id="grommetStepField" class="grommet-step-field" style="display: none;">
                    <label class="form-label" for="grommetStep">Шаг люверсов (м):</label>
                    <input name="grommetStep" 
                           id="grommetStep" 
                           type="number" 
                           class="form-control" 
                           min="<?= $validationRules['min_grommet_step'] ?? 0.1 ?>"
                           max="<?= $validationRules['max_grommet_step'] ?? 10 ?>"
                           step="0.01"
                           value="0.5" 
                           placeholder="Расстояние между люверсами">
                    <small class="text-muted">Чем меньше шаг, тем больше люверсов потребуется</small>
                </div>
            </div>
        </div>

        <!-- Скрытые поля -->
        <input type="hidden" name="calcType" value="<?= $calcType ?>">
        <input type="hidden" name="sessid" value="<?= bitrix_sessid() ?>">

        <button id="calcBtn" type="button" class="calc-button">Рассчитать стоимость</button>
        
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

.form-section {
    background: #ffffff;
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
    border-bottom: none;
}

.checkbox-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.checkbox-label {
    display: flex;
    align-items: center;
    cursor: pointer;
    font-size: 14px;
    gap: 8px;
    font-weight: 500;
}

.grommet-step-field {
    margin-top: 15px;
    padding: 15px;
    background: #ffffff;
    border: 1px solid #e9ecef;
    border-radius: 6px;
}

.price-breakdown {
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 20px;
    margin: 15px 0;
}

.price-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #f5f5f5;
}

.price-item:last-child {
    border-bottom: none;
    font-weight: bold;
    font-size: 16px;
    color: #2e7d32;
    margin-top: 10px;
    padding-top: 15px;
    border-top: 2px solid #2e7d32;
}

.banner-dimensions {
    background: #ffffff;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 10px;
    margin: 10px 0;
    color: #495057;
    font-size: 14px;
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
    
    .form-section {
        padding: 15px;
        margin-bottom: 15px;
    }
    
    .section-title {
        font-size: 16px;
    }
    
    .price-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
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
// Конфигурация для JavaScript
const calcConfig = {
    type: '<?= $calcType ?>',
    component: 'my:print.calc'
};

// Инициализация калькулятора
document.addEventListener('DOMContentLoaded', function() {
    initCalculator();
    setupFormLogic();
    initOrderModal();
    initializeDateTimeValidation();
});

// Настройка логики формы
function setupFormLogic() {
    const grommetsCheckbox = document.getElementById('grommetsCheckbox');
    const hemmingCheckbox = document.getElementById('hemmingCheckbox');
    const grommetStepField = document.getElementById('grommetStepField');
    
    if (grommetsCheckbox && hemmingCheckbox && grommetStepField) {
        grommetsCheckbox.addEventListener('change', function() {
            grommetStepField.style.display = this.checked ? 'block' : 'none';
            if (this.checked) {
                hemmingCheckbox.checked = true;
            }
        });
    }
}

// Универсальная функция инициализации
function initCalculator() {
    function checkBX() {
        if (typeof BX !== 'undefined' && BX.ajax && BX.ajax.runComponentAction) {
            console.log('BX доступен, используем стандартный метод');
            initWithBX();
        } else {
            console.log('BX недоступен, используем запасной вариант');
            setTimeout(() => {
                if (typeof BX !== 'undefined' && BX.ajax && BX.ajax.runComponentAction) {
                    initWithBX();
                } else {
                    initWithoutBX();
                }
            }, 1000);
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
        
        resultDiv.innerHTML = '<div class="loading">Выполняется расчет баннера...</div>';

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
        
        resultDiv.innerHTML = '<div class="loading">Выполняется расчет баннера...</div>';

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

// Сбор данных формы
function collectFormData(form) {
    const data = {};
    const formData = new FormData(form);
    
    for (let [key, value] of formData.entries()) {
        if (form.elements[key] && form.elements[key].type === 'checkbox') {
            data[key] = form.elements[key].checked;
        } else {
            data[key] = value;
        }
    }
    
    return data;
}

// Обработка ответа сервера
function handleResponse(response, resultDiv) {
    if (response && response.data) {
        if (response.data.error) {
            resultDiv.innerHTML = '<div class="result-error">Ошибка: ' + response.data.error + '</div>';
        } else {
            displayBannerResult(response.data, resultDiv);
        }
    } else {
        resultDiv.innerHTML = '<div class="result-error">Некорректный ответ сервера</div>';
    }
}

// Отображение результата расчета баннера
function displayBannerResult(result, resultDiv) {
    const totalPrice = Math.round((result.totalPrice || 0) * 100) / 100;
    
    let html = '<div class="result-success">';
    html += '<h3>Результат расчета баннера</h3>';
    
    if (result.dimensions) {
        html += '<div class="banner-dimensions">';
        html += '<strong>Размеры:</strong> ' + result.dimensions.length + ' × ' + result.dimensions.width + ' м';
        html += '</div>';
    }
    
    html += '<div class="price-breakdown">';
    
    if (result.area) {
        html += '<div class="price-item"><span>Площадь баннера:</span><span>' + result.area + ' м²</span></div>';
    }
    
    if (result.bannerType) {
        html += '<div class="price-item"><span>Тип материала:</span><span>' + result.bannerType + '</span></div>';
    }
    
    if (result.bannerCost) {
        html += '<div class="price-item"><span>Стоимость полотна:</span><span>' + formatPrice(result.bannerCost) + ' ₽</span></div>';
    }
    
    if (result.perimeter && result.hemmingCost > 0) {
        html += '<div class="price-item"><span>Проклейка (' + result.perimeter + ' м):</span><span>' + formatPrice(result.hemmingCost) + ' ₽</span></div>';
    }
    
    if (result.grommetCount > 0) {
        html += '<div class="price-item"><span>Люверсы (' + result.grommetCount + ' шт):</span><span>' + formatPrice(result.grommetCost) + ' ₽</span></div>';
    }
    
    html += '<div class="price-item total-price"><span>Итоговая стоимость:</span><span>' + formatPrice(totalPrice) + ' ₽</span></div>';
    html += '</div>';
    
    // Добавляем кнопку заказа
    html += '<button type="button" class="order-button" onclick="openOrderModal()">Заказать баннер</button>';
    
    html += '</div>';
    
    resultDiv.innerHTML = html;
}

// Форматирование цены
function formatPrice(price) {
    return Number(price).toLocaleString('ru-RU', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

// Сбор данных формы
function collectFormData(form) {
    const data = {};
    const formData = new FormData(form);
    
    for (let [key, value] of formData.entries()) {
        if (form.elements[key] && form.elements[key].type === 'checkbox') {
            data[key] = form.elements[key].checked;
        } else {
            data[key] = value;
        }
    }
    
    return data;
}

// Функция инициализации валидации даты и времени
function initializeDateTimeValidation() {
    const dateInput = document.getElementById('callDate');
    const timeInput = document.getElementById('callTime');
    
    if (dateInput) {
        dateInput.addEventListener('change', function() {
            validateDateField(this);
        });
        
        dateInput.addEventListener('blur', function() {
            validateDateField(this);
        });
    }
    
    if (timeInput) {
        timeInput.addEventListener('change', function() {
            validateTimeField(this);
        });
        
        timeInput.addEventListener('blur', function() {
            validateTimeField(this);
        });
    }
}

// Функция валидации поля даты
function validateDateField(dateField) {
    // Сначала очищаем предыдущие ошибки
    clearFieldError(dateField);
    
    const dateValue = dateField.value;
    if (!dateValue) return true;
    
    const selectedDate = new Date(dateValue);
    const now = new Date();
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    const selectedDay = new Date(selectedDate.getFullYear(), selectedDate.getMonth(), selectedDate.getDate());
    
    // Проверяем корректность даты
    if (isNaN(selectedDate.getTime())) {
        showFieldError(dateField, 'Некорректный формат даты');
        return false;
    }
    
    // Проверяем, что дата не в прошлом
    if (selectedDay < today) {
        showFieldError(dateField, 'Дата не может быть в прошлом');
        return false;
    }
    
    // Проверяем, что дата не более чем на год вперед
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
    // Сначала очищаем предыдущие ошибки
    clearFieldError(timeField);
    
    const timeValue = timeField.value;
    if (!timeValue) return true;
    
    const timeParts = timeValue.split(':');
    const hours = parseInt(timeParts[0], 10);
    const minutes = parseInt(timeParts[1], 10);
    
    // Проверяем корректность времени
    if (isNaN(hours) || isNaN(minutes)) {
        showFieldError(timeField, 'Некорректный формат времени');
        return false;
    }
    
    if (hours < 9 || hours > 20 || (hours === 20 && minutes > 0)) {
        showFieldError(timeField, 'Время звонка с 09:00 до 20:00');
        return false;
    }
    
    // Проверяем время для сегодняшнего дня
    const dateField = document.getElementById('callDate');
    if (dateField && dateField.value) {
        const selectedDate = new Date(dateField.value);
        const now = new Date();
        const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        const selectedDay = new Date(selectedDate.getFullYear(), selectedDate.getMonth(), selectedDate.getDate());
        
        if (selectedDay.getTime() === today.getTime()) {
            const currentHours = now.getHours();
            const currentMinutes = now.getMinutes();
            
            if (hours < currentHours || (hours === currentHours && minutes <= currentMinutes)) {
                showFieldError(timeField, 'Время должно быть позже текущего');
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
        errorMessage.remove();
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
        showFieldError(nameField, 'Имя обязательно для заполнения');
        hasErrors = true;
    } else if (name.length < 2) {
        showFieldError(nameField, 'Имя должно содержать минимум 2 символа');
        hasErrors = true;
    }
    
    // Валидация телефона
    if (!phone) {
        showFieldError(phoneField, 'Телефон обязателен для заполнения');
        hasErrors = true;
    } else {
        const cleanPhone = phone.replace(/[^\d+]/g, '');
        if (cleanPhone.length < 10) {
            showFieldError(phoneField, 'Некорректный номер телефона');
            hasErrors = true;
        }
    }
    
    // Валидация email (если указан)
    if (email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            showFieldError(emailField, 'Некорректный email адрес');
            hasErrors = true;
        }
    }
    
    // Валидация даты и времени (если указаны)
    if (date || time) {
        if (date && !validateDateField(dateField)) {
            hasErrors = true;
        }
        if (time && !validateTimeField(timeField)) {
            hasErrors = true;
        }
        if (date && time) {
            // Дополнительная проверка совместимости даты и времени
            if (!hasErrors && !validateDateTimeCompatibility(dateField, timeField)) {
                hasErrors = true;
            }
        }
    }
    
    return !hasErrors;
}

// Функция проверки совместимости даты и времени
function validateDateTimeCompatibility(dateField, timeField) {
    const dateValue = dateField.value;
    const timeValue = timeField.value;
    
    if (!dateValue || !timeValue) return true;
    
    const selectedDate = new Date(dateValue + 'T' + timeValue);
    const now = new Date();
    
    if (selectedDate <= now) {
        showFieldError(timeField, 'Дата и время должны быть в будущем');
        return false;
    }
    
    return true;
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
    const priceElements = resultDiv.querySelectorAll('.price-item.total-price span');
    const totalPrice = priceElements.length > 1 ? 
        priceElements[1].textContent.replace(/[^\d.,]/g, '') : '0';
    
    // Собираем дополнительные данные из результата расчета
    const bannerData = {};
    
    // Получаем данные о размерах и площади из результата
    const dimensionsDiv = resultDiv.querySelector('.banner-dimensions');
    if (dimensionsDiv) {
        const dimensionsText = dimensionsDiv.textContent;
        const match = dimensionsText.match(/(\d+(?:\.\d+)?)\s*×\s*(\d+(?:\.\d+)?)\s*м/);
        if (match) {
            bannerData.length = parseFloat(match[1]);
            bannerData.width = parseFloat(match[2]);
        }
    }
    
    // Формируем данные заказа для баннеров
    const orderData = {
        calcType: calcConfig.type,
        product: 'Баннер',
        width: bannerData.width || formData.width || '',
        length: bannerData.length || formData.length || '',
        bannerType: formData.bannerType || '',
        hemming: formData.hemming || false,
        grommets: formData.grommets || false,
        grommetStep: formData.grommetStep || '',
        totalPrice: parseFloat(totalPrice.replace(',', '.')) || 0
    };
    
    // Добавляем дополнительные поля из результата если есть
    const priceItems = resultDiv.querySelectorAll('.price-item');
    priceItems.forEach(item => {
        const text = item.textContent;
        if (text.includes('Площадь баннера:')) {
            const area = text.match(/(\d+(?:\.\d+)?)\s*м²/);
            if (area) orderData.area = parseFloat(area[1]);
        }
        if (text.includes('Проклейка')) {
            const perimeter = text.match(/\((\d+(?:\.\d+)?)\s*м\)/);
            if (perimeter) orderData.perimeter = parseFloat(perimeter[1]);
        }
        if (text.includes('Люверсы')) {
            const grommetCount = text.match(/\((\d+)\s*шт\)/);
            if (grommetCount) orderData.grommetCount = parseInt(grommetCount[1]);
        }
    });
    
    console.log('Данные заказа баннера:', orderData);
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
    if (closeBtn) {
        closeBtn.addEventListener('click', closeOrderModal);
    }
    
    // Закрытие по клику вне модального окна
    window.addEventListener('click', function(event) {
        if (event.target === modal) {
            closeOrderModal();
        }
    });
    
    // Обработка отправки формы
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Валидация формы
            if (!validateOrderForm()) {
                return;
            }
            
            // Собираем данные формы
            const formData = new FormData(form);
            const clientData = {
                name: formData.get('clientName'),
                phone: formData.get('clientPhone'),
                email: formData.get('clientEmail'),
                callDate: formData.get('callDate'),
                callTime: formData.get('callTime'),
                orderData: formData.get('orderData')
            };
            
            // Формируем время звонка
            let callTime = '';
            if (clientData.callDate && clientData.callTime) {
                callTime = clientData.callDate + ' ' + clientData.callTime;
            } else if (clientData.callDate) {
                callTime = clientData.callDate;
            } else if (clientData.callTime) {
                callTime = clientData.callTime;
            }
            clientData.callTime = callTime;
            
            // Отправляем данные на сервер
            sendOrderEmail(clientData);
        });
    }
}

function sendOrderEmail(clientData) {
    const submitBtn = document.querySelector('#orderForm button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Отправляем...';
    submitBtn.disabled = true;
    
    // Формируем правильные данные для отправки на сервер
    const serverData = {
        clientName: clientData.name,
        clientPhone: clientData.phone,
        clientEmail: clientData.email || '',
        callDate: clientData.callDate || '',
        callTime: clientData.callTime || '',
        orderDetails: clientData.orderData
    };
    
    // Используем BX.ajax если доступен, иначе fetch
    if (typeof BX !== 'undefined' && BX.ajax && BX.ajax.runComponentAction) {
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
    if (response && response.data) {
        if (response.data.success) {
            // Заказ успешно отправлен
            console.log('Заказ успешно отправлен! Мы свяжемся с Вами в ближайшее время.');
            closeOrderModal();
        } else {
            // Показываем ошибку
            console.error('Ошибка при отправке заказа: ' + (response.data.error || 'Неизвестная ошибка'));
        }
    } else {
        console.error('Ошибка при отправке заказа. Попробуйте еще раз.');
    }
    
    submitBtn.textContent = originalText;
    submitBtn.disabled = false;
}

function handleOrderError(submitBtn, originalText) {
    console.error('Ошибка соединения. Попробуйте еще раз.');
    submitBtn.textContent = originalText;
    submitBtn.disabled = false;
}
</script>