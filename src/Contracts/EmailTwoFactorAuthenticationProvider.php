<?php

namespace Mustafa\Lara2fa\Contracts;

interface EmailTwoFactorAuthenticationProvider
{
    /**
     * Generate a new random code.
     *
     * @return string
     */
    public function generateRandomCode();

    /**
     * Generate code expires date
     * 
     * @return Carbon
     */
    public function generateExpiresAt();

    /**
     * Verify the given token.
     *
     * @param  string  $code
     * @param  string  $inputCode
     * @return bool
     */
    public function verify($code, $inputCode);

    /**
     * Check if the code expaired.
     *
     * @param $codeExpiresAt
     * @return bool
     */
    public function isCodeExpired($codeExpiresAt);

}