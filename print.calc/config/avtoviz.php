<?php
// config/avtoviz.php - Конфигурация для калькулятора автовизиток
return [
    'sizes' => ['Евро'],
    'papers' => [150.0, 200.0, 250.0, 300.0, "Самоклейка", "Картон Одн", "Картон Двух"],
    'description' => 'Калькулятор печати автовизиток',
    'default_quantity' => 500,
    'min_quantity' => 1,
    'max_quantity' => 50000,
    'features' => [
        'bigovka' => true,
        'perforation' => true,
        'drill' => true,
        'numbering' => true,
        'corner_radius' => true,
        'lamination' => false
    ]
];
