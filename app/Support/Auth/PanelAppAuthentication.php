<?php

namespace App\Support\Auth;

use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Illuminate\Database\Eloquent\Model;
use SensitiveParameter;

/** Kod z aplikacji uwierzytelniającej z pamięcią przyjętych kodów (patrz AcceptedLoginCodes). */
class PanelAppAuthentication extends AppAuthentication
{
    public function verifyCode(#[SensitiveParameter] string $code, #[SensitiveParameter] ?string $secret = null, bool $shouldPreventCodeReuse = false): bool
    {
        // Pamięć dotyczy tylko logowania (wywołanie z ochroną przed ponownym użyciem i znanym sekretem).
        if (! $shouldPreventCodeReuse || $secret === null) {
            return parent::verifyCode($code, $secret, $shouldPreventCodeReuse);
        }

        $scope = 'app:'.hash('sha256', $secret);

        if (AcceptedLoginCodes::has($scope, $code)) {
            return true;
        }

        if (! parent::verifyCode($code, $secret, true)) {
            return false;
        }

        AcceptedLoginCodes::put($scope, $code);

        return true;
    }

    public function verifyRecoveryCode(#[SensitiveParameter] string $recoveryCode, ?HasAppAuthenticationRecovery $user = null): bool
    {
        if ($user === null) {
            return parent::verifyRecoveryCode($recoveryCode, $user);
        }

        $scope = 'recovery:'.$user::class.':'.($user instanceof Model ? $user->getKey() : spl_object_id($user));

        if (AcceptedLoginCodes::has($scope, $recoveryCode)) {
            return true;
        }

        if (! parent::verifyRecoveryCode($recoveryCode, $user)) {
            return false;
        }

        AcceptedLoginCodes::put($scope, $recoveryCode);

        return true;
    }
}
