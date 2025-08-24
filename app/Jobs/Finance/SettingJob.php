<?php

namespace App\Jobs\Finance;

use App\Services\Finance\SettingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SettingJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected ?int $inn;

    protected ?string $dateStart;

    public function __construct(?int $inn = null, ?string $dateStart = null)
    {
        $this->inn       = $inn;
        $this->dateStart = $dateStart;
    }

    public function handle(): void
    {

        (new SettingService())->createDraftDocCloseOrExport1C([
            'inn'        => $this->inn,
            'date_start' => $this->dateStart,
        ]);

    }
}
