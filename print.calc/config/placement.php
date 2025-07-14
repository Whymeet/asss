<?php
// print.calc/config/placement.php - Конфигурация для калькулятора размещения
return [
    'sizes' => ['A3'], // Только формат А3 доступен
    'papers' => [80.0, 105.0, 115.0, 130.0, 'Крафтовая'], // Ограниченный список бумаги
    'description' => 'Калькулятор печати размещения',
    'default_quantity' => 100,
    'min_quantity' => 1,
    'max_quantity' => 50000,
    'features' => [
        'bigovka' => true,
        'perforation' => true,
        'drill' => true,
        'numbering' => true,
        'corner_radius' => true,
        'lamination' => true // Ламинация доступна
    ],
    'additional' => [
        'format_info' => 'Доступен только формат А3',
        'paper_info' => 'Ограниченный выбор типов бумаги для размещения',
        'lamination_info' => 'Ламинация доступна после основного расчета',
        'corner_radius_max' => 4, // Максимум 4 угла
        'recommended_papers' => [
            'light' => [80.0, 105.0], // Легкая бумага
            'medium' => [115.0, 130.0], // Средняя плотность
            'special' => ['Крафтовая'] // Специальная бумага
        ]
    ]
];