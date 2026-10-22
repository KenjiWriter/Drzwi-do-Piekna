<?php

namespace App\Support\Auth;

use App\Models\User;
use Filament\Auth\MultiFactor\Contracts\HasBeforeChallengeHook;
use Filament\Auth\MultiFactor\Contracts\MultiFactorAuthenticationProvider;
use Filament\Auth\MultiFactor\MultiFactorChallenge;
use Filament\Forms\Components\OneTimeCodeInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Group;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Ekran drugiego składnika przy logowaniu do panelu.
 *
 * Domyślnie (Filament): konto z kilkoma włączonymi metodami wybiera jedną z nich, a wystarczy poprawny kod z tej jednej.
 * Gdy konto ma włączone „wymagaj obu metod” (User::requiresAllFactors) i faktycznie ma włączone co najmniej dwie, ekran
 * pokazuje pola wszystkich metod naraz, bez wyboru, i każde z nich musi być poprawne. Kod z e-maila jest wtedy wysyłany od razu
 * po sprawdzeniu hasła (a nie dopiero po wybraniu metody). Podstawia go AppServiceProvider (MultiFactorChallenge::make()).
 */
class StrictMultiFactorChallenge extends MultiFactorChallenge
{
    public function requiresAll(Authenticatable $user): bool
    {
        return $user instanceof User
            && $user->requiresAllFactors()
            && count($this->getEnabledProviders($user)) > 1;
    }

    public function beforeChallenge(Authenticatable $user, ?MultiFactorAuthenticationProvider $provider = null): void
    {
        if (! $this->requiresAll($user)) {
            parent::beforeChallenge($user, $provider);

            return;
        }

        foreach ($this->getEnabledProviders($user) as $enabledProvider) {
            if ($enabledProvider instanceof HasBeforeChallengeHook) {
                $enabledProvider->beforeChallenge($user);
            }
        }
    }

    protected function getProviderPickerSchemaComponentForEnabledProviders(Authenticatable $user, array $enabledProviders): ?Component
    {
        return $this->requiresAll($user)
            ? null
            : parent::getProviderPickerSchemaComponentForEnabledProviders($user, $enabledProviders);
    }

    protected function getChallengeSchemaComponentsForEnabledProviders(Authenticatable $user, array $enabledProviders): array
    {
        if (! $this->requiresAll($user)) {
            return parent::getChallengeSchemaComponentsForEnabledProviders($user, $enabledProviders);
        }

        return collect($enabledProviders)
            ->map(fn (MultiFactorAuthenticationProvider $provider): Component => Group::make($this->withoutAutoSubmit($provider->getChallengeFormComponents($user)))
                ->statePath($provider->getId()))
            ->all();
    }

    /**
     * Pole kodu wysyła formularz po wpisaniu ostatniej cyfry. Przy dwóch polach przedwczesne wysłanie pokazywałoby błąd
     * „pole wymagane” pod drugim, więc w tym trybie logowanie zatwierdza się przyciskiem.
     *
     * @param  array<int, mixed>  $components
     * @return array<int, mixed>
     */
    private function withoutAutoSubmit(array $components): array
    {
        foreach ($components as $component) {
            if ($component instanceof OneTimeCodeInput) {
                $component->submitOnCompletion(false);
            }
        }

        return $components;
    }
}
