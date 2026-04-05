<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Exports\UsersExport;
use App\Exports\UsersImportTemplate;
use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Download template Excel
            Actions\Action::make('download_template')
                ->label('Template Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->action(fn() => Excel::download(new UsersImportTemplate(), 'template-import-user.xlsx')),

            // Import massal via Excel (halaman preview)
            Actions\Action::make('import')
                ->label('Import Excel')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('info')
                ->url(ImportUsers::getUrl()),

            // Export ke Excel
            Actions\Action::make('export')
                ->label('Export Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(fn() => Excel::download(new UsersExport(), 'daftar-user-' . now()->format('Ymd') . '.xlsx')),

            Actions\CreateAction::make(),
        ];
    }
}
