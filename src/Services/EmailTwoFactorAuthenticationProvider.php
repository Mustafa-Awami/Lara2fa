<?php

namespace Mustafa\Lara2fa\Services;

use Carbon\Carbon;
use Mustafa\Lara2fa\Lara2fa;
use Mustafa\Lara2fa\Contracts\EmailTwoFactorAuthenticationProvider as EmailTwoFactorAuthenticationProviderContract;

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
        // dd($code, $inputCode, ($inputCode == $code)); 
        return ($inputCode == $code) ? true : false;
    }

    /**
     * Check if the code expaired.
     *
     * @param $codeExpiresAt
     * @return bool
     */
    public function isCodeExpired($codeExpiresAt)
    {
        return ($codeExpiresAt < now()) ? true : false;
    }
}