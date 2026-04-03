<?php

namespace App\Filament\Resources\ExamBlueprintResource\Pages;

use App\Filament\Resources\ExamBlueprintResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListExamBlueprints extends ListRecords
{
    protected static string $resource = ExamBlueprintResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
