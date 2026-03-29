<?php

namespace App\Filament\Resources\LaporanResource\Pages;

use App\Filament\Resources\LaporanResource;
use App\Models\ExamSession;
use App\Services\ReportService;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;

class LaporanKehadiran extends Page
{
    protected static string $resource = LaporanResource::class;

    protected static string $view = 'filament.resources.laporan-resource.pages.laporan-kehadiran';

    public ExamSession $record;

    public function mount(ExamSession $record): void
    {
        $this->record = $record->load(['package', 'creator']);
    }

    public function getTitle(): string
    {
        return 'Rekap Kehadiran — ' . str($this->record->nama_sesi)->limit(40)->toString();
    }

    public function getBreadcrumbs(): array
    {
        $sesi = str($this->record->nama_sesi)->limit(35)->toString();
        return [
            LaporanResource::getUrl()                                 => 'Laporan Ujian',
            LaporanKehadiran::getUrl(['record' => $this->record->id]) => $sesi,
            '#'                                                        => 'Rekap Kehadiran',
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('cetak_daftar_hadir')
                ->label('Cetak Daftar Hadir')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn () => route('laporan.cetak.daftar-hadir', $this->record->id))
                ->openUrlInNewTab(),

            Action::make('rekap_nilai')
                ->label('Lihat Rekap Nilai')
                ->icon('heroicon-o-table-cells')
                ->color('primary')
                ->url(LaporanNilai::getUrl(['record' => $this->record->id])),

            Action::make('kembali')
                ->label('Kembali')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(LaporanResource::getUrl()),
        ];
    }

    public function getViewData(): array
    {
        $kehadiran = app(ReportService::class)->rekapKehadiran($this->record->id);

        return [
            'session'   => $this->record,
            'kehadiran' => $kehadiran,
        ];
    }
}
