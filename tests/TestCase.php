<?php

namespace MustafaAwami\Lara2fa\Tests;

use MustafaAwami\Lara2fa\Features;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Laravel\Fortify\Features as FortifyFeatures;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    use WithWorkbench;

    protected function defineEnvironment($app)
    {
        $app['config']->set('database.default', 'testing');
    }

    protected function withFortifyTwoFactorAuthenticationDisabled($app)
    {
        $app['config']->set('fortify.features', [
            FortifyFeatures::registration(),
            FortifyFeatures::resetPasswords(),
            FortifyFeatures::emailVerification(),
            FortifyFeatures::updateProfileInformation(),
            FortifyFeatures::updatePasswords(),
        ]);
    }

    protected function withAuthenticatorAppTwoFactorAuthentication($app)
    {
        $app['config']->set('lara2fa.features', [
            Features::authenticatorAppTwoFactorAuthentication([
                'enable' => true,
                'confirm' => false,
                'confirmPassword' => true,
                'window' => 1,
                'secret-length' => 16
            ]),
        ]);
    }
}