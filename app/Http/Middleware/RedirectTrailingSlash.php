<?php

namespace App\Http\Middleware;

use App\Http\Controllers\LegacyRedirectController;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\RedirectController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Jeden adres dla jednej strony: /cennik/ przekierowuje (301) na /cennik. Stara strona WordPress
 * używała ukośnika końcowego, a Laravel obsługuje oba warianty, więc bez tego Google widziałby duplikaty.
 *
 * Stare adresy, które i tak przekierują na nowy adres (np. /zabieg/x/), zostają dla własnego przekierowania,
 * żeby droga była jednym skokiem, a nie dwoma.
 */
class RedirectTrailingSlash
{
    public function handle(Request $request, Closure $next): Response
    {
        $path = $request->getPathInfo();

        if (! $request->isMethodSafe() || $path === '/' || ! str_ends_with($path, '/') || $this->redirectsAnyway($request)) {
            return $next($request);
        }

        $query = $request->getQueryString();

        return redirect()->to($request->getSchemeAndHttpHost().rtrim($path, '/').($query ? '?'.$query : ''), 301);
    }

    private function redirectsAnyway(Request $request): bool
    {
        try {
            // Route::redirect() zapisuje klasę z wiodącym ukośnikiem odwrotnym.
            $action = ltrim(app('router')->getRoutes()->match($request)->getActionName(), '\\');
        } catch (NotFoundHttpException) {
            return false;
        }

        return str_starts_with($action, RedirectController::class) || str_starts_with($action, LegacyRedirectController::class);
    }
}
