<?php

namespace Mustafa\Lara2fa\Http\Controllers\Auth;

use Inertia\Inertia;
use Mustafa\Lara2fa\Lara2fa;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Auth\StatefulGuard;
use Mustafa\Lara2fa\Events\RecoveryCodeReplaced;
use Illuminate\Http\Exceptions\HttpResponseException;
use Mustafa\Lara2fa\Events\TwoFactorAuthenticationFailed;
use Mustafa\Lara2fa\Contracts\FailedTwoFactorLoginResponse;
use Mustafa\Lara2fa\Events\TwoFactorAuthenticationSuccessful;
use Mustafa\Lara2fa\Http\Requests\Auth\TwoFactorLoginRequest;

class TwoFactorAuthenticatedSessionController extends Controller
{
    /**
     * The guard implementation.
     */
    protected $guard;

    /**
     * Create a new controller instance.
     *
     * @param  \Illuminate\Contracts\Auth\StatefulGuard  $guard
     * @return void
     */
    public function __construct(StatefulGuard $guard)
    {
        $this->guard = $guard;
    }

    /**
     * Show the two factor authentication challenge view.
     *
     * @param  \Mustafa\Lara2fa\Http\Requests\TwoFactorLoginRequest  $request
     */
    public function create(TwoFactorLoginRequest $request)
    {
        if (! $request->hasChallengedUser()) {
            throw new HttpResponseException(redirect()->route('login'));
        }

        $model = $this->guard->getProvider()->getModel();

        if (! $request->session()->has('login.id') ||
            ! $user = $model::find($request->session()->get('login.id'))) {
            throw new HttpResponseException(
                app(FailedTwoFactorLoginResponse::class)->toResponse($this)
            );
        }

        $twoFactorMethod = '';
        $numberOfEnableOptions = 0;

        if (Lara2fa::canPasskeysUsedForTwoFactorAuthentication() && $user->hasEnabledPasskeyAuthentication()) {
            // $twoFactorMethod = 'passkeys';
            $numberOfEnableOptions++;
        }
        if ($user->hasEnabledTwoFactorRecoveryCodes()) {
            // $twoFactorMethod = 'recovery_code';
            $numberOfEnableOptions++;
        } 
        if ($user->hasEnabledEmailTwoFactorAuthentication()) {
            // $twoFactorMethod = 'email_code';
            $numberOfEnableOptions++;
        } 
        if ($user->hasEnabledAuthenticatorAppTwoFactorAuthentication()) {
            // $twoFactorMethod = 'code';
            $numberOfEnableOptions++;
        } 

        $twoFactorEnabled = [
            'authenticatorApp' => $user->hasEnabledAuthenticatorAppTwoFactorAuthentication(),
            'email' => $user->hasEnabledEmailTwoFactorAuthentication(),
            'recoveryCodes' => $user->hasEnabledTwoFactorRecoveryCodes(),
            'passkeys' => Lara2fa::canPasskeysUsedForTwoFactorAuthentication() && $user->hasEnabledPasskeyAuthentication(),
        ];

        if (Lara2fa::stack() === "react") {
            $view = "auth/two-factor-challenge";
        } elseif (Lara2fa::stack() === "vue") {
            $view = "auth/TwoFactorChallenge";
        }

        return Inertia::render($view,[
            'twoFactorMethod' => $twoFactorMethod,
            'twoFactorEnabled' => $twoFactorEnabled,
        ]);
    }

    /**
     * Attempt to authenticate a new session using the two factor authentication code.
     *
     * @param  \Mustafa\Lara2fa\Http\Requests\TwoFactorLoginRequest  $request
     * @return mixed
     */
    public function store(TwoFactorLoginRequest $request)
    {
        $user = $request->challengedUser();

        if ($code = $request->validRecoveryCode()) {
            $user->replaceRecoveryCode($code);
            event(new RecoveryCodeReplaced($user, $code));
        } elseif ($request->filled('email_code') & $request->hasValidEmailCode() == 'invalid') {
            event(new TwoFactorAuthenticationFailed($user));
            return app(FailedTwoFactorLoginResponse::class)->toResponse($request->merge(['email_code_error_message' => __('The provided email two factor authentication code was invalid.')]));
        } elseif ($request->filled('email_code') & $request->hasValidEmailCode() == 'expaired') {
            event(new TwoFactorAuthenticationFailed($user));
            return app(FailedTwoFactorLoginResponse::class)->toResponse($request->merge(['email_code_error_message' => __('The provided email two factor authentication code is expaired.')]));
        } elseif ($request->filled('code') & ! $request->hasValidCode()) {
            event(new TwoFactorAuthenticationFailed($user));
            return app(FailedTwoFactorLoginResponse::class)->toResponse($request);
        } elseif ($request->filled('recovery_code') & ! $request->validRecoveryCode()) {
            event(new TwoFactorAuthenticationFailed($user));
            return app(FailedTwoFactorLoginResponse::class)->toResponse($request);
        } elseif (!$request->filled('code') & !$request->filled('email_code') & !$request->filled('recovery_code')) {
            event(new TwoFactorAuthenticationFailed($user));
            return app(FailedTwoFactorLoginResponse::class)->toResponse($request);
        }
        
        if ($request->filled('email_code')) {
            $user->resetEmailTwoFactorCode();
        }

        event(new TwoFactorAuthenticationSuccessful($user));

        $this->guard->login($user, $request->remember());

        $request->session()->regenerate();

        return redirect()->intended(Lara2fa::redirects('two-factor-login'));
    }
}
