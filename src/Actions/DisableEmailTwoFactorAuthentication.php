<?php

namespace MustafaAwami\Lara2fa\Actions;

use MustafaAwami\Lara2fa\Features;
use MustafaAwami\Lara2fa\Events\EmailTwoFactorAuthenticationDisabled;

class DisableEmailTwoFactorAuthentication
{
    /**
     * Disable email two-factor authentication for the user.
     *
     * @param  mixed  $user
     * @return void
     */
    public function __invoke($user)
    {
        if (! is_null($user->email_two_factor_code) ||
            ! is_null($user->email_two_factor_code_expires_at) ||
            ! is_null($user->email_two_factor_enabled_at) ||
            ! is_null($user->email_two_factor_confirmed_at)) {
            $user->forceFill([
                'email_two_factor_code' => null,
                'email_two_factor_code_expires_at' => null,
                'email_two_factor_enabled_at' => null,
            ] + (Features::confirmsEmailTwoFactorAuthentication() || ! is_null($user->email_two_factor_confirmed_at) ? [
                'email_two_factor_confirmed_at' => null,
            ] : []))->save();

            EmailTwoFactorAuthenticationDisabled::dispatch($user);
        }
    }
}