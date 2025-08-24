<?php

namespace App\Services\Finance;

use App\Models\ClientCompany;
use App\Models\Finance\Finance;
use Carbon\Carbon;

class ClosingDocService extends BankService
{
    private const IPK_NO_STORE_SENDERS = [118,117,116,115,114,113,112,111,110,109,108,104];

    /**
     * Список не проверенных закрывающих документов
     */
    public function getClosingDocNotVerified(array $inp)
    {

        // Подготовка входящих данных
        $inp = $this->prepareIncomingData($inp);

        $finances = (new Finance())->getFinancesClosingDocNotVerified([
            'setting_id'  => $inp['setting'],
            'text'        => $inp['score'] ?? $inp['title'],
            'paycash'     => $inp['pay_cash'],
            'cashbox'     => 1,
            'inn'         => 'not null',
            'upd'         => 0,
            'doc_num'     => 'null',
            'date_start'  => $inp['date_start'],
            'date_finish' => $inp['date_finish'],
            'date_type'   => 'date',
        ]);

        $this->prepareOutgoingData($finances);

        return $finances;

    }

    /**
     * Подготовка исходящих данных
     */
    private function prepareOutgoingData($items): void
    {

        $companyModel = new ClientCompany();

        foreach ($items as $item) {

            $item->type = 'finance';

            // Уникальный код для подсчета по галочкам
            $item->post = uniqid();

            // Склад отправитель
            $item->company = $companyModel->getCompany(['inn' => $item->inn]);

            // Сумма
            if (!empty($item->amount)) {

                $amount       = (float) $item->amount;
                $item->amount = fmod($amount, 1) === 0.0
                    ? number_format($amount, 0, '.', '')   // целое
                    : number_format($amount, 2, '.', ''); // с копейками

            }

            $item->date_display = Carbon::parse($item->date)->translatedFormat('j F Y');
            $item->date_insert  = Carbon::parse($item->created_at)->translatedFormat('j F Y H:i');
            $item->date_update  = Carbon::parse($item->updated_at)->translatedFormat('j F Y H:i');

        }

    }

    /**
     * Подготовка входящих данных
     */
    private function prepareIncomingData(array $inp): array
    {

        // Если ИПК
        if (isset($inp['setting']) && $inp['setting'] === 2) {
            $inp['no_store_sender'] = self::IPK_NO_STORE_SENDERS;
        }

        // Обработка Даты
        $inp['date_start']  = Carbon::parse($inp['date_start'])->format('Y-m-d');
        $inp['date_finish'] = Carbon::parse($inp['date_finish'])->format('Y-m-d');

        // Вид оплаты
        $inp['pay_cash'] = isset($inp['payment_type'])
            ? ($inp['payment_type'] === 'наличные' ? 1 : 0)
            : null;

        if (!empty($inp['scores'])) {
            $inp['score'] = array_values(array_filter(array_column($inp['scores'], 'score')));
        }

        return $inp;

    }
}
