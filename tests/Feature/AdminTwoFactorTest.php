<?php

namespace Tests\Feature;

use App\Models\User;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class AdminTwoFactorTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'JBSWY3DPEHPK3PXPJBSWY3DPEHPK3PXP';

    protected function tearDown(): void
    {
        unset($_ENV['ADMIN_2FA_REQUIRED']);
        putenv('ADMIN_2FA_REQUIRED');

        parent::tearDown();
    }

    private function staff(bool $withTwoFactor = true): User
    {
        $user = User::factory()->create();

        if ($withTwoFactor) {
            $user->saveAppAuthenticationSecret(self::SECRET);
            // Tak jak w aplikacji: kody awaryjne są zapisywane jako skróty (bcrypt), a całość jest dodatkowo szyfrowana.
            AppAuthentication::make()->saveRecoveryCodes($user, ['AAAA-1111', 'BBBB-2222']);
        }

        return $user;
    }

    private function code(): string
    {
        return (new Google2FA)->getCurrentOtp(self::SECRET);
    }

    private function password(User $user): \Livewire\Features\SupportTesting\Testable
    {
        return Livewire::test(Login::class)
            ->fillForm(['email' => $user->email, 'password' => 'password'])
            ->call('authenticate');
    }

    public function test_the_panel_offers_app_authentication_with_recovery_codes_and_a_profile_page(): void
    {
        $panel = Filament::getPanel('admin');
        $provider = $panel->getMultiFactorAuthenticationProviders()['app'] ?? null;

        $this->assertInstanceOf(AppAuthentication::class, $provider);
        $this->assertTrue($provider->isRecoverable());
        $this->assertTrue($panel->hasProfile());
    }

    public function test_a_user_without_two_factor_still_signs_in_with_the_password_only_when_it_is_not_required(): void
    {
        $user = $this->staff(withTwoFactor: false);

        $this->password($user)->assertHasNoFormErrors();

        $this->assertAuthenticatedAs($user);
    }

    public function test_a_user_with_two_factor_is_not_signed_in_by_the_password_alone(): void
    {
        $user = $this->staff();

        $this->password($user)->assertHasNoFormErrors();

        $this->assertGuest();
    }

    public function test_the_code_from_the_app_completes_the_sign_in(): void
    {
        $user = $this->staff();

        $this->password($user)
            ->set('data.multiFactor.app.code', $this->code())
            ->call('authenticate')
            ->assertHasNoFormErrors()
            ->assertRedirect();

        $this->assertAuthenticatedAs($user);
    }

    public function test_a_wrong_code_does_not_sign_in(): void
    {
        $user = $this->staff();

        $this->password($user)
            ->set('data.multiFactor.app.code', '000000')
            ->call('authenticate');

        $this->assertGuest();
    }

    public function test_a_recovery_code_signs_in_once_and_is_then_used_up(): void
    {
        $user = $this->staff();

        // "useRecoveryCode" ustawia przycisk "Użyj kodu awaryjnego" w formularzu; wtedy pole z kodem z aplikacji nie jest wymagane.
        $this->password($user)
            ->set('data.multiFactor.app.useRecoveryCode', true)
            ->set('data.multiFactor.app.recoveryCode', 'AAAA-1111')
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticatedAs($user);
        $remaining = $user->fresh()->getAppAuthenticationRecoveryCodes();
        $this->assertCount(1, $remaining, 'Zużyty kod znika z listy.');
        $this->assertTrue(Hash::check('BBBB-2222', $remaining[0]), 'Zostaje ten drugi.');

        auth()->logout();

        // Ten sam kod drugi raz już nie działa.
        $this->password($user)
            ->set('data.multiFactor.app.useRecoveryCode', true)
            ->set('data.multiFactor.app.recoveryCode', 'AAAA-1111')
            ->call('authenticate');

        $this->assertGuest();
    }

    public function test_the_secret_and_recovery_codes_are_encrypted_in_the_database_and_never_serialised(): void
    {
        $user = $this->staff();
        $row = DB::table('users')->where('id', $user->id)->first();

        $this->assertNotSame(self::SECRET, $row->app_authentication_secret);
        $this->assertStringNotContainsString(self::SECRET, $row->app_authentication_secret);
        $this->assertStringNotContainsString('AAAA-1111', $row->app_authentication_recovery_codes);

        // Po odczycie przez model wraca jawny tekst, ale nie wycieka w tablicy ani w JSON-ie.
        $this->assertSame(self::SECRET, $user->fresh()->getAppAuthenticationSecret());
        $this->assertArrayNotHasKey('app_authentication_secret', $user->fresh()->toArray());
        $this->assertArrayNotHasKey('app_authentication_recovery_codes', $user->fresh()->toArray());
        $this->assertStringNotContainsString(self::SECRET, $user->fresh()->toJson());
    }

    public function test_the_authenticator_app_shows_the_account_email(): void
    {
        $user = $this->staff();

        $this->assertSame($user->email, $user->getAppAuthenticationHolderName());
    }

    public function test_the_profile_page_lets_staff_manage_two_factor(): void
    {
        $this->actingAs($this->staff(withTwoFactor: false));

        $this->get('/admin/profile')->assertOk()->assertSee('Aplikacja uwierzytelniająca');
    }

    public function test_two_factor_is_off_by_default_outside_production_and_on_in_production(): void
    {
        $this->assertFalse(config('clinic.admin.require_2fa'));

        // Konfiguracja czyta środowisko przy starcie, więc "produkcję" odtwarzamy, uruchamiając aplikację od nowa.
        $_ENV['APP_ENV'] = 'production';
        putenv('APP_ENV=production');
        unset($_ENV['ADMIN_2FA_REQUIRED']);
        $this->refreshApplication();
        $production = config('clinic.admin.require_2fa');
        $_ENV['APP_ENV'] = 'testing';
        putenv('APP_ENV=testing');

        $this->assertTrue($production, 'W produkcji 2FA ma być wymagane bez żadnych dodatkowych ustawień.');
    }

    public function test_when_required_a_user_without_two_factor_is_sent_to_set_it_up_and_a_user_with_it_gets_in(): void
    {
        $_ENV['ADMIN_2FA_REQUIRED'] = 'true';
        putenv('ADMIN_2FA_REQUIRED=true');
        $this->refreshApplication();
        // Nowa aplikacja ma nową, pustą bazę w pamięci.
        $this->artisan('migrate');

        $this->assertTrue(Filament::getPanel('admin')->isMultiFactorAuthenticationRequired());

        $this->actingAs($this->staff(withTwoFactor: false))
            ->get('/admin')
            ->assertRedirect(Filament::getSetUpRequiredMultiFactorAuthenticationUrl());

        $this->actingAs($this->staff())->get('/admin')->assertOk();
    }

    public function test_the_reset_command_removes_two_factor_after_confirmation(): void
    {
        $user = $this->staff();

        $this->artisan('admin:reset-2fa', ['email' => $user->email])
            ->expectsConfirmation("Usunąć 2FA z konta {$user->email}? Upewnij się, że to naprawdę ta osoba prosi o reset.", 'no')
            ->assertExitCode(1);
        $this->assertNotNull($user->fresh()->getAppAuthenticationSecret());

        $this->artisan('admin:reset-2fa', ['email' => $user->email, '--force' => true])->assertSuccessful();

        $this->assertNull($user->fresh()->getAppAuthenticationSecret());
        $this->assertNull($user->fresh()->getAppAuthenticationRecoveryCodes());

        // Po resecie wystarczy hasło (gdy 2FA nie jest wymagane) i można skonfigurować 2FA od nowa.
        $this->password($user)->assertHasNoFormErrors();
        $this->assertAuthenticatedAs($user);
    }

    public function test_the_reset_command_handles_a_missing_user_and_an_account_without_two_factor(): void
    {
        $this->artisan('admin:reset-2fa', ['email' => 'nikt@example.test', '--force' => true])->assertExitCode(1);
        $this->artisan('admin:reset-2fa', ['email' => $this->staff(withTwoFactor: false)->email, '--force' => true])->assertSuccessful();
    }
}
