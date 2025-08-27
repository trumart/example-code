<?php

namespace App\Providers;

use App\Models\Contr;
use App\Models\Finance\Cat;
use App\Models\Finance\Finance;
use App\Models\Finance\Setting;
use App\Models\Notice;
use App\Models\RouteParam;
use App\Models\StoreLease;
use App\Models\Upd;
use App\Observers\Contr\ContrObserver;
use App\Observers\Finance\CatObserver;
use App\Observers\Finance\FinanceObserver;
use App\Observers\Finance\SettingObserver;
use App\Observers\Notice\NoticeObserver;
use App\Observers\Route\ParamObserver;
use App\Observers\Store\LeaseObserver;
use App\Observers\Upd\UpdObserver;
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

        Contr::observe(ContrObserver::class);

        Notice::observe(NoticeObserver::class);

        RouteParam::observe(ParamObserver::class);

        StoreLease::observe(LeaseObserver::class);

        Upd::observe(UpdObserver::class);

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
