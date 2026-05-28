<?php

namespace App\Filament\Resources\ExamPackageResource\RelationManagers;

use App\Filament\Resources\ExamPackageResource;
use App\Models\Category;
use App\Models\ExamPackageQuestion;
use App\Models\MataPelajaran;
use App\Models\Question;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class QuestionsRelationManager extends RelationManager
{
    protected static string $relationship = 'questions';
    protected static ?string $title = 'Soal dalam Paket';
    protected static ?string $recordTitleAttribute = 'teks_soal';

    /** True when package is locked (used in active/finished session). */
    private function locked(): bool
    {
        return $this->getOwnerRecord()->isSoftLocked();
    }

    public function form(Form $form): Form
    {
        // Attach form — no body fields needed (urutan managed separately)
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        $locked = $this->locked();

        return $table
            ->recordTitleAttribute('teks_soal')
            ->reorderable('urutan')
            ->defaultSort('urutan')
            ->columns([
                Tables\Columns\TextColumn::make('pivot.urutan')
                    ->label('#')
                    ->width(40),

                Tables\Columns\BadgeColumn::make('tipe')
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

                Tables\Columns\TextColumn::make('teks_soal')
                    ->label('Pertanyaan')
                    ->html()
                    ->limit(60)
                    ->tooltip(fn($record) => $record ? strip_tags($record->teks_soal) : null),

                Tables\Columns\TextColumn::make('kelas')
                    ->label('Kelas')
                    ->formatStateUsing(fn($state) => $state ? 'Kelas ' . $state : '—')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('category.nama')
                    ->label('Kategori')
                    ->placeholder('—'),

                Tables\Columns\BadgeColumn::make('tingkat_kesulitan')
                    ->label('Kesulitan')
                    ->formatStateUsing(fn($state) => Question::KESULITAN_LABELS[$state] ?? $state)
                    ->colors([
                        'success' => 'mudah',
                        'warning' => 'sedang',
                        'danger'  => 'sulit',
                    ]),

                Tables\Columns\TextColumn::make('bobot')
                    ->label('Bobot'),

                Tables\Columns\TextColumn::make('group.judul')
                    ->label('Grup')
                    ->placeholder('—')
                    ->badge()
                    ->color('gray')
                    ->limit(25)
                    ->toggleable(),

                Tables\Columns\IconColumn::make('lock_position')
                    ->label('Kunci Urutan')
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-closed')
                    ->falseIcon('heroicon-o-lock-open')
                    ->trueColor('danger')
                    ->falseColor('gray'),
            ])
            ->headerActions($locked ? [
                Tables\Actions\Action::make('locked_notice')
                    ->label('Paket terkunci — soal tidak dapat diubah')
                    ->icon('heroicon-o-lock-closed')
                    ->color('danger')
                    ->extraAttributes([
                        'title'          => 'Paket ini digunakan oleh sesi ujian yang sedang aktif atau sudah selesai. Soal hanya bisa diubah jika sesi belum dimulai (draft) atau dibatalkan.',
                        'style'          => 'pointer-events:auto;opacity:0.65;cursor:not-allowed;',
                    ])
                    ->action(fn() => null),
            ] : [
                Tables\Actions\Action::make('tambah_soal_sekaligus')
                    ->label('Tambah Soal - Sekaligus')
                    ->icon('heroicon-o-squares-2x2')
                    ->color('success')
                    ->button()
                    ->url(fn() => ExamPackageResource::getUrl('addMultipleQuestions', ['record' => $this->getOwnerRecord()]))
                    ->openUrlInNewTab(false),

                // 1. Tambah Soal Manual
                Tables\Actions\Action::make('tambah_soal')
                    ->label('Tambah Soal - Satu per Satu')
                    ->icon('heroicon-o-plus')
                    ->color('primary')
                    ->form([
                        Forms\Components\Select::make('_mapel_filter')
                            ->label('Mata Pelajaran')
                            ->options(fn() => MataPelajaran::where('aktif', true)->orderBy('nama')->pluck('nama', 'id'))
                            ->searchable()
                            ->nullable()
                            ->native(false)
                            ->reactive()
                            ->afterStateUpdated(fn(callable $set) => $set('kategori_id', null)),

                        Forms\Components\Select::make('kategori_id')
                            ->label('Kategori')
                            ->options(fn(Get $get) => $get('_mapel_filter')
                                ? Category::where('mata_pelajaran_id', $get('_mapel_filter'))
                                ->orderBy('parent_id')
                                ->orderBy('nama')
                                ->get()
                                ->mapWithKeys(fn(Category $category) => [
                                    $category->id => ($category->parent_id ? '→ ' : '') . $category->nama,
                                ])
                                ->toArray()
                                : [])
                            ->searchable()
                            ->nullable()
                            ->native(false)
                            ->disabled(fn(Get $get) => ! filled($get('_mapel_filter')))
                            ->reactive(),

                        Forms\Components\Select::make('question_ids')
                            ->label('Soal')
                            ->multiple()
                            ->searchable()
                            ->placeholder('Cari atau pilih soal...')
                            ->options(fn(Get $get) => Question::query()
                                ->where('aktif', true)
                                ->where(function ($query) {
                                    $query->where('created_by', Auth::id())
                                        ->orWhereIn('visibilitas', [
                                            Question::VISIBILITAS_INTERNAL,
                                            Question::VISIBILITAS_PUBLIK,
                                        ]);
                                })
                                ->when($get('_mapel_filter'), fn($query, $mapelId) => $query->where('mata_pelajaran_id', $mapelId))
                                ->when($get('kategori_id'), fn($query, $kategoriId) => $query->where(function ($query) use ($kategoriId) {
                                    $query->where('kategori_id', $kategoriId)
                                        ->orWhereHas('category', fn($query) => $query->where('parent_id', $kategoriId));
                                }))
                                ->orderBy('id')
                                ->limit(200)
                                ->get()
                                ->mapWithKeys(fn(Question $question) => [
                                    $question->id => ($question->kelas ? '[Kelas ' . $question->kelas . '] ' : '') . Str::limit(strip_tags($question->teks_soal), 180),
                                ])
                                ->toArray())
                            ->getSearchResultsUsing(function (string $search, Get $get): array {
                                $query = Question::query()
                                    ->where('aktif', true)
                                    ->where(function ($query) {
                                        $query->where('created_by', Auth::id())
                                            ->orWhereIn('visibilitas', [
                                                Question::VISIBILITAS_INTERNAL,
                                                Question::VISIBILITAS_PUBLIK,
                                            ]);
                                    });

                                if ($mapelId = $get('_mapel_filter')) {
                                    $query->whereHas('category', fn($query) => $query->where('mata_pelajaran_id', $mapelId));
                                }

                                if ($kategoriId = $get('kategori_id')) {
                                    $query->where(function ($query) use ($kategoriId) {
                                        $query->where('kategori_id', $kategoriId)
                                            ->orWhereHas('category', fn($query) => $query->where('parent_id', $kategoriId));
                                    });
                                }

                                if (filled($search)) {
                                    $query->where('teks_soal', 'like', "%{$search}%");
                                }

                                return $query
                                    ->orderBy('id')
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(fn(Question $question) => [
                                        $question->id => ($question->kelas ? '[Kelas ' . $question->kelas . '] ' : '') . Str::limit(strip_tags($question->teks_soal), 90),
                                    ])
                                    ->toArray();
                            })
                            ->getOptionLabelsUsing(fn(array $values) => Question::whereIn('id', $values)
                                ->get()
                                ->mapWithKeys(fn(Question $question) => [
                                    $question->id => ($question->kelas ? '[Kelas ' . $question->kelas . '] ' : '') . Str::limit(strip_tags($question->teks_soal), 90),
                                ])
                                ->toArray())
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $owner = $this->getOwnerRecord();
                        $questionIds = $data['question_ids'] ?? [];

                        if (empty($questionIds)) {
                            Notification::make()
                                ->title('Pilih minimal satu soal.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $maxUrutan = ExamPackageQuestion::where('exam_package_id', $owner->id)->max('urutan') ?? 0;

                        foreach ($questionIds as $questionId) {
                            ExamPackageQuestion::firstOrCreate(
                                ['exam_package_id' => $owner->id, 'question_id' => $questionId],
                                ['urutan' => ++$maxUrutan],
                            );
                        }

                        Notification::make()
                            ->title(count($questionIds) . ' soal berhasil ditambahkan.')
                            ->success()
                            ->send();
                    }),

                // 2. Auto-Pilih Soal
                Tables\Actions\Action::make('auto_pilih')
                    ->label('Auto-Pilih Soal')
                    ->icon('heroicon-o-sparkles')
                    ->color('info')
                    ->form([
                        Forms\Components\Select::make('_mapel_filter')
                            ->label('Mata Pelajaran')
                            ->options(fn() => MataPelajaran::where('aktif', true)->orderBy('nama')->pluck('nama', 'id'))
                            ->searchable()
                            ->nullable()
                            ->native(false)
                            ->live(),

                        Forms\Components\Select::make('kategori_id')
                            ->label('Kategori')
                            ->options(fn(Get $get) => $get('_mapel_filter')
                                ? Category::whereNull('parent_id')->where('mata_pelajaran_id', $get('_mapel_filter'))->pluck('nama', 'id')
                                : Category::whereNull('parent_id')->pluck('nama', 'id'))
                            ->nullable()
                            ->searchable()
                            ->native(false),

                        Forms\Components\Select::make('tipe')
                            ->label('Tipe Soal')
                            ->options(Question::TIPE_LABELS)
                            ->nullable(),

                        Forms\Components\Select::make('kesulitan')
                            ->label('Tingkat Kesulitan')
                            ->options(Question::KESULITAN_LABELS)
                            ->nullable(),

                        Forms\Components\TextInput::make('jumlah')
                            ->label('Jumlah Soal')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->default(10),
                    ])
                    ->action(function (array $data, $livewire) {
                        $owner = $livewire->getOwnerRecord();

                        $existing = $owner->questions()->pluck('questions.id')->toArray();

                        $query = Question::query()
                            ->where('aktif', true)
                            ->whereNotIn('id', $existing)
                            ->where(function ($query) {
                                $query->where('created_by', Auth::id())
                                    ->orWhereIn('visibilitas', [
                                        Question::VISIBILITAS_INTERNAL,
                                        Question::VISIBILITAS_PUBLIK,
                                    ]);
                            });

                        if (! empty($data['_mapel_filter'])) {
                            $query->whereHas('category', function ($q) use ($data) {
                                $q->where('mata_pelajaran_id', $data['_mapel_filter']);
                            });
                        }
                        if (! empty($data['tipe'])) {
                            $query->where('tipe', $data['tipe']);
                        }
                        if (! empty($data['kesulitan'])) {
                            $query->where('tingkat_kesulitan', $data['kesulitan']);
                        }
                        if (! empty($data['kategori_id'])) {
                            $query->where(function ($q) use ($data) {
                                $q->where('kategori_id', $data['kategori_id'])
                                    ->orWhereHas('category', fn($q) => $q->where('parent_id', $data['kategori_id']));
                            });
                        }

                        $soal = $query->inRandomOrder()->limit((int) $data['jumlah'])->get();

                        if ($soal->isEmpty()) {
                            Notification::make()
                                ->title('Tidak ditemukan soal yang sesuai kriteria.')
                                ->warning()
                                ->send();
                            return;
                        }

                        $maxUrutan = ExamPackageQuestion::where('exam_package_id', $owner->id)->max('urutan') ?? 0;

                        foreach ($soal as $q) {
                            ExamPackageQuestion::firstOrCreate(
                                ['exam_package_id' => $owner->id, 'question_id' => $q->id],
                                ['urutan' => ++$maxUrutan],
                            );
                        }

                        Notification::make()
                            ->title("{$soal->count()} soal berhasil ditambahkan.")
                            ->success()
                            ->send();
                    }),
            ])
            ->actions($locked ? [] : [
                Tables\Actions\DetachAction::make()
                    ->label('Hapus'),
            ])
            ->bulkActions($locked ? [] : [
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make()->label('Hapus Terpilih'),
                ]),
            ]);
    }
}
