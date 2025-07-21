<?php
/** Шаблон калькулятора блокнотов */
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

// Подключаем общие стили
$this->addExternalCss($templateFolder.'/../.default/style.css');
// Подключаем специфичные стили для блокнотов (если есть)
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
            <strong>Блокноты:</strong> <?= $arResult['binding_info'] ?? 'Спиральная сборка включена в стоимость' ?><br>
            <?= $arResult['lamination_info'] ?? 'Ламинация применяется только к обложке' ?><br>
            Спасибо за понимание!
        </p>
    </div>
    
    <h2><?= $arResult['DESCRIPTION'] ?? 'Калькулятор блокнотов' ?></h2>
    
    <form id="<?= $calcType ?>CalcForm" class="calc-form">
        
        <!-- Формат -->
        <div class="form-group">
            <label class="form-label" for="size">Формат блокнота:</label>
            <select name="size" id="size" class="form-control" required>
                <?php if (!empty($arResult['available_sizes'])): ?>
                    <?php foreach ($arResult['available_sizes'] as $s): ?>
                        <option value="<?= htmlspecialchars($s) ?>"><?= htmlspecialchars($s) ?></option>
                    <?php endforeach; ?>
                <?php else: ?>
                    <option value="A4">A4</option>
                    <option value="A5">A5</option>
                    <option value="A6">A6</option>
                <?php endif; ?>
            </select>
        </div>

        <!-- Тираж -->
        <div class="form-group">
            <label class="form-label" for="quantity">Тираж:</label>
            <input name="quantity" 
                   id="quantity" 
                   type="number" 
                   class="form-control" 
                   min="<?= $arResult['MIN_QUANTITY'] ?? 1 ?>" 
                   max="<?= $arResult['MAX_QUANTITY'] ?? '' ?>" 
                   value="<?= $arResult['DEFAULT_QUANTITY'] ?? 100 ?>" 
                   placeholder="Введите количество"
                   required>
        </div>

        <!-- Количество страниц -->
        <div class="form-group">
            <label class="form-label" for="inner_pages">Листов в блоке:</label>
            <select name="inner_pages" id="inner_pages" class="form-control" required>
                <?php if (!empty($arResult['inner_pages_options'])): ?>
                    <?php foreach ($arResult['inner_pages_options'] as $pages): ?>
                        <option value="<?= $pages ?>"><?= $pages ?> листов</option>
                    <?php endforeach; ?>
                <?php else: ?>
                    <option value="40">40 листов</option>
                    <option value="50">50 листов</option>
                <?php endif; ?>
            </select>
        </div>

        <!-- Печать обложки -->
        <div class="form-group">
            <label class="form-label" for="cover_print">Печать обложки:</label>
            <select name="cover_print" id="cover_print" class="form-control" required>
                <?php if (!empty($arResult['cover_print_types'])): ?>
                    <?php foreach ($arResult['cover_print_types'] as $key => $name): ?>
                        <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($name) ?></option>
                    <?php endforeach; ?>
                <?php else: ?>
                    <option value="4+0">4+0 (полноцвет с одной стороны)</option>
                    <option value="4+4">4+4 (полноцвет с двух сторон)</option>
                <?php endif; ?>
            </select>
            <small class="text-muted"><?= $arResult['paper_info']['cover'] ?? 'Обложка: 300 г/м²' ?></small>
        </div>

        <!-- Печать задника -->
        <div class="form-group">
            <label class="form-label" for="back_print">Печать задника:</label>
            <select name="back_print" id="back_print" class="form-control" required>
                <?php if (!empty($arResult['back_print_types'])): ?>
                    <?php foreach ($arResult['back_print_types'] as $key => $name): ?>
                        <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($name) ?></option>
                    <?php endforeach; ?>
                <?php else: ?>
                    <option value="0+0">0+0 (без печати)</option>
                    <option value="4+0">4+0 (полноцвет с одной стороны)</option>
                    <option value="4+4">4+4 (полноцвет с двух сторон)</option>
                <?php endif; ?>
            </select>
            <small class="text-muted"><?= $arResult['paper_info']['back'] ?? 'Задник: 300 г/м²' ?></small>
        </div>

        <!-- Печать внутреннего блока -->
        <div class="form-group">
            <label class="form-label" for="inner_print">Печать внутреннего блока:</label>
            <select name="inner_print" id="inner_print" class="form-control" required>
                <?php if (!empty($arResult['inner_print_types'])): ?>
                    <?php foreach ($arResult['inner_print_types'] as $key => $name): ?>
                        <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($name) ?></option>
                    <?php endforeach; ?>
                <?php else: ?>
                    <option value="0+0">0+0 (без печати)</option>
                    <option value="1+0">1+0 (ризография с одной стороны)</option>
                    <option value="1+1">1+1 (ризография с двух сторон)</option>
                    <option value="4+0">4+0 (полноцвет с одной стороны)</option>
                    <option value="4+4">4+4 (полноцвет с двух сторон)</option>
                <?php endif; ?>
            </select>
            <small class="text-muted"><?= $arResult['paper_info']['inner'] ?? 'Внутренний блок: 80 г/м²' ?></small>
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
   
        <?php if (!empty($features['lamination'])): ?>
        <!-- Секция ламинации - ПЕРЕМЕЩЕНА ПЕРЕД КНОПКОЙ РАСЧЕТА -->
        <div id="laminationSection" class="lamination-section" style="display: none; margin-top: 20px; margin-bottom: 20px;">
            <h3>Дополнительная ламинация</h3>
            <div id="laminationControls"></div>
            <div id="laminationResult" class="lamination-result"></div>
        </div>
        <?php endif; ?>
        <!-- Секция ламинации -->
        
        <?php endif; ?>
        <div id="calcResult" class="calc-result"></div>
        <div class="calc-spacer"></div>
    </form>

    <div class="calc-thanks">
        <p>Спасибо, что Вы с нами!</p>
    </div>
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

// Конфигурация для калькулятора блокнотов
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
        
        resultDiv.innerHTML = '<div class="loading">Выполняется расчет блокнота...</div>';

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
        
        resultDiv.innerHTML = '<div class="loading">Выполняется расчет блокнота...</div>';

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
function handleResponse(response, resultDiv) {
    if (response && response.data) {
        if (response.data.error) {
            resultDiv.innerHTML = '<div class="result-error">Ошибка: ' + 
                response.data.error + '</div>';
        } else {
            displayNoteResult(response.data, resultDiv);
            // Показываем секцию ламинации если доступна и еще не добавлена
            if (calcConfig.features.lamination && response.data.components && !response.data.laminationCost) {
                showLaminationSection(response.data);
            }
        }
    } else {
        resultDiv.innerHTML = '<div class="result-error">Некорректный ответ сервера</div>';
        console.error('Неожиданная структура ответа:', response);
    }
}

// Отображение результата блокнота
function displayNoteResult(result, resultDiv) {
    const totalPrice = Math.round((result.total || 0) * 10) / 10;
    
    let html = '<div class="result-success">';
    html += '<h3 class="result-title">Результат расчета блокнота</h3>';
    html += '<div class="result-price">' + totalPrice + ' <small>₽</small></div>';
    
    // Показываем информацию о ламинации если она была добавлена
    if (result.laminationCost && result.laminationCost > 0) {
        html += '<div style="color: #28a745; background: #f8fff8; padding: 10px; border-radius: 6px; border-left: 4px solid #28a745; margin-bottom: 15px;">';
        html += '<strong>Ламинация обложки добавлена:</strong> ' + Math.round(result.laminationCost * 10) / 10 + ' ₽';
        html += '</div>';
    }
    
    html += '<details class="result-details">';
    html += '<summary class="result-summary">Подробности расчета</summary>';
    html += '<div class="result-details-content">';
    html += '<ul>';
    
    // Детали расчета
    if (result.details) {
        html += '<li>Формат: ' + result.details.size + '</li>';
        html += '<li>Тираж: ' + result.details.quantity + ' шт</li>';
        html += '<li>Листов в блоке: ' + result.details.inner_pages + '</li>';
    }
    
    // Детализация по компонентам
    if (result.components) {
        if (result.components.cover) {
            html += '<li>Обложка: ' + Math.round(result.components.cover.total * 10) / 10 + ' ₽</li>';
        }
        if (result.components.back) {
            html += '<li>Задник: ' + Math.round(result.components.back.total * 10) / 10 + ' ₽</li>';
        }
        if (result.components.inner) {
            html += '<li>Внутренний блок: ' + Math.round(result.components.inner.total * 10) / 10 + ' ₽</li>';
        }
    }
    
    if (result.binding) {
        html += '<li>Спиральная сборка: ' + Math.round(result.binding * 10) / 10 + ' ₽</li>';
    }
    
    if (result.laminationCost && result.laminationCost > 0) {
        html += '<li class="lamination-info">Ламинация обложки: ' + Math.round(result.laminationCost * 10) / 10 + ' ₽</li>';
    }
    
    // Дополнительные услуги
    if (result.details && result.details.services) {
        const services = result.details.services;
        if (services.bigovka) html += '<li>Биговка: включена</li>';
        if (services.perforation) html += '<li>Перфорация: включена</li>';
        if (services.drill) html += '<li>Сверление: включено</li>';
        if (services.numbering) html += '<li>Нумерация: включена</li>';
        if (services.cornerRadius > 0) html += '<li>Скругленных углов: ' + services.cornerRadius + '</li>';
    }
    
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
    
    // Если ламинация уже добавлена, не показываем секцию повторно
    if (result.laminationCost && result.laminationCost > 0) {
        laminationSection.style.display = 'none';
        return;
    }
    
    // Определяем тип печати обложки для выбора ламинации
    let coverPrintingType = 'Цифровая';
    if (result.components && result.components.cover && result.components.cover.base) {
        coverPrintingType = result.components.cover.base.printingType || 'Цифровая';
    }
    
    let html = '<div class="lamination-content">';
    html += '<p class="lamination-title">Добавить ламинацию к обложке:</p>';
    
    if (coverPrintingType === 'Офсетная') {
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
            calculateWithLamination();
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
function calculateWithLamination() {
    const laminationType = document.querySelector('input[name="laminationType"]:checked');
    const laminationThickness = document.querySelector('select[name="laminationThickness"]');
    const resultDiv = document.getElementById('calcResult');
    const laminationResult = document.getElementById('laminationResult');
    
    if (!laminationType) {
        laminationResult.innerHTML = '<div class="result-error">Выберите тип ламинации</div>';
        return;
    }
    
    const form = document.getElementById(calcConfig.type + 'CalcForm');
    const data = collectFormData(form);
    data.calcType = calcConfig.type;
    data.lamination_type = laminationType.value;
    if (laminationThickness) {
        data.lamination_thickness = laminationThickness.value;
    }
    
    resultDiv.innerHTML = '<div class="loading">Пересчитываем с ламинацией...</div>';

    // Используем тот же метод что и для основного расчета
    if (typeof BX !== 'undefined' && BX.ajax) {
        BX.ajax.runComponentAction(calcConfig.component, 'calc', {
            mode: 'class',
            data: data
        }).then(function(response) {
            handleResponse(response, resultDiv);
        }).catch(function(error) {
            console.error('Ошибка BX:', error);
            resultDiv.innerHTML = '<div class="result-error">Ошибка соединения: ' + error.message + '</div>';
        });
    } else {
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
            console.error('Ошибка fetch:', error);
            resultDiv.innerHTML = '<div class="result-error">Ошибка соединения: ' + error.message + '</div>';
        });
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
</style>