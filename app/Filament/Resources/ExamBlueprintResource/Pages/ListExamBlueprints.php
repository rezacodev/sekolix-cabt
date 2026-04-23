<?php

namespace App\Filament\Resources\ExamBlueprintResource\Pages;

use App\Filament\Concerns\HasHelpHeader;
use App\Filament\Resources\ExamBlueprintResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListExamBlueprints extends ListRecords
{
    use HasHelpHeader;

    protected static string $resource = ExamBlueprintResource::class;

    protected function getHeaderActions(): array
    {
        return $this->appendHelpAction([
            Actions\CreateAction::make(),
        ]);
    }

    protected function getHelpModalView(): string
    {
        return 'filament.pages.actions.modal-help-exam-blueprint';
    }
}
