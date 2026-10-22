<?php

namespace App\Filament\Auth;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Auth\MultiFactor\Contracts\MultiFactorAuthenticationProvider;
use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

/**
 * Profil w panelu: do sekcji dwuskładnikowego logowania Filament dokłada przełącznik „wymagaj obu metod naraz”
 * (aplikacja i kod z e-maila przy każdym logowaniu). Samo zachowanie ekranu logowania: App\Support\Auth\StrictMultiFactorChallenge.
 * Klasa leży poza app/Filament/Pages, żeby panel nie wziął jej za zwykłą stronę z menu.
 */
class EditProfile extends BaseEditProfile
{
    public function getMultiFactorAuthenticationContentComponent(): ?Component
    {
        if (! Filament::hasMultiFactorAuthentication()) {
            return null;
        }

        $user = Filament::auth()->user();

        return Section::make()
            ->label(__('filament-panels::auth/pages/edit-profile.multi_factor_authentication.label'))
            ->compact()
            ->divided()
            ->secondary()
            ->schema([
                ...collect(Filament::getMultiFactorAuthenticationProviders())
                    ->sortBy(fn (MultiFactorAuthenticationProvider $provider): int => $provider->isEnabled($user) ? 0 : 1)
                    ->map(fn (MultiFactorAuthenticationProvider $provider): Component => Group::make($provider->getManagementSchemaComponents())
                        ->statePath($provider->getId()))
                    ->values()
                    ->all(),
                $this->requireAllFactorsComponent(),
            ]);
    }

    private function requireAllFactorsComponent(): Component
    {
        return Group::make([
            Actions::make([$this->enableAction(), $this->disableAction()])
                ->key('requireAllFactorsActions')
                ->label('Wymagaj obu metod naraz')
                ->belowContent('Przy każdym logowaniu trzeba podać kod z aplikacji i kod z e-maila (zamiast jednego z nich). Gdy poczta przestanie działać, konto nie wejdzie do panelu: wtedy administrator serwera resetuje 2FA poleceniem admin:reset-2fa.')
                ->afterLabel(function (): Text {
                    $user = Filament::auth()->user();

                    return match (true) {
                        $this->hasBothMethods($user) && $user->requiresAllFactors() => Text::make('Włączone')->badge()->color('success'),
                        $user->requiresAllFactors() => Text::make('Włączone, ale nieaktywne: włącz obie metody')->badge()->color('warning'),
                        $this->hasBothMethods($user) => Text::make('Wyłączone')->badge(),
                        default => Text::make('Niedostępne')->badge(),
                    };
                }),
            // Dopóki żadna akcja nie jest dostępna (nie ma obu metod i nic nie jest włączone), Filament ukrywa cały blok akcji,
            // więc zostaje krótka informacja, że taka opcja istnieje.
            Text::make('Opcja „Wymagaj obu metod naraz” (kod z aplikacji i kod z e-maila przy każdym logowaniu) będzie dostępna po włączeniu obu metod powyżej.')
                ->color('gray')
                ->visible(function (): bool {
                    $user = Filament::auth()->user();

                    return ! $this->hasBothMethods($user) && ! $user->requiresAllFactors();
                }),
        ]);
    }

    private function enableAction(): Action
    {
        return Action::make('requireAllFactors')
            ->label('Włącz')
            ->icon(Heroicon::OutlinedShieldCheck)
            ->link()
            ->visible(fn (): bool => $this->hasBothMethods(Filament::auth()->user()) && ! Filament::auth()->user()->requiresAllFactors())
            ->requiresConfirmation()
            ->modalWidth(Width::Medium)
            ->modalHeading('Wymagać obu metod przy logowaniu?')
            ->modalDescription('Od następnego logowania trzeba będzie podać kod z aplikacji i kod z e-maila. Jeśli poczta przestanie działać albo stracisz telefon, nie zalogujesz się, dopóki administrator serwera nie zresetuje 2FA.')
            ->modalSubmitActionLabel('Włącz')
            ->action(function (): void {
                $user = Filament::auth()->user();

                if (! $this->hasBothMethods($user)) {
                    return;
                }

                $user->forceFill(['require_all_factors' => true])->save();

                Notification::make()->title('Logowanie będzie wymagać obu metod naraz.')->success()->send();
            });
    }

    private function disableAction(): Action
    {
        return Action::make('doNotRequireAllFactors')
            ->label('Wyłącz')
            ->color('danger')
            ->icon(Heroicon::OutlinedLockOpen)
            ->link()
            ->visible(fn (): bool => Filament::auth()->user()->requiresAllFactors())
            ->modalWidth(Width::Medium)
            ->modalHeading('Przestać wymagać obu metod?')
            ->modalDescription('Przy logowaniu wystarczy jedna z włączonych metod. Podaj hasło, żeby potwierdzić.')
            ->schema([
                TextInput::make('password')
                    ->label('Hasło')
                    ->currentPassword(guard: Filament::getAuthGuard())
                    ->password()
                    ->revealable(Filament::arePasswordsRevealable())
                    ->required()
                    ->dehydrated(false),
            ])
            ->modalSubmitActionLabel('Wyłącz')
            ->action(function (): void {
                Filament::auth()->user()->forceFill(['require_all_factors' => false])->save();

                Notification::make()->title('Wystarczy jedna z włączonych metod.')->success()->send();
            })
            ->rateLimit(5);
    }

    private function hasBothMethods(?User $user): bool
    {
        return $user !== null && $user->getAppAuthenticationSecret() !== null && $user->hasEmailAuthentication();
    }
}
