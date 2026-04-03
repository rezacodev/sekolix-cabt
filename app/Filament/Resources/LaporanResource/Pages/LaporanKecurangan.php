<?php

namespace App\Filament\Resources\LaporanResource\Pages;

use App\Exports\KecuranganExport;
use App\Filament\Resources\LaporanResource;
use App\Models\ExamAttempt;
use App\Models\ExamSession;
use App\Services\ReportService;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Maatwebsite\Excel\Facades\Excel;

class LaporanKecurangan extends Page
{
  protected static string $resource = LaporanResource::class;

  protected static string $view = 'filament.resources.laporan-resource.pages.laporan-kecurangan';

  public ExamSession $record;

  // ── Filter state ──────────────────────────────────────────────────────────
  public int $filter_tab_switch = 0;
  public bool $filter_kicked = false;
  public bool $filter_auto_submit = false;

  public function mount(ExamSession $record): void
  {
    $this->record = $record->load(['package', 'creator']);
  }

  public function getTitle(): string
  {
    return 'Rekap Kecurangan — ' . str($this->record->nama_sesi)->limit(40)->toString();
  }

  public function getBreadcrumbs(): array
  {
    $sesi = str($this->record->nama_sesi)->limit(35)->toString();
    return [
      LaporanResource::getUrl()                                   => 'Laporan Ujian',
      LaporanNilai::getUrl(['record' => $this->record->id])       => $sesi,
      '#'                                                          => 'Rekap Kecurangan',
    ];
  }

  protected function getHeaderActions(): array
  {
    return [
      Action::make('export_excel')
        ->label('Export Excel')
        ->icon('heroicon-o-arrow-down-tray')
        ->color('success')
        ->action(function () {
          $data = app(ReportService::class)->rekapKecurangan($this->record->id);
          return Excel::download(
            new KecuranganExport($data),
            'rekap-kecurangan-' . str($this->record->nama_sesi)->slug() . '.xlsx'
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
    $rekap = app(ReportService::class)->rekapKecurangan($this->record->id);

    // Summary cards
    $totalPeserta   = $rekap->count();
    $totalTabSwitch = $rekap->sum('tab_switch');
    $totalKick      = $rekap->sum('kick');
    $totalAutoSubmit = $rekap->where('auto_submitted', true)->count();
    $pesertaPelanggaran = $rekap->filter(fn($r) => ($r->tab_switch + $r->blur + $r->kick) > 0)->count();

    // Apply filters
    $filtered = $rekap;

    if ($this->filter_tab_switch > 0) {
      $filtered = $filtered->filter(fn($r) => $r->tab_switch >= $this->filter_tab_switch);
    }
    if ($this->filter_kicked) {
      $filtered = $filtered->filter(fn($r) => $r->kick > 0);
    }
    if ($this->filter_auto_submit) {
      $filtered = $filtered->filter(fn($r) => $r->auto_submitted);
    }

    return [
      'session'            => $this->record,
      'rekap'              => $filtered->values(),
      'totalPeserta'       => $totalPeserta,
      'totalTabSwitch'     => $totalTabSwitch,
      'totalKick'          => $totalKick,
      'totalAutoSubmit'    => $totalAutoSubmit,
      'pesertaPelanggaran' => $pesertaPelanggaran,
    ];
  }
}
