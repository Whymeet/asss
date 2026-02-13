/**
 * Общий JS для всех калькуляторов печати
 *
 * Зависимости (задаются в каждом шаблоне):
 * - var calcConfig = { type: 'booklet', features: {...}, component: 'my:print.calc' }
 * - window.displayResult = function(data, resultDiv) { ... }  (хук для отображения)
 */

// === ПОДАВЛЕНИЕ ВНЕШНИХ ОШИБОК ===

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

// === ИНИЦИАЛИЗАЦИЯ КАЛЬКУЛЯТОРА ===

/**
 * Инициализация калькулятора: находит форму, кнопку, подключает BX или fetch
 * @param {string} loadingMessage - Сообщение при загрузке (например "Выполняется расчет...")
 */
function initCalculator(loadingMessage) {
    loadingMessage = loadingMessage || 'Выполняется расчет...';

    function setupCalcButton(useBX) {
        var form = document.getElementById(calcConfig.type + 'CalcForm');
        var resultDiv = document.getElementById('calcResult');
        var calcBtn = document.getElementById('calcBtn');

        if (!form || !resultDiv || !calcBtn) {
            console.error('Элементы формы не найдены:', calcConfig.type + 'CalcForm');
            return;
        }

        calcBtn.addEventListener('click', function() {
            var data = collectFormData(form);
            data.calcType = calcConfig.type;

            resultDiv.innerHTML = '<div class="loading">' + loadingMessage + '</div>';

            if (useBX) {
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
            } else {
                fetch('/bitrix/services/main/ajax.php?c=' + calcConfig.component + '&action=calc&mode=class', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams(data)
                })
                .then(function(response) { return response.json(); })
                .then(function(response) {
                    handleResponse(response, resultDiv);
                })
                .catch(function(error) {
                    console.error('Ошибка fetch:', error);
                    resultDiv.innerHTML = '<div class="result-error">Ошибка соединения: ' +
                        error.message + '</div>';
                });
            }
        });
    }

    waitForBX(
        function() { setupCalcButton(true); },
        function() { setupCalcButton(false); },
        3000
    );
}

/**
 * Ожидание BX с таймаутом и fallback
 */
function waitForBX(callback, fallbackCallback, timeout) {
    timeout = timeout || 3000;
    var startTime = Date.now();

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

// === СБОР ДАННЫХ ФОРМЫ ===

/**
 * Собирает все поля формы включая чекбоксы, радио, селекты
 * @param {HTMLFormElement} form
 * @returns {Object}
 */
function collectFormData(form) {
    var formData = new FormData(form);
    var data = {};

    // Собираем все поля формы
    var entries = formData.entries();
    var entry = entries.next();
    while (!entry.done) {
        data[entry.value[0]] = entry.value[1];
        entry = entries.next();
    }

    // Добавляем чекбоксы (FormData не включает unchecked)
    var checkboxNames = ['bigovka', 'perforation', 'drill', 'numbering', 'includePodramnik'];
    checkboxNames.forEach(function(name) {
        var checkbox = form.querySelector('input[name="' + name + '"]');
        if (checkbox) {
            data[name] = checkbox.checked;
        }
    });

    // Добавляем данные ламинации
    var laminationType = form.querySelector('input[name="laminationType"]:checked');
    var laminationThickness = form.querySelector('select[name="laminationThickness"]');
    if (laminationType) {
        data.laminationType = laminationType.value;
        if (laminationThickness) {
            data.laminationThickness = laminationThickness.value;
        }
    }

    // Проверяем foldingCount отдельно
    var foldingSelect = form.querySelector('select[name="foldingCount"]');
    if (foldingSelect) {
        data.foldingCount = parseInt(foldingSelect.value) || 0;
    }

    return data;
}

// === ОБРАБОТКА ОТВЕТА ===

/**
 * Обработка ответа сервера, делегирует отображение в window.displayResult
 */
function handleResponse(response, resultDiv) {
    if (response && response.data) {
        if (response.data.error) {
            resultDiv.innerHTML = '<div class="result-error">Ошибка: ' +
                response.data.error + '</div>';
        } else {
            if (typeof window.displayResult === 'function') {
                window.displayResult(response.data, resultDiv);
            } else {
                // Fallback: показываем только цену
                var price = formatPrice(response.data.totalPrice);
                resultDiv.innerHTML = '<div class="result-success">' +
                    '<h3 class="result-title">Результат расчета</h3>' +
                    '<div class="result-price">' + price + ' <small>₽</small></div>' +
                    '</div>';
            }
        }
    } else {
        resultDiv.innerHTML = '<div class="result-error">Некорректный ответ сервера</div>';
        console.error('Неожиданная структура ответа:', response);
    }
}

// === МОДАЛЬНОЕ ОКНО ЗАКАЗА ===

/**
 * Инициализация модального окна: закрытие, submit, очистка ошибок
 */
function initOrderModal() {
    var modal = document.getElementById('orderModal');
    if (!modal) return;

    var closeBtn = modal.querySelector('.order-modal-close');
    var form = document.getElementById('orderForm');
    if (!form) return;

    // Закрытие по клику на X
    if (closeBtn) {
        closeBtn.onclick = closeOrderModal;
    }

    // Закрытие по клику вне модального окна
    window.addEventListener('click', function(event) {
        if (event.target === modal) {
            closeOrderModal();
        }
    });

    // Очистка ошибок при фокусе на поле
    var formFields = form.querySelectorAll('input[type="text"], input[type="tel"], input[type="email"], input[type="date"], input[type="time"]');
    formFields.forEach(function(field) {
        field.addEventListener('focus', function() {
            clearFieldError(this);
        });
    });

    // Обработчик отправки формы
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        if (!validateOrderForm()) {
            return;
        }

        var clientData = {
            name: document.getElementById('clientName').value,
            phone: document.getElementById('clientPhone').value,
            email: document.getElementById('clientEmail').value,
            callDate: document.getElementById('callDate').value,
            callTime: document.getElementById('callTime').value,
            orderData: document.getElementById('orderData').value
        };

        sendOrderEmail(clientData);
    });
}

/**
 * Закрытие модалки и очистка формы
 */
function closeOrderModal() {
    var modal = document.getElementById('orderModal');
    if (modal) modal.style.display = 'none';

    var form = document.getElementById('orderForm');
    if (form) {
        form.reset();
        clearAllFieldErrors();
    }
}

/**
 * Отправка заказа на сервер
 */
function sendOrderEmail(clientData) {
    var submitBtn = document.querySelector('#orderForm button[type="submit"]');
    if (!submitBtn) return;

    var originalText = submitBtn.textContent;
    submitBtn.textContent = 'Отправляем...';
    submitBtn.disabled = true;

    var serverData = {
        name: clientData.name,
        phone: clientData.phone,
        email: clientData.email || '',
        callDate: clientData.callDate || '',
        callTime: clientData.callTime || '',
        orderData: clientData.orderData
    };

    if (typeof BX !== 'undefined' && BX.ajax) {
        BX.ajax.runComponentAction(calcConfig.component, 'sendOrder', {
            mode: 'class',
            data: serverData
        }).then(function(response) {
            handleOrderResponse(response, submitBtn, originalText);
        }).catch(function(error) {
            console.error('Ошибка отправки заказа:', error);
            handleOrderError(submitBtn, originalText);
        });
    } else {
        fetch('/bitrix/services/main/ajax.php?c=' + calcConfig.component + '&action=sendOrder&mode=class', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams(serverData)
        })
        .then(function(response) { return response.json(); })
        .then(function(response) {
            handleOrderResponse(response, submitBtn, originalText);
        })
        .catch(function(error) {
            console.error('Ошибка отправки заказа:', error);
            handleOrderError(submitBtn, originalText);
        });
    }
}

function handleOrderResponse(response, submitBtn, originalText) {
    if (response && response.data && response.data.success) {
        closeOrderModal();
    } else {
        alert('Ошибка при отправке заказа: ' + (response.data ? response.data.error : 'Неизвестная ошибка'));
    }

    submitBtn.textContent = originalText;
    submitBtn.disabled = false;
}

function handleOrderError(submitBtn, originalText) {
    alert('Ошибка при отправке заказа. Попробуйте еще раз.');
    submitBtn.textContent = originalText;
    submitBtn.disabled = false;
}

// === ВАЛИДАЦИЯ ===

/**
 * Инициализация валидации полей даты и времени
 */
function initializeDateTimeValidation() {
    var dateInput = document.getElementById('callDate');
    var timeInput = document.getElementById('callTime');

    if (dateInput) {
        var today = new Date().toISOString().split('T')[0];
        dateInput.setAttribute('min', today);

        var maxDate = new Date();
        maxDate.setFullYear(maxDate.getFullYear() + 1);
        dateInput.setAttribute('max', maxDate.toISOString().split('T')[0]);

        dateInput.addEventListener('change', function() { validateDateField(this); });
        dateInput.addEventListener('input', function() { validateDateField(this); });
        dateInput.addEventListener('blur', function() { validateDateField(this); });
    }

    if (timeInput) {
        timeInput.setAttribute('min', '09:00');
        timeInput.setAttribute('max', '20:00');
        timeInput.setAttribute('step', '300');

        timeInput.addEventListener('change', function() { validateTimeField(this); });
        timeInput.addEventListener('input', function() { validateTimeField(this); });
        timeInput.addEventListener('blur', function() { validateTimeField(this); });
    }
}

/**
 * Валидация поля даты
 */
function validateDateField(dateField) {
    clearFieldError(dateField);

    var dateValue = dateField.value;
    if (!dateValue) return true;

    var selectedDate = new Date(dateValue);
    var now = new Date();
    var today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    var selectedDay = new Date(selectedDate.getFullYear(), selectedDate.getMonth(), selectedDate.getDate());

    if (isNaN(selectedDate.getTime())) {
        showFieldError(dateField, 'Введите корректную дату');
        return false;
    }

    if (selectedDay < today) {
        showFieldError(dateField, 'Нельзя выбрать дату в прошлом');
        return false;
    }

    var oneYearFromNow = new Date();
    oneYearFromNow.setFullYear(oneYearFromNow.getFullYear() + 1);
    if (selectedDate > oneYearFromNow) {
        showFieldError(dateField, 'Нельзя выбрать дату более чем на год вперед');
        return false;
    }

    return true;
}

/**
 * Валидация поля времени
 */
function validateTimeField(timeField) {
    clearFieldError(timeField);

    var timeValue = timeField.value;
    if (!timeValue) return true;

    var timeParts = timeValue.split(':');
    var hours = parseInt(timeParts[0], 10);
    var minutes = parseInt(timeParts[1], 10);

    if (isNaN(hours) || isNaN(minutes)) {
        showFieldError(timeField, 'Введите корректное время');
        return false;
    }

    if (hours < 9 || hours > 20 || (hours === 20 && minutes > 0)) {
        showFieldError(timeField, 'Время должно быть между 9:00 и 20:00');
        return false;
    }

    // Проверяем время для сегодняшнего дня
    var dateField = document.getElementById('callDate');
    if (dateField && dateField.value) {
        var selectedDate = new Date(dateField.value);
        var now = new Date();
        var today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        var selectedDay = new Date(selectedDate.getFullYear(), selectedDate.getMonth(), selectedDate.getDate());

        if (selectedDay.getTime() === today.getTime()) {
            var selectedDateTime = new Date(dateField.value + 'T' + timeValue);
            if (selectedDateTime < now) {
                showFieldError(timeField, 'Нельзя выбрать время в прошлом');
                return false;
            }
        }
    }

    return true;
}

/**
 * Показать ошибку у поля
 */
function showFieldError(field, message) {
    var formGroup = field.closest('.form-group');
    if (!formGroup) return;

    formGroup.classList.add('error');

    var existingError = formGroup.querySelector('.error-message');
    if (existingError) {
        existingError.remove();
    }

    var errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.textContent = message;

    field.parentNode.insertBefore(errorDiv, field.nextSibling);

    setTimeout(function() {
        clearFieldError(field);
    }, 5000);
}

/**
 * Очистить ошибку у поля
 */
function clearFieldError(field) {
    var formGroup = field.closest('.form-group');
    if (!formGroup) return;

    formGroup.classList.remove('error');

    var errorMessage = formGroup.querySelector('.error-message');
    if (errorMessage) {
        errorMessage.style.animation = 'fadeOut 0.3s ease-out';
        setTimeout(function() {
            if (errorMessage.parentNode) {
                errorMessage.remove();
            }
        }, 300);
    }
}

/**
 * Очистить все ошибки в форме заказа
 */
function clearAllFieldErrors() {
    var formGroups = document.querySelectorAll('#orderForm .form-group');
    formGroups.forEach(function(group) {
        group.classList.remove('error');
        var errorMessage = group.querySelector('.error-message');
        if (errorMessage) {
            errorMessage.remove();
        }
    });
}

/**
 * Валидация формы заказа
 */
function validateOrderForm() {
    var nameField = document.getElementById('clientName');
    var phoneField = document.getElementById('clientPhone');
    var emailField = document.getElementById('clientEmail');
    var dateField = document.getElementById('callDate');
    var timeField = document.getElementById('callTime');

    var name = nameField.value.trim();
    var phone = phoneField.value.trim();
    var email = emailField.value.trim();
    var date = dateField.value;
    var time = timeField.value;

    var hasErrors = false;

    clearAllFieldErrors();

    // Валидация имени
    if (!name) {
        showFieldError(nameField, 'Введите ваше имя');
        hasErrors = true;
    } else if (name.length < 2) {
        showFieldError(nameField, 'Имя должно содержать минимум 2 символа');
        hasErrors = true;
    }

    // Валидация телефона
    if (!phone) {
        showFieldError(phoneField, 'Введите ваш телефон');
        hasErrors = true;
    } else {
        var phoneRegex = /^[\+]?[0-9\(\)\-\s]+$/;
        if (!phoneRegex.test(phone) || phone.length < 10) {
            showFieldError(phoneField, 'Введите корректный номер телефона');
            hasErrors = true;
        }
    }

    // Валидация email
    if (!email) {
        showFieldError(emailField, 'Введите ваш email');
        hasErrors = true;
    } else {
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            showFieldError(emailField, 'Введите корректный email адрес');
            hasErrors = true;
        }
    }

    // Валидация даты
    if (!date) {
        showFieldError(dateField, 'Выберите удобную дату для звонка');
        hasErrors = true;
    } else {
        var selectedDate = new Date(date);
        var now = new Date();
        var today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        var selectedDay = new Date(selectedDate.getFullYear(), selectedDate.getMonth(), selectedDate.getDate());

        if (selectedDay < today) {
            showFieldError(dateField, 'Нельзя выбрать дату в прошлом');
            hasErrors = true;
        }

        var oneYearFromNow = new Date();
        oneYearFromNow.setFullYear(oneYearFromNow.getFullYear() + 1);
        if (selectedDate > oneYearFromNow) {
            showFieldError(dateField, 'Нельзя выбрать дату более чем на год вперед');
            hasErrors = true;
        }
    }

    // Валидация времени
    if (!time) {
        showFieldError(timeField, 'Выберите удобное время для звонка');
        hasErrors = true;
    } else {
        var timeParts = time.split(':');
        var hours = parseInt(timeParts[0], 10);
        var minutes = parseInt(timeParts[1], 10);

        if (hours < 9 || hours > 20 || (hours === 20 && minutes > 0)) {
            showFieldError(timeField, 'Время должно быть между 9:00 и 20:00');
            hasErrors = true;
        }

        if (date) {
            var selDate = new Date(date);
            var nowCheck = new Date();
            var todayCheck = new Date(nowCheck.getFullYear(), nowCheck.getMonth(), nowCheck.getDate());
            var selDay = new Date(selDate.getFullYear(), selDate.getMonth(), selDate.getDate());

            if (selDay.getTime() === todayCheck.getTime()) {
                var selectedDateTime = new Date(date + 'T' + time);
                if (selectedDateTime < nowCheck) {
                    showFieldError(timeField, 'Нельзя выбрать время в прошлом');
                    hasErrors = true;
                }
            }
        }
    }

    return !hasErrors;
}

// === УТИЛИТЫ ===

/**
 * Форматирование цены (округление до десятых)
 */
function formatPrice(price) {
    return Math.round((price || 0) * 10) / 10;
}
