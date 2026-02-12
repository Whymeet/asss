<?php
// print.calc/config/stend.php - Конфигурация для калькулятора ПВХ стендов
return [
    'sizes' => [], // Размеры задаются пользователем
    'papers' => [], // Материал не выбирается отдельно
    'description' => 'Калькулятор ПВХ конструкций',
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
        'pvc_types' => true, // Выбор толщины ПВХ
        'pockets' => true // Карманы для документов
    ],
    'additional' => [
        'pvc_types' => [
            '3mm' => '3 мм',
            '5mm' => '5 мм'
        ],
        'pocket_types' => [
            'flat_a4' => 'Плоский карман А4',
            'flat_a5' => 'Плоский карман А5',
            'volume_a4' => 'Объемный карман А4',
            'volume_a5' => 'Объемный карман А5'
        ],
        'pocket_limits' => [
            'a4_points' => 2,   // А4 карман = 2 балла
            'a5_points' => 1,   // А5 карман = 1 балл
            'max_points_per_m2' => 20 // максимум 20 баллов на м²
        ],
        'pvc_prices' => [
            '3mm' => 3000, // цена за м² для 3 мм
            '5mm' => 4000  // цена за м² для 5 мм
        ],
        'pocket_prices' => [
            'flat_a4' => 50,   // цена плоского кармана А4
            'flat_a5' => 30,   // цена плоского кармана А5
            'volume_a4' => 80, // цена объемного кармана А4
            'volume_a5' => 50  // цена объемного кармана А5
        ],
        'dimension_info' => 'Размеры указываются в сантиметрах',
        'pocket_info' => 'Количество карманов ограничено площадью стенда'
    ]
];