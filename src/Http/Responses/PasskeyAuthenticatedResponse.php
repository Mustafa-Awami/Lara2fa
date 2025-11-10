<?php

namespace MustafaAwami\Lara2fa\Http\Responses;

use Illuminate\Http\JsonResponse;
use MustafaAwami\Lara2fa\Contracts\PasskeyAuthenticatedResponse as PasskeyAuthenticatedResponseContract;
use MustafaAwami\Lara2fa\Lara2fa;

class PasskeyAuthenticatedResponse implements PasskeyAuthenticatedResponseContract
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
                    : redirect()->intended(Lara2fa::redirects('two-factor-login'));
    }
}
