<?php

namespace App\Filament\Resources\CurriculumStandardResource\Pages;

use App\Filament\Concerns\HasHelpHeader;
use App\Filament\Resources\CurriculumStandardResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCurriculumStandards extends ListRecords
{
    use HasHelpHeader;

    protected static string $resource = CurriculumStandardResource::class;

    protected function getHeaderActions(): array
    {
        return $this->appendHelpAction([
            Actions\CreateAction::make(),
        ]);
    }

    protected function getHelpModalView(): string
    {
        return 'filament.pages.actions.modal-help-curriculum-standard';
    }
}
