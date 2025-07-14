<?php
// print.calc/config/kubaric.php - Конфигурация для калькулятора кубариков
return [
    'sizes' => ['9X9'], // Фиксированный формат
    'papers' => [80.0], // Фиксированная плотность
    'description' => 'Калькулятор кубариков',
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
        'pack_calculation' => true, // Особенность кубариков - расчет по пачкам
        'fixed_format' => true, // Фиксированный формат 9X9
        'price_multiplier' => true // Применение коэффициента 1.3
    ],
    'additional' => [
        'sheets_per_pack_options' => [100, 300, 500, 900],
        'print_types' => [
            '1+0' => '1+0 (Односторонняя ч/б)',
            '1+1' => '1+1 (Двусторонняя ч/б)',
            '4+0' => '4+0 (Цветная с одной стороны)',
            '4+4' => '4+4 (Цветная с двух сторон)'
        ],
        'price_multiplier' => 1.3, // Коэффициент наценки
        'format_info' => 'Фиксированный формат 9X9',
        'paper_info' => 'Плотность бумаги: 80 г/м²',
        'pack_info' => 'Кубарики упаковываются в пачки',
        'multiplier_info' => 'К базовой стоимости применяется коэффициент 1.3'
    ]
];