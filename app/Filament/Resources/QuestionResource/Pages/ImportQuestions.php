<?php

namespace App\Filament\Resources\QuestionResource\Pages;

use App\Filament\Resources\QuestionResource;
use App\Imports\QuestionsImport;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ImportQuestions extends Page implements HasForms
{
  use InteractsWithForms;

  protected static string $resource = QuestionResource::class;

  protected static string $view = 'filament.resources.question-resource.pages.import-questions';

  /** State form upload. */
  public ?array $data = [];

  /** Hasil parsing file. */
  public array $parsedRows = [];

  /** Apakah sedang menampilkan tahap preview. */
  public bool $showPreview = false;

  /** Apakah modal konfirmasi import sedang terbuka. */
  public bool $showImportModal = false;

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
          ->directory('imports/soal')
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

    $state     = $this->form->getState();
    $fileValue = $state['file'] ?? null;

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
      $this->parsedRows  = QuestionsImport::parseForPreview($path);
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
   * Import hanya baris valid, lalu redirect ke daftar soal.
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

    $result = QuestionsImport::importValidRows($this->parsedRows, Auth::id());

    if ($result['imported'] > 0) {
      Notification::make()
        ->title("Berhasil import {$result['imported']} soal.")
        ->success()
        ->send();
    }

    if (! empty($result['errors'])) {
      Notification::make()
        ->title(count($result['errors']) . ' soal gagal diimport.')
        ->body(implode("\n", array_slice($result['errors'], 0, 5)))
        ->warning()
        ->persistent()
        ->send();
    }

    // Hapus file upload sementara
    if (! empty($this->data['file'])) {
      Storage::disk('local')->delete($this->data['file']);
    }

    redirect(static::getResource()::getUrl());
  }

  /**
   * Kembali ke tahap upload.
   */
  public function resetImport(): void
  {
    $this->parsedRows  = [];
    $this->showPreview = false;
    $this->form->fill();
  }

  public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
  {
    return 'Import Soal dari Excel';
  }
}
