<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Models\User;
use MustafaAwami\Lara2fa\Lara2fa;
use MustafaAwami\Lara2fa\Features;
use Inertia\Testing\AssertableInertia as Assert;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TwoFactorChallengeTest extends TestCase
{
    use RefreshDatabase;

    public function test_two_factor_challenge_redirects_to_login_when_not_authenticated(): void
    {
        if (! Features::canManageTwoFactorAuthentication()) {
            $this->markTestSkipped('Two-factor authentication is not enabled.');
        }

        $response = $this->get(route('two-factor.login'));

        $response->assertRedirect(route('login'));
    }

    public function test_two_factor_challenge_can_be_rendered_when_authenticator_app_two_factor_is_enabled(): void
    {
        if (! Features::enabled(Features::authenticatorAppTwoFactorAuthentication())) {
            $this->markTestSkipped('authenticator app Two-factor authentication is not enabled.');
        }

        $user = User::factory()->withoutTwoFactor()->create();

        $user->forceFill([
            'two_factor_secret' => encrypt('test-secret'),
            'two_factor_confirmed_at' => now(),
        ])->save();

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->get(route('two-factor.login'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component(Lara2fa::getView('two-factor-challenge'))
            );
    }

    public function test_two_factor_challenge_can_be_rendered_when_email_two_factor_is_enabled(): void
    {
        if (! Features::enabled(Features::emailTwoFactorAuthentication())) {
            $this->markTestSkipped('Email two-factor authentication is not enabled.');
        }

        $user = User::factory()->withoutTwoFactor()->create();

        $user->forceFill([
            'email_two_factor_enabled_at' => now(),
            'email_two_factor_confirmed_at' => now(),
        ])->save();

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->get(route('two-factor.login'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component(Lara2fa::getView('two-factor-challenge'))
            );
    }
}
