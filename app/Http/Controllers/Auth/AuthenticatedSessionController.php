<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $user = Auth::user();

        // One-session check: peserta (Level 1) tidak boleh login ganda
        if ($user->level === User::LEVEL_PESERTA) {
            // Kecualikan session saat ini (anonim, sebelum regenerate)
            $currentSessionId = $request->session()->getId();

            $hasActiveSession = DB::table('sessions')
                ->where('user_id', $user->id)
                ->where('id', '!=', $currentSessionId)
                ->exists();

            if ($hasActiveSession) {
                Auth::logout();

                throw ValidationException::withMessages([
                    'login' => 'Akun ini sedang aktif digunakan di perangkat lain. Hubungi pengawas jika ada masalah.',
                ]);
            }
        }

        $request->session()->regenerate();

        // Redirect berdasarkan level
        if ($user->level >= User::LEVEL_GURU) {
            return redirect()->intended('/cabt');
        }

        // Peserta → dashboard ujian
        return redirect()->intended(route('peserta.dashboard'));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
