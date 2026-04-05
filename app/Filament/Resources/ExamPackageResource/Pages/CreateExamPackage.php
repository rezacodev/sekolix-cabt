<?php

namespace App\Filament\Resources\ExamPackageResource\Pages;

use App\Filament\Resources\ExamPackageResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateExamPackage extends CreateRecord
{
    protected static string $resource = ExamPackageResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();
        return $data;
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Paket ujian berhasil dibuat';
    }
}
