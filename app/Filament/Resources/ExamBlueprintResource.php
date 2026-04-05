<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExamBlueprintResource\Pages;
use App\Filament\Resources\ExamBlueprintResource\RelationManagers;
use App\Models\CurriculumStandard;
use App\Models\ExamBlueprint;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ExamBlueprintResource extends Resource
{
    protected static ?string $model = ExamBlueprint::class;

    protected static ?string $navigationIcon  = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'Kisi-kisi / Blueprint';
    protected static ?string $navigationGroup = 'Kurikulum';
    protected static ?int    $navigationSort  = 2;
    protected static ?string $modelLabel       = 'Blueprint Ujian';
    protected static ?string $pluralModelLabel = 'Kisi-kisi / Blueprint';

    public static function canCreate(): bool
    {
        return Auth::user()->level >= User::LEVEL_GURU;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return Auth::user()->level >= User::LEVEL_GURU;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return Auth::user()->level >= User::LEVEL_ADMIN;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Identitas Blueprint')
                ->schema([
                    Forms\Components\TextInput::make('nama')
                        ->label('Nama Blueprint')
                        ->required()
                        ->maxLength(200)
                        ->placeholder('mis. Kisi-kisi UAS Matematika Kelas X Sem 1')
                        ->columnSpan(2),

                    Forms\Components\TextInput::make('mata_pelajaran')
                        ->label('Mata Pelajaran')
                        ->required()
                        ->maxLength(100)
                        ->placeholder('mis. Matematika'),

                    Forms\Components\TextInput::make('total_soal')
                        ->label('Total Soal')
                        ->numeric()
                        ->required()
                        ->minValue(1)
                        ->maxValue(500)
                        ->default(40),

                    Forms\Components\Textarea::make('deskripsi')
                        ->label('Deskripsi')
                        ->rows(2)
                        ->nullable()
                        ->columnSpanFull(),
                ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('nama')
                    ->label('Nama Blueprint')
                    ->searchable()
                    ->limit(60),

                Tables\Columns\TextColumn::make('mata_pelajaran')
                    ->label('Mapel')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_soal')
                    ->label('Total Soal')
                    ->sortable(),

                Tables\Columns\TextColumn::make('items_count')
                    ->label('Baris Kisi-kisi')
                    ->counts('items')
                    ->sortable(),

                Tables\Columns\TextColumn::make('packages_count')
                    ->label('Paket Terhubung')
                    ->counts('packages')
                    ->sortable(),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Dibuat Oleh')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('mata_pelajaran')
                    ->label('Mata Pelajaran')
                    ->options(fn() => ExamBlueprint::distinct()->pluck('mata_pelajaran', 'mata_pelajaran'))
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\Action::make('validasi_stok')
                    ->label('Validasi Stok')
                    ->icon('heroicon-o-magnifying-glass')
                    ->color('info')
                    ->modalHeading(fn($record) => 'Validasi Stok Soal: ' . $record->nama)
                    ->modalContent(function ($record) {
                        $stock = $record->validateStock();
                        $rows = '';
                        $allOk = true;
                        foreach ($stock as $item) {
                            $ok    = $item['tersedia'] >= $item['butuh'];
                            $allOk = $allOk && $ok;
                            $color = $ok ? 'text-green-600' : 'text-red-600 font-bold';
                            $rows .= "<tr>
                                <td class='px-3 py-1 text-sm text-gray-700'>{$item['label']}</td>
                                <td class='px-3 py-1 text-sm text-center'>{$item['butuh']}</td>
                                <td class='px-3 py-1 text-sm text-center {$color}'>{$item['tersedia']}</td>
                                <td class='px-3 py-1 text-sm text-center'>" . ($ok ? '✔' : '✘') . "</td>
                            </tr>";
                        }
                        $summary = $allOk
                            ? '<p class="mt-2 text-green-700 font-medium">Semua stok mencukupi.</p>'
                            : '<p class="mt-2 text-red-700 font-medium">Beberapa kriteria kekurangan soal.</p>';
                        $html = "
                            <div class='overflow-x-auto'>
                            <table class='min-w-full divide-y divide-gray-200 border rounded'>
                                <thead class='bg-gray-50'>
                                    <tr>
                                        <th class='px-3 py-2 text-left text-xs font-medium text-gray-500'>Kriteria</th>
                                        <th class='px-3 py-2 text-xs font-medium text-gray-500'>Dibutuhkan</th>
                                        <th class='px-3 py-2 text-xs font-medium text-gray-500'>Tersedia</th>
                                        <th class='px-3 py-2 text-xs font-medium text-gray-500'>Status</th>
                                    </tr>
                                </thead>
                                <tbody class='divide-y divide-gray-100'>{$rows}</tbody>
                            </table>
                            {$summary}
                            </div>";
                        return new \Illuminate\Support\HtmlString($html);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),

                Tables\Actions\Action::make('cetak')
                    ->label('Cetak Kisi-kisi')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(fn($record) => route('blueprint.cetak', $record->id))
                    ->openUrlInNewTab(),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->successNotificationTitle('Kisi-kisi ujian berhasil dihapus'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListExamBlueprints::route('/'),
            'create' => Pages\CreateExamBlueprint::route('/create'),
            'edit'   => Pages\EditExamBlueprint::route('/{record}/edit'),
        ];
    }
}
