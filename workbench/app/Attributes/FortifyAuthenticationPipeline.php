<?php

namespace App\Attributes;

use Attribute;
use Laravel\Fortify\Actions\AttemptToAuthenticate;
use Laravel\Fortify\Actions\CanonicalizeUsername;
use Laravel\Fortify\Actions\EnsureLoginIsNotThrottled;
use Laravel\Fortify\Actions\PrepareAuthenticatedSession;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use MustafaAwami\Lara2fa\Actions\RedirectIfTwoFactorAuthenticatable as Lara2faRedirectIfTwoFactorAuthenticatable;
use MustafaAwami\Lara2fa\Features as Lara2faFeatures;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class FortifyAuthenticationPipeline
{
    /**
     * Construct a new attribute.
     */
    public function __construct()
    {
        Fortify::authenticateThrough(function () {
            return array_filter([
                config('fortify.limiters.login') ? null : EnsureLoginIsNotThrottled::class,
                config('fortify.lowercase_usernames') ? CanonicalizeUsername::class : null,
                Features::enabled(Features::twoFactorAuthentication()) ? RedirectIfTwoFactorAuthenticatable::class :
                (Lara2faFeatures::canManagetwoFactorAuthentication() ? Lara2faRedirectIfTwoFactorAuthenticatable::class : null),
                AttemptToAuthenticate::class,
                PrepareAuthenticatedSession::class,
            ]);
        });
    }
}
