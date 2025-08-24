<?php

namespace App\Jobs\Finance;

use App\Models\OrderBidsPay;
use App\Models\Pay;
use App\Models\User;
use App\Services\Finance\BankService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BankJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(): void
    {

        // Запускаем под системой
        $user = (new User())->getUser(['id' => 2]);

        // Обработка банковской выписки
        (new BankService())->processingBankAllStatementSberExcel();

        // Распределение оплат по терминалу
        (new BankService())->distributeSberTerminalPay();

        // Распределение оплат по заказам юр. лиц
        (new BankService())->distributeOrderPay($user);

        // Распределение оплат по кредитным заказам
        (new BankService())->distributeOrderCreditPay($user);

        // Распределение оплат контрагентам
        (new BankService())->distributeContrPay();

        // Распределение оплат по контрагентам
        (new OrderBidsPay())->distributionAuto($user);

        // Выгрузка с Юкассы
        (new Pay())->yooKassa($user);

    }
}
