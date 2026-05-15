<?php

namespace App\Filament\Resources\ExamBlueprintResource\Pages;

use App\Filament\Resources\ExamBlueprintResource;
use App\Models\Category;
use App\Models\CurriculumStandard;
use App\Models\ExamBlueprintItem;
use App\Models\MataPelajaran;
use App\Models\Question;
use App\Models\Tag;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

class CreateBlueprintItem extends Page
{
    protected static string $resource = ExamBlueprintResource::class;

    protected static string $view = 'filament.resources.exam-blueprint-resource.pages.blueprint-item-form';

    public ?array $data = [];

    public Model $blueprint;

    public function mount(int|string $record): void
    {
        $this->blueprint = static::getResource()::getModel()::findOrFail($record);

        $this->form->fill([
            'urutan' => ($this->blueprint->items()->max('urutan') ?? 0) + 1,
            'jumlah_soal' => 5,
            'bobot_per_soal' => 1,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema(self::getItemFormSchema())
            ->statePath('data');
    }

    public static function getItemFormSchema(): array
    {
        return [
            // ── Sumber Soal ──────────────────────────────────────────────────
            Forms\Components\Section::make('Sumber Soal')
                ->description('Filter KD/CP dan kategori yang menjadi sumber pengambilan soal')
                ->schema([
                    Forms\Components\Select::make('_mapel_filter')
                        ->label('Mata Pelajaran')
                        ->helperText('Pilih mapel untuk menyaring pilihan kategori')
                        ->options(fn() => MataPelajaran::where('aktif', true)->orderBy('nama')->pluck('nama', 'id'))
                        ->searchable()
                        ->nullable()
                        ->native(false)
                        ->live()
                        ->dehydrated(false)
                        ->afterStateUpdated(fn(Set $set) => $set('category_id', null)),

                    Forms\Components\Select::make('category_id')
                        ->label('Kategori Soal')
                        ->options(fn(Get $get) => $get('_mapel_filter')
                            ? Category::where('mata_pelajaran_id', $get('_mapel_filter'))->orderBy('nama')->pluck('nama', 'id')
                            : Category::orderBy('nama')->pluck('nama', 'id'))
                        ->searchable()
                        ->nullable()
                        ->native(false),

                    Forms\Components\Select::make('standard_id')
                        ->label('KD / CP')
                        ->options(fn() => CurriculumStandard::query()
                            ->orderBy('mata_pelajaran')->orderBy('kode')
                            ->get()
                            ->mapWithKeys(fn($s) => [$s->id => "[{$s->kode}] {$s->mata_pelajaran} — {$s->nama}"]))
                        ->searchable()
                        ->nullable()
                        ->native(false)
                        ->live()
                        ->afterStateUpdated(function ($state, Set $set) {
                            if ($state) {
                                $std = CurriculumStandard::find($state);
                                if ($std) {
                                    if ($std->capaian_pembelajaran) $set('capaian_pembelajaran', $std->capaian_pembelajaran);
                                    if ($std->materi)               $set('materi', $std->materi);
                                    if ($std->nama)                 $set('indikator', $std->nama);
                                    if ($std->tingkat_kognitif)     $set('bloom_level', $std->tingkat_kognitif);
                                }
                            }
                        })
                        ->columnSpan(2),

                    Forms\Components\Select::make('tipe_soal')
                        ->label('Tipe Soal')
                        ->options(Question::TIPE_LABELS)
                        ->nullable()
                        ->native(false),

                    Forms\Components\Select::make('tingkat_kesulitan')
                        ->label('Tingkat Kesulitan')
                        ->options(Question::KESULITAN_LABELS)
                        ->nullable()
                        ->native(false),

                    Forms\Components\Select::make('bloom_level')
                        ->label('Level Bloom (Kognitif)')
                        ->options(CurriculumStandard::BLOOM_LABELS)
                        ->nullable()
                        ->native(false),

                    Forms\Components\Select::make('tag_id')
                        ->label('Tag')
                        ->options(fn() => Tag::pluck('nama', 'id'))
                        ->searchable()
                        ->nullable()
                        ->native(false),
                ])->columns(3),

            // ── Konten Kisi-kisi Formal ──────────────────────────────────────
            Forms\Components\Section::make('Konten Kisi-kisi Formal')
                ->description('Teks yang tampil di dokumen kisi-kisi cetak. Auto-fill saat KD/CP dipilih.')
                ->schema([
                    Forms\Components\TextInput::make('capaian_pembelajaran')
                        ->label('Capaian Pembelajaran')
                        ->maxLength(200)
                        ->nullable()
                        ->placeholder('mis. Algoritma dan Pemrograman')
                        ->helperText('Auto-fill dari KD/CP, bisa diedit'),

                    Forms\Components\TextInput::make('materi')
                        ->label('Materi')
                        ->maxLength(200)
                        ->nullable()
                        ->placeholder('mis. Scratch dan Blockly')
                        ->helperText('Auto-fill dari KD/CP, bisa diedit'),

                    Forms\Components\Textarea::make('indikator')
                        ->label('Indikator Soal')
                        ->rows(3)
                        ->nullable()
                        ->columnSpanFull()
                        ->helperText('Auto-fill dari nama KD/CP, bisa diedit')
                        ->placeholder('mis. Peserta didik mampu memahami dan mengidentifikasi kode pemrograman Scratch'),
                ])->columns(2),

            // ── Kuantitas & Urutan ───────────────────────────────────────────
            Forms\Components\Section::make('Kuantitas & Penomoran')
                ->schema([
                    Forms\Components\TextInput::make('jumlah_soal')
                        ->label('Jumlah Soal')
                        ->numeric()
                        ->required()
                        ->minValue(1)
                        ->default(5)
                        ->helperText('Berapa soal yang diambil dari kriteria ini'),

                    Forms\Components\TextInput::make('nomor_soal')
                        ->label('Nomor Soal')
                        ->maxLength(100)
                        ->nullable()
                        ->placeholder('mis. 1-5, 6, 7')
                        ->helperText('Nomor soal pada lembar ujian'),

                    Forms\Components\TextInput::make('bobot_per_soal')
                        ->label('Bobot per Soal')
                        ->numeric()
                        ->default(1)
                        ->minValue(0)
                        ->step(0.5)
                        ->helperText('Nilai per butir soal'),

                    Forms\Components\TextInput::make('urutan')
                        ->label('Urutan Baris')
                        ->numeric()
                        ->required()
                        ->helperText('Urutan tampil di kisi-kisi'),
                ])->columns(4),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Simpan Baris Kisi-kisi')
                ->submit('save'),

            Action::make('saveAndCreate')
                ->label('Simpan & Tambah Lagi')
                ->color('gray')
                ->action('saveAndCreate'),

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

        $data['blueprint_id'] = $this->blueprint->id;

        ExamBlueprintItem::create($data);

        Notification::make()
            ->title('Baris kisi-kisi berhasil ditambahkan')
            ->success()
            ->send();

        $this->redirect(ExamBlueprintResource::getUrl('edit', ['record' => $this->blueprint]));
    }

    public function saveAndCreate(): void
    {
        $data = $this->form->getState();
        unset($data['_mapel_filter']);

        $data['blueprint_id'] = $this->blueprint->id;

        ExamBlueprintItem::create($data);

        Notification::make()
            ->title('Baris kisi-kisi berhasil ditambahkan')
            ->success()
            ->send();

        $nextUrutan = ($this->blueprint->items()->max('urutan') ?? 0) + 1;
        $this->form->fill([
            'urutan'         => $nextUrutan,
            'jumlah_soal'    => 5,
            'bobot_per_soal' => 1,
        ]);
    }

    public function getTitle(): string
    {
        return 'Tambah Baris Kisi-kisi — ' . $this->blueprint->nama;
    }

    public function getBreadcrumbs(): array
    {
        return [
            ExamBlueprintResource::getUrl() => 'Kisi-kisi / Blueprint',
            ExamBlueprintResource::getUrl('edit', ['record' => $this->blueprint]) => $this->blueprint->nama,
            '#' => 'Tambah Baris',
        ];
    }
}
