<?php

namespace App\Filament\Pages;

use App\Models\AppSetting;
use App\Models\User;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class GeneralSetting extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon  = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Pengaturan Umum';
    protected static ?string $navigationGroup = 'Pengaturan';
    protected static ?int    $navigationSort  = 100;
    protected static string  $view            = 'filament.pages.general-setting';
    protected static ?string $slug            = 'pengaturan-umum';
    protected static ?string $title           = 'Pengaturan Umum';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user && $user->level >= User::LEVEL_ADMIN;
    }

    public function mount(): void
    {
        $this->form->fill([
            // Identitas Aplikasi
            'app_name'                     => AppSetting::getString('app_name', config('app.name')),
            'school_name'                  => AppSetting::getString('school_name', ''),
            'school_logo_url'              => AppSetting::getString('school_logo_url', ''),
            'maintenance_mode'             => AppSetting::getBool('maintenance_mode', false),

            // Autentikasi & Sesi
            'allow_multi_login'            => AppSetting::getBool('allow_multi_login', false),
            'login_max_attempts'           => AppSetting::getInt('login_max_attempts', 5),
            'login_lockout_minutes'        => AppSetting::getInt('login_lockout_minutes', 15),
            'session_timeout_minutes'      => AppSetting::getInt('session_timeout_minutes', 0),

            // Anti-Kecurangan
            'max_tab_switch'               => AppSetting::getInt('max_tab_switch', 3),
            'tab_switch_action'            => AppSetting::getString('tab_switch_action', 'warn'),
            'auto_submit_on_max_tab'       => AppSetting::getBool('auto_submit_on_max_tab', true),
            'prevent_copy_paste'           => AppSetting::getBool('prevent_copy_paste', false),
            'prevent_right_click'          => AppSetting::getBool('prevent_right_click', false),
            'require_fullscreen'           => AppSetting::getBool('require_fullscreen', false),
            'max_upload_mb'                => AppSetting::getInt('max_upload_mb', 5),
            'ip_whitelist'                 => AppSetting::getString('ip_whitelist', ''),

            // Penilaian & Tampilan
            'realtime_grading'             => AppSetting::getBool('realtime_grading', true),
            'show_ranking_hasil'           => AppSetting::getBool('show_ranking_hasil', false),
            'show_pembahasan_setelah_sesi' => AppSetting::getBool('show_pembahasan_setelah_sesi', false),

            // Livescore
            'show_livescore'               => AppSetting::getBool('show_livescore', true),
            'livescore_public'             => AppSetting::getBool('livescore_public', true),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Identitas Aplikasi')
                    ->icon('heroicon-o-information-circle')
                    ->columns(2)
                    ->schema([
                        TextInput::make('app_name')
                            ->label('Nama Aplikasi')
                            ->required()
                            ->maxLength(60)
                            ->columnSpan(1),

                        Toggle::make('maintenance_mode')
                            ->label('Mode Maintenance')
                            ->helperText('Aktifkan untuk menonaktifkan akses peserta sementara.')
                            ->columnSpan(1),

                        TextInput::make('school_name')
                            ->label('Nama Sekolah')
                            ->maxLength(120)
                            ->helperText('Ditampilkan di header laporan, cetak, dan cover.')
                            ->columnSpan(1),

                        TextInput::make('school_logo_url')
                            ->label('URL Logo Sekolah')
                            ->url()
                            ->maxLength(255)
                            ->helperText('URL/path logo sekolah untuk header laporan.')
                            ->columnSpan(1),
                    ]),

                Section::make('Autentikasi & Sesi')
                    ->icon('heroicon-o-lock-closed')
                    ->columns(2)
                    ->schema([
                        Toggle::make('allow_multi_login')
                            ->label('Izinkan Multi-Login Peserta')
                            ->helperText('Jika aktif, peserta dapat login dari lebih dari satu perangkat sekaligus.')
                            ->columnSpan('full'),

                        TextInput::make('login_max_attempts')
                            ->label('Maks Percobaan Login')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(99)
                            ->suffix('kali')
                            ->helperText('Jumlah percobaan login salah sebelum akun dikunci sementara.')
                            ->columnSpan(1),

                        TextInput::make('login_lockout_minutes')
                            ->label('Durasi Lockout Login')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(1440)
                            ->suffix('menit')
                            ->helperText('Lama akun dikunci setelah melebihi maks percobaan login.')
                            ->columnSpan(1),

                        TextInput::make('session_timeout_minutes')
                            ->label('Timeout Sesi Idle Peserta')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(480)
                            ->suffix('menit')
                            ->helperText('Sesi otomatis berakhir jika tidak ada aktivitas. Isi 0 untuk menonaktifkan.')
                            ->columnSpan(1),
                    ]),

                Section::make('Anti-Kecurangan')
                    ->icon('heroicon-o-shield-exclamation')
                    ->columns(2)
                    ->schema([
                        TextInput::make('max_tab_switch')
                            ->label('Batas Tab Switch')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(99)
                            ->suffix('kali')
                            ->helperText('Jumlah maksimal perpindahan tab/jendela sebelum aksi dijalankan.')
                            ->columnSpan(1),

                        Select::make('tab_switch_action')
                            ->label('Aksi Saat Batas Tercapai')
                            ->options([
                                'log'    => 'Catat saja (log)',
                                'warn'   => 'Tampilkan peringatan',
                                'submit' => 'Kumpulkan ujian otomatis',
                            ])
                            ->helperText('Tindakan yang diambil saat peserta mencapai batas tab switch.')
                            ->columnSpan(1),

                        Toggle::make('auto_submit_on_max_tab')
                            ->label('Auto-Submit Saat Batas Tercapai')
                            ->helperText('Langsung kumpulkan ujian otomatis saat batas tab switch terlampaui, tanpa menunggu aksi lain.')
                            ->columnSpan('full'),

                        Toggle::make('prevent_copy_paste')
                            ->label('Nonaktifkan Copy-Paste')
                            ->helperText('Menonaktifkan copy, paste, dan cut di seluruh area soal via JavaScript.')
                            ->columnSpan(1),

                        Toggle::make('prevent_right_click')
                            ->label('Nonaktifkan Klik Kanan')
                            ->helperText('Menonaktifkan klik kanan dan seleksi teks di area soal.')
                            ->columnSpan(1),

                        Toggle::make('require_fullscreen')
                            ->label('Wajib Fullscreen')
                            ->helperText('Peserta diwajibkan berada di mode fullscreen. Keluar dari fullscreen dicatat sebagai tab switch event.')
                            ->columnSpan(1),

                        TextInput::make('max_upload_mb')
                            ->label('Batas Upload File URAIAN')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(50)
                            ->suffix('MB')
                            ->helperText('Ukuran maksimal file jawaban URAIAN yang dapat diunggah peserta.')
                            ->columnSpan(1),

                        Textarea::make('ip_whitelist')
                            ->label('IP Whitelist Ujian')
                            ->rows(3)
                            ->helperText('Daftar IP yang diizinkan mengakses halaman ujian, pisahkan dengan koma. Kosongkan untuk mengizinkan semua IP.')
                            ->placeholder('192.168.1.1, 192.168.1.2')
                            ->columnSpan('full'),
                    ]),

                Section::make('Penilaian & Tampilan Hasil')
                    ->icon('heroicon-o-calculator')
                    ->columns(2)
                    ->schema([
                        Toggle::make('realtime_grading')
                            ->label('Grading Real-time')
                            ->helperText('Jika aktif, nilai dihitung otomatis saat peserta selesai. Jika nonaktif, perlu trigger manual di halaman Grading.')
                            ->columnSpan(1),

                        Toggle::make('show_ranking_hasil')
                            ->label('Tampilkan Ranking di Hasil')
                            ->helperText('Tampilkan posisi ranking peserta di antara peserta satu sesi di halaman hasil ujian.')
                            ->columnSpan(1),

                        Toggle::make('show_pembahasan_setelah_sesi')
                            ->label('Pembahasan Hanya Setelah Sesi Selesai')
                            ->helperText('Jika aktif, peserta hanya dapat melihat pembahasan jawaban setelah status sesi berubah menjadi Selesai.')
                            ->columnSpan('full'),
                    ]),

                Section::make('Livescore')
                    ->icon('heroicon-o-chart-bar')
                    ->columns(2)
                    ->schema([
                        Toggle::make('show_livescore')
                            ->label('Tampilkan Tombol Livescore')
                            ->helperText('Tampilkan tombol Livescore di halaman Monitor Sesi.')
                            ->columnSpan(1),

                        Toggle::make('livescore_public')
                            ->label('Livescore Dapat Diakses Publik')
                            ->helperText('Jika nonaktif, livescore hanya dapat diakses oleh pengguna yang sudah login.')
                            ->columnSpan(1),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        // Identitas Aplikasi
        AppSetting::set('app_name',                     $state['app_name'],                       'string');
        AppSetting::set('school_name',                  $state['school_name'] ?? '',              'string');
        AppSetting::set('school_logo_url',              $state['school_logo_url'] ?? '',          'string');
        AppSetting::set('maintenance_mode',             $state['maintenance_mode'],               'bool');

        // Autentikasi & Sesi
        AppSetting::set('allow_multi_login',            $state['allow_multi_login'],              'bool');
        AppSetting::set('login_max_attempts',           $state['login_max_attempts'],             'int');
        AppSetting::set('login_lockout_minutes',        $state['login_lockout_minutes'],          'int');
        AppSetting::set('session_timeout_minutes',      $state['session_timeout_minutes'],        'int');

        // Anti-Kecurangan
        AppSetting::set('max_tab_switch',               $state['max_tab_switch'],                 'int');
        AppSetting::set('tab_switch_action',            $state['tab_switch_action'],              'string');
        AppSetting::set('auto_submit_on_max_tab',       $state['auto_submit_on_max_tab'],         'bool');
        AppSetting::set('prevent_copy_paste',           $state['prevent_copy_paste'],             'bool');
        AppSetting::set('prevent_right_click',          $state['prevent_right_click'],            'bool');
        AppSetting::set('require_fullscreen',           $state['require_fullscreen'],             'bool');
        AppSetting::set('max_upload_mb',                $state['max_upload_mb'],                  'int');
        AppSetting::set('ip_whitelist',                 $state['ip_whitelist'] ?? '',             'string');

        // Penilaian & Tampilan
        AppSetting::set('realtime_grading',             $state['realtime_grading'],               'bool');
        AppSetting::set('show_ranking_hasil',           $state['show_ranking_hasil'],             'bool');
        AppSetting::set('show_pembahasan_setelah_sesi', $state['show_pembahasan_setelah_sesi'],   'bool');

        // Livescore
        AppSetting::set('show_livescore',               $state['show_livescore'],                 'bool');
        AppSetting::set('livescore_public',             $state['livescore_public'],               'bool');

        Notification::make()
            ->success()
            ->title('Pengaturan berhasil disimpan')
            ->send();
    }
}
