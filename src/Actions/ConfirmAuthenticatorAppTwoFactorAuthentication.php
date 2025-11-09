<?php

namespace Mustafa\Lara2fa\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Mustafa\Lara2fa\Contracts\AuthenticatorAppTwoFactorAuthenticationProvider;
use Illuminate\Validation\ValidationException;
use Mustafa\Lara2fa\Events\AuthenticatorAppTwoFactorAuthenticationConfirmed;

class ConfirmAuthenticatorAppTwoFactorAuthentication
{
    /**
     * The authenticator app two factor authentication provider.
     *
     * @var \Mustafa\Lara2fa\Contracts\AuthenticatorAppTwoFactorAuthenticationProvider
     */
    protected $provider;

    /**
     * Create a new action instance.
     *
     * @param  \Mustafa\Lara2fa\Contracts\AuthenticatorAppTwoFactorAuthenticationProvider  $provider
     * @return void
     */
    public function __construct(AuthenticatorAppTwoFactorAuthenticationProvider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * Confirm the authenticator app two factor authentication configuration for the user.
     *
     * @param  mixed  $user
     * @param  string  $code
     * @return void
     */
    public function __invoke($user, $code)
    {
        if (empty($user->two_factor_secret) ||
            empty($code) ||
            ! $this->provider->verify((Model::$encrypter ?? Crypt::getFacadeRoot())->decrypt($user->two_factor_secret), $code)) {
            throw ValidationException::withMessages([
                'code' => [__('The provided two factor authentication code was invalid.')],
            ])->errorBag('confirmTwoFactorAuthentication');
        }

        $user->forceFill([
            'two_factor_confirmed_at' => now(),
        ])->save();

        AuthenticatorAppTwoFactorAuthenticationConfirmed::dispatch($user);
    }
}
