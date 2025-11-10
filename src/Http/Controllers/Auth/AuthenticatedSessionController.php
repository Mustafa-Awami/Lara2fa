<?php

namespace MustafaAwami\Lara2fa\Http\Controllers\Auth;

use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use MustafaAwami\Lara2fa\Lara2fa;
use MustafaAwami\Lara2fa\Features;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;
use Illuminate\Contracts\Auth\StatefulGuard;
use MustafaAwami\Lara2fa\Http\Requests\Auth\LoginRequest;

class AuthenticatedSessionController extends Controller
{

    /**
     * The guard implementation.
     *
     * @var \Illuminate\Contracts\Auth\StatefulGuard
     */
    protected $guard;

    /**
     * Create a new controller instance.
     *
     * @param  \Illuminate\Contracts\Auth\StatefulGuard  $guard
     * @return void
     */
    public function __construct(StatefulGuard $guard)
    {
        $this->guard = $guard;
    }
    
    /**
     * Show the login page.
     */
    public function create(Request $request): Response
    {

        if (Lara2fa::stack() === "react") {
            $view = "auth/login";
        } elseif (Lara2fa::stack() === "vue") {
            $view = "auth/Login";
        }

        return Inertia::render($view, [
            'canResetPassword' => Route::has('password.request'),
            'status' => $request->session()->get('status'),
            'canUsePasskeys' => Lara2fa::canPasskeysUsedForSingleFactorAuthentication()
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request)
    {
        $user = $request->validateCredentials();

        if (Features::canManagetwoFactorAuthentication() && $user->hasEnabledTwoFactorAuthentication()) {
            $request->session()->put([
                'login.id' => $user->getKey(),
                'login.remember' => $request->boolean('remember'),
            ]);

            return to_route('two-factor.login');
        }

        Auth::login($user, $request->boolean('remember'));

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $this->guard->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
