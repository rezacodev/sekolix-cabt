<?php

namespace App\Console\Commands;

use App\Jobs\RecalculateItemStatistics;
use App\Models\ExamSession;
use App\Services\ItemAnalysisService;
use Illuminate\Console\Command;

class RecalculateItemStatisticsCommand extends Command
{
    protected $signature = 'item-stats:recalculate
                            {--session= : ID sesi ujian}
                            {--package= : ID paket ujian}
                            {--question= : ID soal}
                            {--all : Hitung ulang semua soal dari sesi aktif/selesai}
                            {--queue : Dispatch ke queue (hanya berlaku untuk --session dan --all)}';

    protected $description = 'Hitung ulang statistik butir soal (P-value, Discrimination Index, Distractor Distribution).';

    public function handle(ItemAnalysisService $service): int
    {
        if ($sessionId = $this->option('session')) {
            $this->info("Menghitung statistik soal untuk sesi ID: {$sessionId} ...");

            if ($this->option('queue')) {
                RecalculateItemStatistics::dispatch((int) $sessionId);
                $this->info('Job dikirim ke queue.');
            } else {
                $service->calculateForSession((int) $sessionId);
                $this->info('Selesai.');
            }

            return self::SUCCESS;
        }

        if ($packageId = $this->option('package')) {
            $this->info("Menghitung statistik soal untuk paket ID: {$packageId} ...");
            $service->calculateForPackage((int) $packageId);
            $this->info('Selesai.');
            return self::SUCCESS;
        }

        if ($questionId = $this->option('question')) {
            $this->info("Menghitung statistik untuk soal ID: {$questionId} ...");
            $stat = $service->calculateForQuestion((int) $questionId);

            $this->table(
                ['Field', 'Nilai', 'Interpretasi'],
                [
                    ['P-value',              number_format((float) ($stat->p_value ?? 0), 3),              $stat->pValueLabel()],
                    ['Discrimination Index', number_format((float) ($stat->discrimination_index ?? 0), 3), $stat->discriminationLabel()],
                    ['Total Attempts',       $stat->total_attempts,                                        ''],
                    ['Avg. Response (det)',  number_format((float) ($stat->avg_response_seconds ?? 0), 1), ''],
                ]
            );

            return self::SUCCESS;
        }

        if ($this->option('all')) {
            $sessions = ExamSession::whereIn('status', [
                ExamSession::STATUS_AKTIF,
                ExamSession::STATUS_SELESAI,
            ])->get();

            $this->info("Menghitung statistik untuk {$sessions->count()} sesi...");
            $bar = $this->output->createProgressBar($sessions->count());
            $bar->start();

            foreach ($sessions as $session) {
                if ($this->option('queue')) {
                    RecalculateItemStatistics::dispatch($session->id);
                } else {
                    $service->calculateForSession($session->id);
                }
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
            $this->info($this->option('queue') ? 'Semua job dikirim ke queue.' : 'Selesai.');
            return self::SUCCESS;
        }

        $this->error('Pilih salah satu opsi: --session=ID | --package=ID | --question=ID | --all');
        $this->line('Contoh:');
        $this->line('  php artisan item-stats:recalculate --session=5');
        $this->line('  php artisan item-stats:recalculate --question=42');
        $this->line('  php artisan item-stats:recalculate --all --queue');

        return self::FAILURE;
    }
}
