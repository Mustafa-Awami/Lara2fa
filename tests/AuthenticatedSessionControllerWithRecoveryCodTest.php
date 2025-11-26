<?php

namespace MustafaAwami\Lara2fa\Tests;

use App\Attributes\FortifyAuthenticationPipeline;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use MustafaAwami\Lara2fa\Tests\Models\UserWithTwoFactor;
use Orchestra\Testbench\Attributes\DefineEnvironment;
use Orchestra\Testbench\Attributes\WithConfig;
use Orchestra\Testbench\Attributes\WithMigration;

#[WithMigration]
#[WithConfig('auth.providers.users.model', UserWithTwoFactor::class)]
#[DefineEnvironment('withFortifyTwoFactorAuthenticationDisabled')]
#[FortifyAuthenticationPipeline]
#[WithConfig('lara2fa.features', [
    'authenticator-app-two-factor-authentication',
    'recovery-codes',
])]
class AuthenticatedSessionControllerWithRecoveryCodTest extends TestCase
{
    use RefreshDatabase;

    #[WithConfig('lara2fa-options.recovery-codes', [
        'enabled' => true,
        'requireTwoFactorAuthenticationEnabled' => true,
    ])]
    public function test_two_factor_challenge_can_be_passed_via_recovery_code()
    {

        $user = UserWithTwoFactor::forceCreate([
            'name' => 'Mustafa Awami',
            'email' => 'mustafa.awami1@gmail.com',
            'password' => bcrypt('secret'),
            'two_factor_recovery_codes' => encrypt(json_encode(['invalid-code', 'valid-code'])),
        ]);

        $response = $this->withSession([
            'login.id' => $user->id,
            'login.remember' => false,
        ])->withoutExceptionHandling()->post('/two-factor-challenge', [
            'recovery_code' => 'valid-code',
        ]);

        $response->assertRedirect('/home')
            ->assertSessionMissing('login.id');
        $this->assertNotNull(Auth::getUser());
        $this->assertNotContains('valid-code', json_decode(decrypt($user->fresh()->two_factor_recovery_codes), true));
    }

    public function test_two_factor_challenge_can_fail_via_recovery_code()
    {
        $user = UserWithTwoFactor::forceCreate([
            'name' => 'Mustafa Awami',
            'email' => 'mustafa.awami1@gmail.com',
            'password' => bcrypt('secret'),
            'two_factor_recovery_codes' => encrypt(json_encode(['invalid-code', 'valid-code'])),
        ]);

        $response = $this->withSession([
            'login.id' => $user->id,
            'login.remember' => false,
        ])->withoutExceptionHandling()->post('/two-factor-challenge', [
            'recovery_code' => 'missing-code',
        ]);

        $response->assertRedirect('/two-factor-challenge')
            ->assertSessionHas('login.id')
            ->assertSessionHasErrors(['recovery_code']);
        $this->assertNull(Auth::getUser());
    }
}
