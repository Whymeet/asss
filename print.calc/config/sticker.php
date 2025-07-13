<?php
// print.calc/config/sticker.php - Конфигурация для калькулятора наклеек
return [
    'sizes' => [], // Размеры задаются пользователем
    'papers' => [], // Материал не выбирается отдельно
    'description' => 'Калькулятор печати наклеек',
    'default_quantity' => 100,
    'min_quantity' => 1,
    'max_quantity' => 100000,
    'features' => [
        'bigovka' => false,
        'perforation' => false,
        'drill' => false,
        'numbering' => false,
        'corner_radius' => false,
        'lamination' => false,
        'custom_dimensions' => true, // Особенность наклеек
        'sticker_types' => true // Специальная особенность наклеек
    ],
    'additional' => [
        'sticker_types' => [
            'simple_print' => 'Просто печать СМУК',
            'print_cut' => 'Печать + контурная резка',
            'print_white' => 'Печать смук + белый',
            'print_white_cut' => 'Печать смук + белый + контурная резка',
            'print_white_varnish' => 'Печать смук + белый + лак',
            'print_white_varnish_cut' => 'Печать смук + белый + лак + контурная резка',
            'print_varnish' => 'Печать смук+лак',
            'print_varnish_cut' => 'Печать смук+лак+резка'
        ],
        'price_ranges' => [
            'simple_print' => [
                [0, 5, 700],
                [5, 10, 650],
                [10, PHP_INT_MAX, 600]
            ],
            'print_cut' => [
                [0, 5, 850],
                [5, 10, 800],
                [10, PHP_INT_MAX, 750]
            ],
            'print_white' => [
                [0, 5, 1000],
                [5, 10, 950],
                [10, PHP_INT_MAX, 900]
            ],
            'print_white_cut' => [
                [0, 5, 1150],
                [5, 10, 1100],
                [10, PHP_INT_MAX, 1050]
            ],
            'print_white_varnish' => [
                [0, 5, 1300],
                [5, 10, 1250],
                [10, PHP_INT_MAX, 1200]
            ],
            'print_white_varnish_cut' => [
                [0, 5, 1450],
                [5, 10, 1400],
                [10, PHP_INT_MAX, 1350]
            ],
            'print_varnish' => [
                [0, 5, 1000],
                [5, 10, 950],
                [10, PHP_INT_MAX, 900]
            ],
            'print_varnish_cut' => [
                [0, 5, 1150],
                [5, 10, 1100],
                [10, PHP_INT_MAX, 1050]
            ]
        ],
        'dimension_info' => 'Размеры указываются в метрах (например: 0.1 м = 10 см)',
        'area_info' => 'Цена зависит от общей площади всех наклеек'
    ]
];