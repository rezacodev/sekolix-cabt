<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use Illuminate\Database\Seeder;

class AppSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            // Identitas Aplikasi
            ['key' => 'app_name',                     'value' => 'CAT Sekolix', 'tipe' => 'string'],
            ['key' => 'school_name',                  'value' => '',             'tipe' => 'string'],
            ['key' => 'school_logo_url',              'value' => '',             'tipe' => 'string'],
            ['key' => 'maintenance_mode',             'value' => '0',            'tipe' => 'bool'],

            // Autentikasi & Sesi
            ['key' => 'allow_multi_login',            'value' => '0',            'tipe' => 'bool'],
            ['key' => 'login_max_attempts',           'value' => '5',            'tipe' => 'int'],
            ['key' => 'login_lockout_minutes',        'value' => '15',           'tipe' => 'int'],
            ['key' => 'session_timeout_minutes',      'value' => '0',            'tipe' => 'int'],

            // Anti-Kecurangan
            ['key' => 'max_tab_switch',               'value' => '3',            'tipe' => 'int'],
            ['key' => 'tab_switch_action',            'value' => 'warn',         'tipe' => 'string'],
            ['key' => 'auto_submit_on_max_tab',       'value' => '1',            'tipe' => 'bool'],
            ['key' => 'prevent_copy_paste',           'value' => '0',            'tipe' => 'bool'],
            ['key' => 'prevent_right_click',          'value' => '0',            'tipe' => 'bool'],
            ['key' => 'require_fullscreen',           'value' => '0',            'tipe' => 'bool'],
            ['key' => 'max_upload_mb',                'value' => '5',            'tipe' => 'int'],
            ['key' => 'ip_whitelist',                 'value' => '',             'tipe' => 'string'],

            // Penilaian & Tampilan
            ['key' => 'realtime_grading',             'value' => '1',            'tipe' => 'bool'],
            ['key' => 'show_ranking_hasil',           'value' => '0',            'tipe' => 'bool'],
            ['key' => 'show_pembahasan_setelah_sesi', 'value' => '0',            'tipe' => 'bool'],

            // Livescore
            ['key' => 'show_livescore',               'value' => '1',            'tipe' => 'bool'],
            ['key' => 'livescore_public',             'value' => '1',            'tipe' => 'bool'],
        ];

        foreach ($defaults as $row) {
            AppSetting::updateOrCreate(
                ['key' => $row['key']],
                ['value' => $row['value'], 'tipe' => $row['tipe'], 'updated_at' => now()]
            );
        }
    }
}
