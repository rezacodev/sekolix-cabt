<?php

namespace App\Filament\Resources\LaporanResource\Pages;

use App\Filament\Concerns\HasHelpHeader;
use App\Filament\Resources\LaporanResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListLaporan extends ListRecords
{
    use HasHelpHeader;

    protected static string $resource = LaporanResource::class;

    protected function getHeaderActions(): array
    {
        return $this->appendHelpAction([
            Action::make('komparasi')
                ->label('Bandingkan Sesi')
                ->icon('heroicon-o-scale')
                ->color('info')
                ->url(KomparasiSesi::getUrl()),
        ]);
    }

    protected function getHelpModalView(): string
    {
        return 'filament.pages.actions.modal-help-laporan';
    }

    public function getTitle(): string
    {
        return 'Laporan Ujian';
    }
}
