<?php

namespace Tests\Feature;

use App\Models\User;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Auth\Pages\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AdminResetPasswordTest extends TestCase
{
    use RefreshDatabase;

    private const NEW_PASSWORD = 'nowe-haslo-do-panelu';

    private function signIn(User $user, string $password): void
    {
        Livewire::test(Login::class)
            ->fillForm(['email' => $user->email, 'password' => $password])
            ->call('authenticate');
    }

    public function test_it_sets_a_new_password_that_signs_in_and_retires_the_old_one(): void
    {
        $user = User::factory()->create(['remember_token' => 'stary-token']);

        $this->artisan('admin:reset-password', ['email' => $user->email])
            ->expectsQuestion("Nowe hasło dla {$user->email}", self::NEW_PASSWORD)
            ->expectsQuestion('Powtórz hasło', self::NEW_PASSWORD)
            ->expectsOutputToContain('Ustawiono nowe hasło')
            ->doesntExpectOutputToContain('admin:reset-2fa')
            ->assertSuccessful();

        $fresh = $user->fresh();
        $this->assertTrue(Hash::check(self::NEW_PASSWORD, $fresh->password));
        $this->assertNotSame(self::NEW_PASSWORD, $fresh->password);
        // "Zapamiętaj mnie" z innych urządzeń przestaje działać.
        $this->assertNotSame('stary-token', $fresh->remember_token);

        $this->signIn($user, 'password');
        $this->assertGuest();

        $this->signIn($user, self::NEW_PASSWORD);
        $this->assertAuthenticatedAs($user);
    }

    public function test_without_an_email_it_lets_you_pick_the_account(): void
    {
        User::factory()->create(['email' => 'pierwsza@example.test']);
        $user = User::factory()->create(['email' => 'druga@example.test']);

        $this->artisan('admin:reset-password')
            ->expectsChoice('Które konto?', 'druga@example.test', ['pierwsza@example.test', 'druga@example.test'])
            ->expectsQuestion('Nowe hasło dla druga@example.test', self::NEW_PASSWORD)
            ->expectsQuestion('Powtórz hasło', self::NEW_PASSWORD)
            ->assertSuccessful();

        $this->assertTrue(Hash::check(self::NEW_PASSWORD, $user->fresh()->password));
    }

    public function test_a_short_or_mistyped_password_changes_nothing(): void
    {
        $user = User::factory()->create();
        $hash = $user->password;

        $this->artisan('admin:reset-password', ['email' => $user->email])
            ->expectsQuestion("Nowe hasło dla {$user->email}", 'krotkie')
            ->expectsOutputToContain('co najmniej 12 znaków')
            ->assertFailed();

        $this->artisan('admin:reset-password', ['email' => $user->email])
            ->expectsQuestion("Nowe hasło dla {$user->email}", self::NEW_PASSWORD)
            ->expectsQuestion('Powtórz hasło', self::NEW_PASSWORD.'x')
            ->expectsOutputToContain('Hasła się różnią')
            ->assertFailed();

        $this->assertSame($hash, $user->fresh()->password);
    }

    public function test_an_unknown_email_lists_the_accounts_and_an_empty_database_says_so(): void
    {
        $this->artisan('admin:reset-password')
            ->expectsOutputToContain('W bazie nie ma kont')
            ->assertFailed();

        User::factory()->create(['email' => 'jest@example.test']);

        $this->artisan('admin:reset-password', ['email' => 'nikt@example.test'])
            ->expectsOutputToContain('Konta w bazie: jest@example.test')
            ->assertFailed();
    }

    public function test_two_factor_stays_on_and_the_command_points_to_its_reset(): void
    {
        $user = User::factory()->create();
        $user->saveAppAuthenticationSecret('JBSWY3DPEHPK3PXPJBSWY3DPEHPK3PXP');
        AppAuthentication::make()->saveRecoveryCodes($user, ['AAAA-1111']);

        $this->artisan('admin:reset-password', ['email' => $user->email])
            ->expectsQuestion("Nowe hasło dla {$user->email}", self::NEW_PASSWORD)
            ->expectsQuestion('Powtórz hasło', self::NEW_PASSWORD)
            ->expectsOutputToContain("admin:reset-2fa {$user->email}")
            ->assertSuccessful();

        $this->assertNotNull($user->fresh()->getAppAuthenticationSecret());

        // Samo nowe hasło nie wpuszcza do panelu, potrzebny jest jeszcze kod.
        $this->signIn($user, self::NEW_PASSWORD);
        $this->assertGuest();
    }
}
