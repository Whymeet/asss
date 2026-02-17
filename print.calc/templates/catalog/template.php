<?php
/** Шаблон калькулятора каталогов */
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

// Подключаем общие стили и скрипты
$this->addExternalCss($templateFolder.'/../.default/style.css');
if (file_exists($templateFolder.'/style.css')) {
    $this->addExternalCss($templateFolder.'/style.css');
}
$this->addExternalJs($templateFolder.'/../_shared/shared.js');

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

        <input type="hidden" name="calcType" value="<?= $calcType ?>">
        <input type="hidden" name="sessid" value="<?= bitrix_sessid() ?>">

        <button id="calcBtn" type="button" class="calc-button">Рассчитать стоимость</button>

        <div id="calcResult" class="calc-result"></div>

        <div class="calc-spacer"></div>
    </form>

    <?php include dirname(__DIR__) . '/_shared/order-modal.php'; ?>
</div>

<style>
/* Специфичные стили для каталогов */
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
</style>

<script>
// Конфигурация калькулятора
var calcConfig = {
    type: '<?= $calcType ?>',
    features: <?= json_encode($features) ?>,
    component: 'my:print.calc'
};

// Hook для отображения результата (вызывается из shared.js)
window.displayResult = function(data, resultDiv) {
    displayCatalogResult(data, resultDiv);
};

// Инициализация
document.addEventListener('DOMContentLoaded', function() {
    initCalculator('Выполняется расчет каталогов...');
    initOrderModal();
    initializeDateTimeValidation();
});

// === УНИКАЛЬНАЯ ЛОГИКА КАТАЛОГОВ ===

// Отображение результата каталога
function displayCatalogResult(result, resultDiv) {
    var totalPrice = Math.round((result.totalPrice || 0) * 10) / 10;

    var html = '<div class="result-success">';
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
        var bindingType = result.bindingType || 'Сборка';
        var bindingName = bindingType === 'spiral' ? 'Пружина' : 'Скоба';
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

    // Добавляем кнопку заказа
    html += '<button type="button" class="order-button" onclick="openOrderModal()">Заказать печать</button>';

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

// Открытие модалки с данными заказа каталогов
function openOrderModal() {
    var modal = document.getElementById('orderModal');
    var orderDataInput = document.getElementById('orderData');

    // Собираем данные расчета
    var form = document.getElementById(calcConfig.type + 'CalcForm');
    var formData = collectFormData(form);

    // Получаем результат расчета
    var resultDiv = document.getElementById('calcResult');
    var priceElement = resultDiv.querySelector('.result-price');
    var totalPrice = priceElement ? priceElement.textContent.replace(/[^\d.,]/g, '') : '0';

    // Формируем данные заказа для каталогов
    var orderData = {
        product: 'Каталоги',
        coverPaper: formData.coverPaper || 'Не указано',
        coverPrintType: formData.coverPrintType || 'Не указано',
        innerPaper: formData.innerPaper || 'Не указано',
        innerPrintType: formData.innerPrintType || 'Не указано',
        size: formData.size || 'Не указан',
        pages: formData.pages || 'Не указано',
        quantity: formData.quantity || 0,
        bindingType: formData.bindingType || 'Не указан',
        totalPrice: totalPrice,
        calcType: 'catalog'
    };

    orderDataInput.value = JSON.stringify(orderData);
    modal.style.display = 'block';
}
</script>