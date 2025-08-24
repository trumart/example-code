<?php

namespace App\Jobs\Finance;

use App\Enums\PaymentType;
use App\Services\Finance\FinanceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CashBoxJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected $store_id;

    public function __construct($store_id)
    {
        $this->store_id = $store_id;
    }

    public function handle(): void
    {

        (new FinanceService())->calcSumInCashBoxStore([
            'store_id'    => $this->store_id,
            'date_start'  => date('Y-m-d'),
            'date_finish' => date('Y-m-d'),
        ], PaymentType::CASH);

    }
}
