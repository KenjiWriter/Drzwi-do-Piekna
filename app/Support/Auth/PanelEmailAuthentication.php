<?php

namespace App\Support\Auth;

use Filament\Auth\MultiFactor\Email\Contracts\HasEmailAuthentication;
use Filament\Auth\MultiFactor\Email\EmailAuthentication;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use SensitiveParameter;

/** Kod z e-maila (6 cyfr, 4 minuty) z pamięcią przyjętych kodów (patrz AcceptedLoginCodes). */
class PanelEmailAuthentication extends EmailAuthentication
{
    public function verifyCode(#[SensitiveParameter] string $code, ?HasEmailAuthentication $user = null): bool
    {
        $user ??= Filament::auth()->user();

        if (! ($user instanceof HasEmailAuthentication) || ! ($user instanceof Model)) {
            return parent::verifyCode($code, $user);
        }

        $scope = 'email:'.$user::class.':'.$user->getKey();

        if (AcceptedLoginCodes::has($scope, $code)) {
            return true;
        }

        if (! parent::verifyCode($code, $user)) {
            return false;
        }

        AcceptedLoginCodes::put($scope, $code);

        return true;
    }
}
