<?php

namespace Mustafa\Lara2fa\Http\Controllers\Settings;

use Illuminate\Http\Request;
use Mustafa\Lara2fa\Features;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Database\Eloquent\Model;
use Mustafa\Lara2fa\Actions\DisableRecoveryCodes;
use Mustafa\Lara2fa\Actions\GenerateNewRecoveryCodes;
use Mustafa\Lara2fa\Contracts\RecoveryCodesDisabledResponse;
use Mustafa\Lara2fa\Contracts\RecoveryCodesGeneratedResponse;

class RecoveryCodeController extends Controller
{
    /**
     * Get the two factor authentication recovery codes for authenticated user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if (! (Features::recoveryCodesRequireTwoFactorAuthenticationEnabled() ?
            $request->user()->hasEnabledTwoFactorAuthentication() : true)) {
                return [];
        }

        if (! $request->user()->hasEnabledTwoFactorRecoveryCodes()) {
            return [];
        }

        return response()->json(json_decode((Model::$encrypter ?? Crypt::getFacadeRoot())->decrypt(
            $request->user()->two_factor_recovery_codes
        ), true));
    }

    /**
     * Generate a fresh set of two factor authentication recovery codes.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Mustafa\Lara2fa\Actions\GenerateNewRecoveryCodes  $generate
     * @return  \Mustafa\Lara2fa\Contracts\RecoveryCodesGeneratedResponse
     */
    public function store(Request $request, GenerateNewRecoveryCodes $generate)
    {
        if (! (Features::recoveryCodesRequireTwoFactorAuthenticationEnabled() ?
            $request->user()->hasEnabledTwoFactorAuthentication() : true)) {
                return null;
        }
        $generate($request->user());

        return app(RecoveryCodesGeneratedResponse::class);
    }

    /**
     * Delete the two factor authentication recovery codes for authenticated user
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return  \Mustafa\Lara2fa\Contracts\RecoveryCodesDisabledResponse
     */
    public function destroy(Request $request, DisableRecoveryCodes $disable)
    {
        if (! $request->user()->hasEnabledTwoFactorRecoveryCodes()) {
            return ;
        }

        $disable($request->user());

        return app(RecoveryCodesDisabledResponse::class);
    }
}
