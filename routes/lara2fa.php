<?php

use Mustafa\Lara2fa\Features;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features as FortifyFeatures;
use Mustafa\Lara2fa\Http\Controllers\ConfirmPasswordController;
use Mustafa\Lara2fa\Http\Controllers\Settings\RecoveryCodeController;
use Mustafa\Lara2fa\Http\Controllers\Auth\TwoFactorAuthenticatedSessionController;
use Mustafa\Lara2fa\Http\Controllers\Settings\EmailTwoFactorAuthenticationController;
use Mustafa\Lara2fa\Http\Controllers\Settings\PasskeysTwoFactorAuthenticationController;
use Mustafa\Lara2fa\Http\Controllers\Settings\PasskeysTwoFactorAuthenticationOptionsController;
use Mustafa\Lara2fa\Http\Controllers\Settings\AuthenticatorAppTwoFactorAuthenticationController;

Route::group(['middleware' => config('fortify.middleware', ['web'])], function () {

    Route::group(['middleware' => array_values(array_filter(['auth:'.config('fortify.guard')]))], function () {
        // For password confirmation dialog
        Route::get('/password-confirmation-status', [ConfirmPasswordController::class, 'show'])->name('password-confirmation.show');

        Route::post('/password-confirmation', [ConfirmPasswordController::class, 'store'])->name('password-confirmation.store');
    });

    if ((! FortifyFeatures::canManagetwoFactorAuthentication()) & Features::canManagetwoFactorAuthentication()) {
        // Two factor authentication....
        $twoFactorLimiter = config('lara2fa.limiters.two-factor');

        Route::get('/two-factor-challenge', [TwoFactorAuthenticatedSessionController::class, 'create'])
                ->middleware(['guest:'.config('fortify.guard')])
                ->name('two-factor.login');

        Route::post('/two-factor-challenge', [TwoFactorAuthenticatedSessionController::class, 'store'])
        ->middleware(array_filter([
            'guest:'.config('fortify.guard'),
            $twoFactorLimiter ? 'throttle:'.$twoFactorLimiter : null,
        ]));
    
            
        if(Features::enabled(Features::authenticatorAppTwoFactorAuthentication())){
            $authenticatorAppTwoFactorMiddleware = (Features::confirmsPasswordAuthenticatorAppTwoFactorAuthentication())
                ? ['auth:'.config('fortify.guard'), 'password.confirm']
                : ['auth:'.config('fortify.guard')];

            Route::post('/settings/authenticator-app-two-factor-authentication', [AuthenticatorAppTwoFactorAuthenticationController::class, 'store'])
                ->middleware($authenticatorAppTwoFactorMiddleware)
                ->name('authenticator-app-two-factor.enable');
    
            Route::post( '/settings/confirmed-authenticator-app-two-factor-authentication', [AuthenticatorAppTwoFactorAuthenticationController::class, 'confirm'])
                ->middleware($authenticatorAppTwoFactorMiddleware)
                ->name('authenticator-app-two-factor.confirm');
    
            Route::delete('/settings/authenticator-app-two-factor-authentication', [AuthenticatorAppTwoFactorAuthenticationController::class, 'destroy'])
                ->middleware($authenticatorAppTwoFactorMiddleware)
                ->name('authenticator-app-two-factor.disable');
    
            Route::get('/settings/authenticator-app-two-factor-qr-code', [AuthenticatorAppTwoFactorAuthenticationController::class, 'qrCode'])
                ->middleware($authenticatorAppTwoFactorMiddleware)
                ->name('authenticator-app-two-factor.qr-code');
    
            Route::get('/settings/authenticator-app-two-factor-secret-key', [AuthenticatorAppTwoFactorAuthenticationController::class, 'secretKey'])
                ->middleware($authenticatorAppTwoFactorMiddleware)
                ->name('authenticator-app-two-factor.secret-key');
        }

        if(Features::enabled(Features::emailTwoFactorAuthentication())){
            $emailTwoFactorMiddleware = (Features::confirmsPasswordEmailTwoFactorAuthentication())
                ? ['auth:'.config('fortify.guard'), 'password.confirm']
                : ['auth:'.config('fortify.guard')];

            Route::post('/settings/email-two-factor-authentication', [EmailTwoFactorAuthenticationController::class, 'store'])
                ->middleware($emailTwoFactorMiddleware)
                ->name('email-two-factor.enable');
    
            Route::post('/settings/confirmed-email-two-factor-authentication', [EmailTwoFactorAuthenticationController::class, 'confirm'])
                ->middleware($emailTwoFactorMiddleware)
                ->name('email-two-factor.confirm');
    
            Route::delete('/settings/email-two-factor-authentication', [EmailTwoFactorAuthenticationController::class, 'destroy'])
                ->middleware($emailTwoFactorMiddleware)
                ->name('email-two-factor.disable');

            $twoFactorEmailNotifyLimiter = config('lara2fa.limiters.two-factor-email-notify');
            
            Route::post('/settings/email-two-factor-authentication-send-code', [EmailTwoFactorAuthenticationController::class, 'notify'])
                ->middleware($twoFactorEmailNotifyLimiter ? ['throttle:'.$twoFactorEmailNotifyLimiter] : [])
                ->name('email-two-factor.send-code');
        }

        if(Features::enabled(Features::passkeys())){
            $passkeysTwoFactorMiddleware = (Features::confirmsPasswordPasskeys())
                ? ['auth:'.config('fortify.guard'), 'password.confirm']
                : ['auth:'.config('fortify.guard')];

            Route::get('/settings/passkeys-two-factor-authentication', [PasskeysTwoFactorAuthenticationController::class, 'index'])
                ->middleware($passkeysTwoFactorMiddleware)
                ->name('passkeys-two-factor.get');

            Route::get('/settings/passkeys-two-factor-authentication-registerOptions', [PasskeysTwoFactorAuthenticationOptionsController::class, 'registerOptions'])
                ->middleware($passkeysTwoFactorMiddleware)
                ->name('passkeys-two-factor.getRegisterOptions');

            Route::post('/settings/passkeys-two-factor-authentication', [PasskeysTwoFactorAuthenticationController::class, 'store'])
                ->middleware($passkeysTwoFactorMiddleware)
                ->name('passkeys-two-factor.store');

            Route::delete('/settings/passkeys-two-factor-authentication', [PasskeysTwoFactorAuthenticationController::class, 'disable'])
                ->middleware($passkeysTwoFactorMiddleware)
                ->name('passkeys-two-factor.disable');
    
            Route::delete('/settings/passkeys-two-factor-authentication/{passkey}/destroy', [PasskeysTwoFactorAuthenticationController::class, 'destroy'])
                ->middleware($passkeysTwoFactorMiddleware)
                ->name('passkeys-two-factor.destroy');

            Route::put('/settings/passkeys-two-factor-authentication/{passkey}/update', [PasskeysTwoFactorAuthenticationController::class, 'update'])
                ->middleware($passkeysTwoFactorMiddleware)
                ->name('passkeys-two-factor.update');
        }

        if(Features::enabled(Features::recoveryCodes())){
            $recoveryCodesMiddleware = (Features::confirmsPasswordRecoveryCode())
                ? ['auth:'.config('fortify.guard'), 'password.confirm']
                : ['auth:'.config('fortify.guard')];

            Route::get('/settings/two-factor-recovery-codes', [RecoveryCodeController::class, 'index'])
                ->middleware($recoveryCodesMiddleware)
                ->name('two-factor-recovery-codes.get');
            
            Route::post('/settings/two-factor-recovery-codes', [RecoveryCodeController::class, 'store'])
                ->middleware($recoveryCodesMiddleware)
                ->name('two-factor-recovery-codes.generate');

            Route::delete('/settings/two-factor-recovery-codes', [RecoveryCodeController::class, 'destroy'])
                ->middleware($recoveryCodesMiddleware)
                ->name('two-factor-recovery-codes.disable');
        }
    }

    if(Features::enabled(Features::passkeys())){
        // Passkey Authentication
        $limiterMiddleware = ($limiter = config('lara2fa.limiters.passkey-login')) !== null
            ? 'throttle:'.$limiter
            : null;

        Route::group(['middleware' => array_filter(['guest:'.config('fortify.guard', 'web'), $limiterMiddleware])], function () {
            Route::get('passkeys-two-factor/authenticateOptions', [PasskeysTwoFactorAuthenticationOptionsController::class, 'authenticateOptions'])->name('passkeys-two-factor.authenticateOptions');
            Route::post('passkeys-two-factor/authenticate', [PasskeysTwoFactorAuthenticationController::class, 'authenticate'])->name('passkeys-two-factor.authenticate');
        });
    }
});
