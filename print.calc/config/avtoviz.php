<?php
// print.calc/config/avtoviz.php - Конфигурация для калькулятора автовизиток
return [
    'sizes' => ['Евро'], // Фиксированный формат Евро
    'papers' => [150.0, 200.0, 250.0, 300.0, "Самоклейка", "Картон Одн", "Картон Двух"], // Доступные типы бумаги
    'description' => 'Калькулятор печати автовизиток',
    'default_quantity' => 500,
    'min_quantity' => 1,
    'max_quantity' => 50000,
    'features' => [
        'bigovka' => true,
        'perforation' => true,
        'drill' => true,
        'numbering' => true,
        'corner_radius' => true,
        'lamination' => true,
        'fixed_format' => true, // Фиксированный формат
        'extended_papers' => true // Расширенный выбор бумаги
    ],
    'additional' => [
        'available_sizes' => ['Евро'],
        'available_papers' => [
            '150.0' => '150 г/м²',
            '200.0' => '200 г/м²', 
            '250.0' => '250 г/м²',
            '300.0' => '300 г/м²',
            'Самоклейка' => 'Самоклеящаяся бумага',
            'Картон Одн' => 'Картон односторонний',
            'Картон Двух' => 'Картон двусторонний'
        ],
        'print_types' => [
            'single' => 'Односторонняя печать',
            'double' => 'Двусторонняя печать'
        ],
        'format_info' => 'Формат: Евро (99×210 мм)',
        'paper_info' => 'Доступны различные типы бумаги от 150 г/м² до картона',
        'services_info' => 'Все дополнительные услуги доступны',
        'corner_radius_max' => 4,
        'additional_services' => [
            'bigovka' => 'Биговка',
            'corner_radius' => 'Скругление углов (до 4-х)',
            'perforation' => 'Перфорация',
            'drill' => 'Сверление диаметром 5мм',
            'numbering' => 'Нумерация'
        ],
        'paper_recommendations' => [
            'standard' => ['150.0', '200.0', '250.0'],
            'premium' => ['300.0'],
            'special' => ['Самоклейка', 'Картон Одн', 'Картон Двух']
        ]
    ]
];