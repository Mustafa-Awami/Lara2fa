<?php

namespace MustafaAwami\Lara2fa\Actions;

use MustafaAwami\Lara2fa\Contracts\EmailTwoFactorAuthenticationProvider;
use MustafaAwami\Lara2fa\Events\EmailTwoFactorAuthenticationEnabled;

class EnableEmailTwoFactorAuthentication
{
    /**
     * The email two factor authentication provider.
     *
     * @var \MustafaAwami\Lara2fa\Contracts\EmailTwoFactorAuthenticationProvider
     */
    protected $provider;

    /**
     * Create a new action instance.
     *
     * @param  \MustafaAwami\Lara2fa\Contracts\EmailTwoFactorAuthenticationProvider  $provider
     * @return void
     */
    public function __construct(EmailTwoFactorAuthenticationProvider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * Enable email two factor authentication for the user.
     *
     * @param  mixed  $user
     * @param  bool  $force
     * @return void
     */
    public function __invoke($user, $force = false)
    {
        if (empty($user->email_two_factor_enabled_at) || $force === true) {
            $user->forceFill([
                'email_two_factor_enabled_at' => now(),
            ])->save();

            EmailTwoFactorAuthenticationEnabled::dispatch($user);
        }
    }
}