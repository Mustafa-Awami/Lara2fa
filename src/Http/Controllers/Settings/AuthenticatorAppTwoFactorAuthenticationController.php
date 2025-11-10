<?php

namespace Mustafa\Lara2fa\Http\Controllers\Settings;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Database\Eloquent\Model;
use Mustafa\Lara2fa\Actions\DisableRecoveryCodes;
use Mustafa\Lara2fa\Actions\GenerateNewRecoveryCodes;
use Mustafa\Lara2fa\Contracts\AuthenticatorAppTwoFactorEnabledResponse;
use Mustafa\Lara2fa\Contracts\AuthenticatorAppTwoFactorDisabledResponse;
use Mustafa\Lara2fa\Contracts\AuthenticatorAppTwoFactorConfirmedResponse;
use Mustafa\Lara2fa\Actions\EnableAuthenticatorAppTwoFactorAuthentication;
use Mustafa\Lara2fa\Actions\ConfirmAuthenticatorAppTwoFactorAuthentication;
use Mustafa\Lara2fa\Actions\DisableAuthenticatorAppTwoFactorAuthentication;

class AuthenticatorAppTwoFactorAuthenticationController extends Controller
{
    /**
     * Enable authenticator app two factor authentication for the user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Mustafa\Lara2fa\Actions\EnableAuthenticatorAppTwoFactorAuthentication  $enable
     * @param  \Mustafa\Lara2fa\Actions\GenerateNewRecoveryCodes  $generateRecoveryCodes
     * @return \Mustafa\Lara2fa\Contracts\AuthenticatorAppTwoFactorEnabledResponse
     */
    public function store(Request $request, EnableAuthenticatorAppTwoFactorAuthentication $enable, GenerateNewRecoveryCodes $generateRecoveryCodes)
    {
        $enable($request->user(), $request->boolean('force', false));

        if ($request->user()->hasEnabledTwoFactorAuthentication() & !$request->user()->hasEnabledTwoFactorRecoveryCodes()) {
            $generateRecoveryCodes($request->user());
        }

        return app(AuthenticatorAppTwoFactorEnabledResponse::class);
    }

    /**
     * Confirm authenticator app two factor authentication for the user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Mustafa\Lara2fa\Actions\ConfirmAuthenticatorAppTwoFactorAuthentication  $confirm
     * @param  \Mustafa\Lara2fa\Actions\GenerateNewRecoveryCodes  $generateRecoveryCodes
     * @return \Mustafa\Lara2fa\Contracts\AuthenticatorAppTwoFactorConfirmedResponse
     */
    public function confirm(Request $request, ConfirmAuthenticatorAppTwoFactorAuthentication $confirm, GenerateNewRecoveryCodes $generateRecoveryCodes)
    {
        $confirm($request->user(), $request->input('code'));

        if ($request->user()->hasEnabledTwoFactorAuthentication() & !$request->user()->hasEnabledTwoFactorRecoveryCodes()) {
            $generateRecoveryCodes($request->user());
        }

        return app(AuthenticatorAppTwoFactorConfirmedResponse::class);
    }

    /**
     * Disable authenticator app two factor authentication for the user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Mustafa\Lara2fa\Actions\DisableAuthenticatorAppTwoFactorAuthentication  $disable
     * @return \Mustafa\Lara2fa\Contracts\AuthenticatorAppTwoFactorDisabledResponse
     */
    public function destroy(Request $request, DisableAuthenticatorAppTwoFactorAuthentication $disable, DisableRecoveryCodes $disableRecoveryCodes)
    {
        $disable($request->user());

        if (!$request->user()->hasEnabledTwoFactorAuthentication() & $request->user()->hasEnabledTwoFactorRecoveryCodes()) {
            $disableRecoveryCodes($request->user());
        }

        return app(AuthenticatorAppTwoFactorDisabledResponse::class);
    }

    /**
     * Get the SVG element for the user's two factor authentication QR code.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function qrCode(Request $request)
    {
        if (is_null($request->user()->two_factor_secret)) {
            return [];
        }

        return response()->json([
            'svg' => $request->user()->twoFactorQrCodeSvg(),
            'url' => $request->user()->twoFactorQrCodeUrl(),
        ]);
    }

    /**
     * Get the current user's two factor authentication setup / secret key.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function secretKey(Request $request)
    {
        if (is_null($request->user()->two_factor_secret)) {
            abort(404, 'Two factor authentication has not been enabled.');
        }

        return response()->json([
            'secretKey' => (Model::$encrypter ?? Crypt::getFacadeRoot())->decrypt($request->user()->two_factor_secret),
        ]);
    }
}
