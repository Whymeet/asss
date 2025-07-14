<?php
/** Шаблон калькулятора размещения */
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

// Подключаем общие стили
$this->addExternalCss($templateFolder.'/../.default/style.css');
// Подключаем специфичные стили для размещения (если есть)
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
            <strong>Размещение:</strong> <?= $arResult['format_info'] ?? '' ?><br>
            <?= $arResult['paper_info'] ?? '' ?><br>
            Спасибо за понимание!
        </p>
    </div>
    
    <h2><?= $arResult['DESCRIPTION'] ?? 'Калькулятор печати размещения' ?></h2>
    
    <form id="<?= $calcType ?>CalcForm" class="calc-form">
        
        <?php if (!empty($arResult['PAPER_TYPES'])): ?>
        <!-- Тип бумаги (ограниченный выбор) -->
        <div class="form-group">
            <label class="form-label" for="paperType">Тип бумаги:</label>
            <select name="paperType" id="paperType" class="form-control" required>
                <?php foreach ($arResult['PAPER_TYPES'] as $paper): ?>
                    <option value="<?= htmlspecialchars($paper['ID']) ?>"><?= htmlspecialchars($paper['NAME']) ?></option>
                <?php endforeach; ?>
            </select>
            <small class="text-muted">Ограниченный выбор типов бумаги для размещения</small>
        </div>
        <?php endif; ?>

        <?php if (!empty($arResult['FORMATS'])): ?>
        <!-- Формат (только А3) -->
        <div class="form-group">
            <label class="form-label" for="size">Формат:</label>
            <select name="size" id="size" class="form-control" required>
                <?php foreach ($arResult['FORMATS'] as $format): ?>
                    <option value="<?= htmlspecialchars($format['ID']) ?>"><?= htmlspecialchars($format['NAME']) ?></option>
                <?php endforeach; ?>
            </select>
            <small class="text-muted">Доступен только формат А3</small>
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
                   value="<?= $arResult['DEFAULT_QUANTITY'] ?? 100 ?>" 
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
        // Показываем дополнительные услуги
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
            <small class="text-muted">Максимум <?= $arResult['corner_radius_max'] ?? 4 ?> угла</small>
        </div>
        <?php endif; ?>

        <input type="hidden" name="calcType" value="<?= $calcType ?>">
        <input type="hidden" name="sessid" value="<?= bitrix_sessid() ?>">

        <?php if (!empty($features['lamination'])): ?>
        <!-- Секция ламинации -->
        <div id="laminationSection" class="lamination-section">
            <h3>Дополнительная ламинация</h3>
            <div id="laminationControls"></div>
            <div id="laminationResult" class="lamination-result"></div>
        </div>
        <?php endif; ?>
        <button id="calcBtn" type="button" class="calc-button mt-4" style="margin-top:32px;">Рассчитать стоимость</button>
        <div id="calcResult" class="calc-result"></div>
        <div class="calc-spacer"></div>
    </form>

    <div class="calc-thanks">
        <p>Спасибо, что Вы с нами!</p>
    </div>
</div>

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

// Конфигурация для калькулятора размещения
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
        
        resultDiv.innerHTML = '<div class="loading">Выполняется расчет размещения...</div>';

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
        
        resultDiv.innerHTML = '<div class="loading">Выполняется расчет размещения...</div>';

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
            displayPlacementResult(response.data, resultDiv);
            // Показываем секцию ламинации если доступна
            if (calcConfig.features.lamination && response.data.printingType) {
                showLaminationSection(response.data);
            }
        }
    } else {
        resultDiv.innerHTML = '<div class="result-error">Некорректный ответ сервера</div>';
    }
}

// Отображение результата размещения
function displayPlacementResult(result, resultDiv) {
    const totalPrice = Math.round((result.totalPrice || 0) * 10) / 10;
    
    let html = '<div class="result-success">';
    html += '<h3 class="result-title">Результат расчета размещения</h3>';
    html += '<div class="result-price">' + totalPrice + ' <small>₽</small></div>';
    
    // Информация о типе печати
    if (result.printingType) {
        const isOffset = result.printingType === 'Офсетная';
        const color = isOffset ? '#28a745' : '#007bff';
        const bgColor = isOffset ? '#f8fff8' : '#f8f9ff';
        
        html += '<div style="color: ' + color + '; background: ' + bgColor + '; padding: 10px; border-radius: 6px; border-left: 4px solid ' + color + '; margin-bottom: 15px;">';
        html += '<strong>Тип печати:</strong> ' + result.printingType;
        if (isOffset) {
            html += '<br><small>Высокое качество для больших тиражей</small>';
        } else {
            html += '<br><small>Быстрая печать малых тиражей</small>';
        }
        html += '</div>';
    }

    // Показываем информацию о ламинации если она была добавлена
    if (result.laminationCost && result.laminationCost > 0) {
        html += '<div style="color: #28a745; background: #f8fff8; padding: 10px; border-radius: 6px; border-left: 4px solid #28a745; margin-bottom: 15px;">';
        html += '<strong>Ламинация добавлена:</strong> ' + Math.round(result.laminationCost * 10) / 10 + ' ₽';
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
    if (result.laminationCost && result.laminationCost > 0) html += '<li>Ламинация: ' + Math.round(result.laminationCost * 10) / 10 + ' ₽</li>';
    
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
    
    // Секция ламинации всегда видима, даже если ламинация уже добавлена
    
    let html = '<div class="lamination-content">';
    html += '<p class="lamination-title">Добавить ламинацию к заказу:</p>';
    
    if (result.printingType === 'Офсетная') {
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
}

// Функция расчета с ламинацией
function calculateWithLamination() {
    const laminationType = document.querySelector('input[name="laminationType"]:checked');
    const laminationThickness = document.querySelector('select[name="laminationThickness"]');
    const resultDiv = document.getElementById('calcResult');
    const laminationResult = document.getElementById('laminationResult');
    
    if (!laminationType) {
        if (laminationResult) {
            laminationResult.innerHTML = '<div class="result-error">Выберите тип ламинации</div>';
        }
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
    
    // Добавляем чекбоксы
    const checkboxes = ['bigovka', 'perforation', 'drill', 'numbering'];
    checkboxes.forEach(name => {
        const checkbox = form.querySelector(`input[name="${name}"]`);
        if (checkbox) {
            data[name] = checkbox.checked;
        }
    });

    return data;
}

// Запуск инициализации
document.addEventListener('DOMContentLoaded', function() {
    waitForBX(initWithBX, initWithoutBX, 3000);
});
</script>