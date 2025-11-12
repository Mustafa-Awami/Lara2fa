<?php

namespace MustafaAwami\Lara2fa\Http\Controllers\Auth;

use Inertia\Inertia;
use MustafaAwami\Lara2fa\Lara2fa;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Auth\StatefulGuard;
use MustafaAwami\Lara2fa\Events\RecoveryCodeReplaced;
use Illuminate\Http\Exceptions\HttpResponseException;
use MustafaAwami\Lara2fa\Events\TwoFactorAuthenticationFailed;
use MustafaAwami\Lara2fa\Contracts\FailedTwoFactorLoginResponse;
use MustafaAwami\Lara2fa\Events\TwoFactorAuthenticationSuccessful;
use MustafaAwami\Lara2fa\Http\Requests\Auth\TwoFactorLoginRequest;
use Laravel\Fortify\Contracts\TwoFactorChallengeViewResponse;

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
     * @param  \MustafaAwami\Lara2fa\Http\Requests\TwoFactorLoginRequest  $request
     * @return \Laravel\Fortify\Contracts\TwoFactorChallengeViewResponse
     */
    public function create(TwoFactorLoginRequest $request)
    {
        if (! $request->hasChallengedUser()) {
            throw new HttpResponseException(redirect()->route('login'));
        }

        return app(TwoFactorChallengeViewResponse::class);
    }

    /**
     * Attempt to authenticate a new session using the two factor authentication.
     *
     * @param  \MustafaAwami\Lara2fa\Http\Requests\TwoFactorLoginRequest  $request
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
