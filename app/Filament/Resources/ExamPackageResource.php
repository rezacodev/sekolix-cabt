<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExamPackageResource\Pages;
use App\Filament\Resources\ExamPackageResource\RelationManagers;
use App\Models\ExamBlueprint;
use App\Models\ExamPackage;
use App\Models\MataPelajaran;
use App\Models\Category;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ExamPackageResource extends Resource
{
    protected static ?string $model = ExamPackage::class;

    protected static ?string $navigationIcon       = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup      = 'Ujian';
    protected static ?int    $navigationSort       = 20;
    protected static ?string $modelLabel           = 'Paket Ujian';
    protected static ?string $pluralModelLabel     = 'Paket Ujian';
    protected static ?string $recordTitleAttribute = 'nama';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // ── Informasi Paket ──────────────────────────────────
                Forms\Components\Section::make('Informasi Paket')
                    ->columns(1)
                    ->schema([
                        Forms\Components\TextInput::make('nama')
                            ->label('Nama Paket')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('deskripsi')
                            ->label('Deskripsi')
                            ->rows(3)
                            ->nullable()
                            ->columnSpanFull(),
                    ]),

                // ── Mata Pelajaran & Kategori ────────────────────────
                Forms\Components\Section::make('Mata Pelajaran & Kategori')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('mata_pelajaran_id')
                            ->label('Mata Pelajaran')
                            ->options(fn() => MataPelajaran::where('aktif', true)->orderBy('nama')->pluck('nama', 'id'))
                            ->searchable()
                            ->nullable()
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(fn(Forms\Set $set) => $set('kategori_id', null)),

                        Forms\Components\Select::make('kategori_id')
                            ->label('Kategori / Bab (opsional)')
                            ->options(fn(Forms\Get $get) => $get('mata_pelajaran_id')
                                ? Category::where('mata_pelajaran_id', $get('mata_pelajaran_id'))->orderBy('nama')->pluck('nama', 'id')
                                : collect([]))
                            ->searchable()
                            ->nullable()
                            ->native(false)
                            ->disabled(fn(Forms\Get $get) => ! $get('mata_pelajaran_id'))
                            ->dehydrated()
                            ->placeholder('Pilih mata pelajaran dulu'),
                    ]),

                // ── Blueprint Kisi-kisi ──────────────────────────────
                Forms\Components\Section::make('Blueprint / Kisi-kisi')
                    ->columns(1)
                    ->schema([
                        Forms\Components\Select::make('blueprint_id')
                            ->label('Blueprint Ujian (opsional)')
                            ->options(fn() => ExamBlueprint::orderBy('nama')->pluck('nama', 'id'))
                            ->searchable()
                            ->nullable()
                            ->native(false)
                            ->helperText('Pilih blueprint untuk mengaktifkan fitur "Generate Soal dari Kisi-kisi"'),
                    ])
                    ->collapsible()
                    ->collapsed(),

                // ── Pengaturan Waktu ─────────────────────────────────
                Forms\Components\Section::make('Pengaturan Waktu')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('durasi_menit')
                            ->label('Durasi (menit)')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->default(60),

                        Forms\Components\TextInput::make('waktu_minimal_menit')
                            ->label('Waktu Minimal Submit (menit)')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->default(0)
                            ->helperText('0 = peserta bisa submit kapan saja'),
                    ]),

                // ── Pengaturan Ujian ─────────────────────────────────
                Forms\Components\Section::make('Pengaturan Ujian')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('grading_mode')
                            ->label('Mode Penilaian')
                            ->options(ExamPackage::GRADING_LABELS)
                            ->required()
                            ->default(fn() => \App\Models\AppSetting::getBool('realtime_grading', true) ? 'realtime' : 'manual')
                            ->helperText('Realtime: nilai dihitung otomatis saat submit. Manual: guru input nilai.'),

                        Forms\Components\TextInput::make('max_pengulangan')
                            ->label('Maks. Percobaan')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->default(1)
                            ->helperText('0 = tidak dibatasi. 1 = hanya 1 kali (tanpa remidi). 2 = boleh remidi 1 kali. dst.'),

                        Forms\Components\Toggle::make('has_sections')
                            ->label('Ujian Multi-Bagian (Seksi)')
                            ->helperText('Aktifkan untuk membagi ujian menjadi beberapa bagian dengan timer masing-masing. Soal disusun per seksi, bukan dari daftar soal paket.')
                            ->default(false)
                            ->columnSpanFull()
                            ->live(),

                        Forms\Components\Select::make('navigasi_seksi')
                            ->label('Mode Navigasi Antar-Bagian')
                            ->options(\App\Models\ExamPackage::NAV_SEKSI_LABELS)
                            ->default(\App\Models\ExamPackage::NAV_SEKSI_URUT)
                            ->required()
                            ->native(false)
                            ->columnSpanFull()
                            ->visible(fn(Forms\Get $get) => (bool) $get('has_sections'))
                            ->helperText('Urut: harus selesaikan bagian sebelum lanjut. Bebas: bisa pindah kapan saja.'),

                        Forms\Components\Toggle::make('acak_soal')
                            ->label('Acak Urutan Soal')
                            ->default(false),

                        Forms\Components\Toggle::make('acak_opsi')
                            ->label('Acak Urutan Opsi')
                            ->default(false),

                        Forms\Components\Toggle::make('tampilkan_hasil')
                            ->label('Tampilkan Nilai ke Peserta')
                            ->default(true),

                        Forms\Components\Toggle::make('tampilkan_review')
                            ->label('Peserta Bisa Review Jawaban')
                            ->default(false),
                    ]),

                // ── Penilaian Negatif ────────────────────────────────
                Forms\Components\Section::make('Penilaian Negatif')
                    ->description('Kurangi poin untuk setiap jawaban yang salah. Kosongkan / 0 untuk menonaktifkan.')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('nilai_negatif')
                            ->label('Pengurangan per Jawaban Salah')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->step(0.25)
                            ->suffix('poin')
                            ->helperText('0 = tidak ada pengurangan'),

                        Forms\Components\Toggle::make('nilai_negatif_kosong')
                            ->label('Kurangi Jawaban Kosong')
                            ->helperText('Berlaku hanya jika pengurangan > 0')
                            ->default(false),

                        Forms\Components\Toggle::make('nilai_negatif_clamp')
                            ->label('Nilai Minimum 0')
                            ->helperText('Nilai per soal tidak pernah negatif')
                            ->default(true),
                    ])
                    ->collapsible()
                    ->collapsed(),

                // ── Waktu Per Soal ───────────────────────────────────
                Forms\Components\Section::make('Waktu Per Soal')
                    ->description('Batasi waktu pengerjaan tiap soal secara individual. 0 = tidak dibatasi.')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('waktu_per_soal_detik')
                            ->label('Waktu per Soal (detik)')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->suffix('detik')
                            ->helperText('0 = tidak ada batas waktu per soal'),

                        Forms\Components\Select::make('waktu_per_soal_navigasi')
                            ->label('Mode Setelah Waktu Habis')
                            ->options(\App\Models\ExamPackage::NAV_SOAL_LABELS)
                            ->default(\App\Models\ExamPackage::NAV_SOAL_BEBAS)
                            ->native(false)
                            ->helperText('Hanya Maju: otomatis pindah ke soal berikutnya saat waktu habis'),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama')
                    ->label('Nama Paket')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('durasi_menit')
                    ->label('Durasi')
                    ->suffix(' mnt')
                    ->sortable(),

                Tables\Columns\TextColumn::make('questions_count')
                    ->label('Jumlah Soal')
                    ->counts('questions')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('max_pengulangan')
                    ->label('Maks. Percobaan')
                    ->formatStateUsing(fn($state) => $state == 0 ? 'Tak terbatas' : $state)
                    ->badge()
                    ->color(fn($state) => $state == 0 ? 'warning' : 'gray')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('grading_mode')
                    ->label('Penilaian')
                    ->formatStateUsing(fn($state) => ExamPackage::GRADING_LABELS[$state] ?? $state)
                    ->colors([
                        'success' => 'realtime',
                        'warning' => 'manual',
                    ]),

                Tables\Columns\IconColumn::make('acak_soal')
                    ->label('Acak Soal')
                    ->boolean(),

                Tables\Columns\IconColumn::make('tampilkan_hasil')
                    ->label('Tampil Nilai')
                    ->boolean(),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Dibuat Oleh')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\Filter::make('mapel_kategori')
                    ->label('Mata Pelajaran / Kategori')
                    ->form([
                        Forms\Components\Select::make('mata_pelajaran_id')
                            ->label('Mata Pelajaran')
                            ->options(fn() => MataPelajaran::where('aktif', true)->orderBy('nama')->pluck('nama', 'id'))
                            ->searchable()
                            ->native(false)
                            ->live(),
                        Forms\Components\Select::make('kategori_id')
                            ->label('Kategori')
                            ->options(fn(Forms\Get $get) => $get('mata_pelajaran_id')
                                ? Category::where('mata_pelajaran_id', $get('mata_pelajaran_id'))->orderBy('nama')->pluck('nama', 'id')
                                : Category::orderBy('nama')->pluck('nama', 'id'))
                            ->searchable()
                            ->native(false),
                    ])
                    ->query(fn(Builder $query, array $data) => $query
                        ->when($data['mata_pelajaran_id'] ?? null, fn($q, $v) => $q->where('mata_pelajaran_id', $v))
                        ->when($data['kategori_id'] ?? null, fn($q, $v) => $q->where('kategori_id', $v))
                    )
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if (! empty($data['mata_pelajaran_id'])) {
                            $nama = MataPelajaran::find($data['mata_pelajaran_id'])?->nama;
                            if ($nama) $indicators[] = Indicator::make('Mapel: ' . $nama)->removeField('mata_pelajaran_id');
                        }
                        if (! empty($data['kategori_id'])) {
                            $nama = Category::find($data['kategori_id'])?->nama;
                            if ($nama) $indicators[] = Indicator::make('Kategori: ' . $nama)->removeField('kategori_id');
                        }
                        return $indicators;
                    }),

                Tables\Filters\SelectFilter::make('grading_mode')
                    ->label('Mode Penilaian')
                    ->options(ExamPackage::GRADING_LABELS),

                Tables\Filters\TernaryFilter::make('has_sections')
                    ->label('Tipe Paket')
                    ->trueLabel('Multi-Seksi')
                    ->falseLabel('Single Paket')
                    ->placeholder('Semua'),

                Tables\Filters\TernaryFilter::make('acak_soal')
                    ->label('Acak Soal')
                    ->trueLabel('Diacak')
                    ->falseLabel('Tidak Diacak')
                    ->placeholder('Semua'),

                Tables\Filters\TernaryFilter::make('tampilkan_hasil')
                    ->label('Tampilkan Hasil')
                    ->trueLabel('Ya')
                    ->falseLabel('Tidak')
                    ->placeholder('Semua'),

                Tables\Filters\SelectFilter::make('created_by')
                    ->label('Dibuat Oleh')
                    ->options(fn() => User::where('level', '>=', User::LEVEL_GURU)->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->native(false)
                    ->hidden(fn(): bool => Auth::user()->level < User::LEVEL_ADMIN),
            ])
            ->actions([
                Tables\Actions\Action::make('generate_dari_blueprint')
                    ->label('Generate Soal')
                    ->icon('heroicon-o-sparkles')
                    ->color('success')
                    ->visible(fn($record) => $record->blueprint_id !== null)
                    ->requiresConfirmation()
                    ->modalHeading(fn($record) => 'Generate Soal dari Blueprint: ' . optional($record->blueprint)->nama)
                    ->modalDescription('Soal akan dipilih secara acak sesuai kisi-kisi. Soal yang sudah ada di paket tidak akan dipilih ulang.')
                    ->action(function ($record) {
                        $blueprint = $record->blueprint;
                        if (! $blueprint) return;

                        $existingIds  = $record->questions()->pluck('questions.id')->all();
                        $newIds       = [];
                        $missing      = [];

                        foreach ($blueprint->items as $item) {
                            $picked = $item->pickQuestions($item->jumlah_soal, array_merge($existingIds, $newIds));
                            if (count($picked) < $item->jumlah_soal) {
                                $missing[] = "Baris {$item->urutan} ({$item->kriteria_label}): butuh {$item->jumlah_soal}, tersedia " . count($picked);
                            }
                            $newIds = array_merge($newIds, $picked);
                        }

                        if (! empty($newIds)) {
                            $attach = [];
                            foreach ($newIds as $qid) {
                                $attach[$qid] = ['urutan' => count($existingIds) + count($attach) + 1];
                            }
                            $record->questions()->attach($attach);
                        }

                        if (empty($missing)) {
                            Notification::make()
                                ->title('Berhasil!')
                                ->body(count($newIds) . ' soal ditambahkan ke paket.')
                                ->success()->send();
                        } else {
                            Notification::make()
                                ->title('Generate Sebagian')
                                ->body(count($newIds) . ' soal ditambahkan. Kekurangan: ' . implode('; ', $missing))
                                ->warning()->send();
                        }
                    }),

                Tables\Actions\Action::make('copy')
                    ->label('Salin')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading('Salin Paket Ujian')
                    ->modalDescription('Paket ujian beserta seluruh soal dan seksinya akan disalin. Salinan tidak memiliki sesi ujian sehingga dapat langsung diedit.')
                    ->action(function (ExamPackage $record) {
                        \Illuminate\Support\Facades\DB::transaction(function () use ($record) {
                            // 1. Salin paket
                            $newPackage = $record->replicate(['id', 'created_at', 'updated_at']);
                            $newPackage->nama       = 'Salinan — ' . $record->nama;
                            $newPackage->created_by = \Illuminate\Support\Facades\Auth::id();
                            $newPackage->save();

                            if ($record->has_sections) {
                                // 2a. Salin seksi + soal per seksi
                                $record->sections()->with('questionPivots')->each(function (\App\Models\ExamSection $section) use ($newPackage) {
                                    $newSection = $section->replicate(['id', 'exam_package_id', 'created_at', 'updated_at']);
                                    $newSection->exam_package_id = $newPackage->id;
                                    $newSection->save();

                                    $pivots = $section->questionPivots->map(fn($p) => [
                                        'section_id'  => $newSection->id,
                                        'question_id' => $p->question_id,
                                        'urutan'      => $p->urutan,
                                    ])->all();

                                    if (! empty($pivots)) {
                                        \App\Models\ExamSectionQuestion::insert($pivots);
                                    }
                                });
                            } else {
                                // 2b. Salin soal langsung di paket
                                $pivots = $record->questionPivots->map(fn($p) => [
                                    'exam_package_id' => $newPackage->id,
                                    'question_id'     => $p->question_id,
                                    'urutan'          => $p->urutan,
                                ])->all();

                                if (! empty($pivots)) {
                                    \App\Models\ExamPackageQuestion::insert($pivots);
                                }
                            }
                        });

                        Notification::make()
                            ->title('Paket berhasil disalin')
                            ->body('Salinan — ' . $record->nama . ' telah dibuat dan siap diedit.')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->successNotificationTitle('Paket ujian berhasil dihapus'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function canViewAny(): bool
    {
        return Auth::user()?->level >= User::LEVEL_GURU;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()?->level === User::LEVEL_GURU;
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();
        $user  = Auth::user();

        if ($user->level === User::LEVEL_GURU) {
            return $query->where('created_by', $user->id);
        }

        return $query;
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\QuestionsRelationManager::class,
            RelationManagers\SectionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListExamPackages::route('/'),
            'create' => Pages\CreateExamPackage::route('/create'),
            'edit'   => Pages\EditExamPackage::route('/{record}/edit'),
            'addMultipleQuestions' => Pages\AddMultipleQuestions::route('/{record}/tambah-soal-sekaligus'),
        ];
    }
}
