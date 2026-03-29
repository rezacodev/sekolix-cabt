<?php

namespace App\Filament\Resources\QuestionResource\Pages;

use App\Exports\QuestionsImportTemplate;
use App\Filament\Resources\QuestionResource;
use App\Models\Category;
use App\Models\Question;
use App\Services\ImportService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class ListQuestions extends ListRecords
{
    protected static string $resource = QuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Download template Excel
            Actions\Action::make('download_template')
                ->label('Template Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->action(fn () => Excel::download(
                    new QuestionsImportTemplate(),
                    'template-import-soal.xlsx'
                )),

            // Import massal via Excel
            Actions\Action::make('import')
                ->label('Import Excel')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('info')
                ->form([
                    Forms\Components\FileUpload::make('file')
                        ->label('File Excel (.xlsx)')
                        ->required()
                        ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                        ->disk('local')
                        ->directory('imports/soal'),
                ])
                ->action(function (array $data) {
                    $path   = storage_path('app/' . $data['file']);
                    $result = ImportService::importSoal($path);

                    $msg = "Berhasil import {$result['imported']} soal.";
                    if (!empty($result['errors'])) {
                        $msg .= ' Gagal: ' . count($result['errors']) . ' baris. Error pertama: ' . $result['errors'][0];
                    }

                    Notification::make()
                        ->title($msg)
                        ->color(empty($result['errors']) ? 'success' : 'warning')
                        ->persistent(!empty($result['errors']))
                        ->send();
                }),

            // Generate soal dummy untuk testing
            Actions\Action::make('generate_test')
                ->label('Generate Test Data')
                ->icon('heroicon-o-beaker')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Generate Soal Dummy')
                ->modalDescription('Soal ini hanya untuk keperluan testing. Akan digenerate secara acak.')
                ->form([
                    Forms\Components\Select::make('tipe')
                        ->label('Tipe Soal')
                        ->options(array_merge(['acak' => 'Acak (semua tipe)'], Question::TIPE_LABELS))
                        ->default('acak')
                        ->required()
                        ->native(false),

                    Forms\Components\Select::make('kategori_id')
                        ->label('Kategori')
                        ->options(fn () => Category::pluck('nama', 'id'))
                        ->placeholder('Tanpa kategori')
                        ->nullable()
                        ->searchable()
                        ->native(false),

                    Forms\Components\TextInput::make('jumlah')
                        ->label('Jumlah Soal')
                        ->numeric()
                        ->default(10)
                        ->minValue(1)
                        ->maxValue(100)
                        ->required(),
                ])
                ->action(function (array $data) {
                    $jumlah     = (int) $data['jumlah'];
                    $kategoriId = $data['kategori_id'] ?? null;
                    $userId     = Auth::id();

                    $tipePool = $data['tipe'] === 'acak'
                        ? array_keys(Question::TIPE_LABELS)
                        : [$data['tipe']];

                    $generated = 0;
                    for ($i = 0; $i < $jumlah; $i++) {
                        $tipe = $tipePool[array_rand($tipePool)];

                        $factory = Question::factory()
                            ->state([
                                'kategori_id' => $kategoriId,
                                'created_by'  => $userId,
                            ]);

                        match ($tipe) {
                            Question::TIPE_PG,
                            Question::TIPE_PG_BOBOT,
                            Question::TIPE_PGJ => $factory->pg()->create(),
                            Question::TIPE_JODOH  => $factory->jodoh()->create(),
                            Question::TIPE_ISIAN  => $factory->isian()->create(),
                            default              => $factory->state(['tipe' => $tipe])->create(),
                        };

                        $generated++;
                    }

                    Notification::make()
                        ->title("Berhasil generate {$generated} soal dummy.")
                        ->success()
                        ->send();
                }),

            Actions\CreateAction::make(),
        ];
    }
}
