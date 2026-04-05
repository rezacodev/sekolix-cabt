<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Pengguna berhasil dibuat';
    }

    protected function afterCreate(): void
    {
        $record = $this->record;
        if ($record->level === User::LEVEL_PESERTA) {
            $first = $record->rombels()->first();
            $record->update(['rombel_id' => $first?->id]);
        }
    }
}
