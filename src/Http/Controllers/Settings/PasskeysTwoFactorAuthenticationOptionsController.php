<?php

namespace MustafaAwami\Lara2fa\Http\Controllers\Settings;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use MustafaAwami\Lara2fa\Models\Passkey;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;
use Illuminate\Contracts\Auth\StatefulGuard;
use Webauthn\AuthenticatorSelectionCriteria;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialCreationOptions;
use MustafaAwami\Lara2fa\Services\WebauthnJsonSerializer;

class PasskeysTwoFactorAuthenticationOptionsController extends Controller
{

    /**
     * Get passkey registration options
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    public function registerOptions(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        if (! $request->user()->canRegisterPasskey()) {
            throw ValidationException::withMessages([
                'name' => [__('You can’t add any more keys')],
            ]);
        }

        /** @var array<array-key,PublicKeyCredentialDescriptor> */
        $excludeCredentials = $request->user()->passkeys()->get()
                            ->map(fn (Passkey $passkey) => $passkey->data)
                            ->map(fn (PublicKeyCredentialSource $publicKeyCredentialSource) => $publicKeyCredentialSource->getPublicKeyCredentialDescriptor())
                            ->toArray() ?? [];

        $options = new PublicKeyCredentialCreationOptions(
            rp: new PublicKeyCredentialRpEntity(
                name: config('app.name'),
                id: parse_url(config('app.url'), PHP_URL_HOST),
                icon: config('lara2fa-options.passkeys.icon')
            ),
            user: new PublicKeyCredentialUserEntity(
                name: $request->user()->{Fortify::username()},
                id: $request->user()->getAuthIdentifier(),
                displayName: $request->user()->name ?? $request->user()->{Fortify::username()}
            ),
            challenge: \random_bytes((int) config('lara2fa-options.passkeys.challenge_length', 32)),
            authenticatorSelection : new AuthenticatorSelectionCriteria(
                authenticatorAttachment: config('lara2fa-options.passkeys.attachment_mode', 'null'),
                userVerification: config('lara2fa-options.passkeys.user_verification', 'preferred'),
                residentKey: config('lara2fa-options.passkeys.resident_key', 'preferred')
            ),
            attestation: config('lara2fa-options.passkeys.attestation_conveyance', 'none'),
            excludeCredentials: $excludeCredentials,
            timeout: ((int) config('lara2fa-options.passkeys.timeout', 60000))
        );

        Session::flash('passkey-registration-options', $options);

        return WebauthnJsonSerializer::serialize($options);
    }

    /**
     * Get passkey authentication options
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    public function authenticateOptions(Request $request)
    {
        $userModel = app(StatefulGuard::class)->getProvider()->getModel();

        $userId = null;

        if ($request->filled('email') && $user = $userModel::where(Fortify::username(), $request->email)->first()) 
            $userId = $user->id;
        else if ($request->session()->has('login.id'))
            $userId = $request->session()->get('login.id');

        
        $allowedCredentials = Passkey::query()->where('user_id', $userId)
                ->get()
                ->map(fn (Passkey $passkey) => $passkey->data)
                ->map(fn (PublicKeyCredentialSource $publicKeyCredentialSource) => $publicKeyCredentialSource->getPublicKeyCredentialDescriptor())
                ->toArray() ?? [];

        $options = new PublicKeyCredentialRequestOptions(
            challenge: Str::random(),
            rpId: parse_url(config('app.url'), PHP_URL_HOST),
            allowCredentials: $allowedCredentials,
            userVerification: config('lara2fa-options.passkeys.user_verification', 'preferred'),
            timeout: ((int) config('lara2fa-options.passkeys.timeout', 60000))
        );

        Session::flash('passkey-authentication-options', $options);

        return WebauthnJsonSerializer::serialize($options);
    }
}