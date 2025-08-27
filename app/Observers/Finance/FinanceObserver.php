<?php

namespace App\Observers\Finance;

use App\Jobs\Finance\CashBoxJob;
use App\Models\Finance\Finance;
use App\Models\Operation;
use Illuminate\Support\Facades\Cache;

class FinanceObserver
{
    /**
     * Handle the Finance "created" event.
     */
    public function created(Finance $finance): void
    {

        $this->clearCache();

        if (!auth()->user()) {
            return;
        }

        // Добавляем операцию
        (new Operation())->insert([
            'user' => auth()->user()->id,
            'type' => 'finance_cat',
            'post' => $finance->id,
            'text' => "Создал финансовую операцию {$finance->id} {$finance->title}",
        ]);

        dispatch(new CashBoxJob($finance->store_id));

    }

    /**
     * Handle the Finance "updated" event.
     */
    public function updated(Finance $finance): void
    {

        $this->clearCache();

        if (!auth()->user()) {
            return;
        }

        $changes = $finance->getChanges(); // новые значения изменённых полей

        unset($changes['updated_at']);

        $log = [];

        foreach ($changes as $field => $newValue) {

            if (empty($newValue)) {
                continue;
            }

            $oldValue = $finance->getOriginal($field);

            if ($oldValue == $newValue) {
                continue;
            }

            $log[] = $field . ':' . $oldValue . ' => ' . $newValue;

        }

        if (!empty($log)) {

            // Добавляем операцию
            (new Operation())->insert([
                'user' => auth()->user()->id,
                'type' => 'finance_cat',
                'post' => $finance->id,
                'text' => 'Отредактировал финансовую операцию ' . $finance->id . ' ' . $finance->title . ' ' . implode("\r\n", $log),
            ]);

        }

    }

    /**
     * Handle the Finance "deleted" event.
     */
    public function deleted(Finance $finance): void
    {

        $this->clearCache();

        if (!auth()->user()) {
            return;
        }

        // Добавляем операцию
        (new Operation())->insert([
            'user' => auth()->user()->id,
            'type' => 'finance_cat',
            'post' => $finance->id,
            'text' => 'Удалил финансовую операцию ' . $finance->id . ' ' . $finance->title,
        ]);

    }

    /**
     * Handle the Finance "restored" event.
     */
    public function restored(Finance $finance): void
    {

        $this->clearCache();

    }

    /**
     * Handle the Finance "force deleted" event.
     */
    public function forceDeleted(Finance $finance): void
    {

        $this->clearCache();

    }

    /**
     * Сброс кэша
     *
     * @return void
     */
    private function clearCache(): void
    {
        Cache::tags(['finance'])->flush();
    }
}
