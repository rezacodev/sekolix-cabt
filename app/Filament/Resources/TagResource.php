<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TagResource\Pages;
use App\Models\Tag;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class TagResource extends Resource
{
    protected static ?string $model = Tag::class;

    protected static ?string $navigationIcon  = 'heroicon-o-tag';
    protected static ?string $navigationLabel = 'Tag Soal';
    protected static ?string $navigationGroup = 'Bank Soal';
    protected static bool $shouldRegisterNavigation = false;
    protected static ?int    $navigationSort  = 13;
    protected static ?string $modelLabel       = 'Tag';
    protected static ?string $pluralModelLabel = 'Tag Soal';

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
        return Auth::user()->level >= User::LEVEL_ADMIN && $record->questions()->count() === 0;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('nama')
                ->label('Nama Tag')
                ->required()
                ->maxLength(100)
                ->unique(ignoreRecord: true)
                ->placeholder('mis. HOTS, Literasi, Numerasi'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('nama')
            ->columns([
                Tables\Columns\TextColumn::make('nama')
                    ->label('Nama Tag')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('questions_count')
                    ->label('Jml Soal')
                    ->counts('questions')
                    ->sortable(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (Tables\Actions\DeleteAction $action, $record) {
                        if ($record->questions()->count() > 0) {
                            Notification::make()
                                ->title('Tidak bisa dihapus')
                                ->body('Tag ini masih dipakai oleh ' . $record->questions()->count() . ' soal.')
                                ->danger()
                                ->send();
                            $action->cancel();
                        }
                    })
                    ->successNotificationTitle('Tag berhasil dihapus'),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTags::route('/'),
            'create' => Pages\CreateTag::route('/create'),
            'edit'   => Pages\EditTag::route('/{record}/edit'),
        ];
    }
}
