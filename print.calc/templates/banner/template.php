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
                <div class="checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="hemming" id="hemmingCheckbox">
                        <span class="checkbox-custom"></span>
                        Проклейка (90 руб/м периметра)
                    </label>
                    <small class="text-muted service-description">Проклейка по всему периметру баннера</small>
                </div>
            </div>

            <div class="form-group">
                <div class="checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="grommets" id="grommetsCheckbox">
                        <span class="checkbox-custom"></span>
                        Люверсы (30 руб/шт)
                    </label>
                    <small class="text-muted service-description">Металлические кольца для крепления (требует проклейку)</small>
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

    <div class="calc-thanks">
        <p>Спасибо, что Вы с нами!</p>
    </div>
</div>

<style>
.form-section {
    background: #f8f9fa;
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
    border-bottom: 2px solid #007bff;
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

.checkbox-custom {
    width: 18px;
    height: 18px;
    border: 2px solid #007bff;
    border-radius: 3px;
    position: relative;
    background: white;
    flex-shrink: 0;
}

.checkbox-label input[type="checkbox"] {
    display: none;
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

.service-description {
    margin-left: 26px;
    color: #6c757d;
    font-size: 12px;
}

.grommet-step-field {
    margin-top: 15px;
    padding: 15px;
    background: #fff;
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
    background: #e3f2fd;
    border: 1px solid #2196f3;
    border-radius: 6px;
    padding: 10px;
    margin: 10px 0;
    color: #1976d2;
    font-size: 14px;
}

@media (max-width: 768px) {
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