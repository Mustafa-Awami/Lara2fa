<?php

namespace MustafaAwami\Lara2fa\Console;

use Exception;
use RuntimeException;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Console\Attribute\AsCommand;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use MustafaAwami\Lara2fa\Services\StackDetector;

#[AsCommand(name: 'lara2fa:install')]
class InstallCommand extends Command implements PromptsForMissingInput
{
    private array $stacks = [
        1 => 'react',
        2 => 'vue',
    ];

    private array $choices = [
        1 => 'Authenticator App (TOTP)',
        2 => 'Email OTP',
        3 => 'Passkeys'
    ];

    private int $defaultChoice = 1;

    protected $signature = 'lara2fa:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install and configure Lara2FA';

    /**
     * Execute the console command.
     *
     * @return int|null
     */
    public function handle()
    {

        $this->info('🔐 Welcome to Lara2FA Installation Wizard');

        $selectedStack = $this->choice(
            'Which stack are you using?',
            $this->stacks,
            in_array(StackDetector::detectFrontendStack(), $this->stacks) 
                ? StackDetector::detectFrontendStack()
                : null,
            null,
            false
        );

        $selectedChoices = $this->choice(
            'Which 2FA methods would you like to enable? (comma separated)',
            $this->choices,
            $this->defaultChoice,
            null,
            true // multiple selection allowed
        );

        // Publish...
        $this->callSilent('vendor:publish', ['--tag' => 'lara2fa-config', '--force' => true]);

        $this->callSilent('vendor:publish', ['--tag' => 'lara2fa-two-factor-email-migrations', '--force' => true]);
        $this->callSilent('vendor:publish', ['--tag' => 'lara2fa-passkeys-migrations', '--force' => true]);

        if (in_array('Authenticator App (TOTP)', $selectedChoices)) {
            $this->updateFeatureEnable('authenticatorAppTwoFactorAuthentication', true);
        }

        if (in_array('Email OTP', $selectedChoices)) {
            $this->updateFeatureEnable('emailTwoFactorAuthentication', true);
        }

        if (in_array('Passkeys', $selectedChoices)) {
            $this->updateFeatureEnable('passkeys', true);
        }

        if ($selectedStack === "react") {
            copy(__DIR__.'/../../stubs/inertia/react/resources/js/pages/settings/two-factor.tsx', resource_path('js/pages/settings/two-factor.tsx'));
            copy(__DIR__.'/../../stubs/inertia/react/resources/js/pages/auth/login.tsx', resource_path('js/pages/auth/login.tsx'));
            copy(__DIR__.'/../../stubs/inertia/react/resources/js/pages/auth/two-factor-challenge.tsx', resource_path('js/pages/auth/two-factor-challenge.tsx'));
            copy(__DIR__.'/../../stubs/inertia/react/resources/js/components/confirm-password-dialog.tsx', resource_path('js/components/confirm-password-dialog.tsx'));

            // Install other NPM packages...
            $this->installNodePackages([
                '@simplewebauthn/browser' => '^13.1.2',
            ]);
            
        } elseif ($selectedStack === "vue") {
            copy(__DIR__.'/../../stubs/inertia/vue/resources/js/pages/settings/TwoFactor.vue', resource_path('js/pages/settings/TwoFactor.vue'));

            copy(__DIR__.'/../../stubs/inertia/vue/resources/js/components/TwoFactorAuthenticatorApp.vue', resource_path('js/components/TwoFactorAuthenticatorApp.vue'));
            copy(__DIR__.'/../../stubs/inertia/vue/resources/js/components/TwoFactorEmail.vue', resource_path('js/components/TwoFactorEmail.vue'));
            copy(__DIR__.'/../../stubs/inertia/vue/resources/js/components/TwoFactorPasskeys.vue', resource_path('js/components/TwoFactorPasskeys.vue'));
            copy(__DIR__.'/../../stubs/inertia/vue/resources/js/components/TwoFactorRecoveryCodes.vue', resource_path('js/components/TwoFactorRecoveryCodes.vue'));

            copy(__DIR__.'/../../stubs/inertia/vue/resources/js/pages/auth/Login.vue', resource_path('js/pages/auth/Login.vue'));
            copy(__DIR__.'/../../stubs/inertia/vue/resources/js/pages/auth/TwoFactorChallenge.vue', resource_path('js/pages/auth/TwoFactorChallenge.vue'));
            copy(__DIR__.'/../../stubs/inertia/vue/resources/js/components/ConfirmPasswordDialog.vue', resource_path('js/components/ConfirmPasswordDialog.vue'));

            // Install other NPM packages...
            $this->installNodePackages([
                '@simplewebauthn/browser' => '^13.1.2',
                '@headlessui/vue' => '^1.7.23'
            ]);
        }

        $this->replaceInFile("'stack' => 'react'", "'stack' => '" . $selectedStack . "'",config_path('lara2fa.php'));

        // Service Providers...
        $this->registerLara2faServiceProvider();

        $this->newLine();
        $this->info('✅ Lara2FA installed successfully!');
        $this->info('Enabled methods: ' . implode(', ', $selectedChoices));
        
    }

    /**
     * Replace a given string within a given file.
     *
     * @param  string  $search
     * @param  string  $replace
     * @param  string  $path
     * @return void
     */
    protected function replaceInFile($search, $replace, $path)
    {
        file_put_contents($path, str_replace($search, $replace, file_get_contents($path)));
    }

    protected function updateFeatureEnable(string $featureName, bool $newValue)
    {
        $file = base_path('config/lara2fa.php'); // adjust if needed
        $content = file_get_contents($file);

        // Convert boolean to string (true/false)
        $valueStr = $newValue ? 'true' : 'false';

        // Regex: find the specific Features::<featureName>([ ... 'enable' => X ... ])
        $pattern = "/(Features::{$featureName}\s*\(\s*\[[^\]]*?)'enable'\s*=>\s*(true|false)/";

        $replacement = "$1'enable' => {$valueStr}";

        $updated = preg_replace($pattern, $replacement, $content);

        if ($updated === null) {
            throw new Exception("Regex error while updating feature: {$featureName}");
        }

        if ($updated === $content) {
            throw new Exception("No match found for feature: {$featureName}");
        }

        file_put_contents($file, $updated);
    }

    function appendLineToWebFile(?string $filePath, string $lineToAdd = ""): void
    {
        // Default path to routes/web.php
        $file = $filePath;

        if (!file_exists($file)) {
            throw new RuntimeException("File not found: {$file}");
        }

        $content = file_get_contents($file);
        $content = trim($content);

        if (strpos($content, $lineToAdd) === false) {
            $content .= PHP_EOL . $lineToAdd . PHP_EOL;
            file_put_contents($file, $content);
        }
    }

    /**
     * Register the Lara2fa service provider in the application configuration file.
     */
    protected function registerLara2faServiceProvider(): void
    {
        copy(__DIR__.'/../../stubs/app/Providers/Lara2faServiceProvider.php', app_path('Providers/Lara2faServiceProvider.php'));

        if (! method_exists(ServiceProvider::class, 'addProviderToBootstrapFile')) {
            return;
        }

        ServiceProvider::addProviderToBootstrapFile(\App\Providers\Lara2faServiceProvider::class);
    }

    protected function installNodePackages(array $newPackagesArray, bool $runBuild = false)
    {
        $this->updateNodePackages(function ($packages) use($newPackagesArray) {
            return $newPackagesArray + $packages;
        });

        if (file_exists(base_path('pnpm-lock.yaml'))) {
            if ($runBuild)
                $this->runCommands(['pnpm install', 'pnpm run build']);
            else
                $this->runCommands(['pnpm install']);
        } elseif (file_exists(base_path('yarn.lock'))) {
            if ($runBuild)
                $this->runCommands(['yarn install', 'yarn run build']);
            else
                $this->runCommands(['yarn install']);
        } else {
            if ($runBuild)
                $this->runCommands(['npm install', 'npm run build']);
            else
                $this->runCommands(['npm install']);
        }
    }

    /**
     * Update the "package.json" file.
     *
     * @param  callable  $callback
     * @param  bool  $dev
     * @return void
     */
    protected static function updateNodePackages(callable $callback, $dev = true)
    {
        if (! file_exists(base_path('package.json'))) {
            return;
        }

        $configurationKey = $dev ? 'devDependencies' : 'dependencies';

        $packages = json_decode(file_get_contents(base_path('package.json')), true);

        $packages[$configurationKey] = $callback(
            array_key_exists($configurationKey, $packages) ? $packages[$configurationKey] : [],
            $configurationKey
        );

        ksort($packages[$configurationKey]);

        file_put_contents(
            base_path('package.json'),
            json_encode($packages, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT).PHP_EOL
        );
    }

    /**
     * Run the given commands.
     *
     * @param  array  $commands
     * @return void
     */
    protected function runCommands($commands)
    {
        $process = Process::fromShellCommandline(implode(' && ', $commands), null, null, null, null);

        if ('\\' !== DIRECTORY_SEPARATOR && file_exists('/dev/tty') && is_readable('/dev/tty')) {
            try {
                $process->setTty(true);
            } catch (RuntimeException $e) {
                $this->output->writeln('  <bg=yellow;fg=black> WARN </> '.$e->getMessage().PHP_EOL);
            }
        }

        $process->run(function ($type, $line) {
            $this->output->write('    '.$line);
        });
    }

}