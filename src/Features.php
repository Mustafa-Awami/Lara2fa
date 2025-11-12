<?php

namespace MustafaAwami\Lara2fa;

class Features
{
    /**
     * Determine if the given feature is enabled.
     *
     * @param  string  $feature
     * @return bool
     */
    public static function enabled(string $feature)
    {
        return in_array($feature, config('lara2fa.features', []))  &&
                (
                    array_key_exists("enable", config("lara2fa-options.{$feature}", [])) ? 
                    config("lara2fa-options.{$feature}.enable") === true :
                    true
                );
    }

    /**
     * Determine if the feature is enabled and has a given option enabled.
     *
     * @param  string  $feature
     * @param  string  $option
     * @return bool
     */
    public static function optionEnabled(string $feature, string $option)
    {
        return static::enabled($feature) &&
               config("lara2fa-options.{$feature}.{$option}") === true;
    }

    /**
     * Enable the authenticator app two-factor authentication feature.
     *
     * @param  array  $options
     * @return string
     */
    public static function authenticatorAppTwoFactorAuthentication(array $options = [])
    {
        if (! empty($options)) {
            config(['lara2fa-options.authenticator-app-two-factor-authentication' => $options]);
        }

        return 'authenticator-app-two-factor-authentication';
    }

    public static function confirmsAuthenticatorAppTwoFactorAuthentication()
    {
        return Features::enabled(Features::authenticatorAppTwoFactorAuthentication()) &&
               Features::optionEnabled(Features::authenticatorAppTwoFactorAuthentication(), 'confirm');
    }

    public static function confirmsPasswordAuthenticatorAppTwoFactorAuthentication()
    {
        return Features::enabled(Features::authenticatorAppTwoFactorAuthentication()) &&
               Features::optionEnabled(Features::authenticatorAppTwoFactorAuthentication(), 'confirmPassword');
    }

    
    /**
     * Enable the email two-factor authentication feature.
     *
     * @param  array  $options
     * @return string
     */
    public static function emailTwoFactorAuthentication(array $options = [])
    {
        if (! empty($options)) {
            config(['lara2fa-options.email-two-factor-authentication' => $options]);
        }

        return 'email-two-factor-authentication';
    }

    public static function confirmsEmailTwoFactorAuthentication()
    {
        return Features::enabled(Features::emailTwoFactorAuthentication()) &&
               Features::optionEnabled(Features::emailTwoFactorAuthentication(), 'confirm');
    }

    public static function confirmsPasswordEmailTwoFactorAuthentication()
    {
        return Features::enabled(Features::emailTwoFactorAuthentication()) &&
               Features::optionEnabled(Features::emailTwoFactorAuthentication(), 'confirmPassword');
    }

    /**
     * Enable the recoveryCodes two-factor authentication feature.
     *
     * @param  array  $options
     * @return string
     */
    public static function recoveryCodes(array $options = [])
    {
        if (! empty($options)) {
            config(['lara2fa-options.recovery-codes' => $options]);
        }

        return 'recovery-codes';
    }

    public static function confirmsPasswordRecoveryCode()
    {
        return Features::enabled(Features::recoveryCodes()) &&
               Features::optionEnabled(Features::recoveryCodes(), 'confirmPassword');
    }

    /**
     * Enable the recoveryCodes two-factor authentication feature.
     *
     * @param  array  $options
     * @return string
     */
    public static function passkeys(array $options = [])
    {
        if (! empty($options)) {
            config(['lara2fa-options.passkeys' => $options]);
        }

        return 'passkeys';
    }

    public static function confirmsPasswordPasskeys()
    {
        return Features::enabled(Features::passkeys()) &&
               Features::optionEnabled(Features::passkeys(), 'confirmPassword');
    }

    /**
     * Determine if the application can manage two-factor authentication.
     *
     * @return bool
     */
    public static function canManagetwoFactorAuthentication()
    {
        return static::enabled(static::authenticatorAppTwoFactorAuthentication()) || 
                static::enabled(static::emailTwoFactorAuthentication()) ||
                static::enabled(static::passkeys());
        
    }

    /**
     * Determine if the application can manage two additional authentication.
     *
     * @return bool
     */
    public static function canManageAdditionalAuthentication()
    {
        return static::enabled(static::recoveryCodes());
    }

    /**
     * Determine if the recovery codes require the two-factor authentication to be enabled.
     *
     * @return bool
     */
    public static function recoveryCodesRequireTwoFactorAuthenticationEnabled() {
        return static::optionEnabled(static::recoveryCodes(), 'requireTwoFactorAuthenticationEnabled');
    }
}