<?php

namespace App\Providers;

use App\Auth\CookieGuard;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->registerPolicies();

        Auth::extend('cookie', fn ($app, $name, array $config) => new CookieGuard(
            Auth::createUserProvider($config['provider']),
            $app['request']
        ));
        //
    }
}
