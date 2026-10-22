<?php

namespace App\Support\Auth;

use Filament\Facades\Filament;
use SensitiveParameter;

/**
 * Pamięć kodów przyjętych w bieżącym logowaniu do panelu.
 *
 * Filament zużywa kod przy pierwszej poprawnej weryfikacji (kod z aplikacji zapisuje znacznik czasu, kod z e-maila znika,
 * kod awaryjny jest usuwany z listy). Przy logowaniu wymagającym obu metod naraz osoba może wpisać jeden kod poprawnie,
 * a drugi błędnie. Po poprawce drugiego pierwszy kod byłby już „zużyty” i logowanie nie miałoby szans się udać.
 * Dlatego ten sam kod, ponowiony w tej samej sesji przeglądarki w ciągu kilku minut, jest przyjmowany jeszcze raz.
 * Nie osłabia to zabezpieczenia: kod zna tylko ta sesja, która go podała, a pamięć działa wyłącznie przed zalogowaniem
 * (zalogowane konto, np. wyłączające 2FA w Profilu, dostaje zwykłą ochronę przed ponownym użyciem kodu).
 */
class AcceptedLoginCodes
{
    private const SESSION_KEY = 'ddp_accepted_login_codes';

    private const REMEMBER_SECONDS = 180;

    public static function has(string $scope, #[SensitiveParameter] string $code): bool
    {
        if (! self::isLoggingIn()) {
            return false;
        }

        $entry = session(self::SESSION_KEY.'.'.self::slot($scope));

        return is_array($entry)
            && ($entry['until'] ?? 0) >= now()->getTimestamp()
            && hash_equals((string) ($entry['hash'] ?? ''), hash('sha256', $code));
    }

    public static function put(string $scope, #[SensitiveParameter] string $code): void
    {
        if (! self::isLoggingIn()) {
            return;
        }

        session()->put(self::SESSION_KEY.'.'.self::slot($scope), [
            'hash' => hash('sha256', $code),
            'until' => now()->getTimestamp() + self::REMEMBER_SECONDS,
        ]);
    }

    public static function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    private static function isLoggingIn(): bool
    {
        return Filament::auth()->guest();
    }

    private static function slot(string $scope): string
    {
        return hash('sha256', $scope);
    }
}
