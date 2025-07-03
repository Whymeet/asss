<?php
require $_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php';

$APPLICATION->IncludeComponent(
    'my:print.calc',        // ваш компонент
    '',                     // используем шаблон по-умолчанию
    ['PAGE_TYPE' => 'list'] // подтянется config/list.php
);

require $_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php';
