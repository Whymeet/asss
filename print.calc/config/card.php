<?php
// config/card.php - Конфигурация для калькулятора открыток
return [
    'sizes' => ['A5', 'Евро', 'A6', '200X210'],
    'papers' => ['300.0'], // Фиксированная плотность 300 г/м²
    'description' => 'Калькулятор печати открыток',
    'default_quantity' => 100,
    'min_quantity' => 1,
    'max_quantity' => 50000,
    'features' => [
        'bigovka' => true,
        'perforation' => true,
        'drill' => true,
        'numbering' => true,
        'corner_radius' => true,
        'lamination' => false,
        'multi_component' => false,
        'fixed_paper_density' => true
    ],
    'additional' => [
        'paper_info' => 'Плотность бумаги: 300 г/м² (фиксированная)'
    ]
];
