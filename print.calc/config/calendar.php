<?php
// ================== ФАЙЛ: print.calc/config/calendar.php ==================
return [
    'sizes' => ['A4', 'A3'],
    'papers' => [300.0], // Только плотная бумага 300г для календарей
    'description' => 'Калькулятор календарей',
    'default_quantity' => 100,
    'min_quantity' => 1,
    'max_quantity' => 5000,
    'features' => [
        'bigovka' => false,
        'perforation' => false,
        'drill' => false,
        'numbering' => false,
        'corner_radius' => false,
        'lamination' => false
    ],
    'additional' => [
        'calendar_types' => [
            'desktop' => 'Настольный календарь',
            'pocket' => 'Карманный календарь',
            'wall' => 'Настенный перекидной календарь'
        ],
        'calendar_info' => [
            'desktop' => 'Формат A4, плотность 300г, с биговкой',
            'pocket' => 'Формат 100x70мм (A6), плотность 300г, скругление углов',
            'wall' => 'Перекидной календарь с пружиной, выбор формата A4 или A3'
        ],
        'wall_sizes' => ['A4', 'A3'],
        'print_types' => [
            '4+0' => 'Полноцветная печать с одной стороны',
            '4+4' => 'Полноцветная печать с двух сторон'
        ],
        'assembly_info' => 'Для настенных календарей включена стоимость сборки с пружиной',
        'desktop_bigovka' => 'Для настольных календарей включено 3 биговки',
        'pocket_corners' => 'Для карманных календарей включено скругление 4 углов'
    ]
];
?>