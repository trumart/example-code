<?php



namespace App\Observers\Finance;

use App\Jobs\Finance\CashBoxJob;
use App\Models\Finance\Finance;
use App\Models\Operation;

class FinanceObserver
{
    /**
     * Handle the Finance "created" event.
     */
    public function created(Finance $finance): void
    {

        if (!auth()->user()) {
            return;
        }

        // Добавляем операцию
        (new Operation())->insert([
            'user' => auth()->user()->id,
            'type' => 'finance_cat',
            'post' => $finance->id,
            'text' => "Создал финансовую категорию {$finance->id} {$finance->title}",
        ]);

        dispatch(new CashBoxJob($finance->store_id));

    }

    /**
     * Handle the Finance "updated" event.
     */
    public function updated(Finance $finance): void
    {

        if (!auth()->user()) {
            return;
        }

        $changes = $finance->getChanges(); // новые значения изменённых полей

        unset($changes['updated_at']);

        $log = [];

        foreach ($changes as $field => $newValue) {

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
                'text' => 'Отредактировал финансовую операцию ' . $finance->id . ' ' . $finance->title . implode("\r\n", $log),
            ]);

        }

    }

    /**
     * Handle the Finance "deleted" event.
     */
    public function deleted(Finance $finance): void
    {

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
        //
    }

    /**
     * Handle the Finance "force deleted" event.
     */
    public function forceDeleted(Finance $finance): void
    {
        //
    }
}
