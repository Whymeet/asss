<?php
/** Шаблон калькулятора кубариков */
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
$sheetsPerPackOptions = $arResult['sheets_per_pack_options'] ?? [];
$printTypes = $arResult['print_types'] ?? [];
?>

<div class="calc-container">
    <!-- Информационный блок -->
    <div class="calc-disclaimer">
        <p>
            Данные, полученные при расчете на калькуляторе – являются ориентировочными в связи с регулярным изменением стоимости материалов.<br>
            Конечную стоимость заказа уточняйте у менеджера: <a href="tel:+78462060068">+7 (846) 206-00-68</a><br>
            <strong>Кубарики:</strong> <?= $arResult['format_info'] ?? '' ?><br>
            <?= $arResult['paper_info'] ?? '' ?><br>
            <?= $arResult['multiplier_info'] ?? '' ?><br>
            Спасибо за понимание!
        </p>
    </div>

    <h2><?= $arResult['DESCRIPTION'] ?? 'Калькулятор кубариков' ?></h2>

    <form id="<?= $calcType ?>CalcForm" class="calc-form">

        <!-- Листов в пачке -->
        <div class="form-group">
            <label class="form-label" for="sheetsPerPack">Листов в пачке:</label>
            <select name="sheetsPerPack" id="sheetsPerPack" class="form-control" required>
                <?php if (!empty($sheetsPerPackOptions)): ?>
                    <?php foreach ($sheetsPerPackOptions as $count): ?>
                        <option value="<?= $count ?>" <?= $count == 100 ? 'selected' : '' ?>><?= $count ?> листов</option>
                    <?php endforeach; ?>
                <?php else: ?>
                    <option value="100" selected>100 листов</option>
                    <option value="300">300 листов</option>
                    <option value="500">500 листов</option>
                    <option value="900">900 листов</option>
                <?php endif; ?>
            </select>
            <small class="text-muted">Стандартные варианты упаковки кубариков</small>
        </div>

        <!-- Количество пачек -->
        <div class="form-group">
            <label class="form-label" for="packsCount">Количество пачек:</label>
            <input name="packsCount"
                   id="packsCount"
                   type="number"
                   class="form-control"
                   min="1"
                   value="1"
                   placeholder="Введите количество пачек"
                   required>
            <small class="text-muted">Общее количество листов будет рассчитано автоматически</small>
        </div>

        <!-- Тип печати -->
        <div class="form-group">
            <label class="form-label" for="printType">Тип печати:</label>
            <select name="printType" id="printType" class="form-control" required>
                <?php if (!empty($printTypes)): ?>
                    <?php foreach ($printTypes as $key => $name): ?>
                        <option value="<?= htmlspecialchars($key) ?>" <?= $key == '4+0' ? 'selected' : '' ?>><?= htmlspecialchars($name) ?></option>
                    <?php endforeach; ?>
                <?php else: ?>
                    <option value="1+0">1+0 (Односторонняя ч/б)</option>
                    <option value="1+1">1+1 (Двусторонняя ч/б)</option>
                    <option value="4+0" selected>4+0 (Цветная с одной стороны)</option>
                    <option value="4+4">4+4 (Цветная с двух сторон)</option>
                <?php endif; ?>
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
    displayKubaricResult(data, resultDiv);
};

// Инициализация
document.addEventListener('DOMContentLoaded', function() {
    initCalculator('Выполняется расчет кубариков...');
    initOrderModal();
    initializeDateTimeValidation();
});

// === УНИКАЛЬНАЯ ЛОГИКА КУБАРИКОВ ===

// Отображение результата кубариков (использует finalPrice вместо totalPrice)
function displayKubaricResult(result, resultDiv) {
    var finalPrice = Math.round((result.finalPrice || 0) * 10) / 10;

    var html = '<div class="result-success">';
    html += '<h3 class="result-title">Результат расчета кубариков</h3>';
    html += '<div class="result-price">' + finalPrice + ' <small>₽</small></div>';

    // Кнопка заказа
    html += '<button type="button" class="order-button" onclick="openOrderModal()">Заказать печать</button>';

    html += '</div>';

    resultDiv.innerHTML = html;
}

// Открытие модалки с данными заказа кубариков
function openOrderModal() {
    var modal = document.getElementById('orderModal');
    var orderDataInput = document.getElementById('orderData');

    var form = document.getElementById(calcConfig.type + 'CalcForm');
    var formData = collectFormData(form);

    var resultDiv = document.getElementById('calcResult');
    var priceElement = resultDiv.querySelector('.result-price');
    var totalPrice = priceElement ? priceElement.textContent.replace(/[^\d.,]/g, '') : '0';

    var orderData = {
        product: 'Кубарики',
        sheetsPerPack: formData.sheetsPerPack || '',
        packsCount: formData.packsCount || '',
        totalSheets: (parseInt(formData.sheetsPerPack) || 0) * (parseInt(formData.packsCount) || 0),
        printType: formData.printType || '',
        format: '9×9 см',
        paperDensity: '80 г/м²',
        totalPrice: totalPrice,
        calcType: 'kubaric'
    };

    orderDataInput.value = JSON.stringify(orderData);
    modal.style.display = 'block';
}
</script>