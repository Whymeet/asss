<?php
/** Шаблон калькулятора открыток */
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

// Подключаем общие стили
$this->addExternalCss($templateFolder.'/../.default/style.css');
// Подключаем специфичные стили для открыток (если есть)
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
?>

<div class="calc-container">
    <!-- Информационный блок -->
    <div class="calc-disclaimer">
        <p>
            Данные, полученные при расчете на калькуляторе – являются ориентировочными в связи с регулярным изменением стоимости материалов.<br>
            Конечную стоимость заказа уточняйте у менеджера: <a href="tel:+78462060068">+7 (846) 206-00-68</a><br>
            <strong>Открытки:</strong> <?= $arResult['format_info'] ?? '' ?><br>
            <?= $arResult['paper_info'] ?? '' ?><br>
            Спасибо за понимание!
        </p>
    </div>
    
    <h2><?= $arResult['DESCRIPTION'] ?? 'Калькулятор печати открыток' ?></h2>
    
    <form id="<?= $calcType ?>CalcForm" class="calc-form">
        
        <!-- Формат открытки -->
        <div class="form-group">
            <label class="form-label" for="size">Формат открытки:</label>
            <select name="size" id="size" class="form-control" required>
                <?php if (!empty($arResult['available_sizes'])): ?>
                    <?php foreach ($arResult['available_sizes'] as $s): ?>
                        <option value="<?= htmlspecialchars($s) ?>"><?= htmlspecialchars($s) ?></option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
            <small class="text-muted">Выберите формат открытки</small>
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
                   value="<?= $arResult['default_quantity'] ?? 100 ?>" 
                   placeholder="Введите количество экземпляров"
                   required>
            <small class="text-muted">Минимальный тираж: <?= $arResult['min_quantity'] ?? 1 ?> шт.</small>
        </div>

        <!-- Тип печати -->
        <div class="form-group">
            <label class="form-label">Тип печати:</label>
            <div class="radio-group">
                <label class="radio-label">
                    <input type="radio" name="printType" value="single" checked>
                    Односторонняя печать
                </label>
                <label class="radio-label">
                    <input type="radio" name="printType" value="double">
                    Двусторонняя печать
                </label>
            </div>
        </div>

        <!-- Дополнительные услуги -->
        <div class="form-group">
            <label class="form-label">Дополнительные услуги:</label>
            <div class="checkbox-group">
                
                <?php if ($features['bigovka'] ?? false): ?>
                <label class="checkbox-label">
                    <input type="checkbox" name="bigovka">
                    Биговка
                </label>
                <?php endif; ?>

                <?php if ($features['perforation'] ?? false): ?>
                <label class="checkbox-label">
                    <input type="checkbox" name="perforation">
                    Перфорация
                </label>
                <?php endif; ?>

                <?php if ($features['drill'] ?? false): ?>
                <label class="checkbox-label">
                    <input type="checkbox" name="drill">
                    Сверление диаметром 5мм
                </label>
                <?php endif; ?>

                <?php if ($features['numbering'] ?? false): ?>
                <label class="checkbox-label">
                    <input type="checkbox" name="numbering">
                    Нумерация
                </label>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($features['corner_radius'] ?? false): ?>
        <!-- Скругление углов -->
        <div class="form-group">
            <label class="form-label" for="cornerRadius">Количество скругленных углов:</label>
            <input name="cornerRadius" 
                   id="cornerRadius" 
                   type="number" 
                   class="form-control" 
                   min="0" 
                   max="<?= $arResult['corner_radius_max'] ?? 4 ?>" 
                   value="0"
                   placeholder="0">
            <small class="text-muted">Максимум <?= $arResult['corner_radius_max'] ?? 4 ?> угла</small>
        </div>
        <?php endif; ?>

        <!-- Скрытые поля -->
        <input type="hidden" name="paperType" value="300.0">
        <input type="hidden" name="calcType" value="<?= $calcType ?>">
        <input type="hidden" name="sessid" value="<?= bitrix_sessid() ?>">

        <button id="calcBtn" type="button" class="calc-button">Рассчитать стоимость</button>
        
        <div id="calcResult" class="calc-result"></div>
        
        <div class="calc-spacer"></div>
    </form>

    <div class="calc-thanks">
        <p>Спасибо, что Вы с нами!</p>
    </div>
</div>

<script>
// Конфигурация для JavaScript
const calcConfig = {
    type: '<?= $calcType ?>',
    component: 'my:print.calc'
};

// Универсальная функция ожидания BX
function waitForBX(successCallback, fallbackCallback, timeout = 3000) {
    let attempts = 0;
    const maxAttempts = timeout / 100;
    
    function checkBX() {
        attempts++;
        
        if (typeof BX !== 'undefined' && BX.ajax && BX.ajax.runComponentAction) {
            console.log('BX доступен, используем стандартный метод');
            successCallback();
        } else if (attempts >= maxAttempts) {
            console.log('Превышено время ожидания BX, используем запасной вариант');
            fallbackCallback();
        } else {
            setTimeout(checkBX, 100);
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
        
        resultDiv.innerHTML = '<div class="loading">Выполняется расчет открыток...</div>';

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
        
        resultDiv.innerHTML = '<div class="loading">Выполняется расчет открыток...</div>';

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
    const formData = new FormData(form);
    const data = {};
    
    // Собираем все обычные поля
    for (let [key, value] of formData.entries()) {
        data[key] = value;
    }
    
    // Добавляем чекбоксы с правильным состоянием
    const checkboxes = ['bigovka', 'perforation', 'drill', 'numbering'];
    checkboxes.forEach(name => {
        const checkbox = form.querySelector(`input[name="${name}"]`);
        if (checkbox) {
            data[name] = checkbox.checked;
        }
    });
    
    // Устанавливаем фиксированную плотность бумаги
    data.paperType = '300.0';
    
    console.log('Собранные данные формы:', data);
    return data;
}

// Обработка ответа сервера
function handleResponse(response, resultDiv) {
    if (response && response.data) {
        if (response.data.error) {
            resultDiv.innerHTML = '<div class="result-error">Ошибка: ' + response.data.error + '</div>';
        } else {
            displayResult(response.data, resultDiv);
        }
    } else {
        resultDiv.innerHTML = '<div class="result-error">Некорректный ответ сервера</div>';
    }
    
    // Добавляем анимацию появления результата
    setTimeout(() => {
        resultDiv.classList.add('calc-result-enter');
    }, 50);
}

// Отображение результата расчета
function displayResult(result, resultDiv) {
    const totalPrice = Math.round((result.totalPrice || 0) * 10) / 10;
    
    let html = '<div class="result-success">';
    html += '<h3 class="result-title">Результат расчета открыток</h3>';
    html += '<div class="result-price">' + formatPrice(totalPrice) + ' ₽';
    html += '<small>итоговая стоимость</small></div>';
    
    // Детали расчета
    html += '<details class="result-details">';
    html += '<summary class="result-summary">Подробности расчета</summary>';
    html += '<div class="result-details-content">';
    html += '<ul>';
    
    if (result.printingType) {
        html += '<li>Тип печати: <strong>' + result.printingType + '</strong></li>';
    }
    
    if (result.baseA3Sheets) {
        html += '<li>Базовые листы A3: <strong>' + result.baseA3Sheets + '</strong></li>';
    }
    
    if (result.adjustment) {
        html += '<li>Приладочные листы: <strong>' + result.adjustment + '</strong></li>';
    }
    
    if (result.totalA3Sheets) {
        html += '<li>Всего листов A3: <strong>' + result.totalA3Sheets + '</strong></li>';
    }
    
    if (result.printingCost) {
        html += '<li>Стоимость печати: <strong>' + formatPrice(result.printingCost) + ' ₽</strong></li>';
    }
    
    if (result.plateCost) {
        html += '<li>Стоимость пластины: <strong>' + formatPrice(result.plateCost) + ' ₽</strong></li>';
    }
    
    if (result.paperCost) {
        html += '<li>Стоимость бумаги: <strong>' + formatPrice(result.paperCost) + ' ₽</strong></li>';
    }
    
    if (result.additionalCosts) {
        html += '<li>Дополнительные услуги: <strong>' + formatPrice(result.additionalCosts) + ' ₽</strong></li>';
    }
    
    html += '</ul>';
    html += '</div>';
    html += '</details>';
    
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

// Инициализация калькулятора
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM загружен, ждем BX...');
    console.log('Калькулятор:', calcConfig.type);
    console.log('Время запуска:', new Date().toLocaleTimeString());
    waitForBX(initWithBX, initWithoutBX, 3000);
});
</script>ё