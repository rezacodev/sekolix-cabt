<?php

namespace App\Filament\Resources\CurriculumStandardResource\Pages;

use App\Filament\Resources\CurriculumStandardResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateCurriculumStandard extends CreateRecord
{
    protected static string $resource = CurriculumStandardResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();
        return $data;
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Standar kurikulum berhasil dibuat';
    }
}
