<?php

namespace MustafaAwami\Lara2fa\Tests;

use App\Attributes\FortifyAuthenticationPipeline;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MustafaAwami\Lara2fa\Tests\Models\UserWithTwoFactor;
use Orchestra\Testbench\Attributes\DefineEnvironment;
use Orchestra\Testbench\Attributes\WithConfig;
use Orchestra\Testbench\Attributes\WithMigration;
use PragmaRX\Google2FA\Google2FA;

#[WithMigration]
#[WithConfig('auth.providers.users.model', UserWithTwoFactor::class)]
#[DefineEnvironment('withFortifyTwoFactorAuthenticationDisabled')]
#[FortifyAuthenticationPipeline]
#[WithConfig('lara2fa.features', [
    'authenticator-app-two-factor-authentication',
])]
class AuthenticatedSessionControllerWithAuthenticatorAppTwoFactorTest extends TestCase
{
    use RefreshDatabase;

    #[WithConfig('lara2fa-options.authenticator-app-two-factor-authentication', [
        'enabled' => true,
    ])]
    public function test_user_is_redirected_to_challenge_when_using_authenticator_app_two_factor_authentication()
    {
        UserWithTwoFactor::forceCreate([
            'name' => 'Mustafa Awami',
            'email' => 'mustafa.awami1@gmail.com',
            'password' => bcrypt('secret'),
            'two_factor_secret' => 'test-secret',
        ]);

        $response = $this->withoutExceptionHandling()->post('/login', [
            'email' => 'mustafa.awami1@gmail.com',
            'password' => 'secret',
        ]);

        $response->assertRedirect('/two-factor-challenge');
    }

    #[WithConfig('lara2fa-options.authenticator-app-two-factor-authentication', [
        'enabled' => true,
        'confirm' => true,
    ])]
    public function test_user_is_not_redirected_to_challenge_when_using_authenticator_app_two_factor_authentication_that_has_not_been_confirmed_and_confirmation_is_enabled()
    {
        UserWithTwoFactor::forceCreate([
            'name' => 'Mustafa Awami',
            'email' => 'mustafa.awami1@gmail.com',
            'password' => bcrypt('secret'),
            'two_factor_secret' => 'test-secret',
        ]);

        $response = $this->withoutExceptionHandling()->post('/login', [
            'email' => 'mustafa.awami1@gmail.com',
            'password' => 'secret',
        ]);

        $response->assertRedirect('/home');
    }

    #[WithConfig('lara2fa-options.authenticator-app-two-factor-authentication', [
        'enabled' => true,
        'confirm' => true,
    ])]
    public function test_user_is_redirected_to_challenge_when_using_authenticator_app_two_factor_authentication_that_has_been_confirmed_and_confirmation_is_enabled()
    {
        UserWithTwoFactor::forceCreate([
            'name' => 'Mustafa Awami',
            'email' => 'mustafa.awami1@gmail.com',
            'password' => bcrypt('secret'),
            'two_factor_secret' => 'test-secret',
            'two_factor_confirmed_at' => now(),
        ]);

        $response = $this->withoutExceptionHandling()->post('/login', [
            'email' => 'mustafa.awami1@gmail.com',
            'password' => 'secret',
        ]);

        $response->assertRedirect('/two-factor-challenge');
    }

    #[WithConfig('lara2fa-options.authenticator-app-two-factor-authentication', [
        'enabled' => true,
    ])]
    public function test_two_factor_challenge_can_be_passed_via_authenticator_app()
    {
        $tfaEngine = app(Google2FA::class);
        $userSecret = $tfaEngine->generateSecretKey();
        $validOtp = $tfaEngine->getCurrentOtp($userSecret);

        $user = UserWithTwoFactor::forceCreate([
            'name' => 'Mustafa Awami',
            'email' => 'mustafa.awami1@gmail.com',
            'password' => bcrypt('secret'),
            'two_factor_secret' => encrypt($userSecret),
        ]);

        $response = $this->withSession([
            'login.id' => $user->id,
            'login.remember' => false,
        ])->withoutExceptionHandling()->post('/two-factor-challenge', [
            'code' => $validOtp,
        ]);

        $response->assertRedirect('/home')
            ->assertSessionMissing('login.id');
    }

    #[WithConfig('lara2fa-options.authenticator-app-two-factor-authentication', [
        'enabled' => true,
        'window' => 0,
    ])]
    public function test_authenticator_app_two_factor_challenge_fails_for_old_otp_and_zero_window()
    {
        $tfaEngine = app(Google2FA::class);
        $userSecret = $tfaEngine->generateSecretKey();
        $currentTs = $tfaEngine->getTimestamp();
        $previousOtp = $tfaEngine->oathTotp($userSecret, $currentTs - 1);

        $user = UserWithTwoFactor::forceCreate([
            'name' => 'Mustafa Awami',
            'email' => 'mustafa.awami1@gmail.com',
            'password' => bcrypt('secret'),
            'two_factor_secret' => encrypt($userSecret),
        ]);

        $response = $this->withSession([
            'login.id' => $user->id,
            'login.remember' => false,
        ])->withoutExceptionHandling()->post('/two-factor-challenge', [
            'code' => $previousOtp,
        ]);

        $response->assertRedirect('/two-factor-challenge')
            ->assertSessionHas('login.id')
            ->assertSessionHasErrors(['code']);
    }

    #[WithConfig('lara2fa.features', [])]
    public function test_user_can_authenticate_when_two_factor_challenge_is_disabled()
    {
        UserWithTwoFactor::forceCreate([
            'name' => 'Mustafa Awami',
            'email' => 'mustafa.awami1@gmail.com',
            'password' => bcrypt('secret'),
            'two_factor_secret' => 'test-secret',
        ]);

        $response = $this->withoutExceptionHandling()->post('/login', [
            'email' => 'mustafa.awami1@gmail.com',
            'password' => 'secret',
        ]);

        $response->assertRedirect('/home');
    }
}
