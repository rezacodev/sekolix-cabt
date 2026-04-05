<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuestionResource\Pages;
use App\Models\Category;
use App\Models\CurriculumStandard;
use App\Models\Question;
use App\Models\QuestionGroup;
use App\Models\Tag;
use App\Models\User;
use App\Services\AuditLogService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class QuestionResource extends Resource
{
    protected static ?string $model = Question::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Bank Soal';

    protected static ?string $navigationGroup = 'Bank Soal';

    protected static ?int $navigationSort = 11;

    protected static ?string $modelLabel = 'Soal';

    protected static ?string $pluralModelLabel = 'Bank Soal';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Soal')
                    ->schema([
                        Forms\Components\Select::make('tipe')
                            ->label('Tipe Soal')
                            ->options(Question::TIPE_LABELS)
                            ->required()
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                // Clear child data when tipe changes
                                $set('options', []);
                                $set('matches', []);
                                $set('keywords', []);
                                $set('clozeBlank', []);
                                // Auto-populate B/S fixed options for BS type
                                if ($state === Question::TIPE_BS) {
                                    $set('options', [
                                        ['kode_opsi' => 'B', 'teks_opsi' => 'Benar', 'is_correct' => true,  'bobot_persen' => 100, 'urutan' => 0],
                                        ['kode_opsi' => 'S', 'teks_opsi' => 'Salah', 'is_correct' => false, 'bobot_persen' => 100, 'urutan' => 1],
                                    ]);
                                }
                            }),

                        Forms\Components\Select::make('kategori_id')
                            ->label('Kategori')
                            ->options(fn() => Category::pluck('nama', 'id'))
                            ->searchable()
                            ->nullable()
                            ->native(false),

                        Forms\Components\Select::make('tingkat_kesulitan')
                            ->label('Tingkat Kesulitan')
                            ->options(Question::KESULITAN_LABELS)
                            ->default('sedang')
                            ->required()
                            ->native(false),

                        Forms\Components\TextInput::make('bobot')
                            ->label('Bobot Soal')
                            ->numeric()
                            ->default(1)
                            ->minValue(0)
                            ->maxValue(999.99)
                            ->step(0.5),

                        Forms\Components\Toggle::make('lock_position')
                            ->label('Kunci Posisi')
                            ->helperText('Soal ini tidak ikut diacak saat pengacakan paket')
                            ->default(false),

                        Forms\Components\Toggle::make('aktif')
                            ->label('Aktif')
                            ->default(true),

                        Forms\Components\Select::make('visibilitas')
                            ->label('Visibilitas')
                            ->options(Question::VISIBILITAS_LABELS)
                            ->default(Question::VISIBILITAS_PRIVATE)
                            ->required()
                            ->native(false)
                            ->helperText('Kontrol siapa yang dapat melihat soal ini di bank soal'),

                        Forms\Components\Select::make('curriculum_standard_id')
                            ->label('KD / CP')
                            ->options(fn() => CurriculumStandard::query()
                                ->orderBy('mata_pelajaran')->orderBy('kode')
                                ->get()
                                ->mapWithKeys(fn($s) => [$s->id => "[{$s->kode}] {$s->mata_pelajaran} — {$s->nama}"]))
                            ->searchable()
                            ->nullable()
                            ->native(false)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('bloom_level')
                            ->label('Level Bloom')
                            ->options(CurriculumStandard::BLOOM_LABELS)
                            ->nullable()
                            ->native(false),

                        Forms\Components\TagsInput::make('tags')
                            ->label('Tag')
                            ->relationship('tags', 'nama')
                            ->suggestions(fn() => Tag::pluck('nama')->toArray())
                            ->nullable(),
                    ])->columns(3),

                Forms\Components\Section::make('Pengelompokan Stimulus')
                    ->schema([
                        Forms\Components\Select::make('question_group_id')
                            ->label('Grup Soal (Stimulus)')
                            ->options(fn() => QuestionGroup::pluck('judul', 'id'))
                            ->searchable()
                            ->nullable()
                            ->native(false)
                            ->helperText('Pilih grup stimulus jika soal ini bagian dari bacaan/gambar/tabel bersama'),

                        Forms\Components\TextInput::make('group_urutan')
                            ->label('Urutan dalam Grup')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->helperText('Menentukan urutan soal di dalam grup'),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),

                Forms\Components\Section::make('Teks Soal')
                    ->schema([
                        Forms\Components\RichEditor::make('teks_soal')
                            ->label('Teks Soal')
                            ->required()
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'underline',
                                'strike',
                                'h2',
                                'h3',
                                'bulletList',
                                'orderedList',
                                'blockquote',
                                'codeBlock',
                                'link',
                            ])
                            ->columnSpanFull(),

                        Forms\Components\RichEditor::make('penjelasan')
                            ->label('Pembahasan / Penjelasan Jawaban')
                            ->helperText('Ditampilkan saat peserta review setelah ujian')
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'underline',
                                'bulletList',
                                'orderedList',
                                'link',
                            ])
                            ->nullable()
                            ->columnSpanFull(),
                    ]),

                // === Opsi Jawaban (PG, PG_BOBOT, PGJ, BS) ===
                Forms\Components\Section::make('Opsi Jawaban')
                    ->schema([
                        Forms\Components\Repeater::make('options')
                            ->label('')
                            ->relationship('options')
                            ->schema([
                                Forms\Components\TextInput::make('kode_opsi')
                                    ->label('Kode')
                                    ->required()
                                    ->maxLength(5)
                                    ->default(function (Forms\Components\Repeater $repeater) {
                                        $codes = ['A', 'B', 'C', 'D', 'E', 'F'];
                                        return $codes[count($repeater->getState())] ?? '';
                                    }),
                                Forms\Components\RichEditor::make('teks_opsi')
                                    ->label('Teks Opsi')
                                    ->required()
                                    ->toolbarButtons(['bold', 'italic', 'underline'])
                                    ->columnSpan(3),
                                Forms\Components\Toggle::make('is_correct')
                                    ->label('Benar')
                                    ->default(false),
                                Forms\Components\TextInput::make('bobot_persen')
                                    ->label('Bobot (%)')
                                    ->numeric()
                                    ->default(100)
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->hidden(fn(Get $get, $record) => $get('../../tipe') !== Question::TIPE_PG_BOBOT),
                                Forms\Components\TextInput::make('urutan')
                                    ->label('Urutan')
                                    ->numeric()
                                    ->default(0)
                                    ->hidden(),
                            ])
                            ->columns(5)
                            ->addActionLabel('Tambah Opsi')
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->minItems(2)
                            ->maxItems(fn(Get $get) => $get('tipe') === Question::TIPE_BS ? 2 : PHP_INT_MAX)
                            ->addable(fn(Get $get) => $get('tipe') !== Question::TIPE_BS)
                            ->deletable(fn(Get $get) => $get('tipe') !== Question::TIPE_BS),
                    ])
                    ->visible(fn(Get $get) => in_array($get('tipe'), [
                        Question::TIPE_PG,
                        Question::TIPE_PG_BOBOT,
                        Question::TIPE_PGJ,
                        Question::TIPE_BS,
                    ])),

                // === Pasangan Jodoh (JODOH) ===
                Forms\Components\Section::make('Pasangan Menjodohkan')
                    ->schema([
                        Forms\Components\Repeater::make('matches')
                            ->label('')
                            ->relationship('matches')
                            ->schema([
                                Forms\Components\Textarea::make('premis')
                                    ->label('Premis')
                                    ->required()
                                    ->rows(2),
                                Forms\Components\Textarea::make('respon')
                                    ->label('Respon / Jawaban')
                                    ->required()
                                    ->rows(2),
                                Forms\Components\TextInput::make('urutan')
                                    ->label('Urutan')
                                    ->numeric()
                                    ->default(0)
                                    ->hidden(),
                            ])
                            ->columns(2)
                            ->addActionLabel('Tambah Pasangan')
                            ->reorderableWithButtons()
                            ->minItems(2),
                    ])
                    ->visible(fn(Get $get) => $get('tipe') === Question::TIPE_JODOH),

                // === Kata Kunci (ISIAN) ===
                Forms\Components\Section::make('Kata Kunci Jawaban')
                    ->description('Semua kata kunci berikut diterima sebagai jawaban benar (case-insensitive)')
                    ->schema([
                        Forms\Components\Repeater::make('keywords')
                            ->label('')
                            ->relationship('keywords')
                            ->schema([
                                Forms\Components\TextInput::make('keyword')
                                    ->label('Kata Kunci')
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->addActionLabel('Tambah Kata Kunci')
                            ->minItems(1),
                    ])
                    ->visible(fn(Get $get) => $get('tipe') === Question::TIPE_ISIAN),

                // === Isian Teks / Blanks (CLOZE) ===
                Forms\Components\Section::make('Isian Teks (CLOZE)')
                    ->description('Tandai bagian yang dikosongkan dalam soal dengan [1], [2], dst. Isi jawaban benar tiap blank di bawah.')
                    ->schema([
                        Forms\Components\Repeater::make('clozeBlank')
                            ->label('')
                            ->relationship('clozeBlank')
                            ->schema([
                                Forms\Components\TextInput::make('urutan')
                                    ->label('No.')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->required()
                                    ->columnSpan(1),
                                Forms\Components\TextInput::make('placeholder')
                                    ->label('Placeholder [N]')
                                    ->maxLength(20)
                                    ->placeholder('misal: kota')
                                    ->columnSpan(2),
                                Forms\Components\Textarea::make('jawaban_benar')
                                    ->label('Jawaban Benar')
                                    ->required()
                                    ->rows(1)
                                    ->columnSpan(3),
                                Forms\Components\Textarea::make('keywords_json')
                                    ->label('Sinonim / Variasi (JSON array)')
                                    ->placeholder('["Jakarta","Ibukota"]')
                                    ->rows(1)
                                    ->helperText('JSON array string variasi jawaban yang diterima')
                                    ->nullable()
                                    ->columnSpan(3),
                                Forms\Components\Toggle::make('case_sensitive')
                                    ->label('Case-sensitive')
                                    ->default(false)
                                    ->columnSpan(2),
                            ])
                            ->columns(11)
                            ->addActionLabel('Tambah Blank')
                            ->reorderableWithButtons()
                            ->orderColumn('urutan')
                            ->minItems(1),
                    ])
                    ->visible(fn(Get $get) => $get('tipe') === Question::TIPE_CLOZE),

                // === Audio / Listening ===
                Forms\Components\Section::make('Audio / Listening')
                    ->schema([
                        Forms\Components\FileUpload::make('audio_upload_temp')
                            ->label('Upload File Audio')
                            ->disk('public')
                            ->directory('audio')
                            ->acceptedFileTypes(['audio/mpeg', 'audio/ogg', 'audio/wav', 'audio/mp3'])
                            ->maxSize(fn() => \App\Models\AppSetting::getInt('max_audio_mb', 20) * 1024)
                            ->nullable()
                            ->dehydrated(false)
                            ->live()
                            ->afterStateUpdated(fn($state, Forms\Set $set) => $state ? $set('audio_url', $state) : null)
                            ->helperText('Upload MP3/OGG/WAV. Atau isi URL audio eksternal di field bawah.'),
                        Forms\Components\TextInput::make('audio_url')
                            ->label('URL Audio (atau path hasil upload)')
                            ->maxLength(500)
                            ->nullable()
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('audio_play_limit')
                            ->label('Batas Pemutaran')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->helperText('0 = tidak dibatasi'),
                        Forms\Components\Toggle::make('audio_auto_play')
                            ->label('Auto-play saat soal tampil')
                            ->default(false),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\BadgeColumn::make('tipe')
                    ->label('Tipe')
                    ->colors([
                        'info'    => Question::TIPE_PG,
                        'primary' => Question::TIPE_PG_BOBOT,
                        'success' => Question::TIPE_PGJ,
                        'warning' => Question::TIPE_JODOH,
                        'gray'    => Question::TIPE_ISIAN,
                        'danger'  => Question::TIPE_URAIAN,
                    ])
                    ->formatStateUsing(fn($state) => Question::TIPE_LABELS[$state] ?? $state),

                Tables\Columns\TextColumn::make('teks_soal')
                    ->label('Teks Soal')
                    ->html()
                    ->limit(80)
                    ->searchable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('category.nama')
                    ->label('Kategori')
                    ->placeholder('—')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('tingkat_kesulitan')
                    ->label('Kesulitan')
                    ->colors([
                        'success' => 'mudah',
                        'warning' => 'sedang',
                        'danger'  => 'sulit',
                    ])
                    ->formatStateUsing(fn($state) => Question::KESULITAN_LABELS[$state] ?? $state),

                Tables\Columns\TextColumn::make('bobot')
                    ->label('Bobot')
                    ->sortable(),

                Tables\Columns\IconColumn::make('lock_position')
                    ->label('Kunci')
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-closed')
                    ->falseIcon('heroicon-o-lock-open'),

                Tables\Columns\ToggleColumn::make('aktif')
                    ->label('Aktif'),

                Tables\Columns\TextColumn::make('group.judul')
                    ->label('Grup')
                    ->placeholder('—')
                    ->badge()
                    ->color('gray')
                    ->limit(30)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('bloom_level')
                    ->label('Bloom')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'C1', 'C2' => 'gray',
                        'C3'       => 'info',
                        'C4'       => 'warning',
                        'C5', 'C6' => 'danger',
                        default    => 'gray',
                    })
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('standard.kode')
                    ->label('KD/CP')
                    ->placeholder('—')
                    ->badge()
                    ->color('primary')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('visibilitas')
                    ->label('Visibilitas')
                    ->badge()
                    ->formatStateUsing(fn($state) => Question::VISIBILITAS_LABELS[$state] ?? $state)
                    ->color(fn($state) => match ($state) {
                        Question::VISIBILITAS_INTERNAL => 'warning',
                        Question::VISIBILITAS_PUBLIK   => 'success',
                        default                        => 'gray',
                    })
                    ->icon(fn($state) => match ($state) {
                        Question::VISIBILITAS_INTERNAL => 'heroicon-o-user-group',
                        Question::VISIBILITAS_PUBLIK   => 'heroicon-o-globe-alt',
                        default                        => 'heroicon-o-lock-closed',
                    }),

                Tables\Columns\IconColumn::make('audio_url')
                    ->label('Audio')
                    ->boolean()
                    ->falsy(null)
                    ->trueIcon('heroicon-o-musical-note')
                    ->falseIcon('')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Dibuat Oleh')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tipe')
                    ->label('Tipe Soal')
                    ->options(Question::TIPE_LABELS)
                    ->multiple(),

                Tables\Filters\SelectFilter::make('kategori_id')
                    ->label('Kategori')
                    ->options(fn() => Category::pluck('nama', 'id'))
                    ->searchable(),

                Tables\Filters\SelectFilter::make('tingkat_kesulitan')
                    ->label('Kesulitan')
                    ->options(Question::KESULITAN_LABELS),

                Tables\Filters\TernaryFilter::make('aktif')
                    ->label('Status')
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif')
                    ->placeholder('Semua'),

                Tables\Filters\TernaryFilter::make('lock_position')
                    ->label('Kunci Posisi')
                    ->trueLabel('Terkunci')
                    ->falseLabel('Bebas')
                    ->placeholder('Semua'),

                Tables\Filters\Filter::make('standalone')
                    ->label('Soal Standalone')
                    ->query(fn(\Illuminate\Database\Eloquent\Builder $query) => $query->whereNull('question_group_id'))
                    ->toggle(),

                Tables\Filters\Filter::make('in_group')
                    ->label('Soal dalam Grup')
                    ->query(fn(\Illuminate\Database\Eloquent\Builder $query) => $query->whereNotNull('question_group_id'))
                    ->toggle(),

                Tables\Filters\SelectFilter::make('question_group_id')
                    ->label('Grup Stimulus')
                    ->options(fn() => QuestionGroup::pluck('judul', 'id'))
                    ->searchable(),

                Tables\Filters\SelectFilter::make('bloom_level')
                    ->label('Level Bloom')
                    ->options(CurriculumStandard::BLOOM_LABELS),

                Tables\Filters\SelectFilter::make('curriculum_standard_id')
                    ->label('KD / CP')
                    ->options(fn() => CurriculumStandard::query()
                        ->orderBy('mata_pelajaran')->orderBy('kode')
                        ->get()
                        ->mapWithKeys(fn($s) => [$s->id => "[{$s->kode}] {$s->mata_pelajaran}"]))
                    ->searchable(),

                Tables\Filters\SelectFilter::make('tags')
                    ->label('Tag')
                    ->relationship('tags', 'nama')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('visibilitas')
                    ->label('Visibilitas')
                    ->options(Question::VISIBILITAS_LABELS),

                Tables\Filters\Filter::make('soal_saya')
                    ->label('Soal Saya')
                    ->query(fn(\Illuminate\Database\Eloquent\Builder $query) => $query->where('created_by', Auth::id()))
                    ->toggle(),

                Tables\Filters\TernaryFilter::make('punya_audio')
                    ->label('Audio')
                    ->trueLabel('Ada Audio')
                    ->falseLabel('Tanpa Audio')
                    ->placeholder('Semua')
                    ->queries(
                        true: fn($q) => $q->whereNotNull('audio_url')->where('audio_url', '!=', ''),
                        false: fn($q) => $q->where(fn($s) => $s->whereNull('audio_url')->orWhere('audio_url', '')),
                        blank: fn($q) => $q,
                    ),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->after(function (Question $record) {
                        AuditLogService::log('hapus_soal', null, "Soal dihapus: ID {$record->id}");
                    })
                    ->successNotificationTitle('Soal berhasil dihapus'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('jadikan_internal')
                        ->label('Jadikan Internal')
                        ->icon('heroicon-o-user-group')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Jadikan Soal Internal')
                        ->modalDescription('Visibilitas soal yang dipilih akan diubah menjadi Internal (Semua Guru).')
                        ->visible(fn() => Auth::user()?->level >= \App\Models\User::LEVEL_ADMIN)
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $records->each->update(['visibilitas' => Question::VISIBILITAS_INTERNAL]);
                            Notification::make()->success()->title($records->count() . ' soal dijadikan internal.')->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('publish_publik')
                        ->label('Publish Publik')
                        ->icon('heroicon-o-globe-alt')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Publish Soal ke Publik')
                        ->modalDescription('Visibilitas soal yang dipilih akan diubah menjadi Publik (Seluruh Sekolah).')
                        ->visible(fn() => Auth::user()?->level >= \App\Models\User::LEVEL_ADMIN)
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $records->each->update(['visibilitas' => Question::VISIBILITAS_PUBLIK]);
                            Notification::make()->success()->title($records->count() . ' soal dipublikasikan.')->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('bulk_toggle_aktif')
                        ->label('Toggle Aktif/Nonaktif')
                        ->icon('heroicon-o-check-circle')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Toggle Status Aktif Soal')
                        ->modalDescription('Status aktif semua soal yang dipilih akan dibalik (aktif → nonaktif atau sebaliknya).')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            foreach ($records as $record) {
                                $record->update(['aktif' => ! $record->aktif]);
                            }
                            Notification::make()->success()->title('Status aktif ' . $records->count() . ' soal diperbarui.')->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();
        $user  = Auth::user();

        if ($user->level === \App\Models\User::LEVEL_GURU) {
            return $query->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)
                    ->orWhereIn('visibilitas', [
                        Question::VISIBILITAS_INTERNAL,
                        Question::VISIBILITAS_PUBLIK,
                    ]);
            });
        }

        return $query;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        $user = Auth::user();
        if ($user->level >= \App\Models\User::LEVEL_ADMIN) {
            return true;
        }
        return $record->created_by === $user->id;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        $user = Auth::user();
        if ($user->level >= \App\Models\User::LEVEL_ADMIN) {
            return true;
        }
        return $record->created_by === $user->id;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListQuestions::route('/'),
            'create' => Pages\CreateQuestion::route('/create'),
            'edit'   => Pages\EditQuestion::route('/{record}/edit'),
        ];
    }
}
