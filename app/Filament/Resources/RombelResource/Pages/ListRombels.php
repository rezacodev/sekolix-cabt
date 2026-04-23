<?php

namespace App\Filament\Resources\RombelResource\Pages;

use App\Filament\Concerns\HasHelpHeader;
use App\Filament\Resources\RombelResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRombels extends ListRecords
{
    use HasHelpHeader;

    protected static string $resource = RombelResource::class;

    protected function getHeaderActions(): array
    {
        return $this->appendHelpAction([
            Actions\CreateAction::make(),
        ]);
    }

    protected function getHelpModalView(): string
    {
        return 'filament.pages.actions.modal-help-rombel';
    }
}
