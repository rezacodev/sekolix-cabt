<?php

namespace App\Filament\Resources\ExamPackageResource\Pages;

use App\Filament\Resources\ExamPackageResource;
use App\Models\Category;
use App\Models\ExamPackage;
use App\Models\MataPelajaran;
use App\Models\ExamPackageQuestion;
use App\Models\Question;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Pages\Page as ResourcePage;
use Filament\Tables;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class AddMultipleQuestions extends ResourcePage implements HasForms, HasTable
{
  use InteractsWithForms;
  use InteractsWithTable;

  protected static string $resource = ExamPackageResource::class;
  protected static string $view = 'filament.resources.exam-package-resource.pages.add-multiple-questions';
  protected static ?string $title = 'Tambah Soal - Sekaligus';
  protected static bool $shouldRegisterNavigation = false;

  public ExamPackage $examPackage;
  public ?array $filters = [];

  public function mount(int|string $record): void
  {
    $this->examPackage = ExamPackage::findOrFail($record);
    $this->form->fill([]);
  }

  public function form(Form $form): Form
  {
    return $form
      ->schema([
        Section::make('Filter Soal')
          ->description('Pilih mata pelajaran dan kategori untuk menampilkan daftar soal.')
          ->schema([
            Select::make('mata_pelajaran_id')
              ->label('Mata Pelajaran')
              ->options(fn() => MataPelajaran::where('aktif', true)->orderBy('nama')->pluck('nama', 'id'))
              ->searchable()
              ->nullable()
              ->native(false)
              ->live()
              ->afterStateUpdated(function (callable $set) {
                $set('kategori_id', null);
                $this->resetTable();
              }),

            Select::make('kategori_id')
              ->label('Kategori')
              ->options(fn(Forms\Get $get) => $get('mata_pelajaran_id')
                ? Category::where('mata_pelajaran_id', $get('mata_pelajaran_id'))
                ->orderBy('parent_id')
                ->orderBy('nama')
                ->pluck('nama', 'id')
                : [])
              ->searchable()
              ->nullable()
              ->native(false)
              ->disabled(fn(Forms\Get $get) => ! filled($get('mata_pelajaran_id')))
              ->live()
              ->afterStateUpdated(fn() => $this->resetTable()),
          ])
          ->columns(2),
      ])
      ->statePath('filters');
  }

  public function table(Table $table): Table
  {
    return $table
      ->query(function () {
        $query = Question::query()
          ->where('aktif', true)
          ->where(function (Builder $query) {
            $query->where('created_by', Auth::id())
              ->orWhereIn('visibilitas', [
                Question::VISIBILITAS_INTERNAL,
                Question::VISIBILITAS_PUBLIK,
              ]);
          });

        $mapelId = $this->filters['mata_pelajaran_id'] ?? null;
        $kategoriId = $this->filters['kategori_id'] ?? null;

        if (! filled($mapelId)) {
          return $query->whereRaw('0 = 1');
        }

        $query->where('mata_pelajaran_id', $mapelId);

        if (filled($kategoriId)) {
          $query->where(function (Builder $query) use ($kategoriId) {
            $query->where('kategori_id', $kategoriId)
              ->orWhereHas('category', fn(Builder $query) => $query->where('parent_id', $kategoriId));
          });
        }

        $existingIds = $this->examPackage->questions()->pluck('questions.id')->toArray();
        if (! empty($existingIds)) {
          $query->whereNotIn('id', $existingIds);
        }

        return $query;
      })
      ->columns([
        TextColumn::make('teks_soal')
          ->label('Pertanyaan')
          ->html()
          ->limit(80)
          ->tooltip(fn($record) => strip_tags($record->teks_soal)),

        TextColumn::make('kelas')
          ->label('Kelas')
          ->formatStateUsing(fn($state) => $state ? 'Kelas ' . $state : '—')
          ->placeholder('—'),

        TextColumn::make('category.nama')
          ->label('Kategori')
          ->placeholder('—'),

        BadgeColumn::make('tipe')
          ->label('Tipe')
          ->formatStateUsing(fn($state) => Question::TIPE_LABELS[$state] ?? $state)
          ->colors([
            'primary'   => 'PG',
            'info'      => 'PG_BOBOT',
            'warning'   => 'PGJ',
            'success'   => 'JODOH',
            'secondary' => 'ISIAN',
            'danger'    => 'URAIAN',
          ]),

        BadgeColumn::make('tingkat_kesulitan')
          ->label('Kesulitan')
          ->formatStateUsing(fn($state) => Question::KESULITAN_LABELS[$state] ?? $state)
          ->colors([
            'success' => 'mudah',
            'warning' => 'sedang',
            'danger'  => 'sulit',
          ]),

        TextColumn::make('bobot')
          ->label('Bobot'),
      ])
      ->bulkActions([
        BulkAction::make('attach_selected')
          ->label('Tambah Soal Terpilih')
          ->action(function (Collection $records) {
            $maxUrutan = ExamPackageQuestion::where('exam_package_id', $this->examPackage->id)->max('urutan') ?? 0;

            foreach ($records as $question) {
              ExamPackageQuestion::firstOrCreate(
                ['exam_package_id' => $this->examPackage->id, 'question_id' => $question->id],
                ['urutan' => ++$maxUrutan],
              );
            }

            Notification::make()
              ->title(count($records) . ' soal berhasil ditambahkan ke paket.')
              ->success()
              ->send();

            redirect(static::getResource()::getUrl('edit', ['record' => $this->examPackage]));
          })
          ->requiresConfirmation(),
      ])
      ->defaultSort('id')
      ->paginated()
      ->paginationPageOptions([25]);
  }

  public function getBreadcrumbs(): array
  {
    return [
      static::getResource()::getUrl() => 'Paket Ujian',
      static::getResource()::getUrl('edit', ['record' => $this->examPackage]) => $this->examPackage->nama,
      '#' => 'Tambah Soal - Sekaligus',
    ];
  }
}
