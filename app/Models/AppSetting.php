<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AppSetting extends Model
{
    public $timestamps = false;

    protected $table = 'app_settings';

    protected $fillable = ['key', 'value', 'tipe', 'updated_at'];

    private const CACHE_TTL = 3600; // 1 hour

    // ── Read ─────────────────────────────────────────────────────────────────

    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = Cache::remember("setting:{$key}", self::CACHE_TTL, function () use ($key) {
            return static::where('key', $key)->first();
        });

        if (! $setting) {
            return $default;
        }

        return match ($setting->tipe) {
            'bool'  => (bool)  $setting->value,
            'int'   => (int)   $setting->value,
            'json'  => json_decode($setting->value, true),
            default => $setting->value,
        };
    }

    public static function getBool(string $key, bool $default = false): bool
    {
        $val = static::get($key);
        return $val === null ? $default : (bool) $val;
    }

    public static function getInt(string $key, int $default = 0): int
    {
        $val = static::get($key);
        return $val === null ? $default : (int) $val;
    }

    public static function getString(string $key, string $default = ''): string
    {
        $val = static::get($key);
        return $val === null ? $default : (string) $val;
    }

    // ── Write ────────────────────────────────────────────────────────────────

    public static function set(string $key, mixed $value, string $tipe = 'string'): void
    {
        $stored = is_bool($value) ? (int) $value : $value;

        static::updateOrCreate(
            ['key' => $key],
            ['value' => $stored, 'tipe' => $tipe, 'updated_at' => now()]
        );

        Cache::forget("setting:{$key}");
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /** Return all settings as key => value array (bypasses cache for admin page). */
    public static function allRaw(): array
    {
        return static::all()->pluck('value', 'key')->all();
    }
}
