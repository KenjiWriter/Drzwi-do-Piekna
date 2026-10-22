<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * Zdejmuje dwuskładnikowe logowanie z konta, gdy ktoś straci telefon i kody awaryjne albo przestanie działać poczta
 * (przy trybie „wymagaj obu metod” konto bez któregoś składnika nie wejdzie do panelu). Wykonuje to administrator
 * serwera (nie da się tego zrobić z panelu, bo bez drugiego składnika nikt się do niego nie zaloguje).
 * Usuwa wszystko: aplikację z kodami awaryjnymi, kod z e-maila i przełącznik „wymagaj obu”.
 * Przy następnym logowaniu, gdy 2FA jest wymagane (produkcja), konto samo trafi na ekran ponownej konfiguracji.
 */
class ResetAdminTwoFactor extends Command
{
    protected $signature = 'admin:reset-2fa {email : Adres e-mail konta w panelu} {--force : Bez pytania o potwierdzenie}';

    protected $description = 'Usuwa dwuskładnikowe logowanie (aplikację, kody awaryjne, kod z e-maila) z konta w panelu /admin';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error('Nie ma konta z takim adresem e-mail.');

            return self::FAILURE;
        }

        if (blank($user->getAppAuthenticationSecret()) && ! $user->hasEmailAuthentication() && ! $user->requiresAllFactors()) {
            $this->info("Konto {$user->email} nie ma włączonego 2FA, nic do zrobienia.");

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("Usunąć 2FA z konta {$user->email}? Upewnij się, że to naprawdę ta osoba prosi o reset.")) {
            $this->warn('Przerwano, nic nie zmieniono.');

            return self::FAILURE;
        }

        $user->saveAppAuthenticationSecret(null);
        $user->saveAppAuthenticationRecoveryCodes(null);
        $user->forceFill(['has_email_authentication' => false, 'require_all_factors' => false])->save();

        $this->info("Usunięto 2FA z konta {$user->email}. Przy następnym logowaniu trzeba skonfigurować je od nowa.");

        return self::SUCCESS;
    }
}
