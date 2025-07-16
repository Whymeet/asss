<?php
// print.calc/config/banner.php - Конфигурация для калькулятора баннеров
return [
    'sizes' => [], // Размеры задаются произвольно в метрах
    'papers' => [], // Не используется, вместо этого banner_types
    'description' => 'Калькулятор стоимости баннеров',
    'default_quantity' => 1, // Для баннеров всегда 1 шт
    'min_quantity' => 1,
    'max_quantity' => 1, // Только 1 баннер за расчет
    'features' => [
        'bigovka' => false,
        'perforation' => false,
        'drill' => false,
        'numbering' => false,
        'corner_radius' => false,
        'lamination' => false,
        'custom_dimensions' => true, // Произвольные размеры
        'area_calculation' => true, // Расчет по площади
        'hemming' => true, // Проклейка
        'grommets' => true, // Люверсы
        'perimeter_services' => true // Услуги по периметру
    ],
    'additional' => [
        'banner_types' => [
            'Баннер 330 г' => 450,
            'Баннер 440 г' => 560,
            'Баннер 510 г' => 600,
            'Перфорированный баннер' => 650
        ],
        'additional_services' => [
            'hemming' => 90,   // руб/метр периметра
            'grommets' => 30   // руб/штука
        ],
        'dimension_info' => 'Размеры указываются в метрах',
        'area_info' => 'Стоимость рассчитывается по площади баннера',
        'hemming_info' => 'Проклейка: 90 руб/м периметра',
        'grommets_info' => 'Люверсы: 30 руб/шт (требует проклейку)',
        'step_info' => 'Шаг люверсов влияет на их количество',
        'validation_rules' => [
            'min_length' => 0.1,
            'max_length' => 50,
            'min_width' => 0.1,
            'max_width' => 50,
            'min_grommet_step' => 0.1,
            'max_grommet_step' => 10
        ]
    ]
];