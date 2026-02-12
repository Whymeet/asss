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
                <span id="pointsWarning" class="points-warning" style="display: none;">На стенде не поместится столько карманов</span>
            </div>
        </div>

        <input type="hidden" name="calcType" value="<?= $calcType ?>">
        <input type="hidden" name="sessid" value="<?= bitrix_sessid() ?>">

        <button id="calcBtn" type="button" class="calc-button">Рассчитать стоимость</button>
        <?php if (!empty($features['lamination'])): ?>
        <!-- Секция ламинации -->
        <div id="laminationSection" class="lamination-section" style="margin-top: 32px;">
            <h3>Дополнительная ламинация</h3>
            <div id="laminationControls"></div>
            <div id="laminationResult" class="lamination-result"></div>
        </div>
        <?php endif; ?>
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

.remove-lamination-btn {
    background: #dc3545;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    font-size: 14px;
    cursor: pointer;
    margin-left: 10px;
    transition: all 0.3s;
}
.remove-lamination-btn:hover {
    background: #c82333;
    transform: translateY(-1px);
}
.lamination-info-container {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 10px;
}
@media (max-width: 768px) {
    .lamination-info-container {
        flex-direction: column;
        align-items: stretch;
    }
    .remove-lamination-btn {
        margin-left: 0;
        width: 100%;
    }
}

@media (max-width: 768px) {
    .pockets-grid {
        grid-template-columns: 1fr;
        gap: 10px;
    }
}

/* Стили для модального окна заказа */
.order-form {
    background: #ffffff; /* Чисто белый фон */
    border: none; /* Убираем рамку */
    border-radius: 0; /* Убираем скругления */
    padding: 0; /* Убираем отступы */
    margin: 0; /* Убираем внешние отступы */
    box-shadow: none; /* Убираем тень */
    animation: none; /* Убираем анимацию */
}

.order-form h3 {
    color: #333; /* Обычный черный цвет вместо зеленого */
    margin: 0 0 20px 0;
    font-size: 22px;
    font-weight: 600;
    text-align: center;
}

.order-buttons {
    display: flex;
    gap: 10px;
    justify-content: center;
    margin-top: 20px;
    flex-wrap: wrap;
}

.calc-button-secondary {
    background: #6c757d;
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 6px;
    font-size: 16px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s;
    min-width: 140px;
}

.calc-button-secondary:hover {
    background: #5a6268;
    transform: translateY(-2px);
}

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

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
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
    
    // Показываем информацию о ламинации если она была добавлена
    if (result.laminationCost && result.laminationCost > 0) {
        html += '<div class="lamination-info-container" style="color: #28a745; background: #f8fff8; padding: 10px; border-radius: 6px; border-left: 4px solid #28a745; margin-bottom: 15px;">';
        html += '<div><strong>Ламинация добавлена:</strong> ' + Math.round(result.laminationCost * 10) / 10 + ' ₽</div>';
        html += '<button type="button" class="remove-lamination-btn" onclick="removeLamination()">Убрать ламинацию</button>';
        html += '</div>';
    }
    
    // Добавляем кнопку заказа
    html += '<button type="button" class="order-button" onclick="openOrderModal()">Заказать ПВХ стенд</button>';
    
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
    // Инициализация модального окна
    initOrderModal();
    // Инициализация валидации даты и времени
    initializeDateTimeValidation();
});

function removeLamination() {
    const laminationSection = document.getElementById('laminationSection');
    const laminationControls = document.getElementById('laminationControls');
    const laminationResult = document.getElementById('laminationResult');
    const calcResult = document.getElementById('calcResult');

    if (laminationSection) {
        laminationSection.remove();
    }
    if (laminationControls) {
        laminationControls.remove();
    }
    if (laminationResult) {
        laminationResult.remove();
    }

    // Пересчитываем стоимость без ламинации
    const form = document.getElementById(calcConfig.type + 'CalcForm');
    const data = collectFormData(form);
    data.calcType = calcConfig.type;
    data.lamination = '0'; // Убираем ламинацию

    const resultDiv = document.getElementById('calcResult');
    resultDiv.innerHTML = '<div class="loading">Выполняется расчет ПВХ стенда без ламинации...</div>';

    BX.ajax.runComponentAction(calcConfig.component, 'calc', {
        mode: 'class',
        data: data
    }).then(function(response) {
        handleResponse(response, resultDiv);
    }).catch(function(error) {
        resultDiv.innerHTML = '<div class="result-error">Ошибка соединения: ' + 
            (error.message || 'Неизвестная ошибка') + '</div>';
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
    
    // Формируем данные заказа для ПВХ стендов
    const orderData = {
        product: 'ПВХ стенд',
        width: formData.width || 'Не указан',
        height: formData.height || 'Не указан',
        pvcType: formData.pvcType || 'Не указан',
        flatA4: formData.flatA4 || '0',
        flatA5: formData.flatA5 || '0',
        volumeA4: formData.volumeA4 || '0',
        volumeA5: formData.volumeA5 || '0',
        totalPrice: totalPrice,
        calcType: 'stend'
    };
    
    // Добавляем информацию о ламинации если выбрана
    if (formData.laminationType) {
        orderData.laminationType = formData.laminationType;
        if (formData.laminationThickness) {
            orderData.laminationThickness = formData.laminationThickness;
        }
    }
    
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
    
    if (!modal || !closeBtn || !form) {
        return; // Элементы не найдены
    }
    
    // Закрытие по клику на X
    closeBtn.onclick = closeOrderModal;
    
    // Закрытие по клику вне модального окна
    window.onclick = function(event) {
        if (event.target === modal) {
            closeOrderModal();
        }
    };
    
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

// Функция инициализации валидации даты и времени
function initializeDateTimeValidation() {
    const dateInput = document.getElementById('callDate');
    const timeInput = document.getElementById('callTime');
    
    if (dateInput) {
        // Устанавливаем минимальную дату как сегодня
        const today = new Date().toISOString().split('T')[0];
        dateInput.setAttribute('min', today);
        
        // Устанавливаем максимальную дату как год вперед (динамически)
        const maxDate = new Date();
        maxDate.setFullYear(maxDate.getFullYear() + 1);
        const maxDateString = maxDate.toISOString().split('T')[0];
        dateInput.setAttribute('max', maxDateString);
        
        dateInput.addEventListener('change', function() {
            validateDateField(this);
        });
        
        dateInput.addEventListener('input', function() {
            validateDateField(this);
        });
        
        dateInput.addEventListener('blur', function() {
            validateDateField(this);
        });
    }
    
    if (timeInput) {
        // Устанавливаем ограничения на время (9:00 - 20:00)
        timeInput.setAttribute('min', '09:00');
        timeInput.setAttribute('max', '20:00');
        timeInput.setAttribute('step', '300'); // 5 минут
        
        timeInput.addEventListener('change', function() {
            validateTimeField(this);
        });
        
        timeInput.addEventListener('input', function() {
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
        showFieldError(dateField, 'Введите корректную дату');
        return false;
    }
    
    // Проверяем, что дата не в прошлом
    if (selectedDay < today) {
        showFieldError(dateField, 'Нельзя выбрать дату в прошлом');
        return false;
    }
    
    // Проверяем, что дата не более чем на год вперед (динамически)
    const oneYearFromNow = new Date();
    oneYearFromNow.setFullYear(oneYearFromNow.getFullYear() + 1);
    if (selectedDate > oneYearFromNow) {
        showFieldError(dateField, 'Нельзя выбрать дату более чем на год вперед');
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
        showFieldError(timeField, 'Введите корректное время');
        return false;
    }
    
    if (hours < 9 || hours > 20 || (hours === 20 && minutes > 0)) {
        showFieldError(timeField, 'Время должно быть между 9:00 и 20:00');
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
            const selectedDateTime = new Date(dateField.value + 'T' + timeValue);
            if (selectedDateTime < now) {
                showFieldError(timeField, 'Нельзя выбрать время в прошлом');
                return false;
            }
        }
    }
    
    return true;
}

function validateOrderForm() {
    const form = document.getElementById('orderForm');
    const name = form.querySelector('#clientName').value.trim();
    const phone = form.querySelector('#clientPhone').value.trim();
    const date = form.querySelector('#callDate').value;
    const time = form.querySelector('#callTime').value;
    
    let isValid = true;
    
    // Очищаем все предыдущие ошибки
    clearAllFieldErrors();
    
    // Проверяем имя
    if (name.length < 2) {
        showFieldError(form.querySelector('#clientName'), 'Имя должно содержать минимум 2 символа');
        isValid = false;
    }
    
    // Проверяем телефон
    const phoneRegex = /^[\d\s\+\-\(\)]{10,}$/;
    if (!phoneRegex.test(phone)) {
        showFieldError(form.querySelector('#clientPhone'), 'Введите корректный номер телефона');
        isValid = false;
    }
    
    // Валидация даты и времени (если указаны)
    if (date || time) {
        if (!date) {
            showFieldError(form.querySelector('#callDate'), 'Если указываете время, пожалуйста, выберите дату');
            isValid = false;
        }
        if (!time) {
            showFieldError(form.querySelector('#callTime'), 'Если указываете дату, пожалуйста, выберите время');
            isValid = false;
        }
        
        // Валидация даты и времени
        if (date && time) {
            const selectedDate = new Date(date);
            const now = new Date();
            const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
            const selectedDay = new Date(selectedDate.getFullYear(), selectedDate.getMonth(), selectedDate.getDate());
            
            // Проверяем, что дата не в прошлом
            if (selectedDay < today) {
                showFieldError(form.querySelector('#callDate'), 'Нельзя выбрать дату в прошлом');
                isValid = false;
            }
            
            // Проверяем, что дата не более чем на год вперед (динамически)
            const oneYearFromNow = new Date();
            oneYearFromNow.setFullYear(oneYearFromNow.getFullYear() + 1);
            if (selectedDate > oneYearFromNow) {
                showFieldError(form.querySelector('#callDate'), 'Нельзя выбрать дату более чем на год вперед');
                isValid = false;
            }
            
            // Валидация времени (с 9:00 до 20:00)
            const timeParts = time.split(':');
            const hours = parseInt(timeParts[0], 10);
            const minutes = parseInt(timeParts[1], 10);
            
            if (hours < 9 || hours > 20 || (hours === 20 && minutes > 0)) {
                showFieldError(form.querySelector('#callTime'), 'Время должно быть между 9:00 и 20:00');
                isValid = false;
            }
            
            // Проверяем, что дата и время не в прошлом (для сегодняшнего дня)
            if (selectedDay.getTime() === today.getTime()) {
                const selectedDateTime = new Date(date + 'T' + time);
                if (selectedDateTime < now) {
                    showFieldError(form.querySelector('#callTime'), 'Нельзя выбрать время в прошлом');
                    isValid = false;
                }
            }
        }
    }
    
    return isValid;
}

function showFieldError(field, message) {
    clearFieldError(field);
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error';
    errorDiv.textContent = message;
    errorDiv.style.color = '#dc3545';
    errorDiv.style.fontSize = '12px';
    errorDiv.style.marginTop = '5px';
    
    field.style.borderColor = '#dc3545';
    field.parentNode.appendChild(errorDiv);
}

function clearFieldError(field) {
    const parent = field.parentNode;
    const existingError = parent.querySelector('.field-error');
    if (existingError) {
        existingError.remove();
    }
    field.style.borderColor = '';
}

function clearAllFieldErrors() {
    const form = document.getElementById('orderForm');
    const errorDivs = form.querySelectorAll('.field-error');
    errorDivs.forEach(div => div.remove());
    
    const fields = form.querySelectorAll('input');
    fields.forEach(field => field.style.borderColor = '');
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
        name: clientData.name,
        phone: clientData.phone,
        email: clientData.email || '',
        callTime: clientData.callTime || '',
        orderData: clientData.orderData
    };
    
    // Используем BX.ajax если доступен, иначе fetch
    if (typeof BX !== 'undefined' && BX.ajax) {
        BX.ajax.runComponentAction(calcConfig.component, 'sendOrder', {
            mode: 'class',
            data: serverData
        }).then(function(response) {
            handleOrderResponse(response, submitBtn, originalText);
        }).catch(function(error) {
            console.error('Ошибка отправки заказа:', error);
            handleOrderError(submitBtn, originalText);
        });
    } else {
        fetch('/bitrix/services/main/ajax.php?c=' + calcConfig.component + '&action=sendOrder&mode=class', {
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
        
        // Показываем уведомление об успешной отправке
        const resultDiv = document.getElementById('calcResult');
        if (resultDiv) {
            const successMessage = document.createElement('div');
            successMessage.className = 'result-success';
            successMessage.style.marginTop = '20px';
            successMessage.innerHTML = '<h3>Заказ отправлен!</h3><p>Спасибо за заказ! Наш менеджер свяжется с вами в ближайшее время.</p>';
            resultDiv.appendChild(successMessage);
            
            // Удаляем сообщение через 5 секунд
            setTimeout(() => {
                if (successMessage.parentNode) {
                    successMessage.parentNode.removeChild(successMessage);
                }
            }, 5000);
        }
    } else {
        alert('Ошибка при отправке заказа. Пожалуйста, попробуйте еще раз или свяжитесь с нами по телефону.');
    }
    
    submitBtn.textContent = originalText;
    submitBtn.disabled = false;
}

function handleOrderError(submitBtn, originalText) {
    alert('Произошла ошибка при отправке заказа. Пожалуйста, попробуйте еще раз или свяжитесь с нами по телефону: +7 (846) 206-00-68');
    
    submitBtn.textContent = originalText;
    submitBtn.disabled = false;
}
</script>