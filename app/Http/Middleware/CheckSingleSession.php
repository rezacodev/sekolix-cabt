<?php

namespace App\Http\Middleware;

use App\Models\AppSetting;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


class CheckSingleSession
{
    /**
     * Memastikan sesi peserta (Level 1) masih valid.
     * Jika session dihapus oleh admin (Paksa Logout), user diredirect ke login.
     */
    /** @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if ($user && $user->level === User::LEVEL_PESERTA) {
            // allow_multi_login setting bypasses single-session check
            if (AppSetting::getBool('allow_multi_login', false)) {
                return $next($request);
            }

            $sessionExists = DB::table('sessions')
                ->where('id', $request->session()->getId())
                ->where('user_id', $user->id)
                ->exists();

            if (! $sessionExists) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')
                    ->withErrors(['email' => 'Sesi Anda telah diakhiri oleh sistem. Silakan login kembali.']);
            }
        }

        return $next($request);
    }
}
