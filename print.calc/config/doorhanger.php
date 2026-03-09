<?php
// config/doorhanger.php - Конфигурация для калькулятора дорхендеров
return [
    'sizes' => [],
    'papers' => [150.0, 170.0, 200.0, 250.0, 300.0],
    'description' => 'Калькулятор дорхендеров (6 шт/А3)',
    'default_quantity' => 6,
    'min_quantity' => 6,
    'max_quantity' => 50000,
    'features' => [
        'bigovka' => false,
        'perforation' => false,
        'drill' => false,
        'numbering' => false,
        'corner_radius' => false,
        'lamination' => false
    ],
    'additional' => [
        'items_per_sheet' => 6,
        'quantity_step' => 6,
        'pricing_rules' => [
            'digital_fee' => 1500,
            'offset_fee_200_1000' => 3500,
            'offset_fee_per_sheet' => 3.5
        ]
    ]
];
