<?php

namespace Mustafa\Lara2fa\Http\Controllers\Settings;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\StatefulGuard;
use Mustafa\Lara2fa\Actions\DisableRecoveryCodes;
use Illuminate\Http\Exceptions\HttpResponseException;
use Mustafa\Lara2fa\Actions\GenerateNewRecoveryCodes;
use Mustafa\Lara2fa\Contracts\EmailTwoFactorNotifyResponse;
use Mustafa\Lara2fa\Contracts\FailedTwoFactorLoginResponse;
use Mustafa\Lara2fa\Contracts\EmailTwoFactorEnabledResponse;
use Mustafa\Lara2fa\Contracts\EmailTwoFactorDisabledResponse;
use Mustafa\Lara2fa\Contracts\EmailTwoFactorConfirmedResponse;
use Mustafa\Lara2fa\Actions\EnableEmailTwoFactorAuthentication;
use Mustafa\Lara2fa\Actions\ConfirmEmailTwoFactorAuthentication;
use Mustafa\Lara2fa\Actions\DisableEmailTwoFactorAuthentication;
use Mustafa\Lara2fa\Contracts\EmailTwoFactorAuthenticationProvider;

class EmailTwoFactorAuthenticationController extends Controller
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
     * Enable email two factor authentication for the user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Mustafa\Lara2fa\Actions\EnableEmailTwoFactorAuthentication  $enable
     * @param  \Mustafa\Lara2fa\Actions\GenerateNewRecoveryCodes  $generateRecoveryCodes
     * @return \Mustafa\Lara2fa\Contracts\EmailTwoFactorEnabledResponse
     */
    public function store(Request $request, EnableEmailTwoFactorAuthentication $enable, GenerateNewRecoveryCodes $generateRecoveryCodes)
    {
        $enable($request->user(), $request->boolean('force', false));

        if ($request->user()->hasEnabledTwoFactorAuthentication() & !$request->user()->hasEnabledTwoFactorRecoveryCodes()) {
            $generateRecoveryCodes($request->user());
        }

        return app(EmailTwoFactorEnabledResponse::class);
    }

    /**
     * Confirm email two factor authentication for the user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Mustafa\Lara2fa\Actions\ConfirmEmailTwoFactorAuthentication  $confirm
     * @param  \Mustafa\Lara2fa\Actions\GenerateNewRecoveryCodes  $generateRecoveryCodes
     * @return \Mustafa\Lara2fa\Contracts\EmailTwoFactorConfirmedResponse
     */
    public function confirm(Request $request, ConfirmEmailTwoFactorAuthentication $confirm, GenerateNewRecoveryCodes $generateRecoveryCodes)
    {
        $confirm($request->user(), $request->input('code'));

        if ($request->user()->hasEnabledTwoFactorAuthentication() & !$request->user()->hasEnabledTwoFactorRecoveryCodes()) {
            $generateRecoveryCodes($request->user());
        }

        return app(EmailTwoFactorConfirmedResponse::class);
    }

    /**
     * Disable authenticator app two factor authentication for the user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Mustafa\Lara2fa\Actions\DisableEmailTwoFactorAuthentication  $disable
     * @return \Mustafa\Lara2fa\Contracts\EmailTwoFactorDisabledResponse
     */
    public function destroy(Request $request, DisableEmailTwoFactorAuthentication $disable, DisableRecoveryCodes $disableRecoveryCodes)
    {
        $disable($request->user());

        if (!$request->user()->hasEnabledTwoFactorAuthentication() & $request->user()->hasEnabledTwoFactorRecoveryCodes()) {
            $disableRecoveryCodes($request->user());
        }

        return app(EmailTwoFactorDisabledResponse::class);
    }

    /**
     * Send a new email two factor authentication code.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Mustafa\Lara2fa\Contracts\EmailTwoFactorDisabledResponse
     */
    public function notify(Request $request)
    {
        $newCode = app(EmailTwoFactorAuthenticationProvider::class)->generateRandomCode();
        $codeExpiresAt = app(EmailTwoFactorAuthenticationProvider::class)->generateExpiresAt();

        if (! $user = $request->user()) {
            $model = $this->guard->getProvider()->getModel();
            if (! $request->session()->has('login.id') ||
                ! $user = $model::find($request->session()->get('login.id'))) {
                throw new HttpResponseException(
                    app(FailedTwoFactorLoginResponse::class)->toResponse($request)
                );
            }
        }

        $user->forceFill([
            'email_two_factor_code' => (Model::$encrypter ?? Crypt::getFacadeRoot())->encrypt($newCode),
            'email_two_factor_code_expires_at' => $codeExpiresAt,
        ])->save();

        $user->sendEmailTwoFactorCode($newCode, $request->action_url);

        return app(EmailTwoFactorNotifyResponse::class);
    }
}
