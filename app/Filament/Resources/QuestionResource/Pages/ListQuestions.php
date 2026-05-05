<?php

namespace App\Filament\Resources\QuestionResource\Pages;

use App\Exports\QuestionsImportTemplate;
use App\Filament\Concerns\HasHelpHeader;
use App\Filament\Resources\CategoryResource;
use App\Filament\Resources\QuestionGroupResource;
use App\Filament\Resources\QuestionResource;
use App\Filament\Resources\QuestionResource\Pages\ImportQuestions;
use App\Filament\Resources\TagResource;
use App\Models\Category;
use App\Models\Question;
use Filament\Actions;
use Filament\Actions\ActionGroup;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

class ListQuestions extends ListRecords
{
    use HasHelpHeader;

    protected static string $resource = QuestionResource::class;

    protected function getHeaderActions(): array
    {
        return $this->appendHelpAction([
            // Download template Excel
            Actions\Action::make('download_template')
                ->label('Template Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->action(fn() => Excel::download(
                    new QuestionsImportTemplate(),
                    'template-import-soal.xlsx'
                )),

            // Import massal via Excel — buka halaman review
            Actions\Action::make('import')
                ->label('Import Excel')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('info')
                ->url(ImportQuestions::getUrl()),


            Actions\CreateAction::make(),

            ActionGroup::make([
                Actions\Action::make('kategori_soal')
                    ->label('Kategori Soal')
                    ->icon('heroicon-o-tag')
                    ->url(CategoryResource::getUrl()),

                Actions\Action::make('grup_soal')
                    ->label('Grup Soal')
                    ->icon('heroicon-o-rectangle-stack')
                    ->url(QuestionGroupResource::getUrl()),

                Actions\Action::make('tag_soal')
                    ->label('Tag Soal')
                    ->icon('heroicon-o-tag')
                    ->url(TagResource::getUrl()),
            ])
                ->label('Pengaturan Bank Soal')
                ->icon('heroicon-o-cog')
                ->color('warning')
                ->button(),

        ]);
    }

    protected function getHelpModalView(): string
    {
        return 'filament.pages.actions.modal-help-question';
    }
}
