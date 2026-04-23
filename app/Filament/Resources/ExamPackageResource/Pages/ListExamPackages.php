<?php

namespace App\Filament\Resources\ExamPackageResource\Pages;

use App\Filament\Concerns\HasHelpHeader;
use App\Filament\Resources\ExamPackageResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListExamPackages extends ListRecords
{
    use HasHelpHeader;

    protected static string $resource = ExamPackageResource::class;

    protected function getHeaderActions(): array
    {
        return $this->appendHelpAction([
            Actions\CreateAction::make(),
        ]);
    }

    protected function getHelpModalView(): string
    {
        return 'filament.pages.actions.modal-help-exam-package';
    }
}
