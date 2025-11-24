<?php

namespace MustafaAwami\Lara2fa\Tests;

use Laravel\Fortify\Fortify;
use Laravel\Fortify\Features;
use MustafaAwami\Lara2fa\Tests\TestCase;
use Orchestra\Testbench\Attributes\WithConfig;
use Laravel\Fortify\Actions\CanonicalizeUsername;
use Orchestra\Testbench\Attributes\WithMigration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Actions\AttemptToAuthenticate;
use Orchestra\Testbench\Attributes\DefineEnvironment;
use Laravel\Fortify\Actions\EnsureLoginIsNotThrottled;
use Laravel\Fortify\Actions\PrepareAuthenticatedSession;
use MustafaAwami\Lara2fa\Tests\Models\UserWithTwoFactor;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use MustafaAwami\Lara2fa\Features as Lara2faFeatures;
use MustafaAwami\Lara2fa\Actions\RedirectIfTwoFactorAuthenticatable as Lara2faRedirectIfTwoFactorAuthenticatable;

#[WithMigration]
#[WithConfig('auth.providers.users.model', UserWithTwoFactor::class)]
#[DefineEnvironment('withFortifyTwoFactorAuthenticationDisabled')]
class AuthenticatedSessionControllerWithTwoFactorTest extends TestCase
{
    use RefreshDatabase;
    
    #[DefineEnvironment('withAuthenticatorAppTwoFactorAuthentication')]
    public function test_user_is_redirected_to_challenge_when_using_two_factor_authentication()
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
        
        UserWithTwoFactor::forceCreate([
            'name' => 'Mustafa Awami',
            'email' => 'mustafa.awami1@gmail.com',
            'password' => bcrypt('secret'),
            'two_factor_secret' => 'test-secret',
        ]);

        // dd(config('lara2fa-options'));

        $response = $this->withoutExceptionHandling()->post('/login', [
            'email' => 'mustafa.awami1@gmail.com',
            'password' => 'secret',
        ]);

        $response->assertRedirect('/two-factor-challenge');
    }

}