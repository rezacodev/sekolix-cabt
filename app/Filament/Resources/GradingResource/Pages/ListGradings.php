<?php

namespace App\Filament\Resources\GradingResource\Pages;

use App\Filament\Concerns\HasHelpHeader;
use App\Filament\Resources\GradingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGradings extends ListRecords
{
    use HasHelpHeader;

    protected static string $resource = GradingResource::class;

    protected function getHeaderActions(): array
    {
        return $this->appendHelpAction([]);
    }

    protected function getHelpModalView(): string
    {
        return 'filament.pages.actions.modal-help-grading';
    }
}
