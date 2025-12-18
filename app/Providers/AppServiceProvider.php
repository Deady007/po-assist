<?php

namespace App\Providers;

use App\Services\GeminiClient;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
         $this->app->singleton(GeminiClient::class, fn () => new GeminiClient());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::defaultView('pagination.poa');
        Paginator::defaultSimpleView('pagination.simple-poa');
    }
}
