<?php
// config/booklet.php - Конфигурация для калькулятора буклетов
return [
    'sizes' => ['A4', 'A3'],
    'papers' => [80.0, 120.0, 90.0, 105.0, 115.0, 130.0, 150.0, 170.0, 200.0, 250.0, 270.0, 300.0, "Самоклейка", "Картон Одн", "Картон Двух"],
    'description' => 'Калькулятор печати буклетов',
    'default_quantity' => 500,
    'min_quantity' => 1,
    'max_quantity' => 50000,
    'max_folding' => 2,
    'features' => [
        'bigovka' => true,
        'perforation' => true,
        'drill' => true,
        'numbering' => true,
        'corner_radius' => true,
        'folding' => true,
        'lamination' => true
    ],
    'additional' => [
        'folding_info' => [
            0 => 'Без сложений - простая листовка',
            1 => '1 сложение - буклет в 2 разворота', 
            2 => '2 сложения - буклет в 3 разворота'
        ],
        'recommended_papers' => [
            'light' => [80, 90, 105], // Легкая бумага
            'medium' => [120, 130, 150], // Средняя плотность
            'heavy' => [170, 200, 250, 300] // Плотная бумага
        ]
    ]
];