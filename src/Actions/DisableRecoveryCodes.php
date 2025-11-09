<?php

namespace Mustafa\Lara2fa\Actions;

use Mustafa\Lara2fa\Events\RecoveryCodesDisabled;

class DisableRecoveryCodes
{
    /**
     * Disable recovery codes for the user.
     *
     * @param  mixed  $user
     * @return void
     */
    public function __invoke($user)
    {
        $user->forceFill([
            'two_factor_recovery_codes' => null
        ])->save();

        RecoveryCodesDisabled::dispatch($user);
    }
}
