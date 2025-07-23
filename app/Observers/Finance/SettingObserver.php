<?php



namespace App\Observers\Finance;

use App\Models\Finance\Setting;
use App\Models\Operation;

class SettingObserver
{
    /**
     * Handle the Setting "created" event.
     */
    public function created(Setting $financeSetting): void
    {

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

    }

    /**
     * Handle the Setting "deleted" event.
     */
    public function deleted(Setting $financeSetting): void
    {

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
        //
    }

    /**
     * Handle the Setting "force deleted" event.
     */
    public function forceDeleted(Setting $financeSetting): void
    {
        //
    }
}
