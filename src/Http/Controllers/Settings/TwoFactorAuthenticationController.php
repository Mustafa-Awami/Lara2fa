<?php

namespace MustafaAwami\Lara2fa\Http\Controllers\Settings;

use Inertia\Inertia;
use Illuminate\Http\Request;
use MustafaAwami\Lara2fa\Lara2fa;
use MustafaAwami\Lara2fa\Features;
use App\Http\Controllers\Controller;

class TwoFactorAuthenticationController extends Controller
{
    /**
     * Show the user's two-factor authentication settings page.
     */
    public function show(Request $request)
    {
        $props = [
            'userEnabledtwoFactor' => $request->user()->hasEnabledTwoFactorAuthentication(),
            'canManageTwoFactorAuthentication' => Features::canManageTwoFactorAuthentication() ? [
                'authenticatorApp' => Features::enabled(Features::authenticatorAppTwoFactorAuthentication()) ? [
                    'userEnabled' => $request->user()->hasEnabledAuthenticatorAppTwoFactorAuthentication(),
                    'requirePasswordConfirmation' => Features::confirmsPasswordAuthenticatorAppTwoFactorAuthentication(),
                    'requiresConfirmation' => Features::confirmsAuthenticatorAppTwoFactorAuthentication()
                ] : false,
                'email' => Features::enabled(Features::emailTwoFactorAuthentication()) ? [
                    'userEnabled' => $request->user()->hasEnabledEmailTwoFactorAuthentication(),
                    'requirePasswordConfirmation' => Features::confirmsPasswordEmailTwoFactorAuthentication(),
                    'requiresConfirmation' => Features::confirmsEmailTwoFactorAuthentication()
                ] : false,
                'passkeys' => Features::enabled(Features::passkeys()) ? [
                    'userEnabled' => $request->user()->hasEnabledPasskeyAuthentication(),
                    'requirePasswordConfirmation' => Features::confirmsPasswordPasskeys(),
                ] : false,
            ] : false,
            'canManageAdditionalAuthentication' => Features::canManageAdditionalAuthentication() ? [
                'recoveryCodes' => Features::enabled(Features::recoveryCodes()) ? [
                    'userEnabled' => $request->user()->hasEnabledTwoFactorRecoveryCodes(),
                    'confirmsPasswordRecoveryCode' => Features::confirmsPasswordRecoveryCode()
                ] : false
            ] : false,
            'recoveryCodesRequireTwoFactorEnabled' => Features::recoveryCodesRequireTwoFactorAuthenticationEnabled(),
            'status' => $request->session()->get('status'),
        ];
        
        return Inertia::render(Lara2fa::getView("two-factor-settings"), $props);
    }
}
