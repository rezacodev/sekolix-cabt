<?php

namespace App\Http\Middleware;

use App\Models\AppSetting;
use Closure;
use Illuminate\Http\Request;


class CheckMaintenance
{
    /** @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse */
    public function handle(Request $request, Closure $next)
    {
        if (AppSetting::getBool('maintenance_mode', false)) {
            abort(503, 'Sistem sedang dalam pemeliharaan. Silakan coba beberapa saat lagi.');
        }

        return $next($request);
    }
}
