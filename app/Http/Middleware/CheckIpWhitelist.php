<?php

namespace App\Http\Middleware;

use App\Models\AppSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckIpWhitelist
{
    public function handle(Request $request, Closure $next): Response
    {
        $whitelist = AppSetting::getString('ip_whitelist', '');

        // Jika whitelist kosong, semua IP diizinkan
        if (empty(trim($whitelist))) {
            return $next($request);
        }

        $allowed = array_map('trim', explode(',', $whitelist));

        if (! in_array($request->ip(), $allowed, true)) {
            abort(403, 'Akses dari IP Anda tidak diizinkan untuk mengikuti ujian ini.');
        }

        return $next($request);
    }
}
