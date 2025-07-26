<?php
/** Шаблон калькулятора автовизиток */
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

// Подключаем общие стили
$this->addExternalCss($templateFolder.'/../.default/style.css');
// Подключаем специфичные стили для автовизиток (если есть)
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
            <strong>Автовизитки:</strong> <?= $arResult['format_info'] ?? '' ?><br>
            <?= $arResult['paper_info'] ?? '' ?><br>
            Спасибо за понимание!
        </p>
    </div>
    
    <h2><?= $arResult['DESCRIPTION'] ?? 'Калькулятор печати автовизиток' ?></h2>
    
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

        <!-- Фиксированный формат Евро (скрытый) -->
        <div class="form-group">
            <label class="form-label" for="size">Формат:</label>
            <select name="size" id="size" class="form-control" required>
                <option value="Евро" selected>Евро (99×210 мм)</option>
            </select>
            <small class="text-muted">Фиксированный формат для автовизиток</small>
        </div>

        <!-- Тираж -->
        <div class="form-group">
            <label class="form-label" for="quantity">Тираж:</label>
            <input name="quantity" 
                   id="quantity" 
                   type="number" 
                   class="form-control" 
                   min="<?= $arResult['min_quantity'] ?? 1 ?>" 
                   max="<?= $arResult['max_quantity'] ?? 50000 ?>" 
                   value="<?= $arResult['default_quantity'] ?? 500 ?>" 
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
        
        <!-- Форма заказа (скрыта по умолчанию) -->
        <div id="orderForm" class="order-form" style="display: none;">
            <h3>Заказать автовизитки</h3>
            <form id="orderFormFields">
                <div class="form-group">
                    <label class="form-label" for="customerName">Ваше имя *:</label>
                    <input type="text" id="customerName" name="customerName" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="customerPhone">Телефон *:</label>
                    <input type="tel" id="customerPhone" name="customerPhone" class="form-control" required 
                           placeholder="+7 (___) ___-__-__">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="customerEmail">E-mail:</label>
                    <input type="email" id="customerEmail" name="customerEmail" class="form-control" 
                           placeholder="your@email.com">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="callTime">Удобное время для звонка:</label>
                    <input type="datetime-local" id="callTime" name="callTime" class="form-control">
                </div>
                
                <div class="order-buttons">
                    <button type="button" id="submitOrder" class="calc-button calc-button-success">
                        Отправить заказ
                    </button>
                    <button type="button" id="cancelOrder" class="calc-button calc-button-secondary">
                        Отмена
                    </button>
                </div>
            </form>
        </div>
        
        <div class="calc-spacer"></div>
    </form>

    <div class="calc-thanks">
        <p>Спасибо, что Вы с нами!</p>
    </div>
</div>

<style>
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
    padding: 15px;
    margin: 15px 0;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    position: relative;
    overflow: hidden;
}

.lamination-section h3 {
    margin: 0 0 10px 0;
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
    margin-bottom: 15px;
}

.lamination-button-container {
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px solid #dee2e6;
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

.calc-button-success {
    background: #28a745;
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

.calc-button-success:hover {
    background: #218838;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
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
    background: #007bff;
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 6px;
    font-size: 16px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s;
    margin-top: 15px;
    width: 100%;
    max-width: 300px;
    margin-left: auto;
    margin-right: auto;
    display: block;
}

.order-button:hover {
    background: #0056b3;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
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
            
            displayResult(response.data, resultDiv);
            
            // Показываем секцию ламинации если доступна
            if (calcConfig.features.lamination && (response.data.laminationAvailable || response.data.printingType)) {
                showLaminationSection(response.data);
            }
        }
    } else {
        resultDiv.innerHTML = '<div class="result-error">Некорректный ответ сервера</div>';
        console.error('Неожиданная структура ответа:', response);
    }
}

// Отображение результата
function displayResult(result, resultDiv) {
    // Сохраняем данные для заказа
    currentOrderData = {
        calcType: 'avtoviz',
        product: 'Автовизитки',
        size: result.format || 'Евро (99×210 мм)',
        printType: result.printingType || 'Не указан',
        quantity: result.quantity || 0,
        totalPrice: result.totalPrice || 0,
        paperType: result.paperType || 'Не указан'
    };
    
    // Округляем все цены до десятых
    const totalPrice = Math.round((result.totalPrice || 0) * 10) / 10;
    const hasLamination = result.laminationCost && result.laminationCost > 0;
    
    let html = '<div class="result-success">';
    html += '<h3 class="result-title">Результат расчета</h3>';
    html += '<div class="result-price">' + totalPrice + ' <small>₽</small></div>';
    
    // Стандартное отображение для автовизиток
    if (result.printingType) {
        html += '<p><strong>Тип печати:</strong> ' + result.printingType + '</p>';
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
    html += '<button type="button" class="order-button" onclick="showOrderForm()">Заказать автовизитки</button>';
    
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
    
    let html = '<div class="lamination-content">';
    html += '<p class="lamination-title">Добавить ламинацию к заказу:</p>';
    
    if (printingType === 'Офсетная') {
        html += '<div class="lamination-options">';
        html += '<div class="radio-group">';
        html += '<label class="radio-label"><input type="radio" name="laminationType" value="1+0"> 1+0 (7 руб/лист)</label>';
        html += '<label class="radio-label"><input type="radio" name="laminationType" value="1+1"> 1+1 (14 руб/лист)</label>';
        html += '</div>';
        html += '</div>';
    } else {
        html += '<div class="lamination-options">';
        html += '<div class="form-group">';
        html += '<label class="form-label">Толщина:';
        html += '<select name="laminationThickness" class="form-control">';
        html += '<option value="32">32 мкм</option>';
        html += '<option value="75">75 мкм</option>';
        html += '<option value="125">125 мкм</option>';
        html += '<option value="250">250 мкм</option>';
        html += '</select></label>';
        html += '</div>';
        html += '<div class="radio-group">';
        html += '<label class="radio-label"><input type="radio" name="laminationType" value="1+0"> 1+0 (x1)</label>';
        html += '<label class="radio-label"><input type="radio" name="laminationType" value="1+1"> 1+1 (x2)</label>';
        html += '</div>';
        html += '</div>';
    }
    
    html += '<div class="lamination-button-container">';
    html += '<button type="button" id="laminationBtn" class="calc-button calc-button-success">Пересчитать с ламинацией</button>';
    html += '</div>';
    html += '</div>';
    
    controlsDiv.innerHTML = html;
    laminationSection.style.display = 'block';
    
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
            laminationDescription = '1+0 (7 руб/лист)';
        } else {
            laminationCost = quantity * 14;
            laminationDescription = '1+1 (14 руб/лист)';
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
        laminationDescription = `${laminationType.value} ${thickness} мкм (${rates[thickness][laminationType.value]} руб/лист)`;
    }
    
    // Создаем новый результат с ламинацией
    const newResult = JSON.parse(JSON.stringify(baseResult));
    newResult.totalPrice = baseResult.totalPrice + laminationCost;
    newResult.laminationCost = laminationCost;
    newResult.laminationDescription = laminationDescription;
    
    displayResult(newResult, resultDiv);
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
    
    // Добавляем чекбоксы
    const checkboxes = ['bigovka', 'perforation', 'drill', 'numbering'];
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
        if (laminationThickness) {
            data.laminationThickness = laminationThickness.value;
        }
    }

    console.log('Собранные данные формы:', data);
    return data;
}

// Функции для работы с заказами
function showOrderForm() {
    const orderForm = document.getElementById('orderForm');
    const calcResult = document.getElementById('calcResult');
    
    if (orderForm && calcResult) {
        orderForm.style.display = 'block';
        
        // Прокручиваем к форме заказа
        orderForm.scrollIntoView({ 
            behavior: 'smooth', 
            block: 'start' 
        });
        
        // Инициализируем обработчики формы заказа
        initOrderFormHandlers();
    }
}

function hideOrderForm() {
    const orderForm = document.getElementById('orderForm');
    if (orderForm) {
        orderForm.style.display = 'none';
    }
}

function initOrderFormHandlers() {
    const submitBtn = document.getElementById('submitOrder');
    const cancelBtn = document.getElementById('cancelOrder');
    
    // Удаляем старые обработчики
    if (submitBtn) {
        submitBtn.replaceWith(submitBtn.cloneNode(true));
    }
    if (cancelBtn) {
        cancelBtn.replaceWith(cancelBtn.cloneNode(true));
    }
    
    // Добавляем новые обработчики
    const newSubmitBtn = document.getElementById('submitOrder');
    const newCancelBtn = document.getElementById('cancelOrder');
    
    if (newSubmitBtn) {
        newSubmitBtn.addEventListener('click', submitOrder);
    }
    
    if (newCancelBtn) {
        newCancelBtn.addEventListener('click', hideOrderForm);
    }
    
    // Маска для телефона
    const phoneInput = document.getElementById('customerPhone');
    if (phoneInput) {
        phoneInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 0) {
                if (value[0] === '8') value = '7' + value.slice(1);
                if (value[0] !== '7') value = '7' + value;
                
                let formatted = '+7';
                if (value.length > 1) formatted += ' (' + value.slice(1, 4);
                if (value.length > 4) formatted += ') ' + value.slice(4, 7);
                if (value.length > 7) formatted += '-' + value.slice(7, 9);
                if (value.length > 9) formatted += '-' + value.slice(9, 11);
                
                e.target.value = formatted;
            }
        });
    }
}

function submitOrder() {
    const form = document.getElementById('orderFormFields');
    const submitBtn = document.getElementById('submitOrder');
    
    if (!form || !currentOrderData) {
        alert('Ошибка: данные заказа не найдены');
        return;
    }
    
    // Собираем данные формы
    const formData = new FormData(form);
    const name = formData.get('customerName')?.trim();
    const phone = formData.get('customerPhone')?.trim();
    const email = formData.get('customerEmail')?.trim();
    const callTime = formData.get('callTime')?.trim();
    
    // Валидация
    if (!name || name.length < 2) {
        alert('Пожалуйста, введите ваше имя (минимум 2 символа)');
        return;
    }
    
    if (!phone || phone.length < 10) {
        alert('Пожалуйста, введите корректный номер телефона');
        return;
    }
    
    if (email && !isValidEmail(email)) {
        alert('Пожалуйста, введите корректный email адрес');
        return;
    }
    
    // Блокируем кнопку
    submitBtn.disabled = true;
    submitBtn.textContent = 'Отправляем...';
    
    // Подготавливаем данные для отправки
    const orderData = JSON.stringify(currentOrderData);
    
    const requestData = {
        name: name,
        phone: phone,
        email: email,
        callTime: callTime,
        orderData: orderData,
        sessid: document.querySelector('input[name="sessid"]')?.value || ''
    };
    
    // Отправляем заказ
    if (typeof BX !== 'undefined' && BX.ajax) {
        BX.ajax.runComponentAction(calcConfig.component, 'sendOrder', {
            mode: 'class',
            data: requestData
        }).then(function(response) {
            handleOrderResponse(response, submitBtn);
        }).catch(function(error) {
            console.error('Ошибка отправки заказа:', error);
            handleOrderError(error, submitBtn);
        });
    } else {
        // Запасной вариант
        fetch('/bitrix/services/main/ajax.php?c=' + calcConfig.component + '&action=sendOrder&mode=class', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams(requestData)
        })
        .then(response => response.json())
        .then(response => handleOrderResponse(response, submitBtn))
        .catch(error => handleOrderError(error, submitBtn));
    }
}

function handleOrderResponse(response, submitBtn) {
    submitBtn.disabled = false;
    submitBtn.textContent = 'Отправить заказ';
    
    if (response && response.data) {
        if (response.data.success) {
            alert('Заказ успешно отправлен! Мы свяжемся с вами в ближайшее время.');
            hideOrderForm();
            // Очищаем форму
            document.getElementById('orderFormFields').reset();
        } else {
            alert('Ошибка: ' + (response.data.error || 'Неизвестная ошибка'));
        }
    } else {
        alert('Ошибка: некорректный ответ сервера');
    }
}

function handleOrderError(error, submitBtn) {
    submitBtn.disabled = false;
    submitBtn.textContent = 'Отправить заказ';
    
    console.error('Ошибка отправки заказа:', error);
    alert('Ошибка отправки заказа. Пожалуйста, попробуйте позже или свяжитесь с нами по телефону.');
}

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Запуск инициализации
console.log('Калькулятор:', calcConfig.type);
console.log('Время запуска:', new Date().toLocaleTimeString());

document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM загружен, ждем BX...');
    waitForBX(initWithBX, initWithoutBX, 3000);
});
</script>