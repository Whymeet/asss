<?php
// config/rizo.php - Конфигурация для калькулятора ризографии
return [
    'sizes' => ['A7', 'A6', 'A5', 'A4', 'A3'],
    'papers' => [80.0, 120.0],
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
        'lamination' => false
    ]
];
