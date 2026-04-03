<?php

namespace App\Filament\Resources\ExamPackageResource\RelationManagers;

use App\Models\Category;
use App\Models\ExamPackageQuestion;
use App\Models\Question;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
                    ->limit(80),

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
                    ->label('Lock')
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-closed')
                    ->falseIcon('heroicon-o-lock-open')
                    ->trueColor('danger')
                    ->falseColor('gray'),
            ])
            ->headerActions($locked ? [
                Tables\Actions\Action::make('locked_notice')
                    ->label('Paket terkunci — tidak bisa tambah/hapus soal')
                    ->disabled()
                    ->icon('heroicon-o-lock-closed')
                    ->color('danger'),
            ] : [
                // 1. Tambah Soal Manual
                Tables\Actions\AttachAction::make()
                    ->label('Tambah Soal')
                    ->icon('heroicon-o-plus')
                    ->color('primary')
                    ->preloadRecordSelect()
                    ->recordSelectOptionsQuery(fn(Builder $query) => $query->where('aktif', true))
                    ->recordSelectSearchColumns(['teks_soal'])
                    ->after(function ($livewire) {
                        // set urutan = max + 1
                        $owner    = $livewire->getOwnerRecord();
                        $maxUrutan = ExamPackageQuestion::where('exam_package_id', $owner->id)->max('urutan') ?? 0;
                        $pivot     = ExamPackageQuestion::where('exam_package_id', $owner->id)
                            ->orderByDesc('id')
                            ->first();
                        if ($pivot && $pivot->urutan === 0) {
                            $pivot->update(['urutan' => $maxUrutan + 1]);
                        }
                    }),

                // 2. Auto-Pilih Soal
                Tables\Actions\Action::make('auto_pilih')
                    ->label('Auto-Pilih Soal')
                    ->icon('heroicon-o-sparkles')
                    ->color('info')
                    ->form([
                        Forms\Components\Select::make('kategori_id')
                            ->label('Kategori')
                            ->options(fn() => Category::whereNull('parent_id')->pluck('nama', 'id'))
                            ->nullable()
                            ->searchable(),

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
                            ->whereNotIn('id', $existing);

                        if (! empty($data['tipe'])) {
                            $query->where('tipe', $data['tipe']);
                        }
                        if (! empty($data['kesulitan'])) {
                            $query->where('tingkat_kesulitan', $data['kesulitan']);
                        }
                        if (! empty($data['kategori_id'])) {
                            $query->whereHas('category', function ($q) use ($data) {
                                $q->where('id', $data['kategori_id'])
                                    ->orWhere('parent_id', $data['kategori_id']);
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
