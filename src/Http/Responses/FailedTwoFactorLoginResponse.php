<?php

namespace MustafaAwami\Lara2fa\Http\Responses;

use Illuminate\Validation\ValidationException;
use MustafaAwami\Lara2fa\Contracts\FailedTwoFactorLoginResponse as FailedTwoFactorLoginResponseContract;

class FailedTwoFactorLoginResponse implements FailedTwoFactorLoginResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        if ($request->filled('email_code')){
            [$key, $message] = ['email_code', $request->email_code_error_message];
        } elseif ($request->filled('recovery_code')) {
            [$key, $message] = ['recovery_code', __('The provided two factor recovery code was invalid.')];
        } elseif ($request->filled('code')) {
            [$key, $message] = ['code', __('The provided authenticator app two factor authentication code was invalid.')];
        } else {
            [$key, $message] = ['empty', __('Empty! please provide the two factor authentication code')];
        }

        if ($request->wantsJson()) {
            throw ValidationException::withMessages([
                $key => [$message],
            ]);
        }

        return redirect()->route('two-factor.login')->withErrors([$key => $message]);
    }
}