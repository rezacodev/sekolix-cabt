<?php

namespace App\Filament\Resources\CurriculumStandardResource\Pages;

use App\Filament\Resources\CurriculumStandardResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCurriculumStandards extends ListRecords
{
    protected static string $resource = CurriculumStandardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
