<?php

namespace MustafaAwami\Lara2fa\Http\Responses;

use Illuminate\Http\JsonResponse;
use MustafaAwami\Lara2fa\Contracts\AuthenticatorAppTwoFactorConfirmedResponse as AuthenticatorAppTwoFactorConfirmedResponseContract;
use MustafaAwami\Lara2fa\Lara2fa;

class AuthenticatorAppTwoFactorConfirmedResponse implements AuthenticatorAppTwoFactorConfirmedResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        return $request->wantsJson()
                    ? new JsonResponse('', 200)
                    : back()->with('status', Lara2fa::AUTHENTICATOR_APP_TWO_FACTOR_AUTHENTICATION_CONFIRMED);
    }
}
