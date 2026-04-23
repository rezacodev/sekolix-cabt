<?php

namespace App\Filament\Resources\CategoryResource\Pages;

use App\Filament\Concerns\HasHelpHeader;
use App\Filament\Resources\CategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCategories extends ListRecords
{
    use HasHelpHeader;

    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return $this->appendHelpAction([
            Actions\CreateAction::make(),
        ]);
    }

    protected function getHelpModalView(): string
    {
        return 'filament.pages.actions.modal-help-category';
    }
}
