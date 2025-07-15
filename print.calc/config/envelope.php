<?php
// print.calc/config/envelope.php - Конфигурация для калькулятора конвертов
return [
    'sizes' => [], // Размеры задаются как форматы конвертов
    'papers' => [], // Материал не выбирается отдельно
    'description' => 'Калькулятор конвертов',
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
        'envelope_formats' => true, // Специальные форматы конвертов
        'tiered_pricing' => true // Ступенчатое ценообразование
    ],
    'additional' => [
        'available_formats' => [
            'Евро' => 'Евро',
            'A5' => 'A5',
            'A4' => 'A4'
        ],
        'envelope_prices' => [
            'Евро' => [
                ['min' => 1,    'max' => 100,  'price' => 13],
                ['min' => 101,  'max' => 300,  'price' => 12],
                ['min' => 301,  'max' => 500,  'price' => 11],
                ['min' => 501,  'max' => 1000, 'price' => 10],
                ['min' => 1001, 'max' => PHP_INT_MAX, 'price' => 9]
            ],
            'A5' => [
                ['min' => 1,    'max' => 100,  'price' => 17],
                ['min' => 101,  'max' => 300,  'price' => 16],
                ['min' => 301,  'max' => 500,  'price' => 15],
                ['min' => 501,  'max' => 1000, 'price' => 13],
                ['min' => 1001, 'max' => PHP_INT_MAX, 'price' => 12]
            ],
            'A4' => [
                ['min' => 1,    'max' => 100,  'price' => 20],
                ['min' => 101,  'max' => 300,  'price' => 19],
                ['min' => 301,  'max' => 500,  'price' => 18],
                ['min' => 501,  'max' => 1000, 'price' => 16],
                ['min' => 1001, 'max' => PHP_INT_MAX, 'price' => 15]
            ]
        ],
        'pricing_info' => 'Цена зависит от формата и тиража',
        'format_info' => 'Доступны стандартные форматы конвертов',
        'quantity_ranges' => [
            '1-100 шт' => 'Максимальная цена за штуку',
            '101-300 шт' => 'Скидка при среднем тираже',
            '301-500 шт' => 'Дополнительная скидка',
            '501-1000 шт' => 'Скидка при большом тираже',
            '1001+ шт' => 'Минимальная цена за штуку'
        ]
    ]
];