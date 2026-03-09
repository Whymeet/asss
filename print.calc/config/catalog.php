<?php
// config/catalog.php - Конфигурация для калькулятора каталогов
return [
    'sizes' => ['A4', 'A5', 'A6'],
    'papers' => [],
    'description' => 'Калькулятор каталогов',
    'default_quantity' => 100,
    'min_quantity' => 1,
    'max_quantity' => 10000,
    'features' => [
        'bigovka' => false,
        'perforation' => false,
        'drill' => false,
        'numbering' => false,
        'corner_radius' => false,
        'lamination' => false,
        'multi_component' => true,
        'catalog_binding' => true
    ],
    'additional' => [
        'available_pages' => [8, 12, 16, 20, 24, 28, 32, 36, 40, 44, 48, 52, 56, 60, 64],
        'cover_paper_types' => [
            130 => '130 г/м²',
            170 => '170 г/м²',
            300 => '300 г/м²'
        ],
        'inner_paper_types' => [
            130 => '130 г/м²',
            170 => '170 г/м²'
        ],
        'cover_print_types' => [
            '4+0' => '4+0 (односторонняя)',
            '4+4' => '4+4 (двусторонняя)'
        ],
        'inner_print_types' => [
            '4+4' => '4+4 (двусторонняя)'
        ],
        'binding_types' => [
            'spiral' => 'Пружина',
            'staple' => 'Скоба'
        ]
    ]
];
