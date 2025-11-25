<?php

namespace MustafaAwami\Lara2fa\Tests;

use MustafaAwami\Lara2fa\Tests\TestCase;
use Orchestra\Testbench\Attributes\WithConfig;
use App\Attributes\FortifyAuthenticationPipeline;
use DateInterval;
use Orchestra\Testbench\Attributes\WithMigration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MustafaAwami\Lara2fa\Contracts\EmailTwoFactorAuthenticationProvider;
use Orchestra\Testbench\Attributes\DefineEnvironment;
use MustafaAwami\Lara2fa\Tests\Models\UserWithTwoFactor;

use function Symfony\Component\Clock\now;

#[WithMigration]
#[WithConfig('auth.providers.users.model', UserWithTwoFactor::class)]
#[DefineEnvironment('withFortifyTwoFactorAuthenticationDisabled')]
#[FortifyAuthenticationPipeline]
#[WithConfig('lara2fa.features', [
    'email-two-factor-authentication',
])]
class AuthenticatedSessionControllerWithEmailTwoFactorTest extends TestCase
{
    use RefreshDatabase;
    
    #[WithConfig('lara2fa-options.email-two-factor-authentication', [
        'enabled' => true,
    ])]
    public function test_user_is_redirected_to_challenge_when_using_email_two_factor_authentication()
    {
        UserWithTwoFactor::forceCreate([
            'name' => 'Mustafa Awami',
            'email' => 'mustafa.awami1@gmail.com',
            'password' => bcrypt('secret'),
            'email_two_factor_enabled_at' => now(),
        ]);

        $response = $this->withoutExceptionHandling()->post('/login', [
            'email' => 'mustafa.awami1@gmail.com',
            'password' => 'secret',
        ]);

        $response->assertRedirect('/two-factor-challenge');
    }

    #[WithConfig('lara2fa-options.email-two-factor-authentication', [
        'enabled' => true,
        'confirm' => true,
    ])]
    public function test_user_is_not_redirected_to_challenge_when_using_email_two_factor_authentication_that_has_not_been_confirmed_and_confirmation_is_enabled()
    {
        UserWithTwoFactor::forceCreate([
            'name' => 'Mustafa Awami',
            'email' => 'mustafa.awami1@gmail.com',
            'password' => bcrypt('secret'),
            'email_two_factor_enabled_at' => now(),
        ]);

        $response = $this->withoutExceptionHandling()->post('/login', [
            'email' => 'mustafa.awami1@gmail.com',
            'password' => 'secret',
        ]);

        $response->assertRedirect('/home');
    }

    #[WithConfig('lara2fa-options.email-two-factor-authentication', [
        'enabled' => true,
        'confirm' => true,
    ])]
    public function test_user_is_redirected_to_challenge_when_using_email_two_factor_authentication_that_has_been_confirmed_and_confirmation_is_enabled()
    {
        UserWithTwoFactor::forceCreate([
            'name' => 'Mustafa Awami',
            'email' => 'mustafa.awami1@gmail.com',
            'password' => bcrypt('secret'),
            'email_two_factor_enabled_at' => now(),
            'email_two_factor_confirmed_at' => now(),
        ]);

        $response = $this->withoutExceptionHandling()->post('/login', [
            'email' => 'mustafa.awami1@gmail.com',
            'password' => 'secret',
        ]);

        $response->assertRedirect('/two-factor-challenge');
    }

    #[WithConfig('lara2fa-options.email-two-factor-authentication', [
        'enabled' => true,
        'window' => 10,
    ])]
    public function test_two_factor_challenge_can_be_passed_via_email()
    {
        $expiresAt = app(EmailTwoFactorAuthenticationProvider::class)->generateExpiresAt();
        $validOtp = app(EmailTwoFactorAuthenticationProvider::class)->generateRandomCode();

        $user = UserWithTwoFactor::forceCreate([
            'name' => 'Mustafa Awami',
            'email' => 'mustafa.awami1@gmail.com',
            'password' => bcrypt('secret'),
            'email_two_factor_code' => encrypt($validOtp),
            'email_two_factor_code_expires_at' => $expiresAt,
            'email_two_factor_enabled_at' => now(),
        ]);

        $response = $this->withSession([
            'login.id' => $user->id,
            'login.remember' => false,
        ])->withoutExceptionHandling()->post('/two-factor-challenge', [
            'email_code' => $validOtp."",
        ]);

        $response->assertRedirect('/home')
            ->assertSessionMissing('login.id');
    }

    #[WithConfig('lara2fa-options.email-two-factor-authentication', [
        'enabled' => true,
        'window' => 0,
    ])]
    public function test_email_two_factor_challenge_fails_for_expaierd_otp()
    {
        $validOtp = app(EmailTwoFactorAuthenticationProvider::class)->generateRandomCode();

        $user = UserWithTwoFactor::forceCreate([
            'name' => 'Mustafa Awami',
            'email' => 'mustafa.awami1@gmail.com',
            'password' => bcrypt('secret'),
            'email_two_factor_code' => encrypt($validOtp),
            'email_two_factor_code_expires_at' => now()->sub(DateInterval::createFromDateString('1 minutes')),
            'email_two_factor_enabled_at' => now(),
        ]);

        $response = $this->withSession([
            'login.id' => $user->id,
            'login.remember' => false,
        ])->withoutExceptionHandling()->post('/two-factor-challenge', [
            'email_code' => $validOtp."",
        ]);

        $response->assertRedirect('/two-factor-challenge')
                 ->assertSessionHas('login.id')
                 ->assertSessionHasErrors(['email_code']);
    }



    #[WithConfig('lara2fa.features', [])]
    public function test_user_can_authenticate_when_two_factor_challenge_is_disabled()
    {
        UserWithTwoFactor::forceCreate([
            'name' => 'Mustafa Awami',
            'email' => 'mustafa.awami1@gmail.com',
            'password' => bcrypt('secret'),
            'email_two_factor_enabled_at' => now(),
        ]);

        $response = $this->withoutExceptionHandling()->post('/login', [
            'email' => 'mustafa.awami1@gmail.com',
            'password' => 'secret',
        ]);

        $response->assertRedirect('/home');
    }

}