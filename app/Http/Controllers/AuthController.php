<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $cle = 'connexion:'.$request->ip();

        if (RateLimiter::tooManyAttempts($cle, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Trop de tentatives. Réessayez dans '.RateLimiter::availableIn($cle).' secondes.',
            ]);
        }

        $identifiants = $request->safe()->only(['email', 'password']);
        $identifiants['actif'] = true;

        if (! Auth::attempt($identifiants, $request->boolean('remember'))) {
            RateLimiter::hit($cle, 60);

            throw ValidationException::withMessages([
                'email' => 'Identifiants invalides.',
            ]);
        }

        RateLimiter::clear($cle);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
