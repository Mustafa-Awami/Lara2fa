<?php

namespace MustafaAwami\Lara2fa\Tests;

use Symfony\Component\Uid\Uuid;
use MustafaAwami\Lara2fa\Tests\TestCase;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Orchestra\Testbench\Attributes\WithConfig;
use App\Attributes\FortifyAuthenticationPipeline;
use Orchestra\Testbench\Attributes\WithMigration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\Attributes\DefineEnvironment;
use MustafaAwami\Lara2fa\Tests\Models\UserWithTwoFactor;

#[WithMigration]
#[WithConfig('auth.providers.users.model', UserWithTwoFactor::class)]
#[DefineEnvironment('withFortifyTwoFactorAuthenticationDisabled')]
#[FortifyAuthenticationPipeline]
#[WithConfig('lara2fa.features', [
    'passkeys',
])]
class AuthenticatedSessionControllerWithPasskeysTwoFactorTest extends TestCase
{
    use RefreshDatabase;
    
    #[WithConfig('lara2fa-options.passkeys', [
        'enabled' => true,
    ])]
    public function test_user_is_redirected_to_challenge_when_using_passkey_two_factor_authentication()
    {
        $user = UserWithTwoFactor::forceCreate([
            'name' => 'Mustafa Awami',
            'email' => 'mustafa.awami1@gmail.com',
            'password' => bcrypt('secret'),
        ]);

        $user->passkeys()->forceCreate([
            'name' => 'My Passkey',
            'credential_id' => Base64UrlSafe::encodeUnpadded($user->id),
            'data' => new \Webauthn\PublicKeyCredentialSource(
                Base64UrlSafe::encodeUnpadded($user->id),
                'public-key',
                [],
                'none',
                \Webauthn\TrustPath\EmptyTrustPath::create(),
                Uuid::fromString('38195f59-0e5b-4ebf-be46-75664177eeee'),
                'oWNrZXlldmFsdWU=',
                'MQ',
                0
            ),
        ]);


        $response = $this->withoutExceptionHandling()->post('/login', [
            'email' => 'mustafa.awami1@gmail.com',
            'password' => 'secret',
        ]);

        $response->assertRedirect('/two-factor-challenge');
    }
}