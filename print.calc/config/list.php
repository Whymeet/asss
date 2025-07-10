<?php
// configs/list.php - Конфигурация для листовок
return [
    'sizes' => ['A7', 'A6', 'Евро', 'A5', 'A4', 'A3'],
    'papers' => [80, 90, 120, 130, 150, 170, 200, 250, 300, 'Крафтовая', 'Самоклейка', 'Картон Одн', 'Картон Двух'],
    'description' => 'Калькулятор печати листовок',
    'features' => [
        'bigovka' => true,
        'perforation' => true,
        'drill' => true,
        'numbering' => true,
        'corner_radius' => true,
        'lamination' => true
    ]
];