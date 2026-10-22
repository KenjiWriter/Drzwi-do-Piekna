<?php

namespace App\Providers\Filament;

use App\Filament\Auth\EditProfile;
use App\Support\Auth\PanelAppAuthentication;
use App\Support\Auth\PanelEmailAuthentication;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/** Panel redakcyjny pod /admin: zabiegi, cennik, zespół i zgłoszenia z formularza. */
class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->brandName('Drzwi do Piękna')
            ->login()
            // Profil: zmiana nazwy, e-maila i hasła oraz zarządzanie dwuskładnikowym logowaniem (konfiguracja, kody awaryjne,
            // przełącznik „wymagaj obu metod naraz”).
            ->profile(EditProfile::class, isSimple: false)
            // Dwuskładnikowe logowanie: aplikacja (TOTP, np. Google Authenticator, Microsoft Authenticator, Authy) z kodami
            // awaryjnymi i/lub kod z e-maila. Konto włącza jedną metodę albo obie. Przy obu logując się wybiera, którą metodą
            // się potwierdza, chyba że ma włączone „wymagaj obu” (wtedy musi podać obie, patrz StrictMultiFactorChallenge).
            // Panel zawiera dane pacjentek (zgłoszenia), więc w produkcji 2FA jest wymagane: konto bez żadnej metody po
            // zalogowaniu trafia na ekran konfiguracji. Lokalnie nie jest wymagane, żeby konto deweloperskie dało się
            // używać (włącza to ADMIN_2FA_REQUIRED, patrz config/clinic.php). Kod z e-maila wymaga działającej poczty (SMTP).
            ->multiFactorAuthentication(
                [PanelAppAuthentication::make()->recoverable(), PanelEmailAuthentication::make()],
                isRequired: fn () => (bool) config('clinic.admin.require_2fa'),
            )
            ->colors([
                'primary' => Color::hex('#5c1f2e'),
            ])
            ->unsavedChangesAlerts()
            ->navigationGroups([
                NavigationGroup::make('Treści'),
                NavigationGroup::make('Pacjentki'),
            ])
            ->navigationItems([
                NavigationItem::make('Zobacz stronę')
                    ->url('/', shouldOpenInNewTab: true)
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->sort(100),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
