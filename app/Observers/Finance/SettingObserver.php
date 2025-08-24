<?php

namespace App\Observers\Finance;

use App\Models\Finance\Setting;
use App\Models\Operation;
use Illuminate\Support\Facades\Cache;

class SettingObserver
{
    /**
     * Handle the Setting "created" event.
     */
    public function created(Setting $financeSetting): void
    {

        $this->clearCache();

        // Добавляем операцию
        (new Operation())->insert([
            'user' => auth()->user()->id,
            'type' => 'finance_setting',
            'post' => $financeSetting->id,
            'text' => 'Создал фин. настройку распределения ' . $financeSetting->id . ' ' . $financeSetting->inn,
        ]);

    }

    /**
     * Handle the Setting "updated" event.
     */
    public function updated(Setting $financeSetting): void
    {

        $this->clearCache();

        if (!auth()->user()) {
            return;
        }

        $changes = $financeSetting->getChanges(); // новые значения изменённых полей

        unset($changes['updated_at']);

        $log = [];

        foreach ($changes as $field => $newValue) {

            if (empty($newValue)) {
                continue;
            }

            $oldValue = $financeSetting->getOriginal($field);

            if ($oldValue == $newValue) {
                continue;
            }

            $log[] = $field . ':' . $oldValue . ' => ' . $newValue;

        }

        if (!empty($log)) {

            // Добавляем операцию
            (new Operation())->insert([
                'user' => auth()->user()->id,
                'type' => 'finance_setting',
                'post' => $financeSetting->id,
                'text' => 'Отредактировал финансовую настройку ' . $financeSetting->id . ' ' . implode("\r\n", $log),
            ]);

        }

    }

    /**
     * Handle the Setting "deleted" event.
     */
    public function deleted(Setting $financeSetting): void
    {

        $this->clearCache();

        // Добавляем операцию
        (new Operation())->insert([
            'user' => auth()->user()->id,
            'type' => 'finance_setting',
            'post' => $financeSetting->id,
            'text' => 'Удалил фин. настройку распределения ' . $financeSetting->id . ' ' . $financeSetting->title,
        ]);

    }

    /**
     * Handle the Setting "restored" event.
     */
    public function restored(Setting $financeSetting): void
    {

        $this->clearCache();

    }

    /**
     * Handle the Setting "force deleted" event.
     */
    public function forceDeleted(Setting $financeSetting): void
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
        Cache::tags(['finance_setting'])->flush();
    }
}
