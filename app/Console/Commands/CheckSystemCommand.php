<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckSystemCommand extends Command
{
    protected $signature   = 'cabt:check';
    protected $description = 'Cek kondisi server: PHP extensions, temp dir, memory, dll.';

    public function handle(): int
    {
        $this->info('=== Sekolix CABT — System Check ===');
        $this->newLine();

        // 1. PHP Version
        $this->line('<fg=cyan>PHP Version:</> ' . PHP_VERSION);
        $this->newLine();

        // 2. Required extensions
        $this->line('<fg=cyan>PHP Extensions (required for Excel export):</>');
        $extensions = ['zip', 'xml', 'gd', 'mbstring', 'fileinfo', 'bcmath', 'intl', 'openssl'];
        $allOk = true;
        foreach ($extensions as $ext) {
            $loaded = extension_loaded($ext);
            $allOk  = $allOk && $loaded;
            $status = $loaded ? '<fg=green>✓ loaded</>' : '<fg=red>✗ MISSING</>';
            $this->line("  ext-{$ext}: {$status}");
        }
        $this->newLine();

        // 3. PHP limits
        $this->line('<fg=cyan>PHP Limits:</>');
        $this->line('  memory_limit     : ' . ini_get('memory_limit'));
        $this->line('  max_execution_time: ' . ini_get('max_execution_time') . 's');
        $this->line('  upload_max_filesize: ' . ini_get('upload_max_filesize'));
        $this->newLine();

        // 4. Temp directory (used by PhpSpreadsheet)
        $tempDir   = sys_get_temp_dir();
        $tempWrite = is_writable($tempDir);
        $this->line('<fg=cyan>System Temp Dir:</>');
        $this->line('  path     : ' . $tempDir);
        $this->line('  writable : ' . ($tempWrite ? '<fg=green>yes</>' : '<fg=red>NO — export will fail</>'));
        $this->newLine();

        // 5. Laravel Excel temp path (from config)
        $excelTemp      = config('excel.temporary_files.local_path', storage_path('framework/cache/laravel-excel'));
        $excelTempExists = is_dir($excelTemp);
        $excelTempWrite  = $excelTempExists && is_writable($excelTemp);
        $this->line('<fg=cyan>Laravel Excel Temp Path:</>');
        $this->line('  path     : ' . $excelTemp);
        $this->line('  exists   : ' . ($excelTempExists ? '<fg=green>yes</>' : '<fg=yellow>no (will be created)</>'));
        $this->line('  writable : ' . ($excelTempWrite ? '<fg=green>yes</>' : ($excelTempExists ? '<fg=red>NO — export will fail</>' : '<fg=yellow>unknown (dir not yet created)</>')));
        $this->newLine();

        // 6. Storage directories
        $this->line('<fg=cyan>Storage Directories:</>');
        $dirs = [
            'storage/framework/cache'               => storage_path('framework/cache'),
            'storage/framework/cache/laravel-excel' => storage_path('framework/cache/laravel-excel'),
            'storage/framework/sessions'            => storage_path('framework/sessions'),
            'storage/framework/views'               => storage_path('framework/views'),
            'storage/logs'                          => storage_path('logs'),
        ];
        foreach ($dirs as $label => $path) {
            $exists   = is_dir($path);
            $writable = $exists && is_writable($path);
            $status   = !$exists ? '<fg=red>✗ not found</>' : ($writable ? '<fg=green>✓ writable</>' : '<fg=red>✗ NOT writable</>');
            $this->line("  {$label}: {$status}");
        }
        $this->newLine();

        // 7. Log file size
        $logFile = storage_path('logs/laravel.log');
        if (file_exists($logFile)) {
            $size = round(filesize($logFile) / 1024 / 1024, 1);
            $color = $size > 50 ? 'red' : ($size > 10 ? 'yellow' : 'green');
            $this->line("<fg=cyan>Log File Size:</> <fg={$color}>{$size} MB</>");
            if ($size > 50) {
                $this->line('  <fg=yellow>Tips: kosongkan log lama → truncate storage/logs/laravel.log</>');
            }
        }
        $this->newLine();

        if ($allOk) {
            $this->info('✓ Semua extension tersedia. Jika export masih gagal, cek log/error setelah jalankan export.');
        } else {
            $this->error('✗ Ada extension yang hilang — aktifkan di PHP Synology Web Station.');
        }

        return self::SUCCESS;
    }
}
