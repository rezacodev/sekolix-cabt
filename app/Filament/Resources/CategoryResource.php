<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Models\Category;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationLabel = 'Kategori Soal';

    protected static ?string $navigationGroup = 'Bank Soal';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'Kategori';

    protected static ?string $pluralModelLabel = 'Kategori Soal';

    /**
     * Guru hanya bisa edit/delete kategori milik sendiri.
     * Admin & SuperAdmin bisa mengelola semua kategori.
     */
    public static function canEdit(Model $record): bool
    {
        $user = Auth::user();
        if ($user->level === User::LEVEL_GURU) {
            // NULL created_by = kategori sistem, hanya Admin yang boleh kelola
            return $record->created_by !== null && $record->created_by === $user->id;
        }
        return $user->level >= User::LEVEL_ADMIN;
    }

    public static function canDelete(Model $record): bool
    {
        $user = Auth::user();
        if ($user->level === User::LEVEL_GURU) {
            return $record->created_by !== null && $record->created_by === $user->id;
        }
        return $user->level >= User::LEVEL_ADMIN;
    }

    public static function canDeleteAny(): bool
    {
        return Auth::user()->level >= User::LEVEL_ADMIN;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nama')
                    ->label('Nama Kategori')
                    ->required()
                    ->maxLength(150)
                    ->columnSpanFull(),

                Forms\Components\Select::make('parent_id')
                    ->label('Kategori Induk')
                    ->helperText('Kosongkan jika ini adalah kategori utama')
                    ->options(fn() => Category::whereNull('parent_id')->pluck('nama', 'id'))
                    ->searchable()
                    ->nullable()
                    ->native(false),

                Forms\Components\Textarea::make('deskripsi')
                    ->label('Deskripsi')
                    ->nullable()
                    ->rows(3)
                    ->columnSpanFull(),
            ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama')
                    ->label('Nama Kategori')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('parent.nama')
                    ->label('Kategori Induk')
                    ->placeholder('—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('children_count')
                    ->label('Sub-kategori')
                    ->counts('children')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('questions_count')
                    ->label('Jumlah Soal')
                    ->counts('questions')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Dibuat Oleh')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_locked')
                    ->label('Status')
                    ->state(function (Category $record): bool {
                        $user = Auth::user();
                        if ($user->level !== User::LEVEL_GURU) {
                            return false;
                        }
                        return $record->created_by !== $user->id;
                    })
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-closed')
                    ->falseIcon('heroicon-o-pencil-square')
                    ->trueColor('danger')
                    ->falseColor('success')
                    ->tooltip(fn(Category $record): string => (Auth::user()->level === User::LEVEL_GURU && $record->created_by !== Auth::id())
                        ? 'Terkunci — hanya dapat diedit oleh pembuat'
                        : 'Dapat diedit')
                    ->visible(fn(): bool => Auth::user()->level === User::LEVEL_GURU),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('parent_id')
                    ->label('Kategori Induk')
                    ->options(fn() => Category::whereNull('parent_id')->pluck('nama', 'id'))
                    ->placeholder('Semua'),

                Tables\Filters\TernaryFilter::make('is_locked')
                    ->label('Status Edit')
                    ->trueLabel('Terkunci (milik orang lain)')
                    ->falseLabel('Dapat diedit (milik saya)')
                    ->queries(
                        true: fn(Builder $query) => $query->where(fn($q) => $q->whereNull('created_by')->orWhere('created_by', '!=', Auth::id())),
                        false: fn(Builder $query) => $query->where('created_by', Auth::id()),
                    )
                    ->hidden(fn(): bool => Auth::user()->level !== User::LEVEL_GURU),

                Tables\Filters\SelectFilter::make('created_by')
                    ->label('Dibuat Oleh')
                    ->options(fn() => User::where('level', '>=', User::LEVEL_GURU)->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->native(false)
                    ->hidden(fn(): bool => Auth::user()->level < User::LEVEL_ADMIN),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->successNotificationTitle('Kategori berhasil dihapus'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ])->visible(fn(): bool => Auth::user()->level >= User::LEVEL_ADMIN),
            ])
            ->defaultSort('nama');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit'   => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
