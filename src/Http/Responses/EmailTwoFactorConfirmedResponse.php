<?php

namespace MustafaAwami\Lara2fa\Http\Responses;

use Illuminate\Http\JsonResponse;
use MustafaAwami\Lara2fa\Contracts\EmailTwoFactorConfirmedResponse as EmailTwoFactorConfirmedResponseContract;
use MustafaAwami\Lara2fa\Lara2fa;

class EmailTwoFactorConfirmedResponse implements EmailTwoFactorConfirmedResponseContract
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
                    : back()->with('status', Lara2fa::EMAIL_TWO_FACTOR_AUTHENTICATION_CONFIRMED);
    }
}
