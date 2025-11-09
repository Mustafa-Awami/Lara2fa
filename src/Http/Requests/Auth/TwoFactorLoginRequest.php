<?php

namespace Mustafa\Lara2fa\Http\Requests\Auth;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\Exceptions\HttpResponseException;
use Mustafa\Lara2fa\Contracts\FailedTwoFactorLoginResponse;
use Mustafa\Lara2fa\Contracts\EmailTwoFactorAuthenticationProvider;
use Mustafa\Lara2fa\Contracts\AuthenticatorAppTwoFactorAuthenticationProvider;

class TwoFactorLoginRequest extends FormRequest
{
    /**
     * The user attempting the two factor challenge.
     *
     * @var mixed
     */
    protected $challengedUser;

    /**
     * Indicates if the user wished to be remembered after login.
     *
     * @var bool
     */
    protected $remember;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => 'nullable|string',
            'recovery_code' => 'nullable|string',
            'email_code' => 'nullable|string',
        ];
    }

    /**
     * Determine if the request has a valid two factor code.
     *
     * @return bool
     */
    public function hasValidCode()
    {
        return $this->code && tap(app(AuthenticatorAppTwoFactorAuthenticationProvider::class)->verify(
            (Model::$encrypter ?? Crypt::getFacadeRoot())->decrypt($this->challengedUser()->two_factor_secret), $this->code
        ), function ($result) {
            if ($result) {
                $this->session()->forget('login.id');
            }
        });
    }

    /**
     * Determine if the request has a valid two factor email code.
     *
     * @return string
     */
    public function hasValidEmailCode()
    {
        $user = $this->challengedUser();
        if (empty($user->email_two_factor_code) ||
            empty($this->email_code) ||
            ! app(EmailTwoFactorAuthenticationProvider::class)->verify((Model::$encrypter ?? Crypt::getFacadeRoot())->decrypt($user->email_two_factor_code), $this->email_code)) {

            return 'invalid';
        } elseif (empty($user->email_two_factor_code_expires_at) || 
            app(EmailTwoFactorAuthenticationProvider::class)->isCodeExpired($user->email_two_factor_code_expires_at)){

            return 'expaired';
        }

        $this->session()->forget('login.id');
        return 'valid';
    }

    /**
     * Get the valid recovery code if one exists on the request.
     *
     * @return string|null
     */
    public function validRecoveryCode()
    {
        if (! $this->recovery_code) {
            return;
        }

        return tap(collect($this->challengedUser()->recoveryCodes())->first(function ($code) {
            return hash_equals($code, $this->recovery_code) ? $code : null;
        }), function ($code) {
            if ($code) {
                $this->session()->forget('login.id');
            }
        });
    }

    /**
     * Determine if there is a challenged user in the current session.
     *
     * @return bool
     */
    public function hasChallengedUser()
    {
        if ($this->challengedUser) {
            return true;
        }

        $model = app(StatefulGuard::class)->getProvider()->getModel();

        return $this->session()->has('login.id') &&
            $model::find($this->session()->get('login.id'));
    }

    /**
     * Get the user that is attempting the two factor challenge.
     *
     * @return mixed
     */
    public function challengedUser()
    {
        if ($this->challengedUser) {
            return $this->challengedUser;
        }

        
        $model = app(StatefulGuard::class)->getProvider()->getModel();
        
        if (! $this->session()->has('login.id') ||
            ! $user = $model::find($this->session()->get('login.id'))) {
            throw new HttpResponseException(
                app(FailedTwoFactorLoginResponse::class)->toResponse($this)
            );
        }

        return $this->challengedUser = $user;
    }

    /**
     * Determine if the user wanted to be remembered after login.
     *
     * @return bool
     */
    public function remember()
    {
        if (! $this->remember) {
            $this->remember = $this->session()->pull('login.remember', false);
        }

        return $this->remember;
    }
}
