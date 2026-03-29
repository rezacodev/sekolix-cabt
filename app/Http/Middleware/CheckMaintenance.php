<?php

namespace App\Http\Middleware;

use App\Models\AppSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenance
{
    public function handle(Request $request, Closure $next): Response
    {
        if (AppSetting::getBool('maintenance_mode', false)) {
            abort(503, 'Sistem sedang dalam pemeliharaan. Silakan coba beberapa saat lagi.');
        }

        return $next($request);
    }
}
