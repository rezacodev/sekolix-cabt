<?php

namespace App\Filament\Resources\LaporanResource\Pages;

use App\Filament\Resources\LaporanResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListLaporan extends ListRecords
{
    protected static string $resource = LaporanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('komparasi')
                ->label('Bandingkan Sesi')
                ->icon('heroicon-o-scale')
                ->color('info')
                ->url(KomparasiSesi::getUrl()),
        ];
    }

    public function getTitle(): string
    {
        return 'Laporan Ujian';
    }
}
