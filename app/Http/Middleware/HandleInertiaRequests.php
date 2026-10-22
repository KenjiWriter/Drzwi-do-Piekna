<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /** Pola z config/clinic.php, które wolno przekazać do przeglądarki. */
    public const PUBLIC_CLINIC_KEYS = [
        'name', 'tagline', 'doctor', 'doctor_role', 'phone', 'phone_e164', 'email',
        'address', 'booking_url', 'maps_url', 'social', 'legal',
    ];

    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Props wspólne dla wszystkich stron: dane gabinetu i nawigacja.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            // Biała lista: te dane trafiają do HTML każdej strony publicznej. Reszta config/clinic.php
            // (konto deweloperskie panelu, mapy przekierowań) nie może tam wyciekać.
            'clinic' => fn () => Arr::only(config('clinic'), self::PUBLIC_CLINIC_KEYS),
            'nav' => fn () => config('clinic.nav'),
            'flash' => fn () => ['success' => $request->session()->get('success')],
        ];
    }
}
