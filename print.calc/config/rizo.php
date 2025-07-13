<?php
// print.calc/config/rizo.php - Конфигурация для калькулятора ризографии
return [
    'sizes' => ['A7', 'A6', 'A5', 'A4', 'A3'],
    'papers' => [80.0, 120.0], // Только эти плотности для ризографии
    'description' => 'Калькулятор ризографической печати',
    'default_quantity' => 500,
    'min_quantity' => 1,
    'max_quantity' => 10000,
    'features' => [
        'bigovka' => true,
        'perforation' => true,
        'drill' => true,
        'numbering' => true,
        'corner_radius' => true,
        'lamination' => false // Ламинация недоступна для ризографии
    ],
    'additional' => [
        'threshold_info' => 'При тираже более 499 листов A3 используется офсетная печать',
        'print_info' => [
            'single' => '1+0 (ч/б с 1 стороны)',
            'double' => '1+1 (ч/б с 2-х сторон)'
        ]
    ]
];