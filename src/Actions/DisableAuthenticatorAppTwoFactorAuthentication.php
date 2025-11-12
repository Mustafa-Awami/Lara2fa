<?php

namespace MustafaAwami\Lara2fa\Actions;

use MustafaAwami\Lara2fa\Events\AuthenticatorAppTwoFactorAuthenticationDisabled;
use MustafaAwami\Lara2fa\Features;

class DisableAuthenticatorAppTwoFactorAuthentication
{
    /**
     * Disable authenticator app two-factor authentication for the user.
     *
     * @param  mixed  $user
     * @return void
     */
    public function __invoke($user)
    {
        if (! is_null($user->two_factor_secret) ||
            ! is_null($user->two_factor_confirmed_at)) {
            $user->forceFill([
                'two_factor_secret' => null,
            ] + (Features::confirmsAuthenticatorAppTwoFactorAuthentication() || ! is_null($user->two_factor_confirmed_at) ? [
                'two_factor_confirmed_at' => null,
            ] : []))->save();

            AuthenticatorAppTwoFactorAuthenticationDisabled::dispatch($user);
        }
    }
}
