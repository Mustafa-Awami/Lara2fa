<?php

namespace MustafaAwami\Lara2fa\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Date;

class ConfirmPasswordController extends Controller
{
    /**
     * Get the password confirmation status.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request)
    {
        $lastConfirmation = $request->session()->get(
            'auth.password_confirmed_at', 0
        );

        $lastConfirmed = (Date::now()->unix() - $lastConfirmation);

        $confirmed = $lastConfirmed < $request->input(
            'seconds', config('auth.password_timeout', 900)
        );

        return response()->json([
            'confirmed' => $confirmed,
        ], headers: array_filter([
            'X-Retry-After' => $confirmed ? $lastConfirmed : null,
        ]));
    }

    /**
     * confirm password
     */
    public function store(Request $request)
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $request->session()->put('auth.password_confirmed_at', Date::now()->unix());

        return redirect()->back();
    }
}
