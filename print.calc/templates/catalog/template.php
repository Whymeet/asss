<?php
/** Шаблон калькулятора каталогов */
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

// Подключаем общие стили
$this->addExternalCss($templateFolder.'/../.default/style.css');

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
            <strong>Каталоги:</strong> <?= $arResult['collation_info'] ?? 'Листоподборка включена в стоимость' ?><br>
            <?= $arResult['binding_info'] ?? 'Доступны два типа сборки: пружина или скоба' ?><br>
            Спасибо за понимание!
        </p>
    </div>
    
    <h2><?= $arResult['DESCRIPTION'] ?? 'Калькулятор каталогов' ?></h2>
    
    <form id="<?= $calcType ?>CalcForm" class="calc-form">
        
        <!-- Обложка -->
        <div class="form-section">
            <h3 class="section-title">Обложка</h3>
            
            <!-- Плотность бумаги обложки -->
            <div class="form-group">
                <label class="form-label" for="coverPaper">Плотность бумаги обложки:</label>
                <select name="coverPaper" id="coverPaper" class="form-control" required>
                    <?php if (!empty($arResult['cover_paper_types'])): ?>
                        <?php foreach ($arResult['cover_paper_types'] as $value => $name): ?>
                            <option value="<?= $value ?>"><?= htmlspecialchars($name) ?></option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="130">130 г/м²</option>
                        <option value="170">170 г/м²</option>
                        <option value="300">300 г/м²</option>
                    <?php endif; ?>
                </select>
            </div>

            <!-- Печать обложки -->
            <div class="form-group">
                <label class="form-label" for="coverPrintType">Печать обложки:</label>
                <select name="coverPrintType" id="coverPrintType" class="form-control" required>
                    <?php if (!empty($arResult['cover_print_types'])): ?>
                        <?php foreach ($arResult['cover_print_types'] as $key => $name): ?>
                            <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($name) ?></option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="4+0">4+0 (полноцвет с одной стороны)</option>
                        <option value="4+4">4+4 (полноцвет с двух сторон)</option>
                    <?php endif; ?>
                </select>
            </div>
        </div>

        <!-- Внутренние листы -->
        <div class="form-section">
            <h3 class="section-title">Внутренние листы</h3>
            
            <!-- Плотность бумаги внутренних листов -->
            <div class="form-group">
                <label class="form-label" for="innerPaper">Плотность бумаги внутренних листов:</label>
                <select name="innerPaper" id="innerPaper" class="form-control" required>
                    <?php if (!empty($arResult['inner_paper_types'])): ?>
                        <?php foreach ($arResult['inner_paper_types'] as $value => $name): ?>
                            <option value="<?= $value ?>"><?= htmlspecialchars($name) ?></option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="130">130 г/м²</option>
                        <option value="170">170 г/м²</option>
                    <?php endif; ?>
                </select>
            </div>

            <!-- Печать внутренних листов -->
            <div class="form-group">
                <label class="form-label" for="innerPrintType">Печать внутренних листов:</label>
                <select name="innerPrintType" id="innerPrintType" class="form-control" required>
                    <?php if (!empty($arResult['inner_print_types'])): ?>
                        <?php foreach ($arResult['inner_print_types'] as $key => $name): ?>
                            <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($name) ?></option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="4+4">4+4 (полноцвет с двух сторон)</option>
                    <?php endif; ?>
                </select>
            </div>
        </div>

        <!-- Параметры каталога -->
        <div class="form-section">
            <h3 class="section-title">Параметры каталога</h3>
            
            <!-- Формат -->
            <div class="form-group">
                <label class="form-label" for="size">Формат:</label>
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

            <!-- Количество страниц -->
            <div class="form-group">
                <label class="form-label" for="pages">Количество страниц:</label>
                <select name="pages" id="pages" class="form-control" required>
                    <?php if (!empty($arResult['available_pages'])): ?>
                        <?php foreach ($arResult['available_pages'] as $p): ?>
                            <option value="<?= $p ?>"><?= $p ?> стр.</option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="8">8 стр.</option>
                        <option value="12">12 стр.</option>
                        <option value="16">16 стр.</option>
                        <option value="20">20 стр.</option>
                        <option value="24">24 стр.</option>
                        <option value="28">28 стр.</option>
                        <option value="32">32 стр.</option>
                        <option value="36">36 стр.</option>
                        <option value="40">40 стр.</option>
                        <option value="44">44 стр.</option>
                        <option value="48">48 стр.</option>
                        <option value="52">52 стр.</option>
                        <option value="56">56 стр.</option>
                        <option value="60">60 стр.</option>
                        <option value="64">64 стр.</option>
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

            <!-- Тип сборки -->
            <div class="form-group">
                <label class="form-label" for="bindingType">Тип сборки:</label>
                <select name="bindingType" id="bindingType" class="form-control" required>
                    <?php if (!empty($arResult['binding_types'])): ?>
                        <?php foreach ($arResult['binding_types'] as $key => $name): ?>
                            <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($name) ?></option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="spiral">Пружина</option>
                        <option value="staple">Скоба</option>
                    <?php endif; ?>
                </select>
                <small class="text-muted"><?= $arResult['binding_info'] ?? 'Выберите тип сборки каталога' ?></small>
            </div>
        </div>

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

.same-paper-info {
    background: #e8f5e9;
    border: 1px solid #4caf50;
    border-radius: 6px;
    padding: 10px;
    margin: 10px 0;
    color: #2e7d32;
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

// Конфигурация для калькулятора каталогов
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
        
        resultDiv.innerHTML = '<div class="loading">Выполняется расчет каталога...</div>';

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
        
        resultDiv.innerHTML = '<div class="loading">Выполняется расчет каталога...</div>';

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
            displayCatalogResult(response.data, resultDiv);
        }
    } else {
        resultDiv.innerHTML = '<div class="result-error">Некорректный ответ сервера</div>';
    }
}

// Отображение результата каталога
function displayCatalogResult(result, resultDiv) {
    const totalPrice = Math.round((result.totalPrice || 0) * 10) / 10;
    
    let html = '<div class="result-success">';
    html += '<h3 class="result-title">Результат расчета каталога</h3>';
    html += '<div class="result-price">' + totalPrice + ' <small>₽</small></div>';
    
    // Информация о структуре каталога
    if (result.isSamePaper) {
        html += '<div class="same-paper-info">';
        html += '<strong>Обложка и внутренние листы на одинаковой бумаге</strong><br>';
        html += 'Печать выполняется одним тиражом';
        html += '</div>';
    }
    
    // Детализация по компонентам
    html += '<div class="price-breakdown">';
    html += '<h4>Детализация стоимости:</h4>';
    
    if (!result.isSamePaper) {
        if (result.coverCost && result.coverCost > 0) {
            html += '<div class="price-item">';
            html += '<span>Обложка:</span>';
            html += '<span>' + Math.round(result.coverCost * 10) / 10 + ' ₽</span>';
            html += '</div>';
        }
        
        if (result.innerCost && result.innerCost > 0) {
            html += '<div class="price-item">';
            html += '<span>Внутренние листы:</span>';
            html += '<span>' + Math.round(result.innerCost * 10) / 10 + ' ₽</span>';
            html += '</div>';
        }
    } else {
        if (result.innerCost && result.innerCost > 0) {
            html += '<div class="price-item">';
            html += '<span>Печать (обложка + внутренние):</span>';
            html += '<span>' + Math.round(result.innerCost * 10) / 10 + ' ₽</span>';
            html += '</div>';
        }
    }
    
    if (result.collationCost && result.collationCost > 0) {
        html += '<div class="price-item">';
        html += '<span>Листоподборка:</span>';
        html += '<span>' + Math.round(result.collationCost * 10) / 10 + ' ₽</span>';
        html += '</div>';
    }
    
    if (result.bindingCost && result.bindingCost > 0) {
        const bindingType = result.bindingType || 'Сборка';
        const bindingName = bindingType === 'spiral' ? 'Пружина' : 'Скоба';
        html += '<div class="price-item">';
        html += '<span>' + bindingName + ':</span>';
        html += '<span>' + Math.round(result.bindingCost * 10) / 10 + ' ₽</span>';
        html += '</div>';
    }
    
    html += '<div class="price-item">';
    html += '<span>Итого:</span>';
    html += '<span>' + totalPrice + ' ₽</span>';
    html += '</div>';
    
    html += '</div>';
    
    html += '<details class="result-details">';
    html += '<summary class="result-summary">Техническая информация</summary>';
    html += '<div class="result-details-content">';
    html += '<ul>';
    
    if (result.sheets) html += '<li>Эквивалент листов A3: ' + result.sheets + '</li>';
    if (result.adjustedPages) html += '<li>Скорректированные страницы: ' + result.adjustedPages + '</li>';
    if (result.isSamePaper !== undefined) html += '<li>Одинаковая бумага: ' + (result.isSamePaper ? 'Да' : 'Нет') + '</li>';
    
    html += '</ul>';
    html += '</div>';
    html += '</details>';
    html += '</div>';
    
    resultDiv.innerHTML = html;
}

// Сбор данных формы для каталогов
function collectFormData(form) {
    const formData = new FormData(form);
    const data = {};
    
    // Собираем все поля формы
    for (let [key, value] of formData.entries()) {
        data[key] = value;
    }

    return data;
}

// Запуск инициализации
document.addEventListener('DOMContentLoaded', function() {
    waitForBX(initWithBX, initWithoutBX, 3000);
});
</script>