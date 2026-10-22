<?php

namespace App\Providers;

use App\Support\Auth\AcceptedLoginCodes;
use App\Support\Auth\StrictMultiFactorChallenge;
use Filament\Auth\MultiFactor\MultiFactorChallenge;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Ekran drugiego składnika w panelu z trybem „wymagaj obu metod naraz” (MultiFactorChallenge::make() bierze klasę z kontenera).
        $this->app->bind(MultiFactorChallenge::class, StrictMultiFactorChallenge::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Formularz kontaktowy: 5 prób na minutę z jednego adresu. Limiter musi być tutaj,
        // bo po `route:cache` plik tras nie jest wczytywany.
        RateLimiter::for('contact', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));

        // Po zalogowaniu pamięć kodów przyjętych w trakcie logowania do panelu przestaje być potrzebna.
        Event::listen(Login::class, fn () => AcceptedLoginCodes::clear());
    }
}
