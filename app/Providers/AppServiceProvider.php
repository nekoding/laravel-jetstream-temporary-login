<?php

namespace App\Providers;

use App\Http\Controllers\Auth\FortifyCustomAuth;
use App\Services\TempLogin\BasicTempLogin;
use App\Services\TempLogin\TempLoginContract;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->bind(AuthenticatedSessionController::class, FortifyCustomAuth::class);
        $this->app->singleton(TempLoginContract::class, BasicTempLogin::class);
    }
}
