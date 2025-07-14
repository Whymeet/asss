<?php
// print.calc/config/catalog.php - Конфигурация для калькулятора каталогов
return [
    'sizes' => ['A4', 'A5', 'A6'], // Доступные форматы каталогов
    'papers' => [], // Бумага задается отдельно для обложки и внутренних листов
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
        'lamination' => false, // Ламинация недоступна для каталогов
        'multi_component' => true, // Многокомпонентный расчет
        'catalog_binding' => true // Специальная сборка каталогов
    ],
    'additional' => [
        'available_sizes' => ['A4', 'A5', 'A6'],
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
            '4+0' => '4+0 (полноцвет с одной стороны)',
            '4+4' => '4+4 (полноцвет с двух сторон)'
        ],
        'inner_print_types' => [
            '4+4' => '4+4 (полноцвет с двух сторон)'
        ],
        'binding_types' => [
            'spiral' => 'Пружина',
            'staple' => 'Скоба'
        ],
        'collation_info' => 'Листоподборка включена в стоимость',
        'binding_info' => 'Доступны два типа сборки: пружина или скоба',
        'paper_info' => 'Обложка и внутренние листы могут быть на разной бумаге',
        'structure_info' => [
            'cover' => 'Обложка (2-4 страницы)',
            'inner' => 'Внутренние листы',
            'collation' => 'Листоподборка',
            'binding' => 'Сборка (пружина/скоба)'
        ]
    ]
];