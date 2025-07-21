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

.lamination-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, #28a745, #20c997);
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

@media (max-width: 768px) {
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
    
    // Информация о ламинации с кнопкой удаления
    if (hasLamination) {
        html += '<div class="lamination-info-container">';
        html += '<p class="lamination-info" style="margin: 0;"><strong>Ламинация включена:</strong> ' + Math.round(result.laminationCost * 10) / 10 + ' ₽</p>';
        html += '<button type="button" class="remove-lamination-btn" onclick="removeLamination()">Убрать ламинацию</button>';
        html += '</div>';
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
        if (laminationThickness) {
            data.laminationThickness = laminationThickness.value;
        }
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
});
</script>