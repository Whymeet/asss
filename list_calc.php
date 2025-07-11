
<?php
require $_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php';
$APPLICATION->SetTitle("Калькулятор печати листовок");
$APPLICATION->IncludeComponent(
    'my:print.calc',        
    'list',                 
    [
        'CALC_TYPE' => 'list'   
    ]
);
require $_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php';