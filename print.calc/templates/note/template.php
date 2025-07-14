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
            <strong>Блокноты:</strong> <?= $arResult['binding_info'] ?? '' ?><br>
            <?= $arResult['lamination_info'] ?? '' ?><br>
            Спасибо за понимание!
        </p>
    </div>
    
    <h2><?= $arResult['DESCRIPTION'] ?? 'Калькулятор блокнотов' ?></h2>
    
    <form id="<?= $calcType ?>CalcForm" class="calc-form">
        
        <!-- Основные параметры -->
        <div class="form-group">
            <h3 class="section-title">Основные параметры</h3>
        </div>
        
        <!-- Формат -->
        <div class="form-group">
            <label class="form-label" for="size">Формат блокнота:</label>
            <select name="size" id="size" class="form-control" required>
                <?php if (!empty($arResult['available_sizes'])): ?>
                    <?php foreach ($arResult['available_sizes'] as $s): ?>
                        <option value="<?= htmlspecialchars($s) ?>"><?= htmlspecialchars($s) ?></option>
                    <?php endforeach; ?>
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
                <?php endif; ?>
            </select>
        </div>

        <!-- Параметры печати -->
        <div class="form-group">
            <h3 class="section-title">Параметры печати</h3>
        </div>

        <!-- Обложка -->
        <div class="form-group">
            <label class="form-label" for="cover_print">Печать обложки:</label>
            <select name="cover_print" id="cover_print" class="form-control" required>
                <?php if (!empty($arResult['cover_print_types'])): ?>
                    <?php foreach ($arResult['cover_print_types'] as $key => $name): ?>
                        <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($name) ?></option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
            <small class="text-muted"><?= $arResult['paper_info']['cover'] ?? '' ?></small>
        </div>

        <!-- Задник -->
        <div class="form-group">
            <label class="form-label" for="back_print">Печать задника:</label>
            <select name="back_print" id="back_print" class="form-control" required>
                <?php if (!empty($arResult['back_print_types'])): ?>
                    <?php foreach ($arResult['back_print_types'] as $key => $name): ?>
                        <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($name) ?></option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
            <small class="text-muted"><?= $arResult['paper_info']['back'] ?? '' ?></small>
        </div>

        <!-- Внутренний блок -->
        <div class="form-group">
            <label class="form-label" for="inner_print">Печать внутреннего блока:</label>
            <select name="inner_print" id="inner_print" class="form-control" required>
                <?php if (!empty($arResult['inner_print_types'])): ?>
                    <?php foreach ($arResult['inner_print_types'] as $key => $name): ?>
                        <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($name) ?></option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
            <small class="text-muted"><?= $arResult['paper_info']['inner'] ?? '' ?></small>
        </div>

        <!-- Дополнительные услуги -->
        <?php 
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
            $supportedServices[] = ['name' => 'drill', 'label' => 'Сверление'];
            $showAdditionalServices = true;
        }
        if (!empty($features['numbering'])) {
            $supportedServices[] = ['name' => 'numbering', 'label' => 'Нумерация'];
            $showAdditionalServices = true;
        }
        
        if ($showAdditionalServices): ?>
        <div class="form-group">
            <h3 class="section-title">Дополнительные услуги</h3>
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
            <small class="text-muted">Максимум <?= $arResult['corner_radius_max'] ?? 4 ?> угла</small>
        </div>
        <?php endif; ?>

        <input type="hidden" name="calcType" value="<?= $calcType ?>">
        <input type="hidden" name="sessid" value="<?= bitrix_sessid() ?>">

        <button id="calcBtn" type="button" class="calc-button">Рассчитать стоимость</button>
        
        <div id="calcResult" class="calc-result"></div>
        
        <!-- Секция ламинации (показывается после расчета) -->
        <?php if (!empty($features['lamination'])): ?>
        <div id="laminationSection" class="lamination-section">
            <h3>Дополнительная ламинация обложки</h3>
            <div id="laminationControls"></div>
            <div id="laminationResult" class="lamination-result"></div>
        </div>
        <?php endif; ?>
        
        <!-- Отступ между результатом и ламинацией -->
        <div class="calc-spacer"></div>
    </form>

    <div class="calc-thanks">
        <p>Спасибо, что Вы с нами!</p>
    </div>
</div>

<style>
.section-title {
    margin: 30px 0 15px 0;
    color: #2c3e50;
    font-size: 18px;
    font-weight: 600;
    border-bottom: 2px solid #007bff;
    padding-bottom: 5px;
}

.component-breakdown {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 15px;
    margin: 15px 0;
}

.component-breakdown h4 {
    margin: 0 0 10px 0;
    color: #495057;
    font-size: 16px;
    font-weight: 600;
}

.component-breakdown ul {
    margin: 0;
    padding-left: 20px;
    list-style-type: disc;
}

.component-breakdown li {
    margin: 8px 0;
    color: #666;
    font-size: 14px;
}

.total-breakdown {
    background: linear-gradient(135deg, #f0f9ff 0%, #e8f5e8 100%);
    border: 1px solid #4caf50;
    border-radius: 6px;
    padding: 20px;
    margin: 20px 0;
}

.total-breakdown h4 {
    margin: 0 0 15px 0;
    color: #2e7d32;
    font-size: 18px;
    font-weight: 600;
}
</style>

<script>
// Блокировка внешних ошибок
window.addEventListener('error', function(e) {
    if (e.message && (
        e.message.includes('Cannot set properties of null') || 
        e.message.includes('Cannot read properties of null') ||
        e.message.includes('recaptcha') ||
        e.message.includes('mail.ru') ||
        e.message.includes('top-fwz1') ||
        e.message.includes('code.js')
    )) {
        e.preventDefault();
        e.stopPropagation();
        return true;
    }
});

window.addEventListener('unhandledrejection', function(e) {
    if (e.reason === null || (e.reason && e.reason.toString().includes('recaptcha'))) {
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

// Функция ожидания BX
function waitForBX(callback, fallbackCallback, timeout = 3000) {
    const startTime = Date.now();
    
    function checkBX() {
        if (typeof BX !== 'undefined' && BX.ajax) {
            callback();
        } else if (Date.now() - startTime < timeout) {
            setTimeout(checkBX, 50);
        } else {
            fallbackCallback();
        }
    }
    checkBX();
}

// Инициализация с BX
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
        
        resultDiv.innerHTML = '<div class="loading">Выполняется расчет блокнота...</div>';

        BX.ajax.runComponentAction(calcConfig.component, 'calc', {
            mode: 'class',
            data: data
        }).then(function(response) {
            handleResponse(response, resultDiv);
        }).catch(function(error) {
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
        .then(response => response.json())
        .then(response => {
            handleResponse(response, resultDiv);
        })
        .catch(error => {
            resultDiv.innerHTML = '<div class="result-error">Ошибка соединения: ' + error.message + '</div>';
        });
    });
}

// Обработка ответа сервера
function handleResponse(response, resultDiv) {
    if (response && response.data) {
        if (response.data.error) {
            resultDiv.innerHTML = '<div class="result-error">Ошибка: ' + response.data.error + '</div>';
        } else {
            displayNoteResult(response.data, resultDiv);
            // Показываем секцию ламинации если доступна и еще не добавлена
            if (calcConfig.features.lamination && response.data.components && !response.data.laminationCost) {
                showLaminationSection(response.data);
            }
        }
    } else {
        resultDiv.innerHTML = '<div class="result-error">Некорректный ответ сервера</div>';
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
    
    // Детализация по компонентам
    if (result.components) {
        html += '<div class="component-breakdown">';
        html += '<h4>Разбивка по компонентам:</h4>';
        html += '<ul>';
        
        if (result.components.cover) {
            html += '<li>Обложка: ' + Math.round(result.components.cover.total * 10) / 10 + ' ₽</li>';
        }
        if (result.components.back) {
            html += '<li>Задник: ' + Math.round(result.components.back.total * 10) / 10 + ' ₽</li>';
        }
        if (result.components.inner) {
            html += '<li>Внутренний блок: ' + Math.round(result.components.inner.total * 10) / 10 + ' ₽</li>';
        }
        if (result.binding) {
            html += '<li>Спиральная сборка: ' + Math.round(result.binding * 10) / 10 + ' ₽</li>';
        }
        if (result.laminationCost && result.laminationCost > 0) {
            html += '<li>Ламинация обложки: ' + Math.round(result.laminationCost * 10) / 10 + ' ₽</li>';
        }
        
        html += '</ul>';
        html += '</div>';
    }
    
    // Дополнительные детали
    html += '<details class="result-details">';
    html += '<summary class="result-summary">Подробности расчета</summary>';
    html += '<div class="result-details-content">';
    html += '<ul>';
    
    if (result.details) {
        html += '<li>Формат: ' + result.details.size + '</li>';
        html += '<li>Тираж: ' + result.details.quantity + ' шт</li>';
        html += '<li>Листов в блоке: ' + result.details.inner_pages + '</li>';
        
        if (result.details.services) {
            const services = result.details.services;
            if (services.bigovka) html += '<li>Биговка: включена</li>';
            if (services.perforation) html += '<li>Перфорация: включена</li>';
            if (services.drill) html += '<li>Сверление: включено</li>';
            if (services.numbering) html += '<li>Нумерация: включена</li>';
            if (services.cornerRadius > 0) html += '<li>Скругленных углов: ' + services.cornerRadius + '</li>';
        }
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
    
    // Если