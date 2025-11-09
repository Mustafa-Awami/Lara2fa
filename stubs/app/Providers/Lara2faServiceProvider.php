<?php

namespace App\Providers;


use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Laravel\Fortify\Fortify;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class Lara2faServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Lara2fa::ignoreRoutes();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('passkey-login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey)->response(function (Request $request,array $headers) use ($throttleKey) {

                $seconds = $headers['Retry-After'];

                throw ValidationException::withMessages([
                    Fortify::username() => [__('Too many attempts. Please try again after') . $seconds. __(' seconds')],
                ]);
            });
        });

        RateLimiter::for('two-factor-email-notify', function (Request $request) {

            $throttleKey = $request->session()->get('login.id');

            return Limit::perMinute(2)->by($throttleKey)->response(function (Request $request,array $headers) use ($throttleKey) {

                $seconds = $headers['Retry-After'];

                throw ValidationException::withMessages([
                    'attempts' => [__('Too many attempts. Please try again after ') . $seconds. __(' seconds')],
                ])->errorBag('EmailTwoFactorAuthenticationNotification');
            });
        });

        RateLimiter::for('two-factor-login', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'))->response(function (Request $request,array $headers) {
                $seconds = $headers['Retry-After'];

                throw ValidationException::withMessages([
                    'attempts' => [__('Too many attempts. Please try again after ') . $seconds. __(' seconds')],
                ]);
            });
        });
    }
}