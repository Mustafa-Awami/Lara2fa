<?php

namespace Mustafa\Lara2fa\Http\Responses;

use Illuminate\Http\JsonResponse;
use Mustafa\Lara2fa\Contracts\EmailTwoFactorEnabledResponse as EmailTwoFactorEnabledResponseContract;
use Mustafa\Lara2fa\Lara2fa;

class EmailTwoFactorEnabledResponse implements EmailTwoFactorEnabledResponseContract
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
                    : back()->with('status', Lara2fa::EMAIL_TWO_FACTOR_AUTHENTICATION_ENABLED);
    }
}
