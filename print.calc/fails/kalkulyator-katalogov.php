<?php
// /kalkulyator-katalogov.php

$isEmbed = (isset($_GET['embed']) && $_GET['embed'] === 'Y');

if ($isEmbed) {
    define("NO_KEEP_STATISTIC", true);
    define("NOT_CHECK_PERMISSIONS", true);

    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

    $APPLICATION->SetTitle("Калькулятор печати каталогов");
    $APPLICATION->SetPageProperty(
        "description",
        "Узнать цену и рассчитать стоимость печати каталогов с помощью калькулятора в WF_CITY_ROD."
    );
    ?>
    <!doctype html>
    <html lang="ru">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <?php $APPLICATION->ShowHead(); ?>
        <style>
            html, body { margin: 0; padding: 0; }
        </style>
    </head>
    <body>
        <?php
        $APPLICATION->IncludeComponent(
            'my:print.calc',
            'catalog',
            [
                'CALC_TYPE' => 'catalog'
            ]
        );
        ?>

        <script>
        (function () {
            function sendHeight() {
                var h = Math.max(
                    document.body.scrollHeight,
                    document.documentElement.scrollHeight
                ) + 10;

                // Отправляем в родителя. Фильтрация по origin будет на странице услуги.
                parent.postMessage(
                    { type: 'calcHeight', height: h, frameId: 'calc-catalog' },
                    '*'
                );
            }

            if (document.readyState === 'complete') {
                sendHeight();
            } else {
                window.addEventListener('load', sendHeight);
            }

            if ('ResizeObserver' in window) {
                var ro = new ResizeObserver(function () { sendHeight(); });
                ro.observe(document.body);
            } else {
                setInterval(sendHeight, 800);
            }
        })();
        </script>

    </body>
    </html>
    <?php

    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php';
    exit;
}

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';

$APPLICATION->SetTitle("Калькулятор печати каталогов");
$APPLICATION->SetPageProperty(
    "description",
    "Узнать цену и рассчитать стоимость печати каталогов с помощью калькулятора в WF_CITY_ROD."
);

$APPLICATION->IncludeComponent(
    'my:print.calc',
    'catalog',
    [
        'CALC_TYPE' => 'catalog'
    ]
);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
