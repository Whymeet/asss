<?php
/** Минимальный шаблон калькулятора листовок */
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

// Проверяем, что конфигурация загружена
if (!$arResult['CONFIG_LOADED']) {
    echo '<div style="color: red; padding: 20px;">Ошибка: Конфигурация калькулятора не загружена</div>';
    return;
}

// Принудительно подключаем основные скрипты Битрикса
CJSCore::Init(['ajax', 'window']);
?>

<div class="calc-container" style="max-width: 800px; font-family: Arial, sans-serif;">
    <form id="listCalcForm" style="background: #f9f9f9; padding: 20px; border-radius: 8px;">
        
        <!-- Тип бумаги -->
        <div style="margin: 15px 0;">
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Тип бумаги:</label>
            <select name="paperType" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" required>
                <?php foreach ($arResult['PAPER_TYPES'] as $paper): ?>
                    <option value="<?= htmlspecialchars($paper['ID']) ?>"><?= htmlspecialchars($paper['NAME']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Формат -->
        <div style="margin: 15px 0;">
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Формат:</label>
            <select name="size" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" required>
                <?php foreach ($arResult['FORMATS'] as $format): ?>
                    <option value="<?= htmlspecialchars($format['ID']) ?>"><?= htmlspecialchars($format['NAME']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Тираж -->
        <div style="margin: 15px 0;">
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Тираж:</label>
            <input name="quantity" type="number" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" min="1" value="1000" required>
        </div>

        <!-- Тип печати -->
        <div style="margin: 15px 0;">
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Тип печати:</label>
            <div>
                <label style="margin-right: 15px;"><input type="radio" name="printType" value="single" checked> Односторонняя</label>
                <label><input type="radio" name="printType" value="double"> Двусторонняя</label>
            </div>
        </div>

        <!-- Дополнительные услуги -->
        <div style="margin: 15px 0;">
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Дополнительные услуги:</label>
            <div>
                <label style="display: block;"><input type="checkbox" name="bigovka"> Биговка</label>
                <label style="display: block;"><input type="checkbox" name="perforation"> Перфорация</label>
                <label style="display: block;"><input type="checkbox" name="drill"> Сверление Ø5мм</label>
                <label style="display: block;"><input type="checkbox" name="numbering"> Нумерация</label>
            </div>
        </div>

        <!-- Скругление углов -->
        <div style="margin: 15px 0;">
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Количество углов (0-4):</label>
            <input name="cornerRadius" type="number" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" min="0" max="4" value="0">
        </div>

        <input type="hidden" name="calcType" value="list">
        <input type="hidden" name="sessid" value="<?= bitrix_sessid() ?>">

        <button id="calcBtn" type="button" style="padding: 12px 24px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px;">Рассчитать</button>
        
        <div id="calcResult" style="margin-top: 20px;"></div>
    </form>
</div>

<script>
// Блокируем внешние ошибки сразу
window.addEventListener('error', function(e) {
    if (e.message && (
        e.message.includes('Cannot set properties of null') || 
        e.message.includes('recaptcha') ||
        e.message.includes('mail.ru') ||
        e.message.includes('top-fwz1')
    )) {
        console.log('🚫 Заблокирована внешняя ошибка:', e.message);
        e.preventDefault();
        return true;
    }
});

window.addEventListener('unhandledrejection', function(e) {
    if (e.reason === null || (e.reason && e.reason.toString().includes('recaptcha'))) {
        console.log('🚫 Заблокирована ошибка Promise');
        e.preventDefault();
        return true;
    }
});

// Функция ожидания BX с таймаутом
function waitForBX(callback, fallbackCallback, timeout = 3000) {
    const startTime = Date.now();
    
    function checkBX() {
        if (typeof BX !== 'undefined' && BX.ajax) {
            console.log('✅ BX найден через', Date.now() - startTime, 'мс');
            callback();
        } else if (Date.now() - startTime < timeout) {
            setTimeout(checkBX, 50);
        } else {
            console.warn('⚠️ BX не загрузился за', timeout, 'мс. Используем запасной вариант');
            fallbackCallback();
        }
    }
    
    checkBX();
}

// Основная инициализация с BX
function initWithBX() {
    console.log('🚀 Инициализация с BX.ajax');
    
    const form = document.getElementById('listCalcForm');
    const resultDiv = document.getElementById('calcResult');
    const calcBtn = document.getElementById('calcBtn');
    
    if (!form || !resultDiv || !calcBtn) {
        console.error('❌ Элементы формы не найдены');
        return;
    }

    calcBtn.addEventListener('click', function() {
        console.log('📤 Отправка через BX.ajax');
        
        const data = collectFormData(form);
        resultDiv.innerHTML = '<div style="padding: 10px; color: #666;">⏳ Расчет...</div>';

        BX.ajax.runComponentAction('my:print.calc', 'calc', {
            mode: 'class',
            data: data
        }).then(function(response) {
            console.log('📥 Ответ BX:', response);
            handleResponse(response, resultDiv);
        }).catch(function(error) {
            console.error('❌ Ошибка BX:', error);
            resultDiv.innerHTML = '<div style="color: red; padding: 10px; background: #ffebee; border-radius: 4px;">Ошибка соединения: ' + 
                (error.message || 'Неизвестная ошибка') + '</div>';
        });
    });
}

// Запасной вариант без BX
function initWithoutBX() {
    console.log('🔄 Инициализация без BX (fetch)');
    
    const form = document.getElementById('listCalcForm');
    const resultDiv = document.getElementById('calcResult');
    const calcBtn = document.getElementById('calcBtn');
    
    if (!form || !resultDiv || !calcBtn) {
        console.error('❌ Элементы формы не найдены');
        return;
    }

    calcBtn.addEventListener('click', function() {
        console.log('📤 Отправка через fetch');
        
        const data = collectFormData(form);
        resultDiv.innerHTML = '<div style="padding: 10px; color: #666;">⏳ Расчет...</div>';

        fetch('/bitrix/services/main/ajax.php?c=my:print.calc&action=calc&mode=class', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams(data)
        })
        .then(response => response.json())
        .then(response => {
            console.log('📥 Ответ fetch:', response);
            handleResponse(response, resultDiv);
        })
        .catch(error => {
            console.error('❌ Ошибка fetch:', error);
            resultDiv.innerHTML = '<div style="color: red; padding: 10px; background: #ffebee; border-radius: 4px;">Ошибка соединения: ' + 
                error.message + '</div>';
        });
    });
}

// Сбор данных формы
function collectFormData(form) {
    const formData = new FormData(form);
    const data = {};
    
    for (let [key, value] of formData.entries()) {
        data[key] = value;
    }
    
    // Добавляем чекбоксы
    data.bigovka = form.querySelector('input[name="bigovka"]').checked;
    data.perforation = form.querySelector('input[name="perforation"]').checked;
    data.drill = form.querySelector('input[name="drill"]').checked;
    data.numbering = form.querySelector('input[name="numbering"]').checked;

    console.log('📋 Собранные данные:', data);
    return data;
}

// Обработка ответа сервера
function handleResponse(response, resultDiv) {
    if (response.data) {
        if (response.data.error) {
            resultDiv.innerHTML = '<div style="color: red; padding: 10px; background: #ffebee; border-radius: 4px;">❌ ' + 
                response.data.error + '</div>';
        } else {
            displayResult(response.data, resultDiv);
        }
    } else {
        resultDiv.innerHTML = '<div style="color: red; padding: 10px; background: #ffebee; border-radius: 4px;">❌ Некорректный ответ сервера</div>';
        console.error('Неожиданная структура ответа:', response);
    }
}

// Отображение результата с округлением до десятых
function displayResult(result, resultDiv) {
    console.log('📊 Отображаем результат:', result);
    
    // Округляем все цены до десятых
    const totalPrice = Math.round((result.totalPrice || 0) * 10) / 10;
    const printingCost = Math.round((result.printingCost || 0) * 10) / 10;
    const paperCost = Math.round((result.paperCost || 0) * 10) / 10;
    const plateCost = result.plateCost ? Math.round(result.plateCost * 10) / 10 : 0;
    const additionalCosts = result.additionalCosts ? Math.round(result.additionalCosts * 10) / 10 : 0;
    
    let html = '<div style="padding: 20px; background: #e8f5e8; border-radius: 8px; border: 1px solid #4caf50;">';
    html += '<h3 style="margin-top: 0; color: #2e7d32;">✅ Результат расчета</h3>';
    html += '<div style="font-size: 24px; font-weight: bold; color: #1b5e20; margin: 15px 0;">💰 Стоимость: ' + totalPrice + ' ₽</div>';
    
    if (result.printingType) {
        html += '<p><strong>🖨️ Тип печати:</strong> ' + result.printingType + '</p>';
    }
    
    html += '<details style="margin-top: 15px;"><summary style="cursor: pointer; font-weight: bold;">📋 Подробности расчета</summary>';
    html += '<div style="margin-top: 10px; padding: 10px; background: white; border-radius: 4px;">';
    html += '<ul style="margin: 0; padding-left: 20px;">';
    html += '<li>📄 Листов A3: ' + (result.baseA3Sheets || 0) + '</li>';
    html += '<li>🖨️ Стоимость печати: ' + printingCost + ' ₽</li>';
    html += '<li>📰 Стоимость бумаги: ' + paperCost + ' ₽</li>';
    if (plateCost > 0) {
        html += '<li>🔧 Стоимость пластин: ' + plateCost + ' ₽</li>';
    }
    if (additionalCosts > 0) {
        html += '<li>⭐ Дополнительные услуги: ' + additionalCosts + ' ₽</li>';
    }
    html += '</ul>';
    html += '</div>';
    html += '</details>';
    html += '</div>';
    
    resultDiv.innerHTML = html;
}

// Запуск инициализации
console.log('🚀 === КАЛЬКУЛЯТОР ЛИСТОВОК ===');
console.log('⏰ Время запуска:', new Date().toLocaleTimeString());

document.addEventListener('DOMContentLoaded', function() {
    console.log('📄 DOM загружен, ждем BX...');
    waitForBX(initWithBX, initWithoutBX, 3000);
});
</script>