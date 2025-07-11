<?php
require $_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php';
$APPLICATION->SetTitle("Калькулятор печати буклетов");
$APPLICATION->IncludeComponent(
    'my:print.calc',        
    'list',                 
    [
        'CALC_TYPE' => 'booklet'   
    ]
);
require $_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php';
