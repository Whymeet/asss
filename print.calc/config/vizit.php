<?php
// print.calc/config/vizit.php - Конфигурация для калькулятора визиток
return [
    'sizes' => [], // Размер фиксированный для визиток
    'papers' => [], // Бумага не выбирается для визиток
    'description' => 'Калькулятор печати визиток',
    'default_quantity' => 500,
    'min_quantity' => 100,
    'max_quantity' => 12000,
    'features' => [
        'bigovka' => false,
        'perforation' => false,
        'drill' => false,
        'numbering' => false,
        'corner_radius' => false,
        'lamination' => false,
        'print_type_selection' => true // Специальная особенность визиток
    ],
    'additional' => [
        'print_types' => [
            'digital' => 'Цифровая печать',
            'offset' => 'Офсетная печать'
        ],
        'digital_range' => [
            'min' => 100,
            'max' => 999,
            'step' => 1
        ],
        'offset_range' => [
            'min' => 1000,
            'max' => 12000,
            'step' => 1000,
            'available' => [1000, 2000, 3000, 4000, 5000, 6000, 7000, 8000, 9000, 10000, 11000, 12000]
        ],
        'side_types' => [
            'single' => 'Односторонняя',
            'double' => 'Двусторонняя'
        ],
        'info_text' => 'Для цифровой печати: тираж 100-999 шт. Для офсетной печати: тираж от 1000 шт (кратно 1000).'
    ]
];