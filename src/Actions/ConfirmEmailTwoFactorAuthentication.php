<?php

namespace MustafaAwami\Lara2fa\Actions;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use MustafaAwami\Lara2fa\Events\EmailTwoFactorAuthenticationConfirmed;
use MustafaAwami\Lara2fa\Contracts\EmailTwoFactorAuthenticationProvider;

class ConfirmEmailTwoFactorAuthentication
{
    /**
     * The email two-factor authentication provider.
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
     * Confirm the email two-factor authentication configuration for the user.
     *
     * @param  mixed  $user
     * @param  string  $code
     * @return void
     */
    public function __invoke($user, $code)
    {
        if (empty($user->email_two_factor_code) ||
            empty($code) ||
            ! $this->provider->verify((Model::$encrypter ?? Crypt::getFacadeRoot())->decrypt($user->email_two_factor_code), $code)) {
            throw ValidationException::withMessages([
                'code' => [__('The provided two-factor authentication code was invalid.')],
            ])->errorBag('confirmEmailTwoFactorAuthentication');
        } elseif (empty($user->email_two_factor_code_expires_at) || 
            $this->provider->isCodeExpired($user->email_two_factor_code_expires_at)){
            throw ValidationException::withMessages([
                'code' => [__('The provided code is expaired.')],
            ])->errorBag('confirmEmailTwoFactorAuthentication');
        }

        $user->forceFill([
            'email_two_factor_confirmed_at' => now(),
        ])->save();

        $user->resetEmailTwoFactorCode();

        EmailTwoFactorAuthenticationConfirmed::dispatch($user);
    }
}