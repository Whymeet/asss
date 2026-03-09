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
            Спасибо за понимание!
        </p>
    </div>

    <h2><?= $arResult['DESCRIPTION'] ?? 'Калькулятор каталогов' ?></h2>

    <form id="<?= $calcType ?>CalcForm" class="calc-form">

        <!-- Бумага обложки -->
        <div class="form-group">
            <label class="form-label" for="coverPaper">Бумага обложки:</label>
            <select name="coverPaper" id="coverPaper" class="form-control" required>
                <?php foreach ($arResult['cover_paper_types'] as $value => $name): ?>
                    <option value="<?= $value ?>"><?= htmlspecialchars($name) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Печать обложки -->
        <div class="form-group">
            <label class="form-label" for="coverPrintType">Печать обложки:</label>
            <select name="coverPrintType" id="coverPrintType" class="form-control" required>
                <?php foreach ($arResult['cover_print_types'] as $key => $name): ?>
                    <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($name) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Бумага внутренних листов -->
        <div class="form-group">
            <label class="form-label" for="innerPaper">Бумага внутренних листов:</label>
            <select name="innerPaper" id="innerPaper" class="form-control" required>
                <?php foreach ($arResult['inner_paper_types'] as $value => $name): ?>
                    <option value="<?= $value ?>"><?= htmlspecialchars($name) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Печать внутренних листов -->
        <div class="form-group">
            <label class="form-label" for="innerPrintType">Печать внутренних листов:</label>
            <select name="innerPrintType" id="innerPrintType" class="form-control" required>
                <?php foreach ($arResult['inner_print_types'] as $key => $name): ?>
                    <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($name) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <?php if (!empty($arResult['FORMATS'])): ?>
        <!-- Формат -->
        <div class="form-group">
            <label class="form-label" for="size">Формат:</label>
            <select name="size" id="size" class="form-control" required>
                <?php foreach ($arResult['FORMATS'] as $format): ?>
                    <option value="<?= htmlspecialchars($format['ID']) ?>"><?= htmlspecialchars($format['NAME']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>

        <!-- Количество страниц -->
        <div class="form-group">
            <label class="form-label" for="pages">Количество страниц:</label>
            <select name="pages" id="pages" class="form-control" required>
                <?php foreach ($arResult['available_pages'] as $p): ?>
                    <option value="<?= $p ?>"><?= $p ?> стр.</option>
                <?php endforeach; ?>
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
                <?php foreach ($arResult['binding_types'] as $key => $name): ?>
                    <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($name) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <input type="hidden" name="calcType" value="<?= $calcType ?>">
        <input type="hidden" name="sessid" value="<?= bitrix_sessid() ?>">

        <button id="calcBtn" type="button" class="calc-button">Рассчитать стоимость</button>

        <div id="calcResult" class="calc-result"></div>
        <div class="calc-spacer"></div>
    </form>

    <?php include dirname(__DIR__) . '/_shared/order-modal.php'; ?>
</div>

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
    var __m = document.getElementById("orderModal"); if (__m && __m.parentElement !== document.body) document.body.appendChild(__m);
    initializeDateTimeValidation();
});

// === УНИКАЛЬНАЯ ЛОГИКА КАТАЛОГОВ ===

// Отображение результата
function displayCatalogResult(result, resultDiv) {
    var totalPrice = formatPrice(result.totalPrice);

    var html = '<div class="result-success">';
    html += '<h3 class="result-title">Результат расчета</h3>';
    html += '<div class="result-price">' + totalPrice + ' <small>₽</small></div>';

    // Кнопка заказа
    html += '<button type="button" class="order-button" onclick="openOrderModal()">Заказать каталоги</button>';
    html += '</div>';

    resultDiv.innerHTML = html;
}

// Открытие модалки с данными заказа каталогов
function openOrderModal() {
    var modal = document.getElementById('orderModal');
    var orderDataInput = document.getElementById('orderData');

    var form = document.getElementById(calcConfig.type + 'CalcForm');
    var formData = collectFormData(form);

    var resultDiv = document.getElementById('calcResult');
    var priceElement = resultDiv.querySelector('.result-price');
    var totalPrice = priceElement ? priceElement.textContent.replace(/[^\d.,]/g, '') : '0';

    // Данные заказа каталогов
    var orderData = {
        calcType: 'catalog',
        product: 'Каталоги',
        quantity: formData.quantity || 0,
        size: formData.size || 'Не указан',
        pages: formData.pages || 'Не указано',
        coverPaper: formData.coverPaper || 'Не указано',
        coverPrintType: formData.coverPrintType || 'Не указано',
        innerPaper: formData.innerPaper || 'Не указано',
        innerPrintType: formData.innerPrintType || 'Не указано',
        bindingType: formData.bindingType || 'Не указан',
        totalPrice: totalPrice
    };

    orderDataInput.value = JSON.stringify(orderData);
    modal.style.display = 'block';
}
</script>
