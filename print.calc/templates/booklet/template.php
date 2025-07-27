<?php
/** Универсальный шаблон калькулятора */
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

// Подключаем общие стили
$this->addExternalCss($templateFolder.'/../.default/style.css');
// Подключаем специфичные стили для буклетов (если есть)
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
?>

<div class="calc-container">
    <!-- Информационный блок -->
    <div class="calc-disclaimer">
        <p>
            Данные, полученные при расчете на калькуляторе – являются ориентировочными в связи с регулярным изменением стоимости материалов.<br>
            Конечную стоимость заказа уточняйте у менеджера: <a href="tel:+78462060068">+7 (846) 206-00-68</a><br>
            Спасибо за понимание!
        </p>
    </div>
    
    <h2><?= $arResult['DESCRIPTION'] ?? 'Калькулятор печати' ?></h2>
    
    <form id="<?= $calcType ?>CalcForm" class="calc-form">
        
        <?php if (!empty($arResult['PAPER_TYPES'])): ?>
        <!-- Тип бумаги -->
        <div class="form-group">
            <label class="form-label" for="paperType">Тип бумаги:</label>
            <select name="paperType" id="paperType" class="form-control" required>
                <?php foreach ($arResult['PAPER_TYPES'] as $paper): ?>
                    <option value="<?= htmlspecialchars($paper['ID']) ?>"><?= htmlspecialchars($paper['NAME']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>

        <?php if (!empty($arResult['FORMATS'])): ?>
        <!-- Формат -->
        <div class="form-group">
            <label class="form-label" for="size">Формат:</label>
            <select name="size" id="size" class="form-control" required>
                <?php foreach ($arResult['FORMATS'] as $format): ?>
                    <option value="<?= htmlspecialchars($format['ID']) ?>"><?= htmlspecialchars($format['NAME']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>

        <!-- Тираж -->
        <div class="form-group">
            <label class="form-label" for="quantity">Тираж:</label>
            <input name="quantity" 
                   id="quantity" 
                   type="number" 
                   class="form-control" 
                   min="<?= $arResult['MIN_QUANTITY'] ?? 1 ?>" 
                   max="<?= $arResult['MAX_QUANTITY'] ?? '' ?>" 
                   value="<?= $arResult['DEFAULT_QUANTITY'] ?? 1000 ?>" 
                   placeholder="Введите количество"
                   required>
        </div>

        <!-- Тип печати -->
        <div class="form-group">
            <label class="form-label">Тип печати:</label>
            <div class="radio-group">
                <label class="radio-label">
                    <input type="radio" name="printType" value="single" checked> 
                    Односторонняя
                </label>
                <label class="radio-label">
                    <input type="radio" name="printType" value="double"> 
                    Двусторонняя
                </label>
            </div>
        </div>

        <?php if ($calcType === 'booklet' && isset($arResult['MAX_FOLDING'])): ?>
        <!-- Количество сложений для буклетов -->
        <div class="form-group">
            <label class="form-label" for="foldingCount">Количество сложений:</label>
            <select name="foldingCount" id="foldingCount" class="form-control">
                <option value="0">Нет сложений</option>
                <?php for ($i = 1; $i <= $arResult['MAX_FOLDING']; $i++): ?>
                    <option value="<?= $i ?>"><?= $i ?> сложение<?= $i > 1 ? 'я' : '' ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <?php endif; ?>

        <?php 
        // Показываем дополнительные услуги только если они поддерживаются
        $showAdditionalServices = false;
        $supportedServices = [];
        
        if (!empty($features['bigovka'])) {
            $supportedServices[] = ['name' => 'bigovka', 'label' => 'Биговка'];
            $showAdditionalServices = true;
        }
        if (!empty($features['perforation'])) {
            $supportedServices[] = ['name' => 'perforation', 'label' => 'Перфорация'];
            $showAdditionalServices = true;
        }
        if (!empty($features['drill'])) {
            $supportedServices[] = ['name' => 'drill', 'label' => 'Сверление Ø5мм'];
            $showAdditionalServices = true;
        }
        if (!empty($features['numbering'])) {
            $supportedServices[] = ['name' => 'numbering', 'label' => 'Нумерация'];
            $showAdditionalServices = true;
        }
        
        if ($showAdditionalServices): ?>
        <!-- Дополнительные услуги -->
        <div class="form-group">
            <label class="form-label">Дополнительные услуги:</label>
            <div class="checkbox-group">
                <?php foreach ($supportedServices as $service): ?>
                <label class="checkbox-label">
                    <input type="checkbox" name="<?= $service['name'] ?>"> <?= $service['label'] ?>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($features['corner_radius'])): ?>
        <!-- Скругление углов -->
        <div class="form-group">
            <label class="form-label" for="cornerRadius">Количество углов для скругления:</label>
            <select name="cornerRadius" id="cornerRadius" class="form-control">
                <option value="0">Без скругления</option>
                <option value="1">1 угол</option>
                <option value="2">2 угла</option>
                <option value="3">3 угла</option>
                <option value="4">4 угла</option>
            </select>
        </div>
        <?php endif; ?>

        <input type="hidden" name="calcType" value="<?= $calcType ?>">
        <input type="hidden" name="sessid" value="<?= bitrix_sessid() ?>">

        <?php if (!empty($features['lamination'])): ?>
        <!-- Секция ламинации - ПЕРЕМЕЩЕНА ПЕРЕД КНОПКОЙ РАСЧЕТА -->
        <div id="laminationSection" class="lamination-section" style="display: none; margin-top: 20px; margin-bottom: 20px;">
            <h3>Дополнительная ламинация</h3>
            <div id="laminationControls"></div>
            <div id="laminationResult" class="lamination-result"></div>
        </div>
        <?php endif; ?>

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
                    <label class="form-label" for="clientEmail">E-mail <span class="required">*</span>:</label>
                    <input type="email" id="clientEmail" name="clientEmail" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="callDate">Удобная дата для звонка <span class="required">*</span>:</label>
                    <input type="date" id="callDate" name="callDate" class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="callTime">Удобное время для звонка <span class="required">*</span>:</label>
                    <input type="time" id="callTime" name="callTime" class="form-control" required>
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
/* Дополнительные стили для кнопки удаления ламинации */
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

/* Улучшенные стили для секции ламинации */
.lamination-section {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border: 1px solid #dee2e6;
    border-radius: 12px;
    padding: 25px;
    margin: 30px 0;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    position: relative;
    overflow: hidden;
}


.lamination-section h3 {
    margin: 0 0 20px 0;
    color: #495057;
    font-size: 20px;
    font-weight: 600;
}

.lamination-content {
    animation: fadeInUp 0.4s ease-out;
}

.lamination-title {
    margin-bottom: 20px;
    font-weight: 600;
    color: #495057;
}

.lamination-options {
    margin-bottom: 25px;
}

.lamination-button-container {
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px solid #dee2e6;
}

.calc-button-success {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
    border: none;
    padding: 12px 30px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    box-shadow: 0 4px 15px rgba(40, 167, 69, 0.2);
}

.calc-button-success:hover {
    background: linear-gradient(135deg, #218838 0%, #1ea085 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(40, 167, 69, 0.3);
}

.calc-button-success:active {
    transform: translateY(0);
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

/* Стили для формы заказа */
.order-form {
    background: linear-gradient(135deg, #e8f5e8 0%, #f0f8f0 100%);
    border: 2px solid #28a745;
    border-radius: 12px;
    padding: 20px;
    margin: 20px 0;
    box-shadow: 0 4px 15px rgba(40, 167, 69, 0.1);
    animation: slideDown 0.3s ease-out;
}

.order-form h3 {
    color: #155724;
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

@media (max-width: 768px) {
    .order-modal-content {
        margin: 10% auto;
        padding: 20px;
        width: 95%;
    }
    
    .modal-buttons {
        flex-direction: column;
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

    .lamination-info-container {
        flex-direction: column;
        align-items: stretch;
    }
    
    .remove-lamination-btn {
        margin-left: 0;
        width: 100%;
    }
    
    .lamination-section {
        padding: 20px 15px;
        margin: 20px 0;
    }
}
</style>

<script>
// Улучшенная блокировка внешних ошибок
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

// Конфигурация для текущего калькулятора
const calcConfig = {
    type: '<?= $calcType ?>',
    features: <?= json_encode($features) ?>,
    component: 'my:print.calc'
};

// Сохраняем исходный результат без ламинации
let originalResultWithoutLamination = null;
let currentPrintingType = null;
let currentOrderData = null; // Добавляем для сохранения данных заказа

console.log('Конфигурация калькулятора:', calcConfig);

// Функция ожидания BX с таймаутом
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
    
    if (!form) {
        console.error('Форма не найдена:', calcConfig.type + 'CalcForm');
        return;
    }
    if (!resultDiv) {
        console.error('Div результата не найден: calcResult');
        return;
    }
    if (!calcBtn) {
        console.error('Кнопка расчета не найдена: calcBtn');
        return;
    }

    calcBtn.addEventListener('click', function() {
        const data = collectFormData(form);
        data.calcType = calcConfig.type;
        
        resultDiv.innerHTML = '<div class="loading">Выполняется расчет...</div>';

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
        
        resultDiv.innerHTML = '<div class="loading">Выполняется расчет...</div>';

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
function handleResponse(response, resultDiv, isLaminationCalculation = false) {
    if (response && response.data) {
        if (response.data.error) {
            resultDiv.innerHTML = '<div class="result-error">Ошибка: ' + 
                response.data.error + '</div>';
        } else {
            // Сохраняем исходный результат без ламинации
            if (!isLaminationCalculation && !response.data.laminationCost) {
                originalResultWithoutLamination = JSON.parse(JSON.stringify(response.data));
                currentPrintingType = response.data.printingType;
            }
            
            // СНАЧАЛА показываем секцию ламинации, ПОТОМ результат
            if (calcConfig.features.lamination && (response.data.laminationAvailable || response.data.printingType)) {
                showLaminationSection(response.data);
            }
            
            displayResult(response.data, resultDiv);
        }
    } else {
        resultDiv.innerHTML = '<div class="result-error">Некорректный ответ сервера</div>';
        console.error('Неожиданная структура ответа:', response);
    }
}

// Отображение результата
function displayResult(result, resultDiv) {
    // Формируем описание сложений
    let foldingDescription = 'Без сложений';
    if (result.foldingCount && result.foldingCount > 0) {
        foldingDescription = result.foldingCount + ' сложение' + (result.foldingCount > 1 ? 'я' : '');
    }
    
    // Сохраняем данные для заказа
    currentOrderData = {
        calcType: 'booklet',
        product: 'Буклеты',
        size: result.size || 'Не указан',
        printType: result.printingType || 'Не указан',
        quantity: result.quantity || 0,
        totalPrice: result.totalPrice || 0,
        paperType: result.paperType || 'Не указан',
        foldingCount: result.foldingCount || '0',
        foldingDescription: foldingDescription
    };
    
    // Округляем все цены до десятых
    const totalPrice = Math.round((result.totalPrice || 0) * 10) / 10;
    const hasLamination = result.laminationCost && result.laminationCost > 0;
    
    let html = '<div class="result-success">';
    html += '<h3 class="result-title">Результат расчета</h3>';
    html += '<div class="result-price">' + totalPrice + ' <small>₽</small></div>';
    
    // Стандартное отображение для листовок и буклетов
    if (result.printingType) {
        html += '<p><strong>Тип печати:</strong> ' + result.printingType + '</p>';
    }
    
    // Добавляем информацию о сложениях для буклетов
    if (calcConfig.type === 'booklet') {
        const foldingInfo = currentOrderData.foldingDescription;
        html += '<p><strong>Данные сложений:</strong> ' + foldingInfo + '</p>';
    }
    
    // Информация о ламинации с кнопкой удаления
    if (hasLamination) {
        html += '<div class="lamination-info-container">';
        html += '<p class="lamination-info" style="margin: 0;"><strong>Ламинация включена:</strong> ' + Math.round(result.laminationCost * 10) / 10 + ' ₽</p>';
        html += '<button type="button" class="remove-lamination-btn" onclick="removeLamination()">Убрать ламинацию</button>';
        html += '</div>';
        
        // Обновляем данные заказа с ламинацией
        currentOrderData.laminationType = result.laminationType || '';
        currentOrderData.laminationCost = result.laminationCost || 0;
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
    if (hasLamination) html += '<li class="lamination-info">Ламинация: ' + Math.round(result.laminationCost * 10) / 10 + ' ₽</li>';
    
    html += '</ul>';
    html += '</div>';
    html += '</details>';
    
    // Добавляем кнопку заказа
    html += '<button type="button" class="order-button" onclick="openOrderModal()">Заказать буклеты</button>';
    
    html += '</div>';
    
    resultDiv.innerHTML = html;
}

// Функция показа секции ламинации
function showLaminationSection(result) {
    const laminationSection = document.getElementById('laminationSection');
    const controlsDiv = document.getElementById('laminationControls');
    
    if (!laminationSection || !controlsDiv || !calcConfig.features.lamination) {
        return;
    }
    
    // Используем сохраненный тип печати или текущий
    const printingType = currentPrintingType || result.printingType;
    
    console.log('Тип печати для ламинации:', printingType); // Отладка
    
    let html = '<div class="lamination-content">';
    html += '<p class="lamination-title">Добавить ламинацию к заказу:</p>';
    
    // Временно всегда показываем блок с толщиной для цифровой печати
    // if (printingType === 'Офсетная') {
    if (false) { // Отключаем офсетный блок для тестирования
        html += '<div class="lamination-options">';
        html += '<div class="radio-group">';
        html += '<label class="radio-label"><input type="radio" name="laminationType" value="1+0"> Односторонняя (7 руб/лист)</label>';
        html += '<label class="radio-label"><input type="radio" name="laminationType" value="1+1"> Двусторонняя (14 руб/лист)</label>';
        html += '</div>';
        html += '</div>';
    } else {
        // Цифровая печать: с выбором толщины (показываем всегда)
        html += '<div class="lamination-options">';
        html += '<div class="form-group">';
        html += '<label class="form-label">Толщина ламинации:';
        html += '<select name="laminationThickness" class="form-control">';
        html += '<option value="32">32 мкм</option>';
        html += '<option value="75">75 мкм</option>';
        html += '<option value="125">125 мкм</option>';
        html += '<option value="250">250 мкм</option>';
        html += '</select></label>';
        html += '</div>';
        html += '<div class="radio-group">';
        html += '<label class="radio-label"><input type="radio" name="laminationType" value="1+0"> Односторонняя</label>';
        html += '<label class="radio-label"><input type="radio" name="laminationType" value="1+1"> Двусторонняя</label>';
        html += '</div>';
        html += '</div>';
    }
    
    html += '<div class="lamination-button-container">';
    html += '<button type="button" id="laminationBtn" class="calc-button calc-button-success">Пересчитать с ламинацией</button>';
    html += '</div>';
    html += '</div>';
    
    controlsDiv.innerHTML = html;
    
    // Плавно показываем секцию
    laminationSection.style.display = 'block';
    laminationSection.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    
    // Обработчик для кнопки ламинации
    const laminationBtn = document.getElementById('laminationBtn');
    if (laminationBtn) {
        laminationBtn.addEventListener('click', function() {
            calculateLamination(result);
        });
    }
    
    // Добавляем обработчики для радио кнопок чтобы убирать ошибку
    const radioButtons = controlsDiv.querySelectorAll('input[name="laminationType"]');
    radioButtons.forEach(radio => {
        radio.addEventListener('change', function() {
            const laminationResult = document.getElementById('laminationResult');
            if (laminationResult && laminationResult.innerHTML.includes('Выберите тип ламинации')) {
                laminationResult.innerHTML = '';
            }
        });
    });
}

// Функция расчета с ламинацией
function calculateLamination(originalResult) {
    const laminationType = document.querySelector('input[name="laminationType"]:checked');
    const laminationThickness = document.querySelector('select[name="laminationThickness"]');
    const resultDiv = document.getElementById('calcResult');
    const laminationResult = document.getElementById('laminationResult');
    
    if (!laminationType) {
        laminationResult.innerHTML = '<div class="result-error">Выберите тип ламинации</div>';
        return;
    }
    
    const form = document.getElementById(calcConfig.type + 'CalcForm');
    const quantity = parseInt(form.querySelector('input[name="quantity"]').value);
    
    // Используем сохраненный результат или текущий
    const baseResult = originalResultWithoutLamination || originalResult;
    const printingType = currentPrintingType || baseResult.printingType;
    
    let laminationCost = 0;
    let laminationDescription = '';
    
    if (printingType === 'Офсетная') {
        // Офсетная печать: простые тарифы
        if (laminationType.value === '1+0') {
            laminationCost = quantity * 7;
            laminationDescription = 'Односторонняя (7 руб/лист)';
        } else {
            laminationCost = quantity * 14;
            laminationDescription = 'Двусторонняя (14 руб/лист)';
        }
    } else {
        // Цифровая печать: зависит от толщины
        const thickness = laminationThickness ? laminationThickness.value : '32';
        const rates = {
            '32': { '1+0': 40, '1+1': 80 },
            '75': { '1+0': 60, '1+1': 120 },
            '125': { '1+0': 80, '1+1': 160 },
            '250': { '1+0': 90, '1+1': 180 }
        };
        
        laminationCost = quantity * rates[thickness][laminationType.value];
        const laminationName = laminationType.value === '1+0' ? 'Односторонняя' : 'Двусторонняя';
        laminationDescription = `${laminationName} ${thickness} мкм (${rates[thickness][laminationType.value]} руб/лист)`;
    }
    
    // Создаем новый результат с ламинацией
    const newResult = JSON.parse(JSON.stringify(baseResult));
    newResult.totalPrice = baseResult.totalPrice + laminationCost;
    newResult.laminationCost = laminationCost;
    newResult.laminationDescription = laminationDescription;
    
    displayResult(newResult, resultDiv);
    
    // Очищаем результат ламинации
    laminationResult.innerHTML = '';
}

// Функция удаления ламинации
function removeLamination() {
    const resultDiv = document.getElementById('calcResult');
    
    if (originalResultWithoutLamination) {
        displayResult(originalResultWithoutLamination, resultDiv);
        // Сбрасываем выбор ламинации
        const laminationRadios = document.querySelectorAll('input[name="laminationType"]');
        laminationRadios.forEach(radio => radio.checked = false);
    }
}

// Сбор данных формы
function collectFormData(form) {
    const formData = new FormData(form);
    const data = {};
    
    // Собираем все поля формы
    for (let [key, value] of formData.entries()) {
        data[key] = value;
    }
    
    // Добавляем чекбоксы и радио кнопки
    const checkboxes = ['bigovka', 'perforation', 'drill', 'numbering', 'includePodramnik'];
    checkboxes.forEach(name => {
        const checkbox = form.querySelector(`input[name="${name}"]`);
        if (checkbox) {
            data[name] = checkbox.checked;
        }
    });

    // Добавляем данные ламинации
    const laminationType = form.querySelector('input[name="laminationType"]:checked');
    const laminationThickness = form.querySelector('select[name="laminationThickness"]');
    if (laminationType) {
        data.laminationType = laminationType.value;
        console.log('Тип ламинации из формы:', data.laminationType);
        
        if (laminationThickness) {
            data.laminationThickness = laminationThickness.value;
            console.log('Толщина ламинации из формы:', data.laminationThickness);
        }
    }

    // Проверяем foldingCount отдельно
    const foldingSelect = form.querySelector('select[name="foldingCount"]');
    if (foldingSelect) {
        data.foldingCount = parseInt(foldingSelect.value) || 0;
        console.log('Количество сложений из формы:', data.foldingCount);
    }

    console.log('Собранные данные формы:', data);
    return data;
}

// Запуск инициализации
console.log('Калькулятор:', calcConfig.type);
console.log('Время запуска:', new Date().toLocaleTimeString());

document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM загружен, ждем BX...');
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
        // Устанавливаем минимальную дату - сегодня
        const today = new Date().toISOString().split('T')[0];
        dateInput.setAttribute('min', today);
        
        dateInput.addEventListener('input', function() {
            clearFieldError(this);
        });
    }
    
    if (timeInput) {
        timeInput.addEventListener('input', function() {
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
            errorMessage.remove();
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
        showFieldError(nameField, 'Введите ваше имя');
        hasErrors = true;
    } else if (name.length < 2) {
        showFieldError(nameField, 'Имя должно содержать минимум 2 символа');
        hasErrors = true;
    }
    
    // Валидация телефона
    if (!phone) {
        showFieldError(phoneField, 'Введите ваш телефон');
        hasErrors = true;
    } else {
        const phoneRegex = /^[\+]?[0-9\(\)\-\s]+$/;
        if (!phoneRegex.test(phone) || phone.length < 10) {
            showFieldError(phoneField, 'Введите корректный номер телефона');
            hasErrors = true;
        }
    }
    
    // Валидация email (теперь обязательное)
    if (!email) {
        showFieldError(emailField, 'Введите ваш email');
        hasErrors = true;
    } else {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            showFieldError(emailField, 'Введите корректный email адрес');
            hasErrors = true;
        }
    }
    
    // Валидация даты (теперь обязательная)
    if (!date) {
        showFieldError(dateField, 'Выберите удобную дату для звонка');
        hasErrors = true;
    } else {
        const selectedDate = new Date(date);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        if (selectedDate < today) {
            showFieldError(dateField, 'Дата не может быть в прошлом');
            hasErrors = true;
        }
    }
    
    // Валидация времени (теперь обязательное)
    if (!time) {
        showFieldError(timeField, 'Выберите удобное время для звонка');
        hasErrors = true;
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
    
    // Формируем данные заказа для буклетов
    const orderData = {
        calcType: 'booklet',
        product: 'Буклеты',
        quantity: formData.quantity || 0,
        size: formData.size || 'Не указан',
        paperType: formData.paperType || 'Не указан',
        printType: formData.printType === 'single' ? 'Односторонняя' : 'Двусторонняя',
        totalPrice: totalPrice,
        foldingCount: formData.foldingCount || 0
    };
    
    // Добавляем информацию о количестве сложений
    if (formData.foldingCount && formData.foldingCount > 0) {
        orderData.foldingDescription = formData.foldingCount + ' сложение' + (formData.foldingCount > 1 ? 'я' : '');
    } else {
        orderData.foldingDescription = 'Без сложений';
    }
    
    // Отладочная информация
    console.log('Данные сложений:', {
        foldingCount: formData.foldingCount,
        foldingDescription: orderData.foldingDescription
    });
    
    // Добавляем дополнительные услуги
    let additionalServices = [];
    if (formData.bigovka) additionalServices.push('Биговка');
    if (formData.perforation) additionalServices.push('Перфорация');
    if (formData.drill) additionalServices.push('Сверление');
    if (formData.numbering) additionalServices.push('Нумерация');
    if (additionalServices.length > 0) {
        orderData.additionalServices = additionalServices.join(', ');
    }
    
    // Добавляем данные ламинации, если она есть в текущем расчете
    const laminationRadio = document.querySelector('input[name="laminationType"]:checked');
    const laminationThicknessSelect = document.querySelector('select[name="laminationThickness"]');
    
    if (laminationRadio || formData.laminationType) {
        // Берем данные из текущего выбора или из formData
        const laminationType = laminationRadio ? laminationRadio.value : formData.laminationType;
        const laminationThickness = laminationThicknessSelect ? laminationThicknessSelect.value : formData.laminationThickness;
        
        orderData.laminationType = laminationType;
        
        if (laminationThickness) {
            orderData.laminationThickness = laminationThickness;
        }
        
        // Получаем стоимость ламинации из отображаемого результата
        const laminationInfo = resultDiv.querySelector('.lamination-info');
        if (laminationInfo) {
            const laminationCostText = laminationInfo.textContent;
            const laminationCostMatch = laminationCostText.match(/(\d+(?:\.\d+)?)/);
            if (laminationCostMatch) {
                orderData.laminationCost = parseFloat(laminationCostMatch[1]);
            }
        }
        
        // Формируем полное описание ламинации
        let laminationDescription = laminationType;
        if (laminationThickness) {
            laminationDescription += ` ${laminationThickness} мкм`;
        }
        orderData.laminationDescription = laminationDescription;
        
        console.log('Данные ламинации:', {
            type: laminationType,
            thickness: laminationThickness,
            description: laminationDescription,
            cost: orderData.laminationCost
        });
    }
    
    orderDataInput.value = JSON.stringify(orderData);
    
    // Отладочная информация - выводим все данные заказа
    console.log('Полные данные заказа для отправки:', orderData);
    
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
    });
    
    // Обработчик отправки формы
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Валидация формы
        if (!validateOrderForm()) {
            return; // Останавливаем отправку если есть ошибки
        }
        
        const clientData = {
            name: document.getElementById('clientName').value,
            phone: document.getElementById('clientPhone').value,
            email: document.getElementById('clientEmail').value,
            callDate: document.getElementById('callDate').value,
            callTime: document.getElementById('callTime').value,
            orderData: document.getElementById('orderData').value
        };
        
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
    
    // Отладочная информация для проверки данных
    console.log('Отправляемые данные клиента:', clientData);
    console.log('Распарсенные данные заказа:', orderData);
    
    // Формируем правильные данные для отправки на сервер
    const serverData = {
        name: clientData.name,
        phone: clientData.phone,
        email: clientData.email || '',
        callDate: clientData.callDate || '',
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
    } else {
        alert('Ошибка при отправке заказа: ' + (response.data ? response.data.error : 'Неизвестная ошибка'));
    }
    
    submitBtn.textContent = originalText;
    submitBtn.disabled = false;
}

function handleOrderError(submitBtn, originalText) {
    alert('Ошибка при отправке заказа. Попробуйте еще раз.');
    submitBtn.textContent = originalText;
    submitBtn.disabled = false;
}
</script>