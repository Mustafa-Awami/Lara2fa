<?php

namespace MustafaAwami\Lara2fa;

class Lara2fa
{
    /**
     * Indicates if Lara2fa routes will be registered.
     *
     * @var bool
     */
    public static $registersRoutes = true;

    const AUTHENTICATOR_APP_TWO_FACTOR_AUTHENTICATION_CONFIRMED = 'authenticator-app-two-factor-authentication-confirmed';
    const AUTHENTICATOR_APP_TWO_FACTOR_AUTHENTICATION_DISABLED = 'authenticator-app-two-factor-authentication-disabled';
    const AUTHENTICATOR_APP_TWO_FACTOR_AUTHENTICATION_ENABLED = 'authenticator-app-two-factor-authentication-enabled';

    const EMAIL_TWO_FACTOR_AUTHENTICATION_CONFIRMED = 'email-two-factor-authentication-confirmed';
    const EMAIL_TWO_FACTOR_AUTHENTICATION_DISABLED = 'email-two-factor-authentication-disabled';
    const EMAIL_TWO_FACTOR_AUTHENTICATION_ENABLED = 'email-two-factor-authentication-enabled';
    const EMAIL_TWO_FACTOR_AUTHENTICATION_NOTIFY = 'email-two-factor-authentication-notify';

    const RECOVERY_CODES_GENERATED = 'recovery-codes-generated';
    const RECOVERY_CODES_DISABLED = 'recovery-codes-disabled';

    const PASSKEY_CREATED = 'passkey-created';
    const PASSKEY_DELETED = 'passkey-deleted';
    const PASSKEY_DISABLED = 'passkey-disabled';
    const PASSKEY_UPDATED = 'passkey-updated';

    /**
     * Configure Lara2fa to not register its routes.
     *
     * @return static
     */
    public static function ignoreRoutes()
    {
        static::$registersRoutes = false;

        return new static;
    }

    /**
     * Get a completion redirect path for a specific feature.
     *
     * @param  string  $redirect
     * @return string
     */
    public static function redirects(string $redirect, $default = null)
    {
        return config('lara2fa.redirects.'.$redirect) ?? $default ?? config('fortify.home');
    }

    public static function numberOfGeneratedRecoveryCodes(): int
    {
        return config('lara2fa-options.recovery-codes.numberOfCodesGenerated', 8);
    }

    public static function emailTwoFactorWindow(): int
    {
        return config('lara2fa-options.email-two-factor-authentication.window', 10);
    }

    public static function authenticatorAppTwoFactorWindow(): int
    {
        return config('lara2fa-options.authenticator-app-two-factor-authentication.window', 1);
    }


    /**
     * Get passkeys mode
     */
    public static function passkeysAuthenticationMode(): null|string
    {
        return Features::enabled(Features::passkeys()) ? (string) config('lara2fa-options.passkeys.authentication_mode', 'sfa') : null;
    }

    public static function canPasskeysUsedForTwoFactorAuthentication(): bool
    {
        return static::passkeysAuthenticationMode() === '2fa' || static::passkeysAuthenticationMode() === 'both';
    }

    public static function canPasskeysUsedForSingleFactorAuthentication(): bool
    {
        return static::passkeysAuthenticationMode() === 'sfa' || static::passkeysAuthenticationMode() === 'both';
    }

    public static function stack(): string
    {
        return config('lara2fa.stack', 'react');
    }

    public static function getView(string $viewName = ""): string
    {
        if (static::stack() === "react") {
            if ($viewName === "two-factor-settings")
                return "settings/two-factor";

            elseif ($viewName === "login")
                return "auth/login";

            elseif ($viewName === "reset-password")
                return "auth/reset-password";

            elseif ($viewName === "forgot-password")
                return "auth/forgot-password";

            elseif ($viewName === "verify-email")
                return "auth/verify-email";

            elseif ($viewName === "register")
                return "auth/register";

            elseif ($viewName === "two-factor-challenge")
                return "auth/two-factor-challenge";

            elseif ($viewName === "confirm-password")
                return "auth/confirm-password";

            else
                return $viewName;

        } elseif (static::stack() === "vue") {
            if ($viewName === "two-factor-settings")
                return "settings/TwoFactor";

            elseif ($viewName === "login")
                return "auth/Login";

            elseif ($viewName === "reset-password")
                return "auth/ResetPassword";

            elseif ($viewName === "forgot-password")
                return "auth/ForgotPassword";

            elseif ($viewName === "verify-email")
                return "auth/VerifyEmail";

            elseif ($viewName === "register")
                return "auth/Register";

            elseif ($viewName === "two-factor-challenge")
                return "auth/TwoFactorChallenge";

            elseif ($viewName === "confirm-password")
                return "auth/ConfirmPassword";

            else
                return $viewName;
        } else {
            return $viewName;
        }
    }
}