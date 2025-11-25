<?php

namespace MustafaAwami\Lara2fa\Tests;

use MustafaAwami\Lara2fa\Features;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Laravel\Fortify\Features as FortifyFeatures;
use App\Attributes\FortifyAuthenticationPipeline;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    use WithWorkbench;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $class = static::class;
        $refl = new \ReflectionClass($class);

        foreach ($refl->getAttributes() as $attribute) {
            if ($attribute->getName() === FortifyAuthenticationPipeline::class) {
                $attribute->newInstance();
            }
        }
    }
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
}