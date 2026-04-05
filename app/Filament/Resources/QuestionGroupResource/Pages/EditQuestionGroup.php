<?php

namespace App\Filament\Resources\QuestionGroupResource\Pages;

use App\Filament\Resources\QuestionGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditQuestionGroup extends EditRecord
{
  protected static string $resource = QuestionGroupResource::class;

  protected function getSavedNotificationTitle(): ?string
  {
    return 'Grup soal berhasil diperbarui';
  }

  protected function getHeaderActions(): array
  {
    return [
      Actions\DeleteAction::make()
          ->successNotificationTitle('Grup soal berhasil dihapus'),
    ];
  }
}
