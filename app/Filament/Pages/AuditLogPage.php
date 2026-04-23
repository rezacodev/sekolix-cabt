<?php

namespace App\Filament\Pages;

use App\Exports\AuditLogExport;
use App\Filament\Concerns\HasHelpHeader;
use App\Models\AuditLog;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class AuditLogPage extends Page implements HasTable
{
  use InteractsWithTable;
  use HasHelpHeader;

  protected static ?string $navigationIcon  = 'heroicon-o-shield-check';
  protected static ?string $navigationLabel = 'Audit Log';
  protected static ?string $navigationGroup = 'Pengaturan';
  protected static ?int    $navigationSort  = 101;
  protected static string  $view            = 'filament.pages.audit-log';
  protected static ?string $slug            = 'audit-log';
  protected static ?string $title           = 'Audit Log';

  public static function canAccess(): bool
  {
    /** @var \App\Models\User|null $user */
    $user = Auth::user();
    return $user && $user->level >= User::LEVEL_SUPER_ADMIN;
  }

  public function table(Table $table): Table
  {
    return $table
      ->query(AuditLog::query()->with('user')->latest())
      ->columns([
        TextColumn::make('created_at')
          ->label('Waktu')
          ->dateTime('d M Y H:i:s')
          ->sortable()
          ->searchable(false),

        TextColumn::make('user.name')
          ->label('User')
          ->placeholder('—')
          ->searchable()
          ->sortable(),

        TextColumn::make('action')
          ->label('Aksi')
          ->badge()
          ->searchable()
          ->sortable(),

        TextColumn::make('model_type')
          ->label('Model')
          ->placeholder('—')
          ->searchable(),

        TextColumn::make('deskripsi')
          ->label('Deskripsi')
          ->placeholder('—')
          ->limit(80)
          ->tooltip(fn($record) => $record->deskripsi),

        TextColumn::make('ip_address')
          ->label('IP')
          ->placeholder('—')
          ->fontFamily('mono')
          ->copyable()
          ->toggleable(isToggledHiddenByDefault: true),
      ])
      ->filters([
        SelectFilter::make('user_id')
          ->label('User')
          ->options(fn() => User::orderBy('name')->pluck('name', 'id'))
          ->searchable()
          ->native(false),

        SelectFilter::make('action')
          ->label('Aksi')
          ->options(fn() => AuditLog::distinct()->orderBy('action')->pluck('action', 'action'))
          ->native(false),

        Filter::make('tanggal')
          ->label('Rentang Tanggal')
          ->form([
            DatePicker::make('dari')
              ->label('Dari')
              ->native(false),
            DatePicker::make('sampai')
              ->label('Sampai')
              ->native(false),
          ])
          ->query(function (Builder $query, array $data): Builder {
            return $query
              ->when($data['dari'],    fn($q) => $q->whereDate('created_at', '>=', $data['dari']))
              ->when($data['sampai'],  fn($q) => $q->whereDate('created_at', '<=', $data['sampai']));
          }),
      ])
      ->defaultSort('created_at', 'desc')
      ->paginated([25, 50, 100]);
  }

  protected function getHeaderActions(): array
  {
    return $this->appendHelpAction([
      Action::make('export')
        ->label('Export Excel')
        ->icon('heroicon-o-arrow-down-tray')
        ->color('gray')
        ->action(function () {
          return Excel::download(new AuditLogExport, 'audit-log-' . now()->format('Ymd-His') . '.xlsx');
        }),
    ]);
  }

  protected function getHelpModalView(): string
  {
    return 'filament.pages.actions.modal-help-audit-log';
  }
}
