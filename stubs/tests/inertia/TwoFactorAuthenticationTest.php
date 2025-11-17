<?php

namespace Tests\Feature\Settings;

use Tests\TestCase;
use App\Models\User;
use MustafaAwami\Lara2fa\Features;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use MustafaAwami\Lara2fa\Lara2fa;

class TwoFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_two_factor_settings_page_can_be_rendered()
    {
        if (! Features::canManageTwoFactorAuthentication()) {
            $this->markTestSkipped('Two-factor authentication is not enabled.');
        }

        $user = User::factory()->withoutTwoFactor()->create();

        $this->actingAs($user)
            ->get(route('two-factor.show'))
            ->assertInertia(fn (Assert $page) => $page
                ->component(Lara2fa::getView("two-factor-settings"))
            );
    }

    public function test_two_factor_settings_page_returns_forbidden_response_when_two_factor_is_disabled()
    {
        if (! Features::canManageTwoFactorAuthentication()) {
            $this->markTestSkipped('Two-factor authentication is not enabled.');
        }

        config(['lara2fa.features' => []]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('two-factor.show'))
            ->assertForbidden();
    }

    public function test_authenticator_app_two_factor_authentication_can_be_enabled(): void
    {
        if (! Features::enabled(Features::authenticatorAppTwoFactorAuthentication())) {
            $this->markTestSkipped('Authenticator-app two-factor authentication is not enabled.');
        }

        $this->actingAs($user = User::factory()->withoutTwoFactor()->create());

        $this->withSession(['auth.password_confirmed_at' => time()]);

        $this->post(route('authenticator-app-two-factor.enable'));

        $this->assertNotNull($user->fresh()->two_factor_secret);
    }

    public function test_authenticator_app_two_factor_authentication_can_be_disabled(): void
    {
        if (! Features::enabled(Features::authenticatorAppTwoFactorAuthentication())) {
            $this->markTestSkipped('Authenticator-app two-factor authentication is not enabled.');
        }

        $this->actingAs($user = User::factory()->withoutTwoFactor()->create());

        $this->withSession(['auth.password_confirmed_at' => time()]);

        $this->post(route('authenticator-app-two-factor.enable'));

        $this->assertNotNull($user->fresh()->two_factor_secret);

        $this->delete(route('authenticator-app-two-factor.disable'));

        $this->assertNull($user->fresh()->two_factor_secret);
    }

    public function test_email_two_factor_authentication_can_be_enabled(): void
    {
        if (! Features::enabled(Features::emailTwoFactorAuthentication())) {
            $this->markTestSkipped('Email two-factor authentication is not enabled.');
        }

        $this->actingAs($user = User::factory()->withoutTwoFactor()->create());

        $this->withSession(['auth.password_confirmed_at' => time()]);

        $this->post(route('email-two-factor.enable'));

        $this->assertNotNull($user->fresh()->email_two_factor_enabled_at);
    }

    public function test_email_two_factor_authentication_can_be_disabled(): void
    {
        if (! Features::enabled(Features::emailTwoFactorAuthentication())) {
            $this->markTestSkipped('Email two-factor authentication is not enabled.');
        }

        $this->actingAs($user = User::factory()->withoutTwoFactor()->create());

        $this->withSession(['auth.password_confirmed_at' => time()]);

        $this->post(route('email-two-factor.enable'));

        $this->assertNotNull($user->fresh()->email_two_factor_enabled_at);

        $this->delete(route('email-two-factor.disable'));

        $this->assertNull($user->fresh()->email_two_factor_enabled_at);
    }

    public function test_recovery_codes_can_be_regenerated(): void
    {
        if (! Features::enabled(Features::recoveryCodes())) {
            $this->markTestSkipped('Two-factor recovery codes are not enabled.');
        }

        $this->actingAs($user = User::factory()->withoutTwoFactor()->create());

        $this->withSession(['auth.password_confirmed_at' => time()]);

        if (Features::recoveryCodesRequireTwoFactorAuthenticationEnabled()) {
            $this->post(route('authenticator-app-two-factor.enable'));
            $user->forceFill([
                'two_factor_confirmed_at' => now(),
            ])->save();
        }

        $this->post(route('two-factor-recovery-codes.generate'));

        $user = $user->fresh();

        $this->post(route('two-factor-recovery-codes.generate'));

        $numberOfCodes = config('lara2fa-options.recovery-codes.numberOfCodesGenerated', 8);

        $this->assertCount($numberOfCodes, $user->recoveryCodes());
        $this->assertCount($numberOfCodes, array_diff($user->recoveryCodes(), $user->fresh()->recoveryCodes()));

    }

    public function test_recovery_codes_can_be_disabled(): void
    {
        if (! Features::enabled(Features::recoveryCodes())) {
            $this->markTestSkipped('Two-factor recovery codes are not enabled.');
        }

        $this->actingAs($user = User::factory()->withoutTwoFactor()->create());

        $this->withSession(['auth.password_confirmed_at' => time()]);

        if (Features::recoveryCodesRequireTwoFactorAuthenticationEnabled()) {
            $this->post(route('authenticator-app-two-factor.enable'));
            $user->forceFill([
                'two_factor_confirmed_at' => now(),
            ])->save();
        }

        $this->post(route('two-factor-recovery-codes.generate'));

        $user = $user->fresh();

        $this->post(route('two-factor-recovery-codes.generate'));

        $numberOfCodes = config('lara2fa-options.recovery-codes.numberOfCodesGenerated', 8);

        $this->assertCount($numberOfCodes, $user->recoveryCodes());
        $this->assertCount($numberOfCodes, array_diff($user->recoveryCodes(), $user->fresh()->recoveryCodes()));

        $this->delete(route('two-factor-recovery-codes.disable'));

        $this->assertNull($user->fresh()->email_two_factor_recovery_codes);

    }
}