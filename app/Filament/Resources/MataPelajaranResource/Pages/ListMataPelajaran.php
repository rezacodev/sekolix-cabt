<?php

namespace App\Filament\Resources\MataPelajaranResource\Pages;

use App\Filament\Resources\MataPelajaranResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class ListMataPelajaran extends ListRecords
{
  protected static string $resource = MataPelajaranResource::class;

  protected function getHeaderActions(): array
  {
    return [
      Actions\CreateAction::make(),
    ];
  }
}
