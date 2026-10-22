<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Formularz kontaktowy: 5 prób na minutę z jednego adresu. Limiter musi być tutaj,
        // bo po `route:cache` plik tras nie jest wczytywany.
        RateLimiter::for('contact', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
    }
}
