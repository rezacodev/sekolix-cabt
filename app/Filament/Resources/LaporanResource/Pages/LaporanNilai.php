<?php

namespace App\Filament\Resources\LaporanResource\Pages;

use App\Exports\NilaiExport;
use App\Filament\Resources\LaporanResource;
use App\Models\ExamSession;
use App\Services\ReportService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Resources\Pages\Page;
use Maatwebsite\Excel\Facades\Excel;

class LaporanNilai extends Page
{
    protected static string $resource = LaporanResource::class;

    protected static string $view = 'filament.resources.laporan-resource.pages.laporan-nilai';

    public ExamSession $record;

    public function mount(ExamSession $record): void
    {
        $this->record = $record->load(['package', 'creator']);
    }

    public function getTitle(): string
    {
        return 'Rekap Nilai — ' . str($this->record->nama_sesi)->limit(40)->toString();
    }

    public function getBreadcrumbs(): array
    {
        $sesi = str($this->record->nama_sesi)->limit(35)->toString();
        return [
            LaporanResource::getUrl()                             => 'Laporan Ujian',
            LaporanNilai::getUrl(['record' => $this->record->id]) => $sesi,
            '#'                                                   => 'Rekap Nilai',
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_excel')
                ->label('Export Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(fn() => Excel::download(
                    new NilaiExport($this->record),
                    'rekap-nilai-' . str($this->record->nama_sesi)->slug() . '.xlsx'
                )),

            Action::make('export_pdf')
                ->label('Export PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->url(fn() => route('laporan.pdf.nilai', $this->record->id))
                ->openUrlInNewTab(),

            ActionGroup::make([
                Action::make('cetak_nilai')
                    ->label('Cetak Rekap Nilai')
                    ->icon('heroicon-o-printer')
                    ->url(fn() => route('laporan.cetak.nilai', $this->record->id))
                    ->openUrlInNewTab(),

                Action::make('cetak_daftar_hadir')
                    ->label('Cetak Daftar Hadir')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->url(fn() => route('laporan.cetak.daftar-hadir', $this->record->id))
                    ->openUrlInNewTab(),

                Action::make('cetak_berita_acara')
                    ->label('Cetak Berita Acara')
                    ->icon('heroicon-o-document-text')
                    ->url(fn() => route('laporan.cetak.berita-acara', $this->record->id))
                    ->openUrlInNewTab(),
            ])
                ->label('Cetak')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->button(),

            Action::make('kembali')
                ->label('Kembali')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(LaporanResource::getUrl()),
        ];
    }

    public function getViewData(): array
    {
        $reportService = app(ReportService::class);

        $rekap         = $reportService->rekapNilai($this->record->id);
        $statistik     = $reportService->statistikNilai($this->record->id);
        $distribusiData = $reportService->distribusiNilai($this->record->id);

        return [
            'session'        => $this->record,
            'rekap'          => $rekap,
            'statistik'      => $statistik,
            'distribusiData' => $distribusiData,
        ];
    }
}
