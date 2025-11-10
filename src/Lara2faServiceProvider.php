<?php

namespace MustafaAwami\Lara2fa;

use PragmaRX\Google2FA\Google2FA;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Cache\Repository;
use MustafaAwami\Lara2fa\Http\Responses\PasskeyCreatedResponse;
use MustafaAwami\Lara2fa\Http\Responses\PasskeyDeletedResponse;
use MustafaAwami\Lara2fa\Http\Responses\PasskeyUpdatedResponse;
use MustafaAwami\Lara2fa\Http\Responses\PasskeyDisapledResponse;
use MustafaAwami\Lara2fa\Http\Responses\EmailTwoFactorNotifyResponse;
use MustafaAwami\Lara2fa\Http\Responses\FailedTwoFactorLoginResponse;
use MustafaAwami\Lara2fa\Http\Responses\PasskeyAuthenticatedResponse;
use MustafaAwami\Lara2fa\Http\Responses\EmailTwoFactorEnabledResponse;
use MustafaAwami\Lara2fa\Http\Responses\RecoveryCodesDisabledResponse;
use MustafaAwami\Lara2fa\Http\Responses\EmailTwoFactorDisabledResponse;
use MustafaAwami\Lara2fa\Http\Responses\RecoveryCodesGeneratedResponse;
use MustafaAwami\Lara2fa\Services\EmailTwoFactorAuthenticationProvider;
use MustafaAwami\Lara2fa\Http\Responses\EmailTwoFactorConfirmedResponse;
use MustafaAwami\Lara2fa\Http\Responses\AuthenticatorAppTwoFactorEnabledResponse;
use MustafaAwami\Lara2fa\Http\Responses\AuthenticatorAppTwoFactorDisabledResponse;
use MustafaAwami\Lara2fa\Services\AuthenticatorAppTwoFactorAuthenticationProvider;
use MustafaAwami\Lara2fa\Http\Responses\AuthenticatorAppTwoFactorConfirmedResponse;
use MustafaAwami\Lara2fa\Contracts\PasskeyCreatedResponse as PasskeyCreatedResponseContract;
use MustafaAwami\Lara2fa\Contracts\PasskeyDeletedResponse as PasskeyDeletedResponseContract;
use MustafaAwami\Lara2fa\Contracts\PasskeyUpdatedResponse as PasskeyUpdatedResponseContract;
use MustafaAwami\Lara2fa\Contracts\PasskeyDisapledResponse as PasskeyDisapledResponseContract;
use MustafaAwami\Lara2fa\Contracts\EmailTwoFactorNotifyResponse as EmailTwoFactorNotifyResponseContract;
use MustafaAwami\Lara2fa\Contracts\PasskeyAuthenticatedResponse as PasskeyAuthenticatedResponseContract;
use MustafaAwami\Lara2fa\Contracts\FailedTwoFactorLoginResponse as FailedTwoFactorLoginResponseContract;
use MustafaAwami\Lara2fa\Contracts\EmailTwoFactorEnabledResponse as EmailTwoFactorEnabledResponseContract;
use MustafaAwami\Lara2fa\Contracts\RecoveryCodesDisabledResponse as RecoveryCodesDisabledResponseContract;
use MustafaAwami\Lara2fa\Contracts\EmailTwoFactorDisabledResponse as EmailTwoFactorDisabledResponseContract;
use MustafaAwami\Lara2fa\Contracts\RecoveryCodesGeneratedResponse as RecoveryCodesGeneratedResponseContract;
use MustafaAwami\Lara2fa\Contracts\EmailTwoFactorConfirmedResponse as EmailTwoFactorConfirmedResponseContract;
use MustafaAwami\Lara2fa\Contracts\EmailTwoFactorAuthenticationProvider as EmailTwoFactorAuthenticationProviderContract;
use MustafaAwami\Lara2fa\Contracts\AuthenticatorAppTwoFactorEnabledResponse as AuthenticatorAppTwoFactorEnabledResponseContract;
use MustafaAwami\Lara2fa\Contracts\AuthenticatorAppTwoFactorDisabledResponse as AuthenticatorAppTwoFactorDisabledResponseContract;
use MustafaAwami\Lara2fa\Contracts\AuthenticatorAppTwoFactorConfirmedResponse as AuthenticatorAppTwoFactorConfirmedResponseContract;
use MustafaAwami\Lara2fa\Contracts\AuthenticatorAppTwoFactorAuthenticationProvider as AuthenticatorAppTwoFactorAuthenticationProviderContract;

class Lara2faServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->registerTwoFactorResponseBinding();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        
        $this->configurePublishing();

        $this->configureCommands();

        $this->configureRoutes();
    }

    /**
     * Configure publishing for the package.
     *
     * @return void
     */
    protected function configurePublishing() {
        if (! $this->app->runningInConsole()) {
            return;
        }

        // Publish the config file to the application's config directory
        $this->publishes([
            __DIR__ . '/../config/lara2fa.php' => config_path('lara2fa.php'),
        ], 'lara2fa-config');


        $this->publishes([
            __DIR__.'/../database/migrations/2024_07_29_090549_add_two_factor_email_columns_to_users_table.php' => database_path('migrations/2024_07_29_090549_add_two_factor_email_columns_to_users_table.php')
        ], 'lara2fa-two-factor-email-migrations');

        $this->publishes([
            __DIR__.'/../database/migrations/2025_09_10_081543_create_passkeys_table.php' => database_path('migrations/2025_09_10_081543_create_passkeys_table.php')
        ], 'lara2fa-passkeys-migrations');

        // Publish the route file to the application's routes directory
        $this->publishes([
            __DIR__.'/../routes/lara2fa.php' => base_path('routes/lara2fa.php'),
        ], 'lara2fa-routes');
    }

    protected function registerTwoFactorResponseBinding() 
    {
        $this->app->singleton(AuthenticatorAppTwoFactorAuthenticationProviderContract::class, function ($app)
        {
            return new AuthenticatorAppTwoFactorAuthenticationProvider(
                $app->make(Google2FA::class),
                $app->make(Repository::class)
            );
        });

        $this->app->singleton(EmailTwoFactorAuthenticationProviderContract::class, EmailTwoFactorAuthenticationProvider::class);

        $this->app->singleton(FailedTwoFactorLoginResponseContract::class, FailedTwoFactorLoginResponse::class);

        $this->app->singleton(AuthenticatorAppTwoFactorConfirmedResponseContract::class, AuthenticatorAppTwoFactorConfirmedResponse::class);
        $this->app->singleton(AuthenticatorAppTwoFactorDisabledResponseContract::class, AuthenticatorAppTwoFactorDisabledResponse::class);
        $this->app->singleton(AuthenticatorAppTwoFactorEnabledResponseContract::class, AuthenticatorAppTwoFactorEnabledResponse::class);
        $this->app->singleton(EmailTwoFactorConfirmedResponseContract::class, EmailTwoFactorConfirmedResponse::class);
        $this->app->singleton(EmailTwoFactorDisabledResponseContract::class, EmailTwoFactorDisabledResponse::class);
        $this->app->singleton(EmailTwoFactorEnabledResponseContract::class, EmailTwoFactorEnabledResponse::class);
        $this->app->singleton(EmailTwoFactorNotifyResponseContract::class, EmailTwoFactorNotifyResponse::class);
        $this->app->singleton(PasskeyAuthenticatedResponseContract::class, PasskeyAuthenticatedResponse::class);
        $this->app->singleton(PasskeyCreatedResponseContract::class, PasskeyCreatedResponse::class);
        $this->app->singleton(PasskeyDeletedResponseContract::class, PasskeyDeletedResponse::class);
        $this->app->singleton(PasskeyDisapledResponseContract::class, PasskeyDisapledResponse::class);
        $this->app->singleton(PasskeyUpdatedResponseContract::class, PasskeyUpdatedResponse::class);
        $this->app->singleton(RecoveryCodesDisabledResponseContract::class, RecoveryCodesDisabledResponse::class);
        $this->app->singleton(RecoveryCodesGeneratedResponseContract::class, RecoveryCodesGeneratedResponse::class);
    }

    /**
     * Configure the commands offered by the application.
     *
     * @return void
     */
    protected function configureCommands()
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            Console\InstallCommand::class,
        ]);
    }

    /**
     * Configure the routes offered by the application.
     *
     * @return void
     */
    protected function configureRoutes()
    {
        if (Lara2fa::$registersRoutes) {
            Route::group([
                'namespace' => 'MustafaAwami\Lara2fa\Http\Controllers\Inertia',
                'domain' => config('lara2fa.domain', null),
                'prefix' => config('lara2fa.prefix', null),
            ], function () {
                $this->loadRoutesFrom(__DIR__.'/../routes/lara2fa.php');
            });
        }
        
    }
}