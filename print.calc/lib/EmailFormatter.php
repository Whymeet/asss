<?php
/**
 * Класс для форматирования email-писем заказов из калькулятора печати.
 * Общий HTML-шаблон + уникальный контент для каждого типа продукта.
 */
class EmailFormatter
{
    /**
     * Конфигурация цветов и названий для каждого типа калькулятора
     */
    private static $typeConfig = [
        'list' => [
            'title' => 'Новый заказ листовок',
            'product' => 'Листовки',
            'gradient' => ['#28a745', '#20c997'],
            'color' => '#28a745',
            'bgLight' => '#e8f5e8',
        ],
        'booklet' => [
            'title' => 'Новый заказ буклетов',
            'product' => 'Буклеты',
            'gradient' => ['#17a2b8', '#20c997'],
            'color' => '#17a2b8',
            'bgLight' => '#e8f4f8',
        ],
        'vizit' => [
            'title' => 'Новый заказ визиток',
            'product' => 'Визитки',
            'gradient' => ['#007bff', '#0056b3'],
            'color' => '#007bff',
            'bgLight' => '#e3f2fd',
        ],
        'note' => [
            'title' => 'Новый заказ блокнотов',
            'product' => 'Блокноты',
            'gradient' => ['#6f42c1', '#e83e8c'],
            'color' => '#6f42c1',
            'bgLight' => '#f3e8ff',
        ],
        'kubaric' => [
            'title' => 'Новый заказ кубариков',
            'product' => 'Кубарики',
            'gradient' => ['#ff9800', '#f57c00'],
            'color' => '#ff9800',
            'bgLight' => '#fff3e0',
        ],
        'sticker' => [
            'title' => 'Новый заказ наклеек',
            'product' => 'Наклейки',
            'gradient' => ['#007bff', '#0056b3'],
            'color' => '#007bff',
            'bgLight' => '#e3f2fd',
        ],
        'canvas' => [
            'title' => 'Новый заказ печати на холсте',
            'product' => 'Печать на холсте',
            'gradient' => ['#6f42c1', '#e83e8c'],
            'color' => '#6f42c1',
            'bgLight' => '#f3e5f5',
        ],
        'calendar' => [
            'title' => 'Новый заказ календарей',
            'product' => 'Календари',
            'gradient' => ['#2c5aa0', '#6a4c93'],
            'color' => '#2c5aa0',
            'bgLight' => '#e8f0ff',
        ],
        'banner' => [
            'title' => 'Новый заказ баннеров',
            'product' => 'Баннер',
            'gradient' => ['#dc3545', '#fd7e14'],
            'color' => '#dc3545',
            'bgLight' => '#ffeaea',
        ],
    ];

    /**
     * Главный метод форматирования — заменяет formatOrderMessage из class.php
     */
    public static function formatOrderMessage($orderInfo, $name, $phone, $email, $callTime, $clientComment = '')
    {
        $calcType = $orderInfo['calcType'] ?? '';

        // Стенд — текстовый формат
        if ($calcType === 'stend') {
            return self::formatStendText($orderInfo, $name, $phone, $email, $callTime);
        }

        // Визитки — отдельный layout (grid вместо table)
        if ($calcType === 'vizit') {
            return self::formatVizitHTML($orderInfo, $name, $phone, $email, $callTime, $clientComment);
        }

        // HTML-форматы через общий шаблон
        if (isset(self::$typeConfig[$calcType])) {
            $rows = self::getOrderRows($calcType, $orderInfo);
            $extra = self::getExtraSection($calcType, $orderInfo);
            return self::buildHTML($calcType, $orderInfo, $name, $phone, $email, $callTime, $clientComment, $rows, $extra);
        }

        // Фоллбэк — старый текстовый формат
        return self::formatFallbackText($orderInfo, $name, $phone, $email, $callTime);
    }

    /**
     * Собирает HTML-письмо из общего шаблона
     */
    private static function buildHTML($calcType, $orderInfo, $name, $phone, $email, $callTime, $clientComment, $tableRows, $extraSection = '')
    {
        $cfg = self::$typeConfig[$calcType];
        $grad1 = $cfg['gradient'][0];
        $grad2 = $cfg['gradient'][1];
        $color = $cfg['color'];
        $bgLight = $cfg['bgLight'];
        $title = $cfg['title'];
        $totalPrice = $orderInfo['totalPrice'] ?? 0;

        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>' . htmlspecialchars($title) . '</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, ' . $grad1 . ', ' . $grad2 . '); color: white; padding: 20px; border-radius: 8px 8px 0 0; text-align: center; }
        .content { background: #f8f9fa; padding: 20px; border: 1px solid #dee2e6; }
        .footer { background: #6c757d; color: white; padding: 15px; border-radius: 0 0 8px 8px; text-align: center; font-size: 14px; }
        .section { margin-bottom: 20px; }
        .section h3 { color: ' . $color . '; margin-bottom: 10px; border-bottom: 2px solid ' . $color . '; padding-bottom: 5px; }
        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { padding: 8px 12px; border-bottom: 1px solid #dee2e6; }
        .info-table td:first-child { font-weight: bold; background: ' . $bgLight . '; width: 40%; }
        .price { font-size: 24px; font-weight: bold; color: ' . $color . '; text-align: center; margin: 20px 0; }
        .client-info { background: white; padding: 15px; border-radius: 6px; border-left: 4px solid ' . $color . '; }
        .highlight { background: ' . $bgLight . '; padding: 10px; border-radius: 4px; border-left: 4px solid ' . $color . '; margin: 10px 0; }
        .badge { background: ' . $color . '; color: white; padding: 3px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .services-list { background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 6px; padding: 10px; margin: 10px 0; }
        .services-list ul { margin: 0; padding-left: 20px; }
        .services-list li { margin: 5px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>' . htmlspecialchars($title) . '</h1>
            <p>Заказ с калькулятора печати</p>
        </div>

        <div class="content">
            <div class="section">
                <h3>Информация о заказе</h3>
                <table class="info-table">
                    ' . $tableRows . '
                </table>
                <div class="price">Итого: ' . number_format($totalPrice, 0, ',', ' ') . ' руб.</div>
            </div>';

        // Дополнительная секция (для календарей и т.д.)
        if (!empty($extraSection)) {
            $html .= $extraSection;
        }

        // Секция клиента
        $html .= '
            <div class="section">
                <h3>Информация о клиенте</h3>
                <div class="client-info">
                    <p><strong>Имя:</strong> ' . htmlspecialchars($name) . '</p>
                    <p><strong>Телефон:</strong> <a href="tel:' . htmlspecialchars($phone) . '">' . htmlspecialchars($phone) . '</a></p>';

        if (!empty($email)) {
            $html .= '<p><strong>E-mail:</strong> <a href="mailto:' . htmlspecialchars($email) . '">' . htmlspecialchars($email) . '</a></p>';
        }

        if (!empty($callTime)) {
            $callTimeFormatted = $callTime;
            if (strpos($callTime, '.') === false && strtotime($callTime)) {
                $callTimeFormatted = date('d.m.Y H:i', strtotime($callTime));
            }
            $html .= '<p><strong>Удобное время для звонка:</strong> ' . htmlspecialchars($callTimeFormatted) . '</p>';
        }

        if (!empty($clientComment)) {
            $html .= '<p><strong>Комментарий к заказу:</strong> ' . nl2br(htmlspecialchars($clientComment)) . '</p>';
        }

        $html .= '<p><strong>Дата заказа:</strong> ' . date('d.m.Y H:i:s') . '</p>
                </div>
            </div>
        </div>

        <div class="footer">
            <p>Заказ получен через калькулятор печати на сайте</p>
            <p>Время получения: ' . date('d.m.Y H:i:s') . '</p>
        </div>
    </div>
</body>
</html>';

        return $html;
    }

    /**
     * Возвращает HTML-строки таблицы заказа для конкретного типа
     */
    private static function getOrderRows($calcType, $orderInfo)
    {
        $rows = '';

        switch ($calcType) {
            case 'list':
                $rows .= self::row('Продукт', $orderInfo['product'] ?? 'Листовки');
                $rows .= self::row('Формат', $orderInfo['size'] ?? 'Не указан');
                $rows .= self::row('Тип бумаги', $orderInfo['paperType'] ?? 'Не указан');
                $rows .= self::row('Тип печати', $orderInfo['printType'] ?? 'Не указан');
                $rows .= self::row('Тираж', number_format($orderInfo['quantity'] ?? 0, 0, ',', ' ') . ' шт.');
                $rows .= self::laminationRows($orderInfo);
                $rows .= self::additionalServicesRow($orderInfo);
                break;

            case 'booklet':
                $rows .= self::row('Продукт', $orderInfo['product'] ?? 'Буклеты');
                $rows .= self::row('Формат', $orderInfo['size'] ?? 'Не указан');
                $rows .= self::row('Тип бумаги', $orderInfo['paperType'] ?? 'Не указан');
                $rows .= self::row('Тип печати', $orderInfo['printType'] ?? 'Не указан');
                $rows .= self::row('Тираж', number_format($orderInfo['quantity'] ?? 0, 0, ',', ' ') . ' шт.');
                // Сложения
                if (isset($orderInfo['foldingDescription'])) {
                    $rows .= self::row('Данные сложений', $orderInfo['foldingDescription']);
                } elseif (isset($orderInfo['foldingCount'])) {
                    $foldingText = $orderInfo['foldingCount'] > 0 ? $orderInfo['foldingCount'] . ' сложение' . ($orderInfo['foldingCount'] > 1 ? 'я' : '') : 'Нет сложений';
                    $rows .= self::row('Данные сложений', $foldingText);
                } else {
                    $rows .= self::row('Данные сложений', 'Нет сложений');
                }
                $rows .= self::laminationRows($orderInfo);
                $rows .= self::additionalServicesRow($orderInfo);
                break;

            case 'note':
                $rows .= self::row('Продукт', $orderInfo['product'] ?? 'Блокноты');
                $rows .= self::row('Формат', $orderInfo['size'] ?? 'Не указан');
                $rows .= self::row('Тираж', number_format($orderInfo['quantity'] ?? 0, 0, ',', ' ') . ' шт.');
                $rows .= self::row('Листов в блоке', $orderInfo['inner_pages'] ?? 'Не указано');
                $rows .= self::row('Печать обложки', $orderInfo['cover_print'] ?? 'Не указано');
                $rows .= self::row('Печать задника', $orderInfo['back_print'] ?? 'Не указано');
                $rows .= self::row('Печать внутреннего блока', $orderInfo['inner_print'] ?? 'Не указано');
                // Ламинация обложки
                if (!empty($orderInfo['laminationType'])) {
                    $lamText = self::getLaminationText($orderInfo);
                    $rows .= self::row('Ламинация обложки', $lamText);
                }
                $rows .= self::additionalServicesRow($orderInfo);
                break;

            case 'kubaric':
                $rows .= '<tr><td>Формат</td><td>' . ($orderInfo['format'] ?? '9×9 см') . '</td></tr>';
                $rows .= '<tr><td>Листов в пачке</td><td>' . ($orderInfo['sheetsPerPack'] ?? 'Не указано') . ' листов</td></tr>';
                $rows .= '<tr><td>Количество пачек</td><td>' . ($orderInfo['packsCount'] ?? 'Не указано') . ' шт</td></tr>';
                $rows .= '<tr><td>Общее количество листов</td><td>' . (isset($orderInfo['totalSheets']) ? number_format($orderInfo['totalSheets'], 0, ',', ' ') : 'Не указано') . ' листов</td></tr>';
                $rows .= '<tr><td>Тип печати</td><td>' . ($orderInfo['printType'] ?? 'Не указан') . '</td></tr>';
                break;

            case 'sticker':
                $stickerTypeNames = [
                    'simple_print' => 'Просто печать СМУК',
                    'print_cut' => 'Печать + контурная резка',
                    'print_white' => 'Печать смук + белый',
                    'print_white_cut' => 'Печать смук + белый + контурная резка',
                    'print_white_varnish' => 'Печать смук + белый + лак',
                    'print_white_varnish_cut' => 'Печать смук + белый + лак + контурная резка',
                    'print_varnish' => 'Печать смук+лак',
                    'print_varnish_cut' => 'Печать смук+лак+резка'
                ];
                $stickerType = $orderInfo['stickerType'] ?? 'simple_print';
                $stickerTypeName = $stickerTypeNames[$stickerType] ?? $stickerType;

                $rows .= '<tr><td>Длина одной наклейки</td><td>' . ($orderInfo['length'] ?? 'Не указана') . ' м</td></tr>';
                $rows .= '<tr><td>Ширина одной наклейки</td><td>' . ($orderInfo['width'] ?? 'Не указана') . ' м</td></tr>';
                $rows .= '<tr><td>Количество</td><td>' . (isset($orderInfo['quantity']) ? number_format($orderInfo['quantity'], 0, ',', ' ') : 'Не указано') . ' шт</td></tr>';
                $rows .= '<tr><td>Тип наклейки</td><td>' . htmlspecialchars($stickerTypeName) . '</td></tr>';
                break;

            case 'canvas':
                $rows .= self::row('Продукт', 'Печать на холсте');
                $rows .= '<tr><td>Ширина</td><td>' . htmlspecialchars($orderInfo['width'] ?? 'Не указана') . ' см</td></tr>';
                $rows .= '<tr><td>Высота</td><td>' . htmlspecialchars($orderInfo['height'] ?? 'Не указана') . ' см</td></tr>';
                $includePodramnik = isset($orderInfo['includePodramnik']) && $orderInfo['includePodramnik'] ? 'Да' : 'Нет';
                $rows .= '<tr><td>Подрамник</td><td>' . htmlspecialchars($includePodramnik) . '</td></tr>';
                break;

            case 'calendar':
                $calendarType = $orderInfo['calendarType'] ?? 'Не указан';
                $calendarTypes = [
                    'wall' => 'Настенный',
                    'desktop' => 'Настольный',
                    'pocket' => 'Карманный'
                ];
                if (isset($calendarTypes[$calendarType])) {
                    $calendarType = $calendarTypes[$calendarType];
                }
                $rows .= self::row('Продукт', 'Календари');
                $rows .= '<tr><td>Тип календаря</td><td>' . htmlspecialchars($calendarType) . '</td></tr>';
                if (!empty($orderInfo['size'])) {
                    $rows .= '<tr><td>Размер</td><td>' . htmlspecialchars($orderInfo['size']) . '</td></tr>';
                }
                $rows .= '<tr><td>Тип печати</td><td>' . htmlspecialchars($orderInfo['printType'] ?? 'Не указан') . '</td></tr>';
                $rows .= '<tr><td>Тираж</td><td>' . number_format($orderInfo['quantity'] ?? 0, 0, ',', ' ') . ' шт.</td></tr>';
                break;

            case 'banner':
                $rows .= self::row('Продукт', $orderInfo['product'] ?? 'Баннер');
                if (!empty($orderInfo['width']) && !empty($orderInfo['length'])) {
                    $width = $orderInfo['width'];
                    $length = $orderInfo['length'];
                    $area = round($width * $length, 2);
                    $rows .= '<tr><td>Размеры</td><td>' . $width . ' × ' . $length . ' м</td></tr>';
                    $rows .= '<tr><td>Площадь</td><td>' . $area . ' м²</td></tr>';
                }
                if (!empty($orderInfo['bannerType'])) {
                    $rows .= '<tr><td>Тип материала</td><td>' . htmlspecialchars($orderInfo['bannerType']) . '</td></tr>';
                }
                // Дополнительные услуги баннера
                $additionalServices = [];
                if (!empty($orderInfo['hemming']) && $orderInfo['hemming'] === true) {
                    $additionalServices[] = 'Проклейка краев';
                }
                if (!empty($orderInfo['grommets']) && $orderInfo['grommets'] === true) {
                    $grommetStep = !empty($orderInfo['grommetStep']) ? $orderInfo['grommetStep'] : '50';
                    $additionalServices[] = 'Люверсы (шаг ' . htmlspecialchars($grommetStep) . ' см)';
                }
                if (!empty($additionalServices)) {
                    $rows .= '<tr><td>Дополнительные услуги</td><td>';
                    $rows .= '<div class="services-list"><ul>';
                    foreach ($additionalServices as $service) {
                        $rows .= '<li>' . htmlspecialchars($service) . '</li>';
                    }
                    $rows .= '</ul></div></td></tr>';
                }
                break;
        }

        return $rows;
    }

    /**
     * Дополнительная секция после таблицы (для календарей и т.д.)
     */
    private static function getExtraSection($calcType, $orderInfo)
    {
        if ($calcType !== 'calendar') {
            return '';
        }

        $calendarType = $orderInfo['calendarType'] ?? '';
        $calendarTypes = ['wall' => 'Настенный', 'desktop' => 'Настольный', 'pocket' => 'Карманный'];
        $typeName = $calendarTypes[$calendarType] ?? $calendarType;

        $html = '
            <div class="section">
                <h3>Дополнительная информация</h3>
                <p><span class="badge">КАЛЕНДАРИ</span> Сборка включена в стоимость</p>';

        if ($typeName === 'Настольный') {
            $html .= '<p><span class="badge">БИГОВКА</span> Для настольных календарей включена биговка</p>';
        }

        if ($typeName === 'Карманный') {
            $html .= '<p><span class="badge">УГЛЫ</span> Возможно скругление углов</p>';
        }

        $html .= '</div>';

        return $html;
    }

    // ==================== Визитки (отдельный grid-layout) ====================

    private static function formatVizitHTML($orderInfo, $name, $phone, $email, $callTime, $clientComment)
    {
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Новый заказ визиток</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .section {
            margin-bottom: 25px;
            padding: 20px;
            background: #f8f9ff;
            border-radius: 8px;
            border-left: 4px solid #007bff;
        }
        .section h2 {
            color: #007bff;
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 18px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }
        .info-item {
            background: white;
            padding: 15px;
            border-radius: 6px;
            border: 1px solid #e3f2fd;
        }
        .info-item strong {
            color: #0056b3;
            display: block;
            margin-bottom: 5px;
        }
        .price-highlight {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            margin: 20px 0;
        }
        .client-info {
            background: #fff3cd;
            border-left-color: #ffc107;
        }
        .client-info h2 {
            color: #856404;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            color: #6c757d;
            font-size: 14px;
        }
        @media (max-width: 600px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Новый заказ визиток</h1>
        </div>

        <div class="section">
            <h2>Параметры заказа</h2>
            <div class="info-grid">';

        // Тип печати
        if (!empty($orderInfo['printType'])) {
            $printTypeDisplay = '';
            if ($orderInfo['printType'] === 'digital') {
                $printTypeDisplay = 'Цифровая печать';
            } elseif ($orderInfo['printType'] === 'offset') {
                $printTypeDisplay = 'Офсетная печать';
            } else {
                $printTypeDisplay = htmlspecialchars($orderInfo['printType']);
            }

            $html .= '<div class="info-item">
                        <strong>Тип печати:</strong>
                        ' . $printTypeDisplay . '
                      </div>';
        }

        // Количество
        if (!empty($orderInfo['quantity'])) {
            $html .= '<div class="info-item">
                        <strong>Тираж:</strong>
                        ' . number_format($orderInfo['quantity'], 0, '', ' ') . ' шт
                      </div>';
        }

        // Тип печати (односторонняя/двусторонняя)
        if (!empty($orderInfo['sideType'])) {
            $sideTypeDisplay = '';
            if ($orderInfo['sideType'] === 'single') {
                $sideTypeDisplay = 'Односторонняя (4+0)';
            } elseif ($orderInfo['sideType'] === 'double') {
                $sideTypeDisplay = 'Двусторонняя (4+4)';
            } else {
                $sideTypeDisplay = htmlspecialchars($orderInfo['sideType']);
            }

            $html .= '<div class="info-item">
                        <strong>Печать:</strong>
                        ' . $sideTypeDisplay . '
                      </div>';
        }

        // Размер
        $html .= '<div class="info-item">
                    <strong>Размер:</strong>
                    90x50 мм (стандартный)
                  </div>';

        $html .= '</div>';

        // Стоимость
        if (!empty($orderInfo['totalPrice'])) {
            $html .= '<div class="price-highlight">
                        Стоимость: ' . number_format($orderInfo['totalPrice'], 2, ',', ' ') . ' ₽
                      </div>';
        }

        $html .= '</div>';

        // Информация о клиенте
        $html .= '<div class="section client-info">
                    <h2>Информация о клиенте</h2>
                    <div class="info-grid">
                        <div class="info-item">
                            <strong>Имя:</strong>
                            ' . htmlspecialchars($name) . '
                        </div>
                        <div class="info-item">
                            <strong>Телефон:</strong>
                            <a href="tel:' . htmlspecialchars($phone) . '">' . htmlspecialchars($phone) . '</a>
                        </div>';

        if (!empty($email)) {
            $html .= '<div class="info-item">
                        <strong>Email:</strong>
                        <a href="mailto:' . htmlspecialchars($email) . '">' . htmlspecialchars($email) . '</a>
                      </div>';
        }

        $html .= '</div>';

        // Предпочтительное время звонка
        if (!empty($callTime)) {
            $callTimeFormatted = $callTime;
            try {
                $dateTime = new DateTime($callTime);
                $callTimeFormatted = $dateTime->format('d.m.Y в H:i');
            } catch (Exception $e) {
                // Если не удалось распарсить дату, оставляем как есть
            }
            $html .= '<p><strong>Удобное время для звонка:</strong> ' . htmlspecialchars($callTimeFormatted) . '</p>';
        }

        $html .= '<p><strong>Дата заказа:</strong> ' . date('d.m.Y H:i:s') . '</p>
                </div>
            </div>
        </div>

        <div class="footer">
            <p>Заказ получен через калькулятор визиток на сайте</p>
            <p>Время получения: ' . date('d.m.Y H:i:s') . '</p>
        </div>
    </div>
</body>
</html>';

        return $html;
    }

    // ==================== Стенд (текстовый формат) ====================

    private static function formatStendText($orderInfo, $name, $phone, $email, $callTime)
    {
        $text = "=== НОВЫЙ ЗАКАЗ ПВХ СТЕНДА ===\n\n";

        $text .= "Информация о заказе:\n";
        $text .= "Продукт: " . ($orderInfo['product'] ?? 'ПВХ стенд') . "\n";
        $text .= "Ширина стенда: " . ($orderInfo['width'] ?? 'Не указана') . " см\n";
        $text .= "Высота стенда: " . ($orderInfo['height'] ?? 'Не указана') . " см\n";

        // Рассчитываем площадь если есть размеры
        if (!empty($orderInfo['width']) && !empty($orderInfo['height'])) {
            $area = ((float)$orderInfo['width'] * (float)$orderInfo['height']) / 10000;
            $text .= "Площадь стенда: " . number_format($area, 2, ',', ' ') . " м²\n";
        }

        // Тип ПВХ
        $pvcTypeText = $orderInfo['pvcType'] ?? 'Не указан';
        if ($pvcTypeText === '3mm') $pvcTypeText = '3 мм';
        if ($pvcTypeText === '5mm') $pvcTypeText = '5 мм';
        $text .= "Толщина ПВХ: " . $pvcTypeText . "\n";

        // Карманы
        $text .= "\nКарманы для документов:\n";
        $hasAnyPockets = false;

        if (!empty($orderInfo['flatA4']) && (int)$orderInfo['flatA4'] > 0) {
            $text .= "- Плоских карманов А4: " . (int)$orderInfo['flatA4'] . "\n";
            $hasAnyPockets = true;
        }
        if (!empty($orderInfo['flatA5']) && (int)$orderInfo['flatA5'] > 0) {
            $text .= "- Плоских карманов А5: " . (int)$orderInfo['flatA5'] . "\n";
            $hasAnyPockets = true;
        }
        if (!empty($orderInfo['volumeA4']) && (int)$orderInfo['volumeA4'] > 0) {
            $text .= "- Объемных карманов А4: " . (int)$orderInfo['volumeA4'] . "\n";
            $hasAnyPockets = true;
        }
        if (!empty($orderInfo['volumeA5']) && (int)$orderInfo['volumeA5'] > 0) {
            $text .= "- Объемных карманов А5: " . (int)$orderInfo['volumeA5'] . "\n";
            $hasAnyPockets = true;
        }

        if (!$hasAnyPockets) {
            $text .= "- Без карманов\n";
        }

        // Ламинация если есть
        if (!empty($orderInfo['laminationType'])) {
            $laminationText = $orderInfo['laminationType'];

            $laminationTypes = [
                '1+0' => 'Односторонняя',
                '1+1' => 'Двусторонняя'
            ];

            if (isset($laminationTypes[$laminationText])) {
                $laminationText = $laminationTypes[$laminationText];
            }

            if (!empty($orderInfo['laminationThickness'])) {
                $thicknessNames = [
                    '80' => '80 мкм',
                    '125' => '125 мкм',
                    '175' => '175 мкм'
                ];
                $thicknessText = $thicknessNames[$orderInfo['laminationThickness']] ?? $orderInfo['laminationThickness'];
                $laminationText .= ' (' . $thicknessText . ')';
            }

            $text .= "\nЛаминация: " . $laminationText . "\n";
        }

        // Цена
        $text .= "\nИТОГО: " . ($orderInfo['totalPrice'] ?? '0') . " руб.\n";

        // Информация о клиенте
        $text .= "\n=== КОНТАКТНАЯ ИНФОРМАЦИЯ ===\n";
        $text .= "Имя: " . $name . "\n";
        $text .= "Телефон: " . $phone . "\n";

        if (!empty($email)) {
            $text .= "E-mail: " . $email . "\n";
        }

        if (!empty($callTime)) {
            $text .= "Удобное время для звонка: " . $callTime . "\n";
        }

        $text .= "\nДата заказа: " . date('d.m.Y H:i:s') . "\n";
        $text .= "Автоматическое уведомление от калькулятора печати\n";

        return $text;
    }

    // ==================== Фоллбэк (старый текстовый формат) ====================

    private static function formatFallbackText($orderInfo, $name, $phone, $email, $callTime)
    {
        $message = "=== НОВЫЙ ЗАКАЗ ИЗ КАЛЬКУЛЯТОРА ===\n\n";

        $message .= "Информация о заказе:\n";
        $message .= "Продукт: " . ($orderInfo['product'] ?? 'Не указан') . "\n";

        if (($orderInfo['calcType'] ?? '') === 'list') {
            $message .= "Формат бланка: " . ($orderInfo['size'] ?? 'Не указан') . "\n";
            $message .= "Печать: " . ($orderInfo['printType'] ?? 'Не указан') . "\n";
            $message .= "Тираж: " . ($orderInfo['quantity'] ?? 'Не указан') . "\n";
            $message .= "Количество слоёв: " . ($orderInfo['layers'] ?? 'Не указан') . "\n";
            $message .= "Нумерация: " . ($orderInfo['numbering'] ?? 'Не указана') . "\n";
            $message .= "Одинаковые слои или разные?: " . ($orderInfo['layersSame'] ?? 'Не указано') . "\n";

            if (!empty($orderInfo['additionalServices'])) {
                $message .= "Дополнительные услуги: " . $orderInfo['additionalServices'] . "\n";
            }
        }

        $message .= "Итого: " . ($orderInfo['totalPrice'] ?? '0') . " руб.\n\n";

        $message .= "Клиент:\n";
        $message .= "Имя: " . $name . "\n";
        $message .= "Телефон: " . $phone . "\n";

        if (!empty($email)) {
            $message .= "E-mail: " . $email . "\n";
        }

        if (!empty($callTime)) {
            if (strpos($callTime, '.') !== false) {
                $message .= "Удобное время для звонка: " . $callTime . "\n";
            } else {
                $callTimeFormatted = date('d.m.Y H:i', strtotime($callTime));
                $message .= "Удобное время для звонка: " . $callTimeFormatted . "\n";
            }
        }

        $message .= "\nДата заказа: " . date('d.m.Y H:i:s') . "\n";

        return $message;
    }

    // ==================== Хелперы ====================

    private static function row($label, $value)
    {
        return '<tr><td>' . htmlspecialchars($label) . '</td><td>' . htmlspecialchars($value) . '</td></tr>';
    }

    private static function getLaminationText($orderInfo)
    {
        $laminationText = $orderInfo['laminationType'];

        $laminationTypes = [
            '1+0' => 'Односторонняя',
            '1+1' => 'Двусторонняя'
        ];

        if (isset($laminationTypes[$laminationText])) {
            $laminationText = $laminationTypes[$laminationText];
        }

        if (!empty($orderInfo['laminationThickness'])) {
            $laminationText .= ' (' . $orderInfo['laminationThickness'] . ' мкм)';
        }

        return $laminationText;
    }

    private static function laminationRows($orderInfo)
    {
        if (empty($orderInfo['laminationType'])) {
            return '';
        }
        return '<tr><td>Ламинация</td><td>' . htmlspecialchars(self::getLaminationText($orderInfo)) . '</td></tr>';
    }

    private static function additionalServicesRow($orderInfo)
    {
        if (empty($orderInfo['additionalServices'])) {
            return '';
        }
        return '<tr><td>Дополнительные услуги</td><td>' . htmlspecialchars($orderInfo['additionalServices']) . '</td></tr>';
    }
}
