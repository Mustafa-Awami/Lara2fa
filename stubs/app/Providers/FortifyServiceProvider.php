<?php

namespace App\Providers;

use Inertia\Inertia;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Laravel\Fortify\Fortify;
use MustafaAwami\Lara2fa\Lara2fa;
use Laravel\Fortify\Features;
use App\Actions\Fortify\CreateNewUser;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use App\Actions\Fortify\ResetUserPassword;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Contracts\Auth\StatefulGuard;
use MustafaAwami\Lara2fa\Features as Lara2faFeatures;
use Laravel\Fortify\Actions\CanonicalizeUsername;
use Laravel\Fortify\Actions\AttemptToAuthenticate;
use Illuminate\Http\Exceptions\HttpResponseException;
use Laravel\Fortify\Actions\EnsureLoginIsNotThrottled;
use Laravel\Fortify\Actions\PrepareAuthenticatedSession;
use MustafaAwami\Lara2fa\Contracts\FailedTwoFactorLoginResponse;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use MustafaAwami\Lara2fa\Actions\RedirectIfTwoFactorAuthenticatable as Lara2faRedirectIfTwoFactorAuthenticatable;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureActions();
        $this->configureViews();
        $this->configureRateLimiting();

        Fortify::authenticateThrough(function (Request $request) {
            return array_filter([
                    config('fortify.limiters.login') ? null : EnsureLoginIsNotThrottled::class,
                    config('fortify.lowercase_usernames') ? CanonicalizeUsername::class : null,
                    Features::enabled(Features::twoFactorAuthentication()) ? RedirectIfTwoFactorAuthenticatable::class : 
                    (Lara2faFeatures::canManagetwoFactorAuthentication() ? Lara2faRedirectIfTwoFactorAuthenticatable::class : null),
                    AttemptToAuthenticate::class,
                    PrepareAuthenticatedSession::class,
            ]);
        });
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::createUsersUsing(CreateNewUser::class);
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(fn (Request $request) => Inertia::render(Lara2fa::getView("login"), [
            'canResetPassword' => Features::enabled(Features::resetPasswords()),
            'canRegister' => Features::enabled(Features::registration()),
            'status' => $request->session()->get('status'),
            'canUsePasskeys' => Lara2fa::canPasskeysUsedForSingleFactorAuthentication()
        ]));

        Fortify::resetPasswordView(fn (Request $request) => Inertia::render(Lara2fa::getView("reset-password"), [
            'email' => $request->email,
            'token' => $request->route('token'),
        ]));

        Fortify::requestPasswordResetLinkView(fn (Request $request) => Inertia::render(Lara2fa::getView("forgot-password"), [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::verifyEmailView(fn (Request $request) => Inertia::render(Lara2fa::getView("verify-email"), [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::registerView(fn () => Inertia::render(Lara2fa::getView("register")));

        Fortify::twoFactorChallengeView(function (Request $request) {
            $model = app(StatefulGuard::class)->getProvider()->getModel();

            if (! $request->session()->has('login.id') ||
                ! $user = $model::find($request->session()->get('login.id'))) {
                throw new HttpResponseException(
                    app(FailedTwoFactorLoginResponse::class)->toResponse($this)
                );
            }
            
            Inertia::render(Lara2fa::getView("two-factor-challenge"), [
                'twoFactorEnabled' => [
                    'authenticatorApp' => $user->hasEnabledAuthenticatorAppTwoFactorAuthentication(),
                    'email' => $user->hasEnabledEmailTwoFactorAuthentication(),
                    'recoveryCodes' => $user->hasEnabledTwoFactorRecoveryCodes(),
                    'passkeys' => Lara2fa::canPasskeysUsedForTwoFactorAuthentication() && $user->hasEnabledPasskeyAuthentication(),
                ]
            ]);
        });

        Fortify::confirmPasswordView(fn () => Inertia::render(Lara2fa::getView("confirm-password")));
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });
    }
}
