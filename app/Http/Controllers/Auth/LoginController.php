<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    public function showLoginForm(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $user = Auth::user();

            // Set tenant context in session
            if ($user->tenant_id) {
                session(['tenant_id' => $user->tenant_id]);
            }

            // Redirect based on onboarding status
            $tenant = $user->tenant_id ? \App\Models\Tenant::find($user->tenant_id) : null;

            if ($tenant && !$tenant->onboarding_completed) {
                return redirect('/onboarding');
            }

            return redirect()->intended('/');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->input('reason') === 'inactivity' || $request->query('reason') === 'inactivity') {
            return redirect('/login?timeout=1')->with('info', 'You were automatically logged out due to inactivity.');
        }

        return redirect('/login');
    }
}
