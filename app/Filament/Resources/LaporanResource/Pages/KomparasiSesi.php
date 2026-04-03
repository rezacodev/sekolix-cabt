<?php

namespace App\Filament\Resources\LaporanResource\Pages;

use App\Exports\KomparasiExport;
use App\Filament\Resources\LaporanResource;
use App\Models\ExamSession;
use App\Models\User;
use App\Services\ReportService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class KomparasiSesi extends Page
{
  protected static string $resource = LaporanResource::class;

  protected static string $view = 'filament.resources.laporan-resource.pages.komparasi-sesi';

  // ── Filter state ──────────────────────────────────────────────────────────
  public ?int $sesi_a = null;
  public ?int $sesi_b = null;
  public string $mode = 'peserta';

  public function getTitle(): string
  {
    return 'Komparasi Antar Sesi';
  }

  public function getBreadcrumbs(): array
  {
    return [
      LaporanResource::getUrl() => 'Laporan Ujian',
      '#'                       => 'Komparasi Sesi',
    ];
  }

  protected function getHeaderActions(): array
  {
    return [
      Action::make('export_excel')
        ->label('Export Excel')
        ->icon('heroicon-o-arrow-down-tray')
        ->color('success')
        ->visible(fn(): bool => $this->sesi_a !== null && $this->sesi_b !== null)
        ->action(function () {
          if (! $this->sesi_a || ! $this->sesi_b) {
            Notification::make()->title('Pilih kedua sesi terlebih dahulu.')->warning()->send();
            return;
          }
          $data  = app(ReportService::class)->komparasiSesi($this->sesi_a, $this->sesi_b, $this->mode);
          $sesiA = ExamSession::find($this->sesi_a);
          $sesiB = ExamSession::find($this->sesi_b);

          return Excel::download(
            new KomparasiExport($data, $sesiA?->nama_sesi ?? 'A', $sesiB?->nama_sesi ?? 'B'),
            'komparasi-sesi.xlsx'
          );
        }),

      Action::make('kembali')
        ->label('Kembali')
        ->icon('heroicon-o-arrow-left')
        ->color('gray')
        ->url(LaporanResource::getUrl()),
    ];
  }

  public function getViewData(): array
  {
    $result    = null;
    $statistik = null;
    $scatterA  = [];
    $scatterB  = [];
    $sesiA     = null;
    $sesiB     = null;

    if ($this->sesi_a && $this->sesi_b) {
      $sesiA  = ExamSession::find($this->sesi_a);
      $sesiB  = ExamSession::find($this->sesi_b);
      $result = app(ReportService::class)->komparasiSesi($this->sesi_a, $this->sesi_b, $this->mode);

      $valid = $result->filter(fn($r) => $r->nilai_a !== null && $r->nilai_b !== null);

      $selisih  = $valid->pluck('selisih');
      $naik     = $valid->where('tren', 'naik')->count();
      $turun    = $valid->where('tren', 'turun')->count();
      $tetap    = $valid->where('tren', 'tetap')->count();
      $total    = $valid->count();

      $statistik = [
        'total'         => $result->count(),
        'valid'         => $total,
        'rata_selisih'  => $total > 0 ? round((float) $selisih->avg(), 2) : 0,
        'persen_naik'   => $total > 0 ? round($naik / $total * 100, 1) : 0,
        'persen_turun'  => $total > 0 ? round($turun / $total * 100, 1) : 0,
        'persen_tetap'  => $total > 0 ? round($tetap / $total * 100, 1) : 0,
        'naik'          => $naik,
        'turun'         => $turun,
        'tetap'         => $tetap,
      ];

      $scatterPoints = $valid->map(fn($r) => ['x' => $r->nilai_a, 'y' => $r->nilai_b, 'label' => $r->nama])->values()->toArray();
    } else {
      $scatterPoints = [];
    }

    /** @var \App\Models\User $user */
    $user = Auth::user();
    $sesiQuery = ExamSession::whereIn('status', [ExamSession::STATUS_AKTIF, ExamSession::STATUS_SELESAI]);
    if ($user->level === User::LEVEL_GURU) {
      $sesiQuery->where('created_by', $user->id);
    }
    $sesiOptions = $sesiQuery->orderByDesc('waktu_mulai')->get()->pluck('nama_sesi', 'id');

    return [
      'sesiOptions'   => $sesiOptions,
      'result'        => $result,
      'statistik'     => $statistik,
      'scatterPoints' => $scatterPoints,
      'sesiA'         => $sesiA,
      'sesiB'         => $sesiB,
    ];
  }
}
