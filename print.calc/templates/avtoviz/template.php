<?php
/** Шаблон калькулятора автовизиток */
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

// Подключаем общие стили
$this->addExternalCss($templateFolder.'/../.default/style.css');
// Подключаем специфичные стили для автовизиток (если есть)
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
$availablePapers = $arResult['available_papers'] ?? [];
$paperRecommendations = $arResult['paper_recommendations'] ?? [];
?>

<div class="calc-container">
    <!-- Информационный блок -->
    <div class="calc-disclaimer">
        <p>
            Данные, полученные при расчете на калькуляторе – являются ориентировочными в связи с регулярным изменением стоимости материалов.<br>
            Конечную стоимость заказа уточняйте у менеджера: <a href="tel:+78462060068">+7 (846) 206-00-68</a><br>
            <strong>Автовизитки:</strong> <?= $arResult['format_info'] ?? '' ?><br>
            <?= $arResult['paper_info'] ?? '' ?><br>
            <?= $arResult['services_info'] ?? '' ?><br>
            Спасибо за понимание!
        </p>
    </div>
    
    <h2><?= $arResult['DESCRIPTION'] ?? 'Калькулятор печати автовизиток' ?></h2>
    
    <form id="<?= $calcType ?>CalcForm" class="calc-form">
        
        <!-- Информация о формате -->
        <div class="form-section">
            <h3 class="section-title">📋 Формат</h3>
            <div class="format-info">
                <h4>Евро (99×210 мм)</h4>
                <p>Стандартный размер для автовизиток. Удобно размещается за стеклом автомобиля.</p>
            </div>
        </div>

        <!-- Тип бумаги -->
        <div class="form-section">
            <h3 class="section-title">Тип бумаги</h3>
            
            <div class="form-group">
                <label class="form-label" for="paperType">Выберите тип бумаги:</label>
                <select name="paperType" id="paperType" class="form-control" required>
                    <?php if (!empty($availablePapers)): ?>
                        <?php foreach ($availablePapers as $type => $name): ?>
                            <option value="<?= htmlspecialchars($type) ?>"><?= htmlspecialchars($name) ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <small class="text-muted">От плотности бумаги зависит долговечность автовизитки</small>
            </div>

            <!-- Рекомендации по типам бумаги -->
            <?php if (!empty($paperRecommendations)): ?>
            <div class="paper-recommendations">
                <h4>💡 Рекомендации:</h4>
                <div class="recommendation-groups">
                    <?php if (!empty($paperRecommendations['standard'])): ?>
                    <div class="recommendation-group">
                        <strong>Стандартные:</strong>
                        <span><?= implode(', ', array_map(function($p) use ($availablePapers) { 
                            return $availablePapers[$p] ?? $p; 
                        }, $paperRecommendations['standard'])) ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($paperRecommendations['premium'])): ?>
                    <div class="recommendation-group">
                        <strong>Премиум:</strong>
                        <span><?= implode(', ', array_map(function($p) use ($availablePapers) { 
                            return $availablePapers[$p] ?? $p; 
                        }, $paperRecommendations['premium'])) ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($paperRecommendations['special'])): ?>
                    <div class="recommendation-group">
                        <strong>Специальные:</strong>
                        <span><?= implode(', ', array_map(function($p) use ($availablePapers) { 
                            return $availablePapers[$p] ?? $p; 
                        }, $paperRecommendations['special'])) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Тираж -->
        <div class="form-section">
            <h3 class="section-title">Тираж</h3>
            
            <div class="form-group">
                <label class="form-label" for="quantity">Количество автовизиток:</label>
                <input name="quantity" 
                       id="quantity" 
                       type="number" 
                       class="form-control" 
                       min="<?= $arResult['min_quantity'] ?? 1 ?>" 
                       max="<?= $arResult['max_quantity'] ?? 50000 ?>" 
                       value="<?= $arResult['default_quantity'] ?? 500 ?>" 
                       placeholder="Введите количество"
                       required>
                <small class="text-muted">Минимальный тираж: <?= $arResult['min_quantity'] ?? 1 ?> шт.</small>
            </div>
        </div>

        <!-- Тип печати -->
        <div class="form-section">
            <h3 class="section-title">Тип печати</h3>
            
            <div class="form-group">
                <div class="radio-group">
                    <label class="radio-label">
                        <input type="radio" name="printType" value="single" checked>
                        <span class="radio-custom"></span>
                        <div class="radio-content">
                            <strong>Односторонняя печать</strong>
                            <small>Печать только с одной стороны</small>
                        </div>
                    </label>
                    <label class="radio-label">
                        <input type="radio" name="printType" value="double">
                        <span class="radio-custom"></span>
                        <div class="radio-content">
                            <strong>Двусторонняя печать</strong>
                            <small>Печать с обеих сторон</small>
                        </div>
                    </label>
                </div>
            </div>
        </div>

        <!-- Дополнительные услуги -->
        <div class="form-section">
            <h3 class="section-title">Дополнительные услуги</h3>
            
            <div class="form-group">
                <div class="services-grid">
                    <?php if ($features['bigovka'] ?? false): ?>
                    <label class="service-label">
                        <input type="checkbox" name="bigovka">
                        <span class="checkbox-custom"></span>
                        <div class="service-info">
                            <strong>Биговка</strong>
                            <small>Создание линий сгиба</small>
                        </div>
                    </label>
                    <?php endif; ?>

                    <?php if ($features['perforation'] ?? false): ?>
                    <label class="service-label">
                        <input type="checkbox" name="perforation">
                        <span class="checkbox-custom"></span>
                        <div class="service-info">
                            <strong>Перфорация</strong>
                            <small>Перфорированные линии</small>
                        </div>
                    </label>
                    <?php endif; ?>

                    <?php if ($features['drill'] ?? false): ?>
                    <label class="service-label">
                        <input type="checkbox" name="drill">
                        <span class="checkbox-custom"></span>
                        <div class="service-info">
                            <strong>Сверление 5мм</strong>
                            <small>Отверстие диаметром 5мм</small>
                        </div>
                    </label>
                    <?php endif; ?>

                    <?php if ($features['numbering'] ?? false): ?>
                    <label class="service-label">
                        <input type="checkbox" name="numbering">
                        <span class="checkbox-custom"></span>
                        <div class="service-info">
                            <strong>Нумерация</strong>
                            <small>Последовательная нумерация</small>
                        </div>
                    </label>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($features['corner_radius'] ?? false): ?>
        <!-- Скругление углов -->
        <div class="form-section">
            <h3 class="section-title">Скругление углов</h3>
            
            <div class="form-group">
                <label class="form-label" for="cornerRadius">Количество скругленных углов:</label>
                <select name="cornerRadius" id="cornerRadius" class="form-control">
                    <option value="0">Без скругления</option>
                    <option value="1">1 угол</option>
                    <option value="2">2 угла</option>
                    <option value="3">3 угла</option>
                    <option value="4">4 угла</option>
                </select>
                <small class="text-muted">Скругление придает автовизитке более привлекательный вид</small>
            </div>
        </div>
        <?php endif; ?>

        <!-- Скрытые поля -->
        <input type="hidden" name="size" value="Евро">
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
/* Основные стили */
.calc-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
}

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

/* Стили для информации о формате */
.format-info {
    background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
    border: 1px solid #2196f3;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 15px;
    color: #1565c0;
    text-align: center;
}

.format-info h4 {
    margin: 0 0 10px 0;
    color: #0d47a1;
    font-size: 18px;
}

.format-info p {
    margin: 0;
    font-size: 14px;
    opacity: 0.9;
}

/* Стили для рекомендаций */
.paper-recommendations {
    background: #fff3e0;
    border: 1px solid #ff9800;
    border-radius: 6px;
    padding: 15px;
    margin-top: 15px;
}

.paper-recommendations h4 {
    margin: 0 0 15px 0;
    color: #ef6c00;
    font-size: 16px;
}

.recommendation-groups {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.recommendation-group {
    font-size: 14px;
}

.recommendation-group strong {
    color: #e65100;
    margin-right: 8px;
}

/* Стили для радио-кнопок */
.radio-group {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.radio-label {
    display: flex;
    align-items: flex-start;
    cursor: pointer;
    gap: 12px;
    padding: 15px;
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    transition: all 0.3s;
}

.radio-label:hover {
    border-color: #007bff;
    box-shadow: 0 2px 8px rgba(0, 123, 255, 0.1);
}

.radio-custom {
    width: 20px;
    height: 20px;
    border: 2px solid #007bff;
    border-radius: 50%;
    position: relative;
    background: white;
    flex-shrink: 0;
    margin-top: 2px;
}

.radio-content {
    flex: 1;
}

.radio-content strong {
    display: block;
    margin-bottom: 4px;
}

.radio-content small {
    color: #6c757d;
    font-size: 12px;
}

.radio-label input[type="radio"] {
    display: none;
}

.radio-label input[type="radio"]:checked + .radio-custom::after {
    content: '';
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #007bff;
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
}

/* Стили для дополнительных услуг */
.services-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
}

.service-label {
    display: flex;
    align-items: center;
    cursor: pointer;
    gap: 12px;
    padding: 15px;
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    transition: all 0.3s;
}

.service-label:hover {
    border-color: #007bff;
    box-shadow: 0 2px 8px rgba(0, 123, 255, 0.1);
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

.service-label input[type="checkbox"] {
    display: none;
}

.service-label input[type="checkbox"]:checked + .checkbox-custom::after {
    content: '✓';
    color: white;
    font-size: 12px;
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
}

.service-label input[type="checkbox"]:checked + .checkbox-custom {
    background: #007bff;
}

.service-info {
    flex: 1;
}

.service-info strong {
    display: block;
    color: #495057;
    font-size: 14px;
}

.service-info small {
    color: #6c757d;
    font-size: 12px;
}

/* Стили для результатов */
.result-success {
    background: #f8f9fa;
    border: 1px solid #28a745;
    border-radius: 8px;
    padding: 20px;
    margin-top: 20px;
}

.result-title {
    color: #28a745;
    margin: 0 0 15px 0;
    font-size: 20px;
}

.result-price {
    font-size: 32px;
    color: #28a745;
    font-weight: bold;
    margin-bottom: 15px;
}

.result-price small {
    font-size: 20px;
}

.result-details {
    margin-top: 15px;
}

.result-summary {
    cursor: pointer;
    color: #007bff;
    font-weight: 500;
}

.result-details-content {
    margin-top: 10px;
    padding: 10px;
    background: white;
    border-radius: 4px;
}

.result-details-content ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.result-details-content li {
    padding: 5px 0;
    border-bottom: 1px solid #eee;
}

.result-details-content li:last-child {
    border-bottom: none;
}

/* Адаптивность */
@media (max-width: 768px) {
    .calc-container {
        padding: 10px;
    }
    
    .form-section {
        padding: 15px;
        margin-bottom: 15px;
    }
    
    .section-title {
        font-size: 16px;
    }
    
    .services-grid {
        grid-template-columns: 1fr;
    }
    
    .radio-group {
        gap: 10px;
    }
    
    .radio-label, .service-label {
        padding: 12px;
    }
    
    .recommendation-groups {
        gap: 8px;
    }
    
    .result-price {
        font-size: 28px;
    }
}

/* Анимации */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.calc-result {
    animation: fadeIn 0.3s ease-out;
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
        
        resultDiv.innerHTML = '<div class="loading">Выполняется расчет автовизиток...</div>';

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
        
        resultDiv.innerHTML = '<div class="loading">Выполняется расчет автовизиток...</div>';

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
    
    // Фиксированный размер
    data.size = 'Евро';
    
    return data;
}

// Обработка ответа сервера
function handleResponse(response, resultDiv) {
    if (response && response.data) {
        if (response.data.error) {
            resultDiv.innerHTML = '<div class="result-error">Ошибка: ' + response.data.error + '</div>';
        } else {
            displayAvtovizResult(response.data, resultDiv);
        }
    } else {
        resultDiv.innerHTML = '<div class="result-error">Некорректный ответ сервера</div>';
    }
}

// Отображение результата расчета автовизиток
function displayAvtovizResult(result, resultDiv) {
    const totalPrice = Math.round((result.totalPrice || 0) * 100) / 100;
    
    let html = '<div class="result-success">';
    html += '<h3 class="result-title">Результат расчета автовизиток</h3>';
    html += '<div class="result-price">' + formatPrice(totalPrice) + ' <small>₽</small></div>';
    
    html += '<div class="avtoviz-details">';
    html += '<strong>Формат:</strong> Евро (99×210 мм) • ';
    html += '<strong>Тираж:</strong> ' + (result.quantity || 0) + ' шт.';
    html += '</div>';
    
    html += '<details class="result-details">';
    html += '<summary class="result-summary">Подробности расчета</summary>';
    html += '<div class="result-details-content">';
    html += '<ul>';
    
    if (result.printingType) {
        html += '<li>Тип печати: <strong>' + result.printingType + '</strong></li>';
    }
    
    if (result.baseA3Sheets) {
        html += '<li>Базовые листы A3: ' + result.baseA3Sheets + '</li>';
    }
    
    if (result.adjustment) {
        html += '<li>Приладочные листы: ' + result.adjustment + '</li>';
    }
    
    if (result.totalA3Sheets) {
        html += '<li>Всего листов A3: ' + result.totalA3Sheets + '</li>';
    }
    
    if (result.printingCost) {
        html += '<li>Стоимость печати: ' + formatPrice(result.printingCost) + ' ₽</li>';
    }
    
    if (result.plateCost) {
        html += '<li>Стоимость пластины: ' + formatPrice(result.plateCost) + ' ₽</li>';
    }
    
    if (result.paperCost) {
        html += '<li>Стоимость бумаги: ' + formatPrice(result.paperCost) + ' ₽</li>';
    }
    
    if (result.additionalCosts) {
        html += '<li>Дополнительные услуги: ' + formatPrice(result.additionalCosts) + ' ₽</li>';
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
</script>