<?php

namespace Mustafa\Lara2fa\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Mustafa\Lara2fa\Lara2fa;
use Mustafa\Lara2fa\Services\RecoveryCode;
use Mustafa\Lara2fa\Events\RecoveryCodesGenerated;

class GenerateNewRecoveryCodes
{
    /**
     * Generate new recovery codes for the user.
     *
     * @param  mixed  $user
     * @return void
     */
    public function __invoke($user)
    {
        $numberOfCodes = Lara2fa::numberOfGeneratedRecoveryCodes();

        $user->forceFill([
            'two_factor_recovery_codes' => (Model::$encrypter ?? Crypt::getFacadeRoot())->encrypt(json_encode(Collection::times($numberOfCodes, function () {
                return RecoveryCode::generate();
            })->all())),
        ])->save();

        RecoveryCodesGenerated::dispatch($user);
    }
}
