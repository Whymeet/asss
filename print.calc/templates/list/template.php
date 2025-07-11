<?php
/** Универсальный шаблон калькулятора */
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

// Проверяем, что конфигурация загружена
if (!$arResult['CONFIG_LOADED']) {
    echo '<div style="color: red; padding: 20px;">Ошибка: Конфигурация калькулятора не загружена</div>';
    return;
}

// Принудительно подключаем основные скрипты Битрикса
CJSCore::Init(['ajax', 'window']);

$calcType = $arResult['CALC_TYPE'];
$features = $arResult['FEATURES'] ?? [];
?>

<div class="calc-container" style="max-width: 800px; font-family: Arial, sans-serif;">
    <h2><?= $arResult['DESCRIPTION'] ?? 'Калькулятор печати' ?></h2>
    
    <form id="<?= $calcType ?>CalcForm" style="background: #f9f9f9; padding: 20px; border-radius: 8px;">
        
        <?php if (!empty($arResult['PAPER_TYPES'])): ?>
        <!-- Тип бумаги -->
        <div style="margin: 15px 0;">
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Тип бумаги:</label>
            <select name="paperType" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" required>
                <?php foreach ($arResult['PAPER_TYPES'] as $paper): ?>
                    <option value="<?= htmlspecialchars($paper['ID']) ?>"><?= htmlspecialchars($paper['NAME']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>

        <?php if (!empty($arResult['FORMATS'])): ?>
        <!-- Формат -->
        <div style="margin: 15px 0;">
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Формат:</label>
            <select name="size" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" required>
                <?php foreach ($arResult['FORMATS'] as $format): ?>
                    <option value="<?= htmlspecialchars($format['ID']) ?>"><?= htmlspecialchars($format['NAME']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>

        <!-- Тираж -->
        <div style="margin: 15px 0;">
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Тираж:</label>
            <input name="quantity" type="number" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" 
                   min="<?= $arResult['MIN_QUANTITY'] ?? 1 ?>" 
                   max="<?= $arResult['MAX_QUANTITY'] ?? '' ?>" 
                   value="<?= $arResult['DEFAULT_QUANTITY'] ?? 1000 ?>" required>
        </div>

        <!-- Тип печати -->
        <div style="margin: 15px 0;">
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Тип печати:</label>
            <div>
                <label style="margin-right: 15px;"><input type="radio" name="printType" value="single" checked> Односторонняя</label>
                <label><input type="radio" name="printType" value="double"> Двусторонняя</label>
            </div>
        </div>

        <?php if ($calcType === 'booklet' && isset($arResult['MAX_FOLDING'])): ?>
        <!-- Количество сложений для буклетов -->
        <div style="margin: 15px 0;">
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Количество сложений:</label>
            <select name="foldingCount" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
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
            $supportedServices[] = 'bigovka';
            $showAdditionalServices = true;
        }
        if (!empty($features['perforation'])) {
            $supportedServices[] = 'perforation';
            $showAdditionalServices = true;
        }
        if (!empty($features['drill'])) {
            $supportedServices[] = 'drill';
            $showAdditionalServices = true;
        }
        if (!empty($features['numbering'])) {
            $supportedServices[] = 'numbering';
            $showAdditionalServices = true;
        }
        
        if ($showAdditionalServices): ?>
        <!-- Дополнительные услуги -->
        <div style="margin: 15px 0;">
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Дополнительные услуги:</label>
            <div>
                <?php if (in_array('bigovka', $supportedServices)): ?>
                <label style="display: block;"><input type="checkbox" name="bigovka"> Биговка</label>
                <?php endif; ?>
                <?php if (in_array('perforation', $supportedServices)): ?>
                <label style="display: block;"><input type="checkbox" name="perforation"> Перфорация</label>
                <?php endif; ?>
                <?php if (in_array('drill', $supportedServices)): ?>
                <label style="display: block;"><input type="checkbox" name="drill"> Сверление Ø5мм</label>
                <?php endif; ?>
                <?php if (in_array('numbering', $supportedServices)): ?>
                <label style="display: block;"><input type="checkbox" name="numbering"> Нумерация</label>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($features['corner_radius'])): ?>
        <!-- Скругление углов -->
        <div style="margin: 15px 0;">
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Количество углов:</label>
            <select name="cornerRadius" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                <option value="0">Без скругления</option>
                <option value="1">1 угол</option>
                <option value="2">2 угла</option>
                <option value="3">3 угла</option>
                <option value="4">4 угла</option>
            </select>
        </div>
        <?php endif; ?>

        <?php if (!empty($features['lamination'])): ?>
        <!-- Секция ламинации -->
        <div id="laminationSection" style="display: none; margin: 15px 0; padding: 15px; border: 2px solid #eee; border-radius: 8px; background: #f8f9fa;">
            <h3 style="margin-top: 0;">Дополнительная ламинация</h3>
            <div id="laminationControls"></div>
            <div id="laminationResult" style="margin-top: 15px;"></div>
        </div>
        <?php endif; ?>

        <input type="hidden" name="calcType" value="<?= $calcType ?>">
        <input type="hidden" name="sessid" value="<?= bitrix_sessid() ?>">

        <button id="calcBtn" type="button" style="padding: 12px 24px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px;">Рассчитать</button>
        
        <div id="calcResult" style="margin-top: 20px;"></div>
    </form>
</div>

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
    console.log('Инициализация с BX.ajax для', calcConfig.type);
    
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

    console.log('Все элементы формы найдены');

    calcBtn.addEventListener('click', function() {
        console.log('Клик по кнопке расчета');
        
        const data = collectFormData(form);
        data.calcType = calcConfig.type;
        
        resultDiv.innerHTML = '<div style="padding: 10px; color: #666;">Расчет...</div>';

        console.log('Отправка данных через BX.ajax:', data);

        BX.ajax.runComponentAction(calcConfig.component, 'calc', {
            mode: 'class',
            data: data
        }).then(function(response) {
            console.log('Получен ответ BX:', response);
            handleResponse(response, resultDiv);
        }).catch(function(error) {
            console.error('Ошибка BX:', error);
            resultDiv.innerHTML = '<div style="color: red; padding: 10px; background: #ffebee; border-radius: 4px;">Ошибка соединения: ' + 
                (error.message || 'Неизвестная ошибка') + '</div>';
        });
    });
}

// Запасной вариант без BX
function initWithoutBX() {
    console.log('Инициализация без BX (fetch) для', calcConfig.type);
    
    const form = document.getElementById(calcConfig.type + 'CalcForm');
    const resultDiv = document.getElementById('calcResult');
    const calcBtn = document.getElementById('calcBtn');
    
    if (!form || !resultDiv || !calcBtn) {
        console.error('Элементы формы не найдены');
        return;
    }

    calcBtn.addEventListener('click', function() {
        console.log('Отправка через fetch');
        
        const data = collectFormData(form);
        data.calcType = calcConfig.type;
        
        resultDiv.innerHTML = '<div style="padding: 10px; color: #666;">Расчет...</div>';

        console.log('Отправка данных через fetch:', data);

        fetch('/bitrix/services/main/ajax.php?c=' + calcConfig.component + '&action=calc&mode=class', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams(data)
        })
        .then(response => {
            console.log('Статус ответа fetch:', response.status);
            return response.json();
        })
        .then(response => {
            console.log('Получен ответ fetch:', response);
            handleResponse(response, resultDiv);
        })
        .catch(error => {
            console.error('Ошибка fetch:', error);
            resultDiv.innerHTML = '<div style="color: red; padding: 10px; background: #ffebee; border-radius: 4px;">Ошибка соединения: ' + 
                error.message + '</div>';
        });
    });
}

// Обработка ответа сервера
function handleResponse(response, resultDiv) {
    console.log('Обработка ответа:', response);
    
    if (response && response.data) {
        if (response.data.error) {
            resultDiv.innerHTML = '<div style="color: red; padding: 10px; background: #ffebee; border-radius: 4px;">Ошибка: ' + 
                response.data.error + '</div>';
        } else {
            displayResult(response.data, resultDiv);
            // Показываем секцию ламинации если доступна
            if (calcConfig.features.lamination && (response.data.laminationAvailable || response.data.printingType)) {
                showLaminationSection(response.data);
            }
        }
    } else {
        resultDiv.innerHTML = '<div style="color: red; padding: 10px; background: #ffebee; border-radius: 4px;">Некорректный ответ сервера</div>';
        console.error('Неожиданная структура ответа:', response);
    }
}

// Отображение результата
function displayResult(result, resultDiv) {
    console.log('Отображение результата:', result);
    
    // Округляем все цены до десятых
    const totalPrice = Math.round((result.totalPrice || 0) * 10) / 10;
    
    let html = '<div style="padding: 20px; background: #e8f5e8; border-radius: 8px; border: 1px solid #4caf50;">';
    html += '<h3 style="margin-top: 0; color: #2e7d32;">Результат расчета</h3>';
    html += '<div style="font-size: 24px; font-weight: bold; color: #1b5e20; margin: 15px 0;">Стоимость: ' + totalPrice + ' ₽</div>';
    
    // Стандартное отображение для листовок и буклетов
    if (result.printingType) {
        html += '<p><strong>Тип печати:</strong> ' + result.printingType + '</p>';
    }
    
    html += '<details style="margin-top: 15px;"><summary style="cursor: pointer; font-weight: bold;">Подробности расчета</summary>';
    html += '<div style="margin-top: 10px; padding: 10px; background: white; border-radius: 4px;">';
    html += '<ul style="margin: 0; padding-left: 20px;">';
    
    if (result.baseA3Sheets) html += '<li>Листов A3: ' + result.baseA3Sheets + '</li>';
    if (result.printingCost) html += '<li>Стоимость печати: ' + Math.round(result.printingCost * 10) / 10 + ' ₽</li>';
    if (result.paperCost) html += '<li>Стоимость бумаги: ' + Math.round(result.paperCost * 10) / 10 + ' ₽</li>';
    if (result.plateCost && result.plateCost > 0) html += '<li>Стоимость пластин: ' + Math.round(result.plateCost * 10) / 10 + ' ₽</li>';
    if (result.additionalCosts && result.additionalCosts > 0) html += '<li>Дополнительные услуги: ' + Math.round(result.additionalCosts * 10) / 10 + ' ₽</li>';
    
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
        console.log('Секция ламинации недоступна');
        return;
    }
    
    console.log('Показываем секцию ламинации');
    
    let html = '<p style="margin-bottom: 15px;">Добавить ламинацию к заказу:</p>';
    
    if (result.printingType === 'Офсетная') {
        html += '<div style="margin: 10px 0;">';
        html += '<label style="display: block; margin: 5px 0;"><input type="radio" name="laminationType" value="1+0"> 1+0 (7 руб/лист)</label>';
        html += '<label style="display: block; margin: 5px 0;"><input type="radio" name="laminationType" value="1+1"> 1+1 (14 руб/лист)</label>';
        html += '</div>';
    } else {
        html += '<div style="margin: 10px 0;">';
        html += '<label style="display: block; margin-bottom: 10px;">Толщина: ';
        html += '<select name="laminationThickness" style="padding: 5px; margin-left: 10px;">';
        html += '<option value="32">32 мкм</option>';
        html += '<option value="75">75 мкм</option>';
        html += '<option value="125">125 мкм</option>';
        html += '<option value="250">250 мкм</option>';
        html += '</select></label>';
        html += '<label style="display: block; margin: 5px 0;"><input type="radio" name="laminationType" value="1+0"> 1+0 (x1)</label>';
        html += '<label style="display: block; margin: 5px 0;"><input type="radio" name="laminationType" value="1+1"> 1+1 (x2)</label>';
        html += '</div>';
    }
    
    html += '<button type="button" id="laminationBtn" style="padding: 8px 16px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; margin-top: 10px;">Пересчитать с ламинацией</button>';
    
    controlsDiv.innerHTML = html;
    laminationSection.style.display = 'block';
    
    // Обработчик для кнопки ламинации
    const laminationBtn = document.getElementById('laminationBtn');
    if (laminationBtn) {
        laminationBtn.addEventListener('click', function() {
            calculateLamination(result);
        });
    }
}

// Функция расчета с ламинацией
function calculateLamination(originalResult) {
    const laminationType = document.querySelector('input[name="laminationType"]:checked');
    const laminationThickness = document.querySelector('select[name="laminationThickness"]');
    const resultDiv = document.getElementById('calcResult');
    const laminationResult = document.getElementById('laminationResult');
    
    if (!laminationType) {
        laminationResult.innerHTML = '<div style="color: red; padding: 10px;">Выберите тип ламинации</div>';
        return;
    }
    
    const form = document.getElementById(calcConfig.type + 'CalcForm');
    const quantity = parseInt(form.querySelector('input[name="quantity"]').value);
    
    let laminationCost = 0;
    let laminationDescription = '';
    
    if (originalResult.printingType === 'Офсетная') {
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
    
    const newTotal = Math.round((originalResult.totalPrice + laminationCost) * 10) / 10;
    const roundedLaminationCost = Math.round(laminationCost * 10) / 10;
    
    // Создаем новый результат с учетом ламинации
    let html = '<div style="padding: 20px; background: #e8f5e8; border-radius: 8px; border: 1px solid #4caf50;">';
    html += '<h3 style="margin-top: 0; color: #2e7d32;">Результат расчета</h3>';
    html += '<div style="font-size: 24px; font-weight: bold; color: #1b5e20; margin: 15px 0;">Итоговая стоимость: ' + newTotal + ' ₽</div>';
    
    if (originalResult.printingType) {
        html += '<p><strong>Тип печати:</strong> ' + originalResult.printingType + '</p>';
    }
    
    html += '<details style="margin-top: 15px;" open>';
    html += '<summary style="cursor: pointer; font-weight: bold;">Подробности расчета</summary>';
    html += '<div style="margin-top: 10px; padding: 10px; background: white; border-radius: 4px;">';
    html += '<ul style="margin: 0; padding-left: 20px;">';
    
    if (originalResult.baseA3Sheets) html += '<li>Листов A3: ' + originalResult.baseA3Sheets + '</li>';
    if (originalResult.printingCost) html += '<li>Стоимость печати: ' + Math.round(originalResult.printingCost * 10) / 10 + ' ₽</li>';
    if (originalResult.paperCost) html += '<li>Стоимость бумаги: ' + Math.round(originalResult.paperCost * 10) / 10 + ' ₽</li>';
    if (originalResult.plateCost && originalResult.plateCost > 0) html += '<li>Стоимость пластин: ' + Math.round(originalResult.plateCost * 10) / 10 + ' ₽</li>';
    if (originalResult.additionalCosts && originalResult.additionalCosts > 0) html += '<li>Дополнительные услуги: ' + Math.round(originalResult.additionalCosts * 10) / 10 + ' ₽</li>';
    
    // Добавляем информацию о ламинации
    html += '<li style="margin-top: 10px; font-weight: bold;">Ламинация ' + laminationDescription + ': ' + roundedLaminationCost + ' ₽</li>';
    
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