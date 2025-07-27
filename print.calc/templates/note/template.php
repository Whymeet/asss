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
            <div id="laminationSection" class="lamination-section">
            <h3>Дополнительная ламинация обложки</h3>
            <div id="laminationControls"></div>
            <div id="laminationResult" class="lamination-result"></div>
        </div>
        <button id="calcBtn" type="button" class="calc-button">Рассчитать стоимость</button>
     
        <!-- Секция ламинации -->
        
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
    
    // Добавляем кнопку заказа
    html += '<button type="button" class="order-button" onclick="openOrderModal()">Заказать печать</button>';
    
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
            if (errorMessage.parentNode) {
                errorMessage.remove();
            }
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
        showFieldError(nameField, 'Пожалуйста, введите ваше имя');
        hasErrors = true;
    } else if (name.length < 2) {
        showFieldError(nameField, 'Имя должно содержать минимум 2 символа');
        hasErrors = true;
    }
    
    // Валидация телефона
    if (!phone) {
        showFieldError(phoneField, 'Пожалуйста, введите номер телефона');
        hasErrors = true;
    } else {
        // Простая валидация телефона (российские номера)
        const phoneRegex = /^(\+7|8)?[\s\-]?\(?[0-9]{3}\)?[\s\-]?[0-9]{3}[\s\-]?[0-9]{2}[\s\-]?[0-9]{2}$/;
        if (!phoneRegex.test(phone.replace(/\s/g, ''))) {
            showFieldError(phoneField, 'Введите корректный номер телефона');
            hasErrors = true;
        }
    }
    
    // Валидация email (если указан)
    if (email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            showFieldError(emailField, 'Введите корректный email адрес');
            hasErrors = true;
        }
    }
    
    // Валидация даты и времени (если указаны)
    if (date || time) {
        if (!date) {
            showFieldError(dateField, 'Укажите дату или оставьте оба поля пустыми');
            hasErrors = true;
        }
        if (!time) {
            showFieldError(timeField, 'Укажите время или оставьте оба поля пустыми');
            hasErrors = true;
        }
        
        // Валидация даты и времени
        if (date && time) {
            if (!validateDateField(dateField)) {
                hasErrors = true;
            }
            if (!validateTimeField(timeField)) {
                hasErrors = true;
            }
        }
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
    
    // Формируем данные заказа для блокнотов
    const orderData = {
        calcType: 'note',
        product: 'Блокноты',
        size: formData.size || 'Не указан',
        quantity: formData.quantity || 0,
        inner_pages: formData.inner_pages || 'Не указано',
        cover_print: formData.cover_print || 'Не указано',
        back_print: formData.back_print || 'Не указано',
        inner_print: formData.inner_print || 'Не указано',
        totalPrice: totalPrice
    };
    
    // Добавляем дополнительные услуги
    let additionalServices = [];
    if (formData.bigovka) additionalServices.push('Биговка');
    if (formData.perforation) additionalServices.push('Перфорация');
    if (formData.drill) additionalServices.push('Сверление');
    if (formData.numbering) additionalServices.push('Нумерация');
    if (formData.cornerRadius && formData.cornerRadius > 0) additionalServices.push(`Скругление ${formData.cornerRadius} углов`);
    if (additionalServices.length > 0) {
        orderData.additionalServices = additionalServices.join(', ');
    }
    
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
        
        if (!validateOrderForm()) {
            return;
        }
        
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        
        // Блокируем кнопку
        submitBtn.disabled = true;
        submitBtn.textContent = 'Отправляем...';
        
        // Собираем данные клиента
        const clientData = {
            name: document.getElementById('clientName').value.trim(),
            phone: document.getElementById('clientPhone').value.trim(),
            email: document.getElementById('clientEmail').value.trim(),
            callTime: '',
            orderData: document.getElementById('orderData').value
        };
        
        // Формируем время звонка
        const date = document.getElementById('callDate').value;
        const time = document.getElementById('callTime').value;
        if (date && time) {
            const callDateTime = new Date(date + 'T' + time);
            clientData.callTime = callDateTime.toISOString();
        }
        
        sendOrderEmail(clientData, submitBtn, originalText);
    });
}

function sendOrderEmail(clientData, submitBtn, originalText) {
    // Используем тот же механизм что и для основного расчета
    if (typeof BX !== 'undefined' && BX.ajax) {
        BX.ajax.runComponentAction(calcConfig.component, 'sendOrder', {
            mode: 'class',
            data: clientData
        }).then(function(response) {
            handleOrderResponse(response, submitBtn, originalText);
        }).catch(function(error) {
            console.error('Ошибка BX при отправке заказа:', error);
            handleOrderError(submitBtn, originalText);
        });
    } else {
        fetch('/bitrix/services/main/ajax.php?c=' + calcConfig.component + '&action=sendOrder&mode=class', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams(clientData)
        })
        .then(response => response.json())
        .then(response => {
            handleOrderResponse(response, submitBtn, originalText);
        })
        .catch(error => {
            console.error('Ошибка fetch при отправке заказа:', error);
            handleOrderError(submitBtn, originalText);
        });
    }
}

function handleOrderResponse(response, submitBtn, originalText) {
    if (response && response.data) {
        if (response.data.error) {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        } else if (response.data.success) {
            closeOrderModal();
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        } else {
            handleOrderError(submitBtn, originalText);
        }
    } else {
        handleOrderError(submitBtn, originalText);
    }
}

function handleOrderError(submitBtn, originalText) {
    submitBtn.disabled = false;
    submitBtn.textContent = originalText;
}
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

/* .lamination-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, #28a745, #20c997);
} */

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

</style>