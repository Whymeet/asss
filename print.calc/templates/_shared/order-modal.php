<?php
/** Общее модальное окно заказа для всех калькуляторов */
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
?>

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
