<?php

namespace App\Filament\Resources\ExamSessionResource\Pages;

use App\Filament\Resources\ExamSessionResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateExamSession extends CreateRecord
{
    protected static string $resource = ExamSessionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Sesi ujian berhasil dibuat';
    }
}
