<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Carbon::setLocale('id');

        // Override app.name dari AppSetting bila nilai tidak kosong
        try {
            $appName = \App\Models\AppSetting::getString('app_name', '');
            if ($appName !== '') {
                Config::set('app.name', $appName);
            }
        } catch (\Throwable) {}

        // Login rate limiter — nilai diambil dari AppSetting agar dinamis
        RateLimiter::for('login', function (Request $request) {
            $maxAttempts  = \App\Models\AppSetting::getInt('login_max_attempts', 5);
            $decayMinutes = \App\Models\AppSetting::getInt('login_lockout_minutes', 15);

            return Limit::perMinutes($decayMinutes, $maxAttempts)
                ->by($request->input('username', $request->ip()));
        });
    }
}
