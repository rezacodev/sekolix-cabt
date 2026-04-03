<?php

namespace App\Filament\Resources\ExamBlueprintResource\Pages;

use App\Filament\Resources\ExamBlueprintResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditExamBlueprint extends EditRecord
{
    protected static string $resource = ExamBlueprintResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
