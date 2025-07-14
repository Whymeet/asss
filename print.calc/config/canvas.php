<?php
// print.calc/config/canvas.php - Конфигурация для калькулятора холстов
return [
    'sizes' => [], // Размеры задаются пользователем
    'papers' => [], // Материал не выбирается отдельно
    'description' => 'Калькулятор печати на холсте',
    'default_quantity' => 1,
    'min_quantity' => 1,
    'max_quantity' => 100,
    'features' => [
        'bigovka' => false,
        'perforation' => false,
        'drill' => false,
        'numbering' => false,
        'corner_radius' => false,
        'lamination' => false,
        'custom_dimensions' => true, // Размеры вводятся пользователем
        'podramnik_option' => true // Опция включения подрамника
    ],
    'additional' => [
        'standard_sizes' => [30, 40, 50, 60, 70, 80, 90, 100],
        'max_standard_size' => 100, // см
        'large_size_price_per_m2' => [
            'canvas' => 2700, // руб за м²
            'podramnik' => 1900 // руб за м²
        ],
        'dimension_info' => 'Размеры указываются в сантиметрах',
        'rounding_info' => 'Размеры до 100 см округляются до стандартных значений',
        'large_size_info' => 'Для размеров больше 100 см расчет ведется по площади',
        'podramnik_info' => 'Подрамник можно добавить к любому размеру холста',
        'size_categories' => [
            'standard' => 'До 100×100 см (фиксированные цены)',
            'large' => 'Больше 100 см (цена за м²)'
        ]
    ]
];