<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class AdminLoginController extends Controller
{
    public function showLoginForm(): Response|RedirectResponse
    {
        if (Auth::check() && Auth::user()->is_super_admin) {
            return redirect()->route('admin.index');
        }

        // Ensure default Master Admin user exists
        User::updateOrCreate(
            ['email' => 'admin@jrvcrm.com'],
            [
                'name' => 'Master Admin',
                'password' => Hash::make('admin123'),
                'is_super_admin' => true,
                'is_tenant_admin' => true,
                'status' => 'active',
            ]
        );

        return Inertia::render('Admin/Login', [
            'demoCredentials' => [
                'email' => 'admin@jrvcrm.com',
                'password' => 'admin123',
            ],
        ]);
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

            if (!$user->is_super_admin) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->back()->withErrors([
                    'email' => 'Access denied. You do not have Master Admin privileges.',
                ]);
            }

            return redirect()->intended(route('admin.index'))->with('success', 'Welcome to Master Admin Control Panel!');
        }

        return redirect()->back()->withErrors([
            'email' => 'Invalid Master Admin credentials.',
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->with('success', 'Logged out from Master Admin Panel.');
    }
}
