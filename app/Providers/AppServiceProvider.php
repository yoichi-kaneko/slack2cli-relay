<?php

namespace App\Providers;

use App\Contracts\Jobs\RelayDispatcher;
use App\Infrastructure\Jobs\LaravelRelayDispatcher;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(RelayDispatcher::class, LaravelRelayDispatcher::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
