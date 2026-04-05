<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Imports\UsersImport;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Storage;

class ImportUsers extends Page implements HasForms
{
  use InteractsWithForms;

  protected static string $resource = UserResource::class;

  protected static string $view = 'filament.resources.user-resource.pages.import-users';

  /** State form upload. */
  public ?array $data = [];

  /** Hasil parsing file: array<{row, row_num, valid, errors, resolved}>. */
  public array $parsedRows = [];

  /** Apakah sedang menampilkan tahap preview. */
  public bool $showPreview = false;

  public function mount(): void
  {
    $this->form->fill();
  }

  public function form(Form $form): Form
  {
    return $form
      ->schema([
        Forms\Components\FileUpload::make('file')
          ->label('File Excel (.xlsx)')
          ->required()
          ->acceptedFileTypes([
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/octet-stream',
          ])
          ->disk('local')
          ->directory('imports/users')
          ->visibility('private'),
      ])
      ->statePath('data');
  }

  /**
   * Baca file, parse, dan tampilkan preview tanpa menulis ke DB.
   */
  public function parseFile(): void
  {
    $this->validate(['data.file' => ['required']]);

    $fileValue = $this->data['file'] ?? null;

    // Guard: nilai bisa tetap TemporaryUploadedFile jika storeAs() gagal secara diam-diam
    if (! is_string($fileValue) || blank($fileValue)) {
      Notification::make()
        ->title('File belum terupload dengan benar. Silakan upload ulang.')
        ->danger()
        ->send();
      return;
    }

    $path = Storage::disk('local')->path($fileValue);

    if (! file_exists($path)) {
      Notification::make()
        ->title('File tidak ditemukan di server. Silakan upload ulang.')
        ->danger()
        ->send();
      return;
    }

    try {
      $this->parsedRows  = UsersImport::parseForPreview($path);
      $this->showPreview = true;
    } catch (\Throwable $e) {
      Notification::make()
        ->title('Gagal memproses file: ' . $e->getMessage())
        ->danger()
        ->persistent()
        ->send();
    }
  }

  /**
   * Import hanya baris valid dari hasil parsing, lalu redirect ke daftar user.
   */
  public function doImport(): void
  {
    $validCount = count(array_filter($this->parsedRows, fn($r) => $r['valid']));

    if ($validCount === 0) {
      Notification::make()
        ->title('Tidak ada baris valid untuk diimport.')
        ->warning()
        ->send();
      return;
    }

    $result = UsersImport::importValidRows($this->parsedRows);

    if ($result['imported'] > 0) {
      Notification::make()
        ->title("Berhasil import {$result['imported']} user.")
        ->success()
        ->send();
    }

    if (! empty($result['errors'])) {
      Notification::make()
        ->title(count($result['errors']) . ' baris gagal diimport.')
        ->body(implode("\n", array_slice($result['errors'], 0, 5)))
        ->warning()
        ->persistent()
        ->send();
    }

    // Bersihkan file upload sementara
    if (! empty($this->data['file'])) {
      Storage::disk('local')->delete($this->data['file']);
    }

    redirect(static::getResource()::getUrl());
  }

  /**
   * Kembali ke tahap upload (batal preview).
   */
  public function resetImport(): void
  {
    $this->parsedRows  = [];
    $this->showPreview = false;
    $this->form->fill();
  }

  public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
  {
    return 'Import User dari Excel';
  }
}
