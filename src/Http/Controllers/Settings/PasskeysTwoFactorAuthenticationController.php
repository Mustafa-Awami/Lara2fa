<?php

namespace MustafaAwami\Lara2fa\Http\Controllers\Settings;

use Illuminate\Http\Request;
use Webauthn\PublicKeyCredential;
use MustafaAwami\Lara2fa\Models\Passkey;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Webauthn\AuthenticatorAssertionResponse;
use Illuminate\Validation\ValidationException;
use Webauthn\AuthenticatorAttestationResponse;
use MustafaAwami\Lara2fa\Actions\DisableRecoveryCodes;
use MustafaAwami\Lara2fa\Services\WebauthnJsonSerializer;
use MustafaAwami\Lara2fa\Actions\GenerateNewRecoveryCodes;
use MustafaAwami\Lara2fa\Contracts\PasskeyCreatedResponse;
use MustafaAwami\Lara2fa\Contracts\PasskeyDeletedResponse;
use MustafaAwami\Lara2fa\Contracts\PasskeyUpdatedResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;
use MustafaAwami\Lara2fa\Contracts\PasskeyDisapledResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use MustafaAwami\Lara2fa\Contracts\PasskeyAuthenticatedResponse;
use MustafaAwami\Lara2fa\Events\PasskeyCreated;
use MustafaAwami\Lara2fa\Events\PasskeyUpdated;
use MustafaAwami\Lara2fa\Events\PasskeyDeleted;
use MustafaAwami\Lara2fa\Events\PasskeyDisabled;

class PasskeysTwoFactorAuthenticationController extends Controller
{

    /**
     * Get the user passkeys.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function index(Request $request)
    {
        return response()->json([
            'passkeys' => $request->user()->passkeysCollection()
        ]);
    }

    /**
     * Create a new passkey for the user.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \MustafaAwami\Lara2fa\Actions\GenerateNewRecoveryCodes  $generateRecoveryCodes
     * @return \MustafaAwami\Lara2fa\Contracts\PasskeyCreatedResponse
     */
    public function store(Request $request, GenerateNewRecoveryCodes $generateRecoveryCodes)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'passkey' => ['required', 'json']
        ]);

        if (! $request->user()->canRegisterPasskey()) {
            throw ValidationException::withMessages([
                'name' => [__('You can’t add any more keys')],
            ]);
        }

        $publicKeyCredential = WebauthnJsonSerializer::deserialize($data['passkey'], PublicKeyCredential::class);

        if (! $publicKeyCredential->response instanceof AuthenticatorAttestationResponse) {
            throw ValidationException::withMessages([
                'name' => "Passkey registration failed"
            ])->errorBag('createPasskey');
        }

        try {
            $publicKeyCredentialSource = AuthenticatorAttestationResponseValidator::create(
                (new CeremonyStepManagerFactory())->creationCeremony()
            )->check(
                authenticatorAttestationResponse: $publicKeyCredential->response,
                publicKeyCredentialCreationOptions: Session::get('passkey-registration-options'),
                host: $request->getHost(),
            );
        } catch (\Throwable $th) {
            throw ValidationException::withMessages([
                'name' => $th->getMessage()
            ])->errorBag('createPasskey');
        }

        $publicKeyCredentialSourceSerialized = json_decode(WebauthnJsonSerializer::serialize($publicKeyCredentialSource));
        
        $request->user()->passkeys()->create([
            'name' => $data['name'],
            'credential_id' => $publicKeyCredentialSourceSerialized->publicKeyCredentialId,
            'data' => $publicKeyCredentialSource
        ]);

        PasskeyCreated::dispatch($request->user());

        if ($request->user()->hasEnabledTwoFactorAuthentication() & !$request->user()->hasEnabledTwoFactorRecoveryCodes()) {
            $generateRecoveryCodes($request->user());
        }

        return app(PasskeyCreatedResponse::class);
    }

    /**
     * Update the passkey for the user.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \MustafaAwami\Lara2fa\Models\Passkey  $passkey
     * @return \MustafaAwami\Lara2fa\Contracts\PasskeyUpdatedResponse
     */
    public function update(Request $request, Passkey $passkey)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $passkey->update([
            'name' => $data['name']
        ]);

        PasskeyUpdated::dispatch($request->user());

        return app(PasskeyUpdatedResponse::class);
    }

    /**
     * Authenticate the user with the given passkey.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \MustafaAwami\Lara2fa\Contracts\PasskeyAuthenticatedResponse
     */
    public function authenticate(Request $request)
    {
        $data = $request->validate([
            'passkey' => ['required', 'json']
        ]);

        $publicKeyCredential = WebauthnJsonSerializer::deserialize($data['passkey'], PublicKeyCredential::class);

        if (! $publicKeyCredential->response instanceof AuthenticatorAssertionResponse) {
            throw ValidationException::withMessages(['passkey' => 'Passkey authentication failed.']);
        }

        $passkey = Passkey::query()->firstWhere('credential_id', json_decode($data['passkey'])->rawId);

        if (! $passkey) {
            throw ValidationException::withMessages(['passkey' => 'This passkey is not valid.']);
        }

        try {
            $publicKeyCredentialSource = AuthenticatorAssertionResponseValidator::create(
                (new CeremonyStepManagerFactory())->requestCeremony()
            )->check(
                publicKeyCredentialSource: $passkey->data,
                authenticatorAssertionResponse: $publicKeyCredential->response,
                publicKeyCredentialRequestOptions: Session::get('passkey-authentication-options'),
                host: $request->getHost(),
                userHandle: null,
            );
        } catch (\Throwable $th) {
            throw ValidationException::withMessages([
                'passkey' => $th->getMessage()
            ]);
        }

        $publicKeyCredentialSourceSerialized = json_decode(WebauthnJsonSerializer::serialize($publicKeyCredentialSource));

        $passkey->update([
            'credential_id' => $publicKeyCredentialSourceSerialized->publicKeyCredentialId,
            'data' => $publicKeyCredentialSource
        ]);

        Auth::loginUsingId($passkey->user_id);
        $request->session()->regenerate();

        return app(PasskeyAuthenticatedResponse::class);
    }

    /**
     * Delete the passkey for the user.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \MustafaAwami\Lara2fa\Models\Passkey  $passkey
     * @param  \MustafaAwami\Lara2fa\Actions\DisableEmailTwoFactorAuthentication  $disable
     * @return \MustafaAwami\Lara2fa\Contracts\PasskeyDeletedResponse
     */
    public function destroy(Request $request, Passkey $passkey, DisableRecoveryCodes $disableRecoveryCodes)
    {
        $passkey->delete();

        if (!$request->user()->hasEnabledTwoFactorAuthentication() & $request->user()->hasEnabledTwoFactorRecoveryCodes()) {
            $disableRecoveryCodes($request->user());
        }

        PasskeyDeleted::dispatch($request->user());

        return app(PasskeyDeletedResponse::class);
    }

    /**
     * Delete all passkeys for the user.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \MustafaAwami\Lara2fa\Actions\DisableEmailTwoFactorAuthentication  $disable
     * @return \MustafaAwami\Lara2fa\Contracts\PasskeyDisapledResponse
     */
    public function disable(Request $request, DisableRecoveryCodes $disableRecoveryCodes)
    {
        $request->user()->passkeys()->delete();

        if (!$request->user()->hasEnabledTwoFactorAuthentication() & $request->user()->hasEnabledTwoFactorRecoveryCodes()) {
            $disableRecoveryCodes($request->user());
        }

        PasskeyDisabled::dispatch($request->user());

        return app(PasskeyDisapledResponse::class);
    }
}