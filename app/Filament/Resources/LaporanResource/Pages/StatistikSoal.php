<?php

namespace App\Filament\Resources\LaporanResource\Pages;

use App\Filament\Resources\LaporanResource;
use App\Models\ExamSession;
use App\Services\ReportService;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;

class StatistikSoal extends Page
{
    protected static string $resource = LaporanResource::class;

    protected static string $view = 'filament.resources.laporan-resource.pages.statistik-soal';

    public ExamSession $record;

    public function mount(ExamSession $record): void
    {
        $this->record = $record->load(['package', 'creator']);
    }

    public function getTitle(): string
    {
        return 'Statistik Soal — ' . str($this->record->nama_sesi)->limit(40)->toString();
    }

    public function getBreadcrumbs(): array
    {
        $sesi = str($this->record->nama_sesi)->limit(35)->toString();
        return [
            LaporanResource::getUrl()                               => 'Laporan Ujian',
            StatistikSoal::getUrl(['record' => $this->record->id]) => $sesi,
            '#'                                                     => 'Statistik Soal',
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('rekap_nilai')
                ->label('Rekap Nilai')
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
        $soalStats = app(ReportService::class)->statistikSoal($this->record->id);

        return [
            'session'   => $this->record,
            'soalStats' => $soalStats,
        ];
    }
}
