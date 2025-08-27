<?php

namespace App\Services\Pay;

use App\Jobs\Pay\PayJob;
use App\Models\Pay;
use App\Services\Service;
use Carbon\Carbon;
use Webklex\IMAP\Facades\Client;

/**
 * Класс для выгрузки данных по чекам в базу на основании писем от ОФД
 */
class ImportCashReceiptService extends Service
{
    /**
     * Данные по чеку
     *
     * @param int $payId
     * @return string
     */
    public function getCashReceipt(int $payId)
    {

        $pay = (new Pay())->getPay(['id' => $payId]);

        if (!empty($pay['fd'])) {
            return 'true';
        }

        if (in_array($pay->type, ['безнал', 'кредит'])) {
            return 'По данной оплате выгрузка данных в уже произведена';
        }

        // Задача в очередь
        dispatch(new PayJob());

        return 'false';

    }

    /**
     * Выгрузка данных по чекам из почты
     *
     * @param int $limit кол-во писем для чтения
     * @param bool $unread открывать только не прочитанные
     */
    public function importCashReceiptFromEmail(int $limit = 50, bool $unread = false): void
    {

        // Работа с почтой
        $client = Client::account('ofd_trumart');
        $client->connect();

        // Получаем папку входящих
        $mailbox = $client->getFolder('INBOX');

        // Получаем письма от отправителя и с нужной темой
        $mails = $mailbox->query()
            ->from('noreply@chek.pofd.ru')
            ->limit($limit)
            ->get();

        foreach ($mails as $mail) {

            // Прочитано ли письмо
            if ($unread === true && $mail->getFlags()->has('\Seen')) {
                continue;
            }

            // Получаем тело письма (обычно HTML или plain text)
            $body = $mail->getHTMLBody();

            if (!$body) {
                continue;
            }

            // Обработка письма
            $cashReceipt = $this->prepareLetter($body);

            if (empty($cashReceipt)) {
                echo "- не найдены данные чека \r\n";
                continue;
            }

            print_r($cashReceipt);

            $pay = $this->searchPay($cashReceipt);

            if (empty($pay)) {
                echo "- не найден платеж \r\n";
                continue;
            }

            $update = (new Pay())->updateCashReceipt($pay->id, [
                'url_check' => $cashReceipt['url_check'],
                'num'       => $cashReceipt['num'],
                'smena'     => $cashReceipt['smena'],
                'fd'        => $cashReceipt['fd'],
                'fp'        => $cashReceipt['fpd'],
                'qr'        => $cashReceipt['qr'],
            ]);

            if (!$update) {
                echo "- не обновлено \r\n";
                continue;
            }

            echo "- обновлено \r\n";

        }

    }

    /**
     * Обработка письма
     *
     * @param string $body содержимое письма в html
     * @return ?array массив с данными по чекам
     */
    private function prepareLetter(string $body): ?array
    {

        $cashReceipt = [];

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true); // Чтобы не было предупреждений из-за невалидного HTML
        $dom->loadHTML(mb_convert_encoding($body, 'HTML-ENTITIES', 'UTF-8'));
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);

        // Номер чека
        $node = $xpath->query("//b/span[contains(text(),'№')]")->item(0);

        if ($node) {
            $cashReceipt['num'] = trim(str_replace('№', '', $node->nodeValue));
        }

        // Дата
        $rawDate = $this->extractValue($xpath, 'Приход');

        if ($rawDate) {
            $cashReceipt['date'] = Carbon::parse($rawDate)->format('Y-m-d H:i:s');
        }

        // QR (берём src у картинки)
        $node = $xpath->query("//img[contains(@src, 'qrcode')]")->item(0);

        if ($node) {
            $cashReceipt['qr'] = str_replace('&amp;', '&', trim($node->getAttribute('src')));
        }

        // URL чека (берём href)
        $node = $xpath->query("//a[contains(@href, '/web/noauth/cheque')]")->item(0);

        if ($node) {
            $cashReceipt['url_check'] = str_replace('&amp;', '&', trim($node->getAttribute('href')));
        }

        $cashReceipt['smena'] = $this->extractValue($xpath, 'Смена');
        $cashReceipt['fd']    = $this->extractValue($xpath, 'ФД');
        $cashReceipt['fpd']   = $this->extractValue($xpath, 'ФПД');
        $cashReceipt['type']  = $this->extractValue($xpath, 'Способ расчета');

        $cashReceipt['sum'] = $this->extractValue($xpath, 'ИТОГ');
        $cashReceipt['sum'] = (float) str_replace(',', '.', preg_replace('/[^\d,\.]/', '', $cashReceipt['sum']));

        return $cashReceipt;

    }

    /**
     * Поиск оплаты по чеку в базе
     *
     * @param array $cashReceipt массив с данными по чеку
     * @return Pay|null оплата из базы или null
     */
    private function searchPay(array $cashReceipt): Pay|null
    {

        if (empty($cashReceipt['sum']) || empty($cashReceipt['date'])) {
            return null;
        }

        $search = [];

        $date = Carbon::parse($cashReceipt['date']);

        // Вид расчета - ПРЕДОПЛАТА ПОЛНЫЙ РАСЧЕТ
        $search['message'] = match ($cashReceipt['type']) {
            'ПРЕДОПЛАТА'    => 'предоплата',
            'ПОЛНЫЙ РАСЧЕТ' => ['полная оплата', 'отгрузка', 'услуга доставки', 'услуга сборки / установки'],
            default         => null,
        };

        $cashReceipt['amount_deposit'] = (int) $cashReceipt['sum'] * 100;

        // Поиск оплаты на данную сумму
        return (new Pay())->getPay([
            'amount_deposit' => $cashReceipt['amount_deposit'],
            'message'        => $search['message'],
            'moder'          => 1,
            'uuid'           => 'NOT NULL',
            'date_start'     => $date->startOfDay()->toDateTimeString(),
            'date_finish'    => $date->endOfDay()->toDateTimeString(),
        ]);

    }

    /**
     * Поиск данных в письме
     *
     * @return ?string найденный контент
     */
    private function extractValue(\DOMXPath $xpath, string $label): ?string
    {
        $node = $xpath->query("//td[contains(., '$label')]/following-sibling::td[1]")->item(0);

        return $node ? trim($node->textContent) : null;
    }
}
