<?php



namespace App\Providers;

use App\Models\Finance\Cat;
use App\Models\Finance\Finance;
use App\Models\Finance\Setting;
use App\Observers\Finance\CatObserver;
use App\Observers\Finance\FinanceObserver;
use App\Observers\Finance\SettingObserver;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {

        App::setLocale('ru');
        Carbon::setLocale('ru');

        // Финансы
        Cat::observe(CatObserver::class);
        Setting::observe(SettingObserver::class);
        Finance::observe(FinanceObserver::class);

    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        //
    }
}
