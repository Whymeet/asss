<?php
/** Шаблон калькулятора листовок с новым дизайном */
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

// Подключаем общие стили
$this->addExternalCss($templateFolder.'/../.default/style.css');
// Подключаем специфичные стили для листовок (если есть)
if (file_exists($templateFolder.'/style.css')) {
    $this->addExternalCss($templateFolder.'/style.css');
}

// Проверяем, что конфигурация загружена
if (!$arResult['CONFIG_LOADED']) {
    echo '<div class="result-error">Ошибка: Конфигурация калькулятора не загружена</div>';
    return;
}

// Принудительно подключаем основные скрипты Битрикса
CJSCore::Init(['ajax', 'core']);

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
    
    <h2><?= $arResult['DESCRIPTION'] ?? 'Калькулятор печати листовок' ?></h2>
    
    <form id="<?= $calcType ?>CalcForm" class="calc-form">
        
        <?php if (!empty($arResult['PAPER_TYPES'])): ?>
        <!-- Тип бумаги -->
        <div class="form-group">
            <label class="form-label" for="paperType">Тип бумаги:</label>
            <select name="paperType" id="paperType" class="form-control" required>
                <?php foreach ($arResult['PAPER_TYPES'] as $paper): ?>
                    <option value="<?= htmlspecialchars($paper['ID']) ?>"><?= htmlspecialchars($paper['NAME']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>

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

        <!-- Тираж -->
        <div class="form-group">
            <label class="form-label" for="quantity">Тираж:</label>
            <input name="quantity" 
                   id="quantity" 
                   type="number" 
                   class="form-control" 
                   min="<?= $arResult['MIN_QUANTITY'] ?? 1 ?>" 
                   max="<?= $arResult['MAX_QUANTITY'] ?? '' ?>" 
                   value="<?= $arResult['DEFAULT_QUANTITY'] ?? 1000 ?>" 
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

        <button id="calcBtn" type="button" class="calc-button">Рассчитать стоимость</button>
        
        <div id="calcResult" class="calc-result"></div>
        
        <?php if (!empty($features['lamination'])): ?>
        <!-- Секция ламинации (показывается после расчета) -->
        <div id="laminationSection" class="lamination-section" style="display: none;">
            <h3>Дополнительная ламинация</h3>
            <p style="margin-bottom: 15px;">Добавить ламинацию к заказу:</p>
            <div class="form-group">
                <label class="form-label">Толщина:</label>
                <select name="laminationThickness" class="form-control">
                    <option value="32">32 мкм</option>
                    <option value="75">75 мкм</option>
                    <option value="125">125 мкм</option>
                    <option value="250">250 мкм</option>
                </select>
            </div>
            <div class="form-group">
                <div class="radio-group">
                    <label class="radio-label">
                        <input type="radio" name="laminationType" value="1+0"> 
                        1+0 (x1)
                    </label>
                    <label class="radio-label">
                        <input type="radio" name="laminationType" value="1+1"> 
                        1+1 (x2)
                    </label>
                </div>
            </div>
            <button id="laminationCalcBtn" type="button" class="calc-button calc-button-success">Пересчитать с ламинацией</button>
            <div id="laminationResult" class="calc-result"></div>
        </div>
        <?php endif; ?>
    </form>
    
    <div class="calc-thanks">
        <p>Спасибо, что Вы с нами!</p>
    </div>
</div>

<script>
BX.ready(function() {
    var calcConfig = {
        type: '<?= $calcType ?>',
        component: 'my:print.calc',
        features: <?= json_encode($features) ?>
    };

    var form = document.getElementById(calcConfig.type + 'CalcForm');
    var resultDiv = document.getElementById('calcResult');
    var calcBtn = document.getElementById('calcBtn');
    var laminationBtn = document.getElementById('laminationCalcBtn');
    var laminationSection = document.getElementById('laminationSection');
    var laminationResult = document.getElementById('laminationResult');
    var lastCalculation = null;

    if (!form || !resultDiv || !calcBtn) {
        console.error('Не найдены необходимые элементы формы');
        return;
    }

    function collectFormData() {
        var formData = new FormData(form);
        var data = {};
        
        formData.forEach(function(value, key) {
            data[key] = value;
        });

        form.querySelectorAll('input[type="checkbox"]').forEach(function(checkbox) {
            data[checkbox.name] = checkbox.checked;
        });

        data.calcType = calcConfig.type;
        data.sessid = BX.bitrix_sessid();

        return data;
    }

    function showError(message) {
        return '<div class="error">' + message + '</div>';
    }

    function formatResult(result) {
        var html = '<div class="success">';
        html += '<p>Тип печати: ' + result.printingType + '</p>';
        html += '<p>Стоимость печати: ' + result.printingCost + ' руб.</p>';
        if (result.plateCost > 0) {
            html += '<p>Стоимость пластин: ' + result.plateCost + ' руб.</p>';
        }
        html += '<p>Стоимость бумаги: ' + result.paperCost + ' руб.</p>';
        if (result.additionalCosts > 0) {
            html += '<p>Дополнительные услуги: ' + result.additionalCosts + ' руб.</p>';
        }
        if (result.laminationCost > 0) {
            html += '<p>Стоимость ламинации: ' + result.laminationCost + ' руб.</p>';
        }
        html += '<p class="total">Итого: ' + result.totalPrice + ' руб.</p>';
        html += '</div>';
        return html;
    }

    calcBtn.addEventListener('click', function() {
        var data = collectFormData();
        resultDiv.innerHTML = '<div class="loading">Выполняется расчет...</div>';

        BX.ajax.runComponentAction(calcConfig.component, 'calc', {
            mode: 'class',
            data: data,
            method: 'POST'
        }).then(function(response) {
            if (response.data.error) {
                resultDiv.innerHTML = showError(response.data.error);
            } else {
                lastCalculation = response.data;
                resultDiv.innerHTML = formatResult(response.data);

                if (calcConfig.features.lamination) {
                    laminationSection.style.display = 'block';
                }
            }
        }).catch(function(error) {
            if (error.status === 401) {
                resultDiv.innerHTML = showError('Ошибка авторизации. Пожалуйста, обновите страницу.');
            } else {
                resultDiv.innerHTML = showError('Ошибка при расчете: ' + (error.errors?.[0]?.message || 'Неизвестная ошибка'));
            }
        });
    });

    if (laminationBtn) {
        laminationBtn.addEventListener('click', function() {
            if (!lastCalculation) {
                laminationResult.innerHTML = showError('Сначала выполните основной расчет');
                return;
            }

            var data = collectFormData();
            var laminationType = form.querySelector('input[name="laminationType"]:checked');
            var laminationThickness = form.querySelector('select[name="laminationThickness"]');

            if (!laminationType || !laminationThickness) {
                laminationResult.innerHTML = showError('Выберите тип и толщину ламинации');
                return;
            }

            data.laminationType = laminationType.value;
            data.laminationThickness = laminationThickness.value;
            data.withLamination = true;

            laminationResult.innerHTML = '<div class="loading">Выполняется расчет...</div>';

            BX.ajax.runComponentAction(calcConfig.component, 'calc', {
                mode: 'class',
                data: data,
                method: 'POST'
            }).then(function(response) {
                if (response.data.error) {
                    laminationResult.innerHTML = showError(response.data.error);
                } else {
                    laminationResult.innerHTML = formatResult(response.data);
                }
            }).catch(function(error) {
                if (error.status === 401) {
                    laminationResult.innerHTML = showError('Ошибка авторизации. Пожалуйста, обновите страницу.');
                } else {
                    laminationResult.innerHTML = showError('Ошибка при расчете: ' + (error.errors?.[0]?.message || 'Неизвестная ошибка'));
                }
            });
        });
    }
});
</script>

<style>
.calc-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

.calc-disclaimer {
    background: #f9f9f9;
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 5px;
}

.form-group {
    margin-bottom: 15px;
}

.form-label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.form-control {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.radio-group, .checkbox-group {
    display: flex;
    gap: 15px;
}

.radio-label, .checkbox-label {
    display: flex;
    align-items: center;
    gap: 5px;
}

.calc-button {
    background: #007bff;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    width: 100%;
    margin-top: 20px;
}

.calc-button:hover {
    background: #0056b3;
}

.calc-button-success {
    background: #28a745;
}

.calc-button-success:hover {
    background: #218838;
}

.calc-result {
    margin-top: 20px;
    padding: 15px;
    border-radius: 4px;
}

.loading {
    text-align: center;
    color: #666;
}

.error {
    background: #fee;
    color: #c00;
    padding: 10px;
    border-radius: 4px;
}

.success {
    background: #efe;
    padding: 10px;
    border-radius: 4px;
}

.success p {
    margin: 5px 0;
}

.success .total {
    font-weight: bold;
    font-size: 1.2em;
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px solid #ccc;
}

.lamination-section {
    margin-top: 20px;
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background: #f9f9f9;
}

.lamination-section h3 {
    margin-top: 0;
    margin-bottom: 15px;
    color: #333;
}

#laminationResult {
    margin-top: 15px;
}

.calc-thanks {
    text-align: center;
    margin-top: 20px;
    font-style: italic;
    color: #666;
}
</style>