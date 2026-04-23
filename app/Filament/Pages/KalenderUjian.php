<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Filament\Concerns\HasHelpHeader;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class KalenderUjian extends Page
{
  use HasHelpHeader;

  protected static ?string $navigationIcon  = 'heroicon-o-calendar';
  protected static ?string $navigationLabel = 'Kalender Ujian';
  protected static ?string $navigationGroup = 'Sesi Ujian';
  protected static ?int    $navigationSort  = 35;
  protected static string  $view            = 'filament.pages.kalender-ujian';
  protected static ?string $slug            = 'kalender-ujian';
  protected static ?string $title           = 'Kalender Ujian';

  protected function getHeaderActions(): array
  {
    return $this->appendHelpAction([]);
  }

  protected function getHelpModalView(): string
  {
    return 'filament.pages.actions.modal-help-calendar';
  }

  public static function canAccess(): bool
  {
    /** @var \App\Models\User|null $user */
    $user = Auth::user();
    return $user && $user->level >= User::LEVEL_GURU;
  }
}
