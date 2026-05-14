<?php

namespace App\Filament\Resources\MataPelajaranResource\Pages;

use App\Filament\Resources\MataPelajaranResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateMataPelajaran extends CreateRecord
{
  protected static string $resource = MataPelajaranResource::class;

  protected function mutateFormDataBeforeCreate(array $data): array
  {
    $data['created_by'] = Auth::id();
    return $data;
  }

  protected function getCreatedNotificationTitle(): ?string
  {
    return 'Mata pelajaran berhasil dibuat';
  }
}
