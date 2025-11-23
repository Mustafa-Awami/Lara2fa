<?php

namespace MustafaAwami\Lara2fa\Tests;

use MustafaAwami\Lara2fa\Tests\TestCase;
use Orchestra\Testbench\Attributes\WithConfig;
use Orchestra\Testbench\Attributes\WithMigration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\Attributes\DefineEnvironment;
use MustafaAwami\Lara2fa\Tests\Models\UserWithTwoFactor;

#[WithMigration]
#[DefineEnvironment('withTwoFactorAuthentication')]
#[WithConfig('auth.providers.users.model', UserWithTwoFactor::class)]
class AuthenticatedSessionControllerWithTwoFactorTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_is_redirected_to_challenge_when_using_two_factor_authentication()
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

}