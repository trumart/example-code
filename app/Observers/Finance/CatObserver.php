<?php

namespace App\Observers\Finance;

use App\Models\Finance\Cat;
use App\Models\Operation;
use Illuminate\Support\Facades\Cache;

class CatObserver
{
    /**
     * Handle the Cat "created" event.
     */
    public function created(Cat $financeCat): void
    {

        $this->clearCache();

        // Добавляем операцию
        (new Operation())->insert([
            'user' => auth()->user()->id,
            'type' => 'finance_cat',
            'post' => $financeCat->id,
            'text' => 'Создал финансовую категорию ' . $financeCat->id . ' ' . $financeCat->title,
        ]);

    }

    /**
     * Handle the Cat "updated" event.
     */
    public function updated(Cat $financeCat): void
    {

        $this->clearCache();

        $changes = $financeCat->getChanges(); // новые значения изменённых полей

        unset($changes['updated_at']);

        $log = [];

        foreach ($changes as $field => $newValue) {

            $oldValue = $financeCat->getOriginal($field);

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
                'post' => $financeCat->id,
                'text' => 'Отредактировал финансовую категорию ' . $financeCat->id . ' ' . $financeCat->title . ' ' . implode("\r\n", $log),
            ]);

        }
    }

    /**
     * Handle the Cat "deleted" event.
     */
    public function deleted(Cat $financeCat): void
    {

        $this->clearCache();

        // Добавляем операцию
        (new Operation())->insert([
            'user' => auth()->user()->id,
            'type' => 'finance_cat',
            'post' => $financeCat->id,
            'text' => 'Удалил финансовую категорию ' . $financeCat->id . ' ' . $financeCat->title,
        ]);

    }

    /**
     * Handle the Cat "restored" event.
     */
    public function restored(Cat $financeCat): void
    {
        $this->clearCache();
    }

    /**
     * Handle the Cat "force deleted" event.
     */
    public function forceDeleted(Cat $financeCat): void
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
        Cache::tags(['finance_cat'])->flush();
    }
}
