<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Tampilkan halaman login aplikasi SPK PMB.
     */
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard.index');
        }

        return view('auth.login');
    }

    /**
     * Proses permintaan autentikasi pengguna.
     */
    public function login(LoginRequest $request): RedirectResponse
    {
        $kredensial = $request->validated();

        if (! Auth::attempt($kredensial, $request->boolean('remember'))) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => 'Kombinasi email dan kata sandi tidak sesuai.',
                ]);
        }

        $request->session()->regenerate();

        return redirect()
            ->intended(route('dashboard.index'))
            ->with('toast_success', 'Selamat datang kembali, ' . $request->user()->name . '.');
    }

    /**
     * Keluar dari sesi pengguna aktif.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}

