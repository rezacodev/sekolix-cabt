<?php

namespace App\Filament\Resources\QuestionGroupResource\Pages;

use App\Filament\Resources\QuestionGroupResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateQuestionGroup extends CreateRecord
{
  protected static string $resource = QuestionGroupResource::class;

  protected function mutateFormDataBeforeCreate(array $data): array
  {
    $data['created_by'] = Auth::id();
    return $data;
  }
}
