<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class CheckLevel
{
    /**
     * Validasi bahwa user yang login memiliki level minimal yang dibutuhkan.
     * Penggunaan: Route::middleware('check.level:2') untuk mewajibkan level ≥ 2.
     */
    /** @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse */
    public function handle(Request $request, Closure $next, int $minLevel = 1)
    {
        if (! Auth::check() || Auth::user()->level < $minLevel) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        if (! Auth::user()->aktif) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['email' => 'Akun Anda telah dinonaktifkan.']);
        }

        return $next($request);
    }
}
