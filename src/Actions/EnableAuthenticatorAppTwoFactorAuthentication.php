<?php

namespace MustafaAwami\Lara2fa\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use MustafaAwami\Lara2fa\Contracts\AuthenticatorAppTwoFactorAuthenticationProvider;
use MustafaAwami\Lara2fa\Events\AuthenticatorAppTwoFactorAuthenticationEnabled;

class EnableAuthenticatorAppTwoFactorAuthentication
{
    /**
     * The authenticator app two factor authentication provider.
     *
     * @var \MustafaAwami\Lara2fa\Contracts\AuthenticatorAppTwoFactorAuthenticationProvider
     */
    protected $provider;

    /**
     * Create a new action instance.
     *
     * @param  \MustafaAwami\Lara2fa\Contracts\AuthenticatorAppTwoFactorAuthenticationProvider  $provider
     * @return void
     */
    public function __construct(AuthenticatorAppTwoFactorAuthenticationProvider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * Enable authenticator app two factor authentication for the user.
     *
     * @param  mixed  $user
     * @param  bool  $force
     * @return void
     */
    public function __invoke($user, $force = false)
    {
        if (empty($user->two_factor_secret) || $force === true) {
            $secretLength = (int) config('lara2fa-options.authenticator-app-two-factor-authentication.secret-length', 16);

            $user->forceFill([
                'two_factor_secret' => (Model::$encrypter ?? Crypt::getFacadeRoot())->encrypt($this->provider->generateSecretKey($secretLength)),
            ])->save();

            AuthenticatorAppTwoFactorAuthenticationEnabled::dispatch($user);
        }
    }
}
