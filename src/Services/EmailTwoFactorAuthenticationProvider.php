<?php

namespace MustafaAwami\Lara2fa\Services;

use Carbon\Carbon;
use MustafaAwami\Lara2fa\Contracts\EmailTwoFactorAuthenticationProvider as EmailTwoFactorAuthenticationProviderContract;
use MustafaAwami\Lara2fa\Lara2fa;

class EmailTwoFactorAuthenticationProvider implements EmailTwoFactorAuthenticationProviderContract
{
    /**
     * Generate a new random code.
     *
     * @return string
     */
    public function generateRandomCode()
    {
        return rand(100000, 999999);
    }

    /**
     * Generate code expires date
     *
     * @return Carbon
     */
    public function generateExpiresAt()
    {
        $expireTimeWindow = Lara2fa::emailTwoFactorWindow();

        return now()->addMinutes($expireTimeWindow);
    }

    /**
     * Verify the given token.
     *
     * @param  string  $code
     * @param  string  $inputCode
     * @return bool
     */
    public function verify($code, $inputCode)
    {
        return ($inputCode == $code) ? true : false;
    }

    /**
     * Check if the code expaired.
     *
     * @return bool
     */
    public function isCodeExpired($codeExpiresAt)
    {
        return ($codeExpiresAt < now()) ? true : false;
    }
}
