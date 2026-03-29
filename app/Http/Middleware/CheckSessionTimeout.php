<?php

namespace App\Http\Middleware;

use App\Models\AppSetting;
use Closure;
use Illuminate\Http\Request;

class CheckSessionTimeout
{
    /** @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse */
    public function handle(Request $request, Closure $next)
    {
        $timeoutMinutes = AppSetting::getInt('session_timeout_minutes', 0);

        if ($timeoutMinutes > 0 && $request->user()) {
            $lastActivity = $request->session()->get('last_activity_at');

            if ($lastActivity) {
                $idleMinutes = now()->diffInMinutes(\Illuminate\Support\Carbon::parse($lastActivity));

                if ($idleMinutes >= $timeoutMinutes) {
                    $request->session()->flush();

                    return redirect()->route('login')
                        ->withErrors(['session' => 'Sesi Anda telah berakhir karena tidak ada aktivitas. Silakan login kembali.']);
                }
            }

            // Perbarui timestamp aktivitas terakhir di setiap request
            $request->session()->put('last_activity_at', now()->toISOString());
        }

        return $next($request);
    }
}
