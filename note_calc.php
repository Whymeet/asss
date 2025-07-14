<?php
require $_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php';

$APPLICATION->SetTitle("Калькулятор блокнотов");

$APPLICATION->IncludeComponent(
    'my:print.calc',
    'note',
    [
        'CALC_TYPE' => 'note'
    ]
);

require $_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php';