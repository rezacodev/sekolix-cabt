<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Exports\UsersExport;
use App\Exports\UsersImportTemplate;
use App\Filament\Concerns\HasHelpHeader;
use App\Filament\Resources\UserResource;
use App\Filament\Resources\UserResource\Pages\ImportUsers;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

class ListUsers extends ListRecords
{
    use HasHelpHeader;

    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return $this->appendHelpAction([
            Actions\Action::make('download_template')
                ->label('Template Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->action(fn() => Excel::download(new UsersImportTemplate(), 'template-import-user.xlsx')),

            Actions\Action::make('import')
                ->label('Import Excel')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('info')
                ->url(ImportUsers::getUrl()),

            Actions\Action::make('export')
                ->label('Export Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(fn() => Excel::download(new UsersExport(), 'daftar-user-' . now()->format('Ymd') . '.xlsx')),

            Actions\CreateAction::make(),
        ]);
    }

    protected function getHelpModalView(): string
    {
        return 'filament.pages.actions.modal-help-user';
    }
}
