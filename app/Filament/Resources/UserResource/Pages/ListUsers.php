<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Exports\UsersExport;
use App\Exports\UsersImportTemplate;
use App\Filament\Resources\UserResource;
use App\Services\ImportService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
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
                ->action(fn () => Excel::download(new UsersImportTemplate(), 'template-import-user.xlsx')),

            // Import massal via Excel
            Actions\Action::make('import')
                ->label('Import Excel')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('info')
                ->form([
                    Forms\Components\FileUpload::make('file')
                        ->label('File Excel (.xlsx)')
                        ->required()
                        ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                        ->disk('local')
                        ->directory('imports/users'),
                ])
                ->action(function (array $data) {
                    $path = storage_path('app/' . $data['file']);
                    $result = ImportService::importUser($path);

                    $msg = "Berhasil import {$result['imported']} user.";
                    if (!empty($result['errors'])) {
                        $msg .= ' Gagal: ' . count($result['errors']) . ' baris. Error pertama: ' . $result['errors'][0];
                    }

                    Notification::make()
                        ->title($msg)
                        ->color(empty($result['errors']) ? 'success' : 'warning')
                        ->persistent(empty($result['errors']) ? false : true)
                        ->send();
                }),

            // Export ke Excel
            Actions\Action::make('export')
                ->label('Export Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(fn () => Excel::download(new UsersExport(), 'daftar-user-' . now()->format('Ymd') . '.xlsx')),

            Actions\CreateAction::make(),
        ];
    }
}
