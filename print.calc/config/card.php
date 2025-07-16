<?php
// print.calc/config/card.php - Конфигурация для калькулятора открыток
return [
    'sizes' => ['A5', 'Евро', 'A6', '200X210'], // Доступные форматы открыток
    'papers' => ['300.0'], // Фиксированная плотность 300 г/м²
    'description' => 'Калькулятор печати открыток',
    'default_quantity' => 100,
    'min_quantity' => 1,
    'max_quantity' => 50000,
    'features' => [
        'bigovka' => true,
        'perforation' => true,
        'drill' => true,
        'numbering' => true,
        'corner_radius' => true,
        'lamination' => false, // Ламинация не применяется к открыткам
        'multi_component' => false, // Однокомпонентный расчет
        'fixed_paper_density' => true // Фиксированная плотность
    ],
    'additional' => [
        'available_sizes' => ['A5', 'Евро', 'A6', '200X210'],
        'fixed_paper_type' => 300.0,
        'print_types' => [
            'single' => 'Односторонняя печать',
            'double' => 'Двусторонняя печать'
        ],
        'paper_info' => 'Плотность бумаги: 300 г/м² (фиксированная)',
        'corner_radius_max' => 4,
        'format_info' => 'Доступные форматы: A5, Евро, A6, 200×210',
        'additional_services' => [
            'bigovka' => 'Биговка',
            'corner_radius' => 'Скругление углов (до 4-х)',
            'perforation' => 'Перфорация',
            'drill' => 'Сверление диаметром 5мм',
            'numbering' => 'Нумерация'
        ]
    ]
];