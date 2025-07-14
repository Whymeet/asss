<?php
require $_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php';

$APPLICATION->SetTitle("Калькулятор печати размещения");

$APPLICATION->IncludeComponent(
    'my:print.calc',
    'placement',
    [
        'CALC_TYPE' => 'placement'
    ]
);

require $_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php';