<?php
/**
 * Класс для отправки email-уведомлений о заказах из калькулятора печати.
 * Поддерживает HTML и текстовые письма, отправку через Bitrix и fallback на mail().
 */
class EmailSender
{
    private static $recipientEmail = 'info@mir-pechati.su';

    /**
     * Маппинг calcType → название продукта для темы письма
     */
    private static $productTypes = [
        'list' => 'листовки',
        'stend' => 'ПВХ стенд',
        'vizit' => 'визитки',
        'booklet' => 'буклеты',
        'note' => 'блокноты',
        'kubaric' => 'кубарики',
        'sticker' => 'наклейки',
        'canvas' => 'печать на холсте',
        'calendar' => 'календари',
        'banner' => 'баннеры',
    ];

    /**
     * Типы калькуляторов, которые отправляются напрямую (не через событие Bitrix)
     */
    private static $directSendTypes = ['list', 'booklet', 'vizit', 'stend', 'note', 'kubaric', 'sticker', 'canvas', 'calendar', 'banner'];

    /**
     * Главный метод отправки — заменяет sendEmailNotification из class.php
     *
     * @param string $message   Отформатированное сообщение (HTML или текст)
     * @param array  $orderInfo Информация о заказе
     * @param string $name      Имя клиента
     * @param string $phone     Телефон клиента
     * @param string $email     Email клиента
     * @param callable|null $debugFn Функция для отладки (опционально)
     * @return bool
     */
    public static function send($message, $orderInfo, $name, $phone, $email, $debugFn = null)
    {
        $debug = $debugFn ?: function () {};

        $debug("EmailSender::send вызван", [
            'calcType' => $orderInfo['calcType'] ?? 'unknown',
            'messageLength' => strlen($message),
            'name' => $name,
            'phone' => $phone,
            'email' => $email
        ]);

        if (!CModule::IncludeModule("main")) {
            $debug("Модуль main не подключен");
            return false;
        }

        $calcType = $orderInfo['calcType'] ?? '';

        // Для поддерживаемых типов отправляем напрямую
        if (in_array($calcType, self::$directSendTypes)) {
            $debug("Отправляем письмо для типа: " . $calcType);

            // Стенды — текстовый формат
            if ($calcType === 'stend') {
                return self::sendTextEmail($message, $orderInfo, $email, $debug);
            }

            // Остальные — HTML
            return self::sendHtmlEmail($message, $orderInfo, $email, $debug);
        }

        // Для остальных — через событие Bitrix
        return self::sendViaBitrixEvent($message, $orderInfo, $name, $phone, $email, $debug);
    }

    /**
     * Отправляет HTML-письмо (multipart: text + html)
     */
    private static function sendHtmlEmail($htmlMessage, $orderInfo, $clientEmail, $debug)
    {
        try {
            $productType = self::getProductType($orderInfo);
            $subject = "Новый заказ: {$productType} - " . number_format($orderInfo['totalPrice'] ?? 0, 0, ',', ' ') . ' руб.';

            $debug("Отправка HTML-письма", [
                'to' => self::$recipientEmail,
                'subject' => $subject,
                'calcType' => $orderInfo['calcType'] ?? '',
                'messageLength' => strlen($htmlMessage),
            ]);

            $textMessage = self::htmlToText($htmlMessage);
            $boundary = "boundary_" . md5(uniqid(time()));

            $headers = [
                "MIME-Version: 1.0",
                "Content-Type: multipart/alternative; boundary=\"{$boundary}\"",
                "From: " . self::$recipientEmail,
                "Reply-To: " . ($clientEmail ?: self::$recipientEmail),
                "X-Mailer: PHP/" . phpversion()
            ];

            $multipartMessage = "--{$boundary}\r\n";
            $multipartMessage .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $multipartMessage .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
            $multipartMessage .= $textMessage . "\r\n\r\n";

            $multipartMessage .= "--{$boundary}\r\n";
            $multipartMessage .= "Content-Type: text/html; charset=UTF-8\r\n";
            $multipartMessage .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
            $multipartMessage .= $htmlMessage . "\r\n\r\n";

            $multipartMessage .= "--{$boundary}--";

            return self::doSend(self::$recipientEmail, $subject, $multipartMessage, $headers, $debug);

        } catch (Exception $e) {
            $debug("Исключение при отправке HTML-письма: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Отправляет текстовое письмо
     */
    private static function sendTextEmail($textMessage, $orderInfo, $clientEmail, $debug)
    {
        try {
            $productType = self::getProductType($orderInfo);
            $subject = "Новый заказ: {$productType} - " . number_format($orderInfo['totalPrice'] ?? 0, 0, ',', ' ') . ' руб.';

            $debug("Отправка текстового письма", [
                'to' => self::$recipientEmail,
                'subject' => $subject,
                'calcType' => $orderInfo['calcType'] ?? ''
            ]);

            $message = strip_tags($textMessage);
            $message = html_entity_decode($message, ENT_QUOTES, 'UTF-8');

            $headers = [
                "Content-Type: text/plain; charset=UTF-8",
                "From: " . self::$recipientEmail,
                "Reply-To: " . ($clientEmail ?: self::$recipientEmail)
            ];

            return self::doSend(self::$recipientEmail, $subject, $message, $headers, $debug);

        } catch (Exception $e) {
            $debug("Исключение при отправке текстового письма: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Отправляет через событие Bitrix (для неподдерживаемых типов)
     */
    private static function sendViaBitrixEvent($message, $orderInfo, $name, $phone, $email, $debug)
    {
        $arEventFields = [
            "CALC_TYPE" => $orderInfo['calcType'] ?? 'list',
            "ORDER_INFO" => $message,
            "CLIENT_NAME" => $name,
            "CLIENT_PHONE" => $phone,
            "CLIENT_EMAIL" => $email,
            "DATE_CREATE" => date('d.m.Y H:i:s'),
            "ORDER_TEXT" => $message,
            "PRODUCT_TYPE" => $orderInfo['product'] ?? '',
            "TOTAL_PRICE" => $orderInfo['totalPrice'] ?? '0',
            "ORDER_DATE" => date('d.m.Y H:i:s')
        ];

        $debug("Отправка события CALC_ORDER_REQUEST с полями:", $arEventFields);

        $result = CEvent::Send("CALC_ORDER_REQUEST", SITE_ID, $arEventFields);

        $debug("Результат отправки события CALC_ORDER_REQUEST", [
            'result' => $result,
            'SITE_ID' => SITE_ID,
        ]);

        if ($result) {
            $debug("Событие отправлено успешно");
        } else {
            $debug("ОШИБКА: Событие не отправлено!");
        }

        return $result;
    }

    /**
     * Непосредственная отправка: bxmail → mail() fallback
     */
    private static function doSend($to, $subject, $body, $headers, $debug)
    {
        $headersStr = implode("\r\n", $headers);

        $result = bxmail($to, $subject, $body, $headersStr);

        if ($result) {
            $debug("Письмо успешно отправлено через bxmail");
            return true;
        }

        $debug("Ошибка отправки через bxmail, пробуем mail()");

        $result = mail($to, $subject, $body, $headersStr);

        if ($result) {
            $debug("Письмо успешно отправлено через mail()");
            return true;
        }

        $debug("Ошибка отправки через mail()");
        return false;
    }

    /**
     * Конвертирует HTML в простой текст
     */
    private static function htmlToText($html)
    {
        $html = preg_replace('/<style\b[^<]*(?:(?!<\/style>)<[^<]*)*<\/style>/mi', '', $html);
        $html = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $html);

        $html = str_replace(['<br>', '<br/>', '<br />'], "\n", $html);
        $html = str_replace(['</p>', '</div>', '</h1>', '</h2>', '</h3>', '</h4>', '</h5>', '</h6>'], "\n\n", $html);
        $html = str_replace(['</tr>'], "\n", $html);
        $html = str_replace(['</td>'], " | ", $html);
        $html = str_replace(['</li>'], "\n- ", $html);

        $html = strip_tags($html);
        $html = preg_replace('/\n\s*\n/', "\n\n", $html);
        $html = trim($html);

        return $html;
    }

    /**
     * Определяет название продукта для темы письма
     */
    private static function getProductType($orderInfo)
    {
        $calcType = $orderInfo['calcType'] ?? '';
        return self::$productTypes[$calcType] ?? ($orderInfo['product'] ?? 'заказ');
    }
}
