<?php

namespace App\Filament\Resources\ExamSessionResource\Pages;

use App\Filament\Concerns\HasHelpHeader;
use App\Filament\Resources\ExamSessionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListExamSessions extends ListRecords
{
    use HasHelpHeader;

    protected static string $resource = ExamSessionResource::class;

    protected function getHeaderActions(): array
    {
        return $this->appendHelpAction([
            Actions\CreateAction::make(),
        ]);
    }

    protected function getHelpModalView(): string
    {
        return 'filament.pages.actions.modal-help-exam-session';
    }
}
