<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\MoySkladService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Регистрируем сервис и передаем токен через контейнер
        $this->app->singleton(MoySkladService::class, function () {
            return new MoySkladService(env('MOYSKlad_API_Token')); // Передаем токен из .env
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
