<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Ustawia nowe hasło konta w panelu, gdy ktoś je zapomni. Panel nie ma resetu hasła przez e-mail, więc robi to
 * administrator serwera. Hasło wpisuje się dwa razy bez podglądu, więc nie trafia do historii powłoki.
 * Zmiana hasła wylogowuje inne sesje tego konta (AuthenticateSession w panelu) i unieważnia „zapamiętaj mnie”.
 * 2FA zostaje bez zmian, do tego służy admin:reset-2fa.
 */
class ResetAdminPassword extends Command
{
    public const MIN_LENGTH = 12;

    protected $signature = 'admin:reset-password {email? : Adres e-mail konta (bez niego polecenie pokaże listę kont)}';

    protected $description = 'Ustawia nowe hasło konta w panelu /admin';

    public function handle(): int
    {
        $user = $this->findUser();

        if (! $user) {
            return self::FAILURE;
        }

        $password = (string) $this->secret("Nowe hasło dla {$user->email}");

        if (mb_strlen($password) < self::MIN_LENGTH) {
            $this->error('Hasło musi mieć co najmniej '.self::MIN_LENGTH.' znaków. Nic nie zmieniono.');

            return self::FAILURE;
        }

        if ((string) $this->secret('Powtórz hasło') !== $password) {
            $this->error('Hasła się różnią. Nic nie zmieniono.');

            return self::FAILURE;
        }

        // Rzutowanie "hashed" w modelu zapisuje skrót, nie jawny tekst.
        $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();

        $this->info("Ustawiono nowe hasło dla {$user->email}. Inne zalogowane sesje tego konta zostaną wylogowane.");

        if (filled($user->getAppAuthenticationSecret()) || $user->hasEmailAuthentication()) {
            $this->line('Konto ma włączone 2FA, więc po haśle panel poprosi o kod. Bez dostępu do kodów zresetuj 2FA:');
            $this->line(self::php()." artisan admin:reset-2fa {$user->email}");
        }

        return self::SUCCESS;
    }

    /** Wersja PHP, którą uruchomiono polecenie (np. php8.5), bo na serwerze samo "php" bywa starsze. */
    private static function php(): string
    {
        return 'php'.PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;
    }

    private function findUser(): ?User
    {
        $email = $this->argument('email');

        if ($email === null) {
            $emails = User::query()->orderBy('id')->pluck('email')->all();

            if ($emails === []) {
                $this->error('W bazie nie ma kont. Załóż je poleceniem: '.self::php().' artisan make:filament-user');

                return null;
            }

            $email = $this->choice('Które konto?', $emails);
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            $emails = User::query()->orderBy('id')->pluck('email');

            $this->error('Nie ma konta z takim adresem e-mail.');
            $this->line($emails->isEmpty() ? 'W bazie nie ma żadnych kont.' : 'Konta w bazie: '.$emails->implode(', '));
        }

        return $user;
    }
}
