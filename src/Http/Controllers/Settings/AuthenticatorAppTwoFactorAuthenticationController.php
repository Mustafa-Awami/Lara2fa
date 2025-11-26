<?php

namespace MustafaAwami\Lara2fa\Http\Controllers\Settings;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Crypt;
use MustafaAwami\Lara2fa\Actions\ConfirmAuthenticatorAppTwoFactorAuthentication;
use MustafaAwami\Lara2fa\Actions\DisableAuthenticatorAppTwoFactorAuthentication;
use MustafaAwami\Lara2fa\Actions\DisableRecoveryCodes;
use MustafaAwami\Lara2fa\Actions\EnableAuthenticatorAppTwoFactorAuthentication;
use MustafaAwami\Lara2fa\Actions\GenerateNewRecoveryCodes;
use MustafaAwami\Lara2fa\Contracts\AuthenticatorAppTwoFactorConfirmedResponse;
use MustafaAwami\Lara2fa\Contracts\AuthenticatorAppTwoFactorDisabledResponse;
use MustafaAwami\Lara2fa\Contracts\AuthenticatorAppTwoFactorEnabledResponse;

class AuthenticatorAppTwoFactorAuthenticationController extends Controller
{
    /**
     * Enable authenticator app two-factor authentication for the user.
     *
     * @return \MustafaAwami\Lara2fa\Contracts\AuthenticatorAppTwoFactorEnabledResponse
     */
    public function store(Request $request, EnableAuthenticatorAppTwoFactorAuthentication $enable, GenerateNewRecoveryCodes $generateRecoveryCodes)
    {
        $enable($request->user(), $request->boolean('force', false));

        if ($request->user()->hasEnabledTwoFactorAuthentication() & ! $request->user()->hasEnabledTwoFactorRecoveryCodes()) {
            $generateRecoveryCodes($request->user());
        }

        return app(AuthenticatorAppTwoFactorEnabledResponse::class);
    }

    /**
     * Confirm authenticator app two-factor authentication for the user.
     *
     * @return \MustafaAwami\Lara2fa\Contracts\AuthenticatorAppTwoFactorConfirmedResponse
     */
    public function confirm(Request $request, ConfirmAuthenticatorAppTwoFactorAuthentication $confirm, GenerateNewRecoveryCodes $generateRecoveryCodes)
    {
        $confirm($request->user(), $request->input('code'));

        if ($request->user()->hasEnabledTwoFactorAuthentication() & ! $request->user()->hasEnabledTwoFactorRecoveryCodes()) {
            $generateRecoveryCodes($request->user());
        }

        return app(AuthenticatorAppTwoFactorConfirmedResponse::class);
    }

    /**
     * Disable authenticator app two-factor authentication for the user.
     *
     * @return \MustafaAwami\Lara2fa\Contracts\AuthenticatorAppTwoFactorDisabledResponse
     */
    public function destroy(Request $request, DisableAuthenticatorAppTwoFactorAuthentication $disable, DisableRecoveryCodes $disableRecoveryCodes)
    {
        $disable($request->user());

        if (! $request->user()->hasEnabledTwoFactorAuthentication() & $request->user()->hasEnabledTwoFactorRecoveryCodes()) {
            $disableRecoveryCodes($request->user());
        }

        return app(AuthenticatorAppTwoFactorDisabledResponse::class);
    }

    /**
     * Get the SVG element for the user's two-factor authentication QR code.
     *
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
     * Get the current user's two-factor authentication setup / secret key.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function secretKey(Request $request)
    {
        if (is_null($request->user()->two_factor_secret)) {
            abort(404, 'Two-factor authentication has not been enabled.');
        }

        return response()->json([
            'secretKey' => (Model::$encrypter ?? Crypt::getFacadeRoot())->decrypt($request->user()->two_factor_secret),
        ]);
    }
}
