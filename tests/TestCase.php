<?php

namespace MustafaAwami\Lara2fa\Tests;

use MustafaAwami\Lara2fa\Features;
use Orchestra\Testbench\Concerns\WithWorkbench;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    use WithWorkbench;

    protected function defineEnvironment($app)
    {
        $app['config']->set('database.default', 'testing');
    }

    protected function withTwoFactorAuthentication($app)
    {
        $app['config']->set('lara2fa.features', [
            Features::authenticatorAppTwoFactorAuthentication(),
        ]);
    }
}