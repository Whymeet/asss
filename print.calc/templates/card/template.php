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
                    <span class="radio-custom"></span>
                    Односторонняя печать
                </label>
                <label class="radio-label">
                    <input type="radio" name="printType" value="double">
                    <span class="radio-custom"></span>
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
                    <span class="checkbox-custom"></span>
                    Биговка
                </label>
                <?php endif; ?>

                <?php if ($features['perforation'] ?? false): ?>
                <label class="checkbox-label">
                    <input type="checkbox" name="perforation">
                    <span class="checkbox-custom"></span>
                    Перфорация
                </label>
                <?php endif; ?>

                <?php if ($features['drill'] ?? false): ?>
                <label class="checkbox-label">
                    <input type="checkbox" name="drill">
                    <span class="checkbox-custom"></span>
                    Сверление диаметром 5мм
                </label>
                <?php endif; ?>

                <?php if ($features['numbering'] ?? false): ?>
                <label class="checkbox-label">
                    <input type="checkbox" name="numbering">
                    <span class="checkbox-custom"></span>
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

<style>
.radio-group, .checkbox-group {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.radio-label, .checkbox-label {
    display: flex;
    align-items: center;
    cursor: pointer;
    font-size: 14px;
    gap: 8px;
}

.radio-custom, .checkbox-custom {
    width: 18px;
    height: 18px;
    border: 2px solid #007bff;
    border-radius: 50%;
    position: relative;
    background: white;
}

.checkbox-custom {
    border-radius: 3px;
}

.radio-label input[type="radio"],
.checkbox-label input[type="checkbox"] {
    display: none;
}

.radio-label input[type="radio"]:checked + .radio-custom::after {
    content: '';
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #007bff;
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
}

.checkbox-label input[type="checkbox"]:checked + .checkbox-custom::after {
    content: '✓';
    color: white;
    font-size: 12px;
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
}

.checkbox-label input[type="checkbox"]:checked + .checkbox-custom {
    background: #007bff;
}

@media (max-width: 768px) {
    .radio-group, .checkbox-group {
        gap: 8px;
    }
    
    .radio-label, .checkbox-label {
        font-size: 13px;
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
});

// Универсальная функция инициализации
function initCalculator() {
    // Проверяем доступность BX
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
    const data = {};
    const formData = new FormData(form);
    
    for (let [key, value] of formData.entries()) {
        if (form.elements[key] && form.elements[key].type === 'checkbox') {
            data[key] = form.elements[key].checked;
        } else {
            data[key] = value;
        }
    }
    
    // Устанавливаем фиксированную плотность бумаги
    data.paperType = '300.0';
    
    return data;
}

// Обработка ответа сервера
function handleResponse(response, resultDiv) {
    if (response && response.data) {
        if (response.data.error) {
            resultDiv.innerHTML = '<div class="result-error">Ошибка: ' + response.data.error + '</div>';
        } else {
            displayCardResult(response.data, resultDiv);
        }
    } else {
        resultDiv.innerHTML = '<div class="result-error">Некорректный ответ сервера</div>';
    }
}

// Отображение результата расчета открыток
function displayCardResult(result, resultDiv) {
    const totalPrice = Math.round((result.totalPrice || 0) * 10) / 10;
    
    let html = '<div class="result-success">';
    html += '<h3>Результат расчета открыток</h3>';
    html += '<div class="price-breakdown">';
    
    if (result.printingType) {
        html += '<div class="price-item"><span>Тип печати:</span><span><strong>' + result.printingType + '</strong></span></div>';
    }
    
    if (result.baseA3Sheets) {
        html += '<div class="price-item"><span>Базовые листы A3:</span><span>' + result.baseA3Sheets + '</span></div>';
    }
    
    if (result.adjustment) {
        html += '<div class="price-item"><span>Приладочные листы:</span><span>' + result.adjustment + '</span></div>';
    }
    
    if (result.totalA3Sheets) {
        html += '<div class="price-item"><span>Всего листов A3:</span><span>' + result.totalA3Sheets + '</span></div>';
    }
    
    if (result.printingCost) {
        html += '<div class="price-item"><span>Стоимость печати:</span><span>' + formatPrice(result.printingCost) + ' ₽</span></div>';
    }
    
    if (result.plateCost) {
        html += '<div class="price-item"><span>Стоимость пластины:</span><span>' + formatPrice(result.plateCost) + ' ₽</span></div>';
    }
    
    if (result.paperCost) {
        html += '<div class="price-item"><span>Стоимость бумаги:</span><span>' + formatPrice(result.paperCost) + ' ₽</span></div>';
    }
    
    if (result.additionalCosts) {
        html += '<div class="price-item"><span>Дополнительные услуги:</span><span>' + formatPrice(result.additionalCosts) + ' ₽</span></div>';
    }
    
    html += '<div class="price-item total-price"><span>Итоговая стоимость:</span><span>' + formatPrice(totalPrice) + ' ₽</span></div>';
    html += '</div>';
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
</script>