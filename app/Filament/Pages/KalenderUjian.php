<?php

namespace App\Filament\Pages;

use App\Models\User;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class KalenderUjian extends Page
{
  protected static ?string $navigationIcon  = 'heroicon-o-calendar';
  protected static ?string $navigationLabel = 'Kalender Ujian';
  protected static ?string $navigationGroup = 'Sesi Ujian';
  protected static ?int    $navigationSort  = 35;
  protected static string  $view            = 'filament.pages.kalender-ujian';
  protected static ?string $slug            = 'kalender-ujian';
  protected static ?string $title           = 'Kalender Ujian';

  public static function canAccess(): bool
  {
    /** @var \App\Models\User|null $user */
    $user = Auth::user();
    return $user && $user->level >= User::LEVEL_GURU;
  }
}
