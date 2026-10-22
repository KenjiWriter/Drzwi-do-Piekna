<?php

namespace Tests\Feature;

use App\Filament\Auth\EditProfile;
use App\Models\User;
use App\Support\Auth\AcceptedLoginCodes;
use App\Support\Auth\PanelAppAuthentication;
use App\Support\Auth\PanelEmailAuthentication;
use App\Support\Auth\StrictMultiFactorChallenge;
use Filament\Actions\Testing\TestAction;
use Filament\Auth\MultiFactor\Email\Notifications\VerifyEmailAuthentication;
use Filament\Auth\MultiFactor\MultiFactorChallenge;
use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;
use Illuminate\Auth\Events\Login as LoginEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Notification;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

/**
 * Druga metoda 2FA w panelu (kod z e-maila), obie metody naraz do wyboru i tryb „wymagaj obu metod” na koncie.
 * Metoda z aplikacją ma własne testy w AdminTwoFactorTest.
 */
class AdminEmailTwoFactorTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'JBSWY3DPEHPK3PXPJBSWY3DPEHPK3PXP';

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    private function staff(bool $app = false, bool $email = false, bool $requireAll = false): User
    {
        $user = User::factory()->create();

        if ($app) {
            $user->saveAppAuthenticationSecret(self::SECRET);
            PanelAppAuthentication::make()->saveRecoveryCodes($user, ['AAAA-1111', 'BBBB-2222']);
        }

        if ($email) {
            $user->toggleEmailAuthentication(true);
        }

        if ($requireAll) {
            $user->forceFill(['require_all_factors' => true])->save();
        }

        return $user->fresh();
    }

    private function appCode(): string
    {
        return (new Google2FA)->getCurrentOtp(self::SECRET);
    }

    /** Ostatni kod wysłany użytkownikowi e-mailem (Filament przekazuje go powiadomieniu VerifyEmailAuthentication). */
    private function emailCode(User $user): string
    {
        $sent = Notification::sent($user, VerifyEmailAuthentication::class);

        $this->assertNotEmpty($sent, 'Kod nie został wysłany e-mailem.');

        return $sent->last()->code;
    }

    private function emailsSentTo(User $user): int
    {
        return Notification::sent($user, VerifyEmailAuthentication::class)->count();
    }

    private function password(User $user): Testable
    {
        return Livewire::test(Login::class)
            ->fillForm(['email' => $user->email, 'password' => 'password'])
            ->call('authenticate');
    }

    public function test_the_panel_offers_the_app_and_the_email_code(): void
    {
        $providers = Filament::getPanel('admin')->getMultiFactorAuthenticationProviders();

        $this->assertSame(['app', 'email_code'], array_keys($providers));
        $this->assertInstanceOf(PanelAppAuthentication::class, $providers['app']);
        $this->assertInstanceOf(PanelEmailAuthentication::class, $providers['email_code']);
        $this->assertInstanceOf(StrictMultiFactorChallenge::class, MultiFactorChallenge::make());
    }

    public function test_the_email_code_is_sent_after_the_password_and_completes_the_sign_in(): void
    {
        $user = $this->staff(email: true);

        $this->password($user)->assertHasNoFormErrors();

        $this->assertGuest();
        Notification::assertSentToTimes($user, VerifyEmailAuthentication::class, 1);

        $this->password($user)
            ->set('data.multiFactor.email_code.code', $this->emailCode($user))
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticatedAs($user);
    }

    /**
     * Cache `array` z testów trzyma obiekty bez serializacji, więc nie pokazuje błędu z prawdziwych środowisk: Laravel 13 nie odczytuje
     * obiektów z cache (serializable_classes), a Filament zapisuje w cache datę ważności kodu jako Carbon. Ten test używa cache plikowego.
     */
    public function test_the_email_code_works_with_a_cache_that_serialises_values(): void
    {
        $directory = sys_get_temp_dir().'/ddp-cache-'.uniqid();
        config(['cache.default' => 'file', 'cache.stores.file.path' => $directory, 'cache.stores.file.lock_path' => $directory]);
        Cache::purge('file');

        try {
            $user = $this->staff(email: true);

            $this->password($user);
            $this->assertGuest();

            $this->password($user)
                ->set('data.multiFactor.email_code.code', $this->emailCode($user))
                ->call('authenticate')
                ->assertHasNoFormErrors();

            $this->assertAuthenticatedAs($user);
        } finally {
            File::deleteDirectory($directory);
            Cache::purge('file');
        }
    }

    public function test_a_wrong_email_code_does_not_sign_in(): void
    {
        $user = $this->staff(email: true);

        $this->password($user)
            ->set('data.multiFactor.email_code.code', '000000')
            ->call('authenticate');

        $this->assertGuest();
    }

    public function test_an_email_code_cannot_be_used_twice(): void
    {
        $user = $this->staff(email: true);

        $this->password($user)
            ->set('data.multiFactor.email_code.code', $code = $this->emailCode($user))
            ->call('authenticate');
        $this->assertAuthenticatedAs($user);

        auth()->logout();
        $this->flushSession();

        // Nowe logowanie wysyła nowy kod. Stary, już zużyty, nie działa.
        $this->password($user)
            ->set('data.multiFactor.email_code.code', $code)
            ->call('authenticate');

        $this->assertGuest();
    }

    public function test_with_both_methods_enabled_the_app_code_alone_is_enough(): void
    {
        $user = $this->staff(app: true, email: true);

        $this->password($user)
            ->set('data.multiFactor.provider', 'app')
            ->set('data.multiFactor.app.code', $this->appCode())
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticatedAs($user);
    }

    public function test_with_both_methods_enabled_choosing_the_email_sends_the_code_and_it_alone_is_enough(): void
    {
        $user = $this->staff(app: true, email: true);

        $this->password($user)->set('data.multiFactor.provider', 'email_code')
            ->set('data.multiFactor.email_code.code', $this->emailCode($user))
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticatedAs($user);
    }

    public function test_with_both_methods_a_wrong_code_of_the_chosen_method_does_not_sign_in(): void
    {
        $user = $this->staff(app: true, email: true);

        $this->password($user)
            ->set('data.multiFactor.provider', 'app')
            ->set('data.multiFactor.app.code', '000000')
            ->call('authenticate');

        $this->assertGuest();
    }

    public function test_the_method_picker_is_shown_unless_both_methods_are_required(): void
    {
        $challenge = MultiFactorChallenge::make();

        $this->assertNotNull($challenge->getProviderPickerSchemaComponent($this->staff(app: true, email: true)));
        $this->assertNull($challenge->getProviderPickerSchemaComponent($this->staff(app: true, email: true, requireAll: true)));
        $this->assertNull($challenge->getProviderPickerSchemaComponent($this->staff(app: true)), 'Przy jednej metodzie nie ma czego wybierać.');
    }

    public function test_when_both_are_required_the_email_code_is_sent_right_after_the_password(): void
    {
        $user = $this->staff(app: true, email: true, requireAll: true);

        $this->password($user);

        Notification::assertSentToTimes($user, VerifyEmailAuthentication::class, 1);
        $this->assertGuest();
    }

    public function test_when_both_are_required_the_app_code_alone_does_not_sign_in(): void
    {
        $user = $this->staff(app: true, email: true, requireAll: true);

        $this->password($user)
            ->set('data.multiFactor.app.code', $this->appCode())
            ->call('authenticate')
            ->assertHasErrors(['data.multiFactor.email_code.code']);

        $this->assertGuest();
    }

    public function test_when_both_are_required_the_email_code_alone_does_not_sign_in(): void
    {
        $user = $this->staff(app: true, email: true, requireAll: true);

        $this->password($user)
            ->set('data.multiFactor.email_code.code', $this->emailCode($user))
            ->call('authenticate')
            ->assertHasErrors(['data.multiFactor.app.code']);

        $this->assertGuest();
    }

    public function test_when_both_are_required_both_codes_sign_in(): void
    {
        $user = $this->staff(app: true, email: true, requireAll: true);

        $this->password($user)
            ->set('data.multiFactor.app.code', $this->appCode())
            ->set('data.multiFactor.email_code.code', $this->emailCode($user))
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticatedAs($user);
    }

    public function test_when_both_are_required_a_wrong_code_of_either_kind_does_not_sign_in(): void
    {
        $user = $this->staff(app: true, email: true, requireAll: true);

        $this->password($user)
            ->set('data.multiFactor.app.code', '000000')
            ->set('data.multiFactor.email_code.code', $this->emailCode($user))
            ->call('authenticate');
        $this->assertGuest();

        $this->password($user)
            ->set('data.multiFactor.app.code', $this->appCode())
            ->set('data.multiFactor.email_code.code', '000000')
            ->call('authenticate');
        $this->assertGuest();
    }

    public function test_when_both_are_required_a_correct_code_survives_a_wrong_one_in_the_other_field(): void
    {
        $user = $this->staff(app: true, email: true, requireAll: true);

        // Pierwsza próba: kod z aplikacji dobry (zostaje przez to zużyty), kod z e-maila zły.
        $component = $this->password($user)
            ->set('data.multiFactor.app.code', $this->appCode())
            ->set('data.multiFactor.email_code.code', '000000')
            ->call('authenticate');
        $this->assertGuest();

        // Poprawka: ten sam kod z aplikacji (aplikacja da nowy dopiero za kilkanaście sekund) i dobry kod z e-maila.
        $component
            ->set('data.multiFactor.email_code.code', $this->emailCode($user))
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticatedAs($user);
    }

    public function test_when_both_are_required_a_recovery_code_replaces_only_the_app_code(): void
    {
        $user = $this->staff(app: true, email: true, requireAll: true);

        $this->password($user)
            ->set('data.multiFactor.app.useRecoveryCode', true)
            ->set('data.multiFactor.app.recoveryCode', 'AAAA-1111')
            ->call('authenticate');
        // Sam kod awaryjny nie zastępuje kodu z e-maila.
        $this->assertGuest();

        $this->password($user)
            ->set('data.multiFactor.app.useRecoveryCode', true)
            ->set('data.multiFactor.app.recoveryCode', 'BBBB-2222')
            ->set('data.multiFactor.email_code.code', $this->emailCode($user))
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticatedAs($user);
    }

    public function test_requiring_both_is_ignored_when_only_one_method_is_enabled(): void
    {
        $user = $this->staff(app: true, requireAll: true);

        $this->password($user)
            ->set('data.multiFactor.app.code', $this->appCode())
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticatedAs($user);
    }

    public function test_a_code_used_in_a_login_is_not_accepted_again_after_it_completes(): void
    {
        $user = $this->staff(app: true, email: true, requireAll: true);
        $code = $this->appCode();

        $this->password($user)
            ->set('data.multiFactor.app.code', $code)
            ->set('data.multiFactor.email_code.code', $this->emailCode($user))
            ->call('authenticate');
        $this->assertAuthenticatedAs($user);

        auth()->logout();

        // Po zalogowaniu pamięć przyjętych kodów jest czyszczona, więc powtórka tego samego kodu z aplikacji jest odrzucona.
        $this->password($user)
            ->set('data.multiFactor.app.code', $code)
            ->set('data.multiFactor.email_code.code', $this->emailCode($user))
            ->call('authenticate');

        $this->assertGuest();
    }

    public function test_accepted_codes_are_remembered_only_while_signing_in_and_only_for_a_few_minutes(): void
    {
        AcceptedLoginCodes::put('scope', '123456');
        $this->assertTrue(AcceptedLoginCodes::has('scope', '123456'));
        $this->assertFalse(AcceptedLoginCodes::has('scope', '654321'), 'Tylko dokładnie ten sam kod.');
        $this->assertFalse(AcceptedLoginCodes::has('other', '123456'), 'Tylko dla tej samej metody i konta.');

        $this->travel(4)->minutes();
        $this->assertFalse(AcceptedLoginCodes::has('scope', '123456'), 'Pamięć wygasa po kilku minutach.');
        $this->travelBack();

        AcceptedLoginCodes::put('scope', '123456');
        event(new LoginEvent('web', $this->staff(), false));
        $this->assertFalse(AcceptedLoginCodes::has('scope', '123456'), 'Zalogowanie czyści pamięć.');

        $this->actingAs($this->staff());
        AcceptedLoginCodes::put('scope', '123456');
        $this->assertFalse(AcceptedLoginCodes::has('scope', '123456'), 'Zalogowane konto dostaje zwykłą ochronę przed ponownym użyciem kodu.');
    }

    public function test_the_profile_offers_requiring_both_only_when_both_methods_are_on(): void
    {
        $action = TestAction::make('requireAllFactors')->schemaComponent('requireAllFactorsActions', schema: 'content');

        $this->actingAs($this->staff(app: true));
        Livewire::test(EditProfile::class)->assertActionDoesNotExist($action);

        $this->actingAs($this->staff(app: true, email: true));
        Livewire::test(EditProfile::class)->assertActionVisible($action);
    }

    public function test_requiring_both_is_switched_on_from_the_profile(): void
    {
        $user = $this->staff(app: true, email: true);
        $this->actingAs($user);

        Livewire::test(EditProfile::class)
            ->callAction(TestAction::make('requireAllFactors')->schemaComponent('requireAllFactorsActions', schema: 'content'));

        $this->assertTrue($user->fresh()->requiresAllFactors());
    }

    public function test_requiring_both_is_switched_off_only_with_the_current_password(): void
    {
        $user = $this->staff(app: true, email: true, requireAll: true);
        $this->actingAs($user);
        $action = TestAction::make('doNotRequireAllFactors')->schemaComponent('requireAllFactorsActions', schema: 'content');

        Livewire::test(EditProfile::class)
            ->callAction($action, ['password' => 'zle-haslo'])
            ->assertHasActionErrors(['password']);
        $this->assertTrue($user->fresh()->requiresAllFactors());

        Livewire::test(EditProfile::class)
            ->callAction($action, ['password' => 'password'])
            ->assertHasNoActionErrors();
        $this->assertFalse($user->fresh()->requiresAllFactors());
    }

    public function test_the_reset_command_also_removes_the_email_code_and_the_requirement_of_both(): void
    {
        $user = $this->staff(app: true, email: true, requireAll: true);

        $this->artisan('admin:reset-2fa', ['email' => $user->email, '--force' => true])->assertSuccessful();

        $user->refresh();
        $this->assertNull($user->getAppAuthenticationSecret());
        $this->assertFalse($user->hasEmailAuthentication());
        $this->assertFalse($user->requiresAllFactors());

        // Konto z samym kodem z e-maila też da się zresetować.
        $emailOnly = $this->staff(email: true);
        $this->artisan('admin:reset-2fa', ['email' => $emailOnly->email, '--force' => true])->assertSuccessful();
        $this->assertFalse($emailOnly->fresh()->hasEmailAuthentication());
    }

    public function test_the_new_columns_default_to_off(): void
    {
        $user = User::factory()->create()->fresh();

        $this->assertFalse($user->hasEmailAuthentication());
        $this->assertFalse($user->requiresAllFactors());
    }
}
