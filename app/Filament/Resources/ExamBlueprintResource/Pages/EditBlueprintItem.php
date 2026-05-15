<?php

namespace App\Filament\Resources\ExamBlueprintResource\Pages;

use App\Filament\Resources\ExamBlueprintResource;
use App\Models\Category;
use App\Models\ExamBlueprintItem;
use Filament\Forms\Form;
use Filament\Resources\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

class EditBlueprintItem extends Page
{
    protected static string $resource = ExamBlueprintResource::class;

    protected static string $view = 'filament.resources.exam-blueprint-resource.pages.blueprint-item-form';

    public ?array $data = [];

    public Model $blueprint;
    public Model $item;

    public function mount(int|string $record, int|string $item): void
    {
        $this->blueprint = static::getResource()::getModel()::findOrFail($record);
        $this->item      = ExamBlueprintItem::findOrFail($item);

        $formData = $this->item->toArray();

        // Restore _mapel_filter from category's mata_pelajaran_id
        if ($this->item->category_id) {
            $mapelId = Category::find($this->item->category_id)?->mata_pelajaran_id;
            if ($mapelId) {
                $formData['_mapel_filter'] = $mapelId;
            }
        }

        $this->form->fill($formData);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema(CreateBlueprintItem::getItemFormSchema())
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Simpan Perubahan')
                ->submit('save'),

            Action::make('cancel')
                ->label('Batal')
                ->color('gray')
                ->url(fn() => ExamBlueprintResource::getUrl('edit', ['record' => $this->blueprint])),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();
        unset($data['_mapel_filter']);

        $this->item->update($data);

        Notification::make()
            ->title('Baris kisi-kisi berhasil diperbarui')
            ->success()
            ->send();

        $this->redirect(ExamBlueprintResource::getUrl('edit', ['record' => $this->blueprint]));
    }

    public function getTitle(): string
    {
        return 'Edit Baris Kisi-kisi — ' . $this->blueprint->nama;
    }

    public function getBreadcrumbs(): array
    {
        return [
            ExamBlueprintResource::getUrl() => 'Kisi-kisi / Blueprint',
            ExamBlueprintResource::getUrl('edit', ['record' => $this->blueprint]) => $this->blueprint->nama,
            '#' => 'Edit Baris #' . $this->item->urutan,
        ];
    }
}
