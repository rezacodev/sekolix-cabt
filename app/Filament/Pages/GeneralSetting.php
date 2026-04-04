<?php

namespace App\Filament\Pages;

use App\Models\AppSetting;
use App\Models\User;
use App\Services\AuditLogService;
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
            'max_audio_mb'                 => AppSetting::getInt('max_audio_mb', 20),
            'ip_whitelist'                 => AppSetting::getString('ip_whitelist', ''),

            // Penilaian & Tampilan
            'realtime_grading'             => AppSetting::getBool('realtime_grading', true),
            'show_ranking_hasil'           => AppSetting::getBool('show_ranking_hasil', false),
            'show_pembahasan_setelah_sesi' => AppSetting::getBool('show_pembahasan_setelah_sesi', false),

            // Livescore
            'show_livescore'               => AppSetting::getBool('show_livescore', true),
            'livescore_public'             => AppSetting::getBool('livescore_public', true),

            // Notifikasi Email
            'email_notifikasi_sesi'        => AppSetting::getBool('email_notifikasi_sesi', false),
            'email_reminder_h1'            => AppSetting::getBool('email_reminder_h1', false),
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
                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Nama yang ditampilkan di seluruh antarmuka, email notifikasi, header cetak dokumen, dan laporan PDF. Ganti dengan nama sistem ujian sekolah Anda.')
                            ->hintColor('info')
                            ->columnSpan(1),

                        Toggle::make('maintenance_mode')
                            ->label('Mode Maintenance')
                            ->helperText('Aktifkan untuk menonaktifkan akses peserta sementara.')
                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Saat aktif, semua peserta tidak bisa mengakses halaman ujian dan melihat pesan "Sistem sedang dalam pemeliharaan". Admin & Guru tetap bisa login normal.')
                            ->hintColor('info')
                            ->columnSpan(1),

                        TextInput::make('school_name')
                            ->label('Nama Sekolah')
                            ->maxLength(120)
                            ->helperText('Ditampilkan di header laporan, cetak, dan cover.')
                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Nama sekolah/institusi yang ditampilkan di header laporan, berita acara, kartu peserta, dan email. Kosongkan jika tidak ingin ditampilkan.')
                            ->hintColor('info')
                            ->columnSpan(1),

                        TextInput::make('school_logo_url')
                            ->label('URL Logo Sekolah')
                            ->url()
                            ->maxLength(255)
                            ->helperText('URL/path logo sekolah untuk header laporan.')
                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'URL gambar logo sekolah (link eksternal atau path relatif ke storage/public). Ditampilkan di sudut kiri atas dokumen cetak dan PDF.')
                            ->hintColor('info')
                            ->columnSpan(1),
                    ]),

                Section::make('Autentikasi & Sesi')
                    ->icon('heroicon-o-lock-closed')
                    ->columns(2)
                    ->schema([
                        Toggle::make('allow_multi_login')
                            ->label('Izinkan Multi-Login Peserta')
                            ->helperText('Jika aktif, peserta dapat login dari lebih dari satu perangkat sekaligus.')
                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Jika nonaktif, peserta yang login dari perangkat baru akan otomatis mengeluarkan sesi sebelumnya (single session enforcement). Direkomendasikan nonaktif saat ujian berlangsung untuk mencegah berbagi akun.')
                            ->hintColor('info')
                            ->columnSpan('full'),

                        TextInput::make('login_max_attempts')
                            ->label('Maks Percobaan Login')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(99)
                            ->suffix('kali')
                            ->helperText('Jumlah percobaan login salah sebelum akun dikunci sementara.')
                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Setelah N kali login gagal berturut-turut, akun dikunci sementara selama durasi lockout yang ditentukan. Berlaku untuk semua peran. Default: 5 kali.')
                            ->hintColor('info')
                            ->columnSpan(1),

                        TextInput::make('login_lockout_minutes')
                            ->label('Durasi Lockout Login')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(1440)
                            ->suffix('menit')
                            ->helperText('Lama akun dikunci setelah melebihi maks percobaan login.')
                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Durasi kunci akun setelah melebihi batas percobaan login. Selama periode ini, user tidak bisa login meskipun password benar. Default: 15 menit.')
                            ->hintColor('info')
                            ->columnSpan(1),

                        TextInput::make('session_timeout_minutes')
                            ->label('Timeout Sesi Idle Peserta')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(480)
                            ->suffix('menit')
                            ->helperText('Sesi otomatis berakhir jika tidak ada aktivitas. Isi 0 untuk menonaktifkan.')
                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Peserta yang tidak melakukan aktivitas apapun selama N menit akan otomatis dikeluarkan (logout). Berguna untuk lab komputer bersama. Isi 0 untuk menonaktifkan fitur ini.')
                            ->hintColor('info')
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
                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Setiap kali peserta berpindah ke tab/jendela lain, minimize browser, atau kehilangan fokus, dihitung sebagai 1 kejadian. Setelah mencapai batas ini, aksi yang ditentukan di bawah dijalankan.')
                            ->hintColor('info')
                            ->columnSpan(1),

                        Select::make('tab_switch_action')
                            ->label('Aksi Saat Batas Tercapai')
                            ->options([
                                'log'    => 'Catat saja (log)',
                                'warn'   => 'Tampilkan peringatan',
                                'submit' => 'Kumpulkan ujian otomatis',
                            ])
                            ->helperText('Tindakan yang diambil saat peserta mencapai batas tab switch.')
                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'log = hanya dicatat di audit log tanpa notifikasi ke peserta; warn = peserta melihat popup peringatan; submit = ujian langsung dikumpulkan tanpa konfirmasi. Semua kejadian selalu dicatat ke audit log.')
                            ->hintColor('info')
                            ->columnSpan(1),

                        Toggle::make('auto_submit_on_max_tab')
                            ->label('Auto-Submit Saat Batas Tercapai')
                            ->helperText('Langsung kumpulkan ujian otomatis saat batas tab switch terlampaui, tanpa menunggu aksi lain.')
                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Jika aktif, ujian langsung dikumpulkan saat batas tercapai terlepas dari pilihan aksi di atas. Jika nonaktif, hanya aksi yang dipilih (warn/log) yang dijalankan. Pastikan konsisten dengan pilihan Aksi di atas.')
                            ->hintColor('info')
                            ->columnSpan('full'),

                        Toggle::make('prevent_copy_paste')
                            ->label('Nonaktifkan Copy-Paste')
                            ->helperText('Menonaktifkan copy, paste, dan cut di seluruh area soal via JavaScript.')
                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Menonaktifkan Ctrl+C, Ctrl+V, Ctrl+X, dan menu salin/tempel via klik kanan di halaman ujian. Catatan: tidak dapat mencegah screenshot, foto layar, atau penggunaan perangkat lain.')
                            ->hintColor('info')
                            ->columnSpan(1),

                        Toggle::make('prevent_right_click')
                            ->label('Nonaktifkan Klik Kanan')
                            ->helperText('Menonaktifkan klik kanan dan seleksi teks di area soal.')
                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Menonaktifkan menu konteks klik kanan di seluruh area soal. Mengurangi kemudahan menyalin teks soal secara langsung. Dapat dikombinasikan dengan Nonaktifkan Copy-Paste.')
                            ->hintColor('info')
                            ->columnSpan(1),

                        Toggle::make('require_fullscreen')
                            ->label('Wajib Fullscreen')
                            ->helperText('Peserta diwajibkan berada di mode fullscreen. Keluar dari fullscreen dicatat sebagai tab switch event.')
                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Saat aktif, peserta diminta masuk mode fullscreen saat memulai ujian. Jika keluar dari fullscreen (tekan Escape/F11/klik di luar), event dicatat sebagai tab switch. Efektivitas tergantung browser dan perangkat.')
                            ->hintColor('info')
                            ->columnSpan(1),

                        TextInput::make('max_upload_mb')
                            ->label('Batas Upload File URAIAN')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(50)
                            ->suffix('MB')
                            ->helperText('Ukuran maksimal file jawaban URAIAN yang dapat diunggah peserta.')
                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Batas ukuran file jawaban URAIAN (PDF, JPG, PNG) yang diunggah peserta saat ujian. Pastikan nilai ini tidak melebihi nilai upload_max_filesize dan post_max_size di php.ini server.')
                            ->hintColor('info')
                            ->columnSpan(1),

                        TextInput::make('max_audio_mb')
                            ->label('Batas Ukuran File Audio')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(100)
                            ->default(20)
                            ->suffix('MB')
                            ->helperText('Ukuran maksimal file audio soal yang dapat diunggah guru.')
                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Batas ukuran file audio (MP3, WAV, OGG, M4A) untuk soal tipe Listening/Audio yang diunggah guru di Bank Soal. Pastikan sesuai dengan upload_max_filesize di php.ini server.')
                            ->hintColor('info')
                            ->columnSpan(1),

                        Textarea::make('ip_whitelist')
                            ->label('IP Whitelist Ujian')
                            ->rows(3)
                            ->helperText('Daftar IP yang diizinkan mengakses halaman ujian, pisahkan dengan koma. Kosongkan untuk mengizinkan semua IP.')
                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Jika diisi, hanya perangkat dengan IP yang tercantum yang bisa mengakses halaman ujian. Cocok untuk lab komputer dengan IP tetap. Mendukung CIDR (mis: 192.168.1.0/24). Kosongkan untuk mengizinkan dari semua IP.')
                            ->hintColor('info')
                            ->placeholder('192.168.1.1, 192.168.1.2')
                            ->columnSpan('full'),
                    ]),

                Section::make('Penilaian & Tampilan Hasil')
                    ->icon('heroicon-o-calculator')
                    ->columns(2)
                    ->schema([
                        Toggle::make('realtime_grading')
                            ->label('Grading Real-time (Default Paket Baru)')
                            ->helperText('Jika aktif, nilai dihitung otomatis saat peserta selesai. Jika nonaktif, perlu trigger manual di halaman Grading.')
                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Menentukan nilai default grading_mode saat membuat paket ujian baru. Jika aktif, default ke Realtime (nilai langsung dihitung); jika nonaktif, default ke Manual (perlu trigger grading). Setiap paket tetap bisa diubah secara individual.')
                            ->hintColor('info')
                            ->columnSpan(1),

                        Toggle::make('show_ranking_hasil')
                            ->label('Tampilkan Ranking di Hasil')
                            ->helperText('Tampilkan posisi ranking peserta di antara peserta satu sesi di halaman hasil ujian.')
                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Setelah peserta selesai ujian, halaman hasil akan menampilkan posisi ranking mereka dibandingkan peserta lain di sesi yang sama. Nonaktifkan jika tidak ingin peserta saling membandingkan nilai.')
                            ->hintColor('info')
                            ->columnSpan(1),

                        Toggle::make('show_pembahasan_setelah_sesi')
                            ->label('Pembahasan Hanya Setelah Sesi Selesai')
                            ->helperText('Jika aktif, peserta hanya dapat melihat pembahasan jawaban setelah status sesi berubah menjadi Selesai.')
                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Jika aktif, peserta TIDAK bisa melihat pembahasan/kunci jawaban selama sesi masih berstatus Aktif. Pembahasan baru tersedia setelah admin mengubah status sesi ke Selesai. Berguna untuk ujian paralel agar soal tidak bocor ke peserta lain.')
                            ->hintColor('info')
                            ->columnSpan('full'),
                    ]),

                Section::make('Livescore')
                    ->icon('heroicon-o-chart-bar')
                    ->columns(2)
                    ->schema([
                        Toggle::make('show_livescore')
                            ->label('Aktifkan Fitur Livescore')
                            ->helperText('Aktifkan untuk menampilkan tombol livescore dan mengizinkan akses ke halaman livescore.')
                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Mengaktifkan/menonaktifkan fitur livescore secara penuh. Jika nonaktif: tombol livescore disembunyikan di halaman Monitor Sesi DAN URL langsung /sesi/{id}/livescore akan mengembalikan 404.')
                            ->hintColor('info')
                            ->columnSpan(1),

                        Toggle::make('livescore_public')
                            ->label('Livescore Dapat Diakses Publik')
                            ->helperText('Jika nonaktif, livescore hanya dapat diakses oleh pengguna yang sudah login.')
                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Jika aktif, siapapun (tanpa login) bisa membuka URL livescore langsung — cocok untuk ditampilkan di layar monitor kelas. Jika nonaktif, harus login terlebih dahulu. Hanya berlaku jika fitur Livescore aktif.')
                            ->hintColor('info')
                            ->columnSpan(1),
                    ]),

                Section::make('Notifikasi Email')
                    ->icon('heroicon-o-envelope')
                    ->columns(2)
                    ->schema([
                        Toggle::make('email_notifikasi_sesi')
                            ->label('Kirim Email Saat Peserta Ditetapkan ke Sesi')
                            ->helperText('Peserta akan mendapat email berisi info sesi ujian saat di-assign. Membutuhkan konfigurasi MAIL_* di .env.')
                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Saat peserta ditambahkan ke sesi ujian, sistem otomatis mengirim email berisi nama paket, jadwal, token (jika ada), dan instruksi masuk. Membutuhkan konfigurasi MAIL_HOST, MAIL_USERNAME, MAIL_PASSWORD, dan MAIL_FROM_ADDRESS di file .env.')
                            ->hintColor('info')
                            ->columnSpan(1),

                        Toggle::make('email_reminder_h1')
                            ->label('Kirim Reminder H-1 Ujian')
                            ->helperText('Jalankan scheduler (exam:reminder) setiap hari pukul 07.00.')
                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Sistem mengirim email pengingat kepada peserta sehari sebelum tanggal ujian dimulai. Membutuhkan Laravel Scheduler aktif: tambahkan cronjob "* * * * * php /path/to/artisan schedule:run" di server atau di Railway/Render gunakan cron job.')
                            ->hintColor('info')
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
        AppSetting::set('max_audio_mb',                 $state['max_audio_mb'],                   'int');
        AppSetting::set('ip_whitelist',                 $state['ip_whitelist'] ?? '',             'string');

        // Penilaian & Tampilan
        AppSetting::set('realtime_grading',             $state['realtime_grading'],               'bool');
        AppSetting::set('show_ranking_hasil',           $state['show_ranking_hasil'],             'bool');
        AppSetting::set('show_pembahasan_setelah_sesi', $state['show_pembahasan_setelah_sesi'],   'bool');

        // Livescore
        AppSetting::set('show_livescore',               $state['show_livescore'],                 'bool');
        AppSetting::set('livescore_public',             $state['livescore_public'],               'bool');

        // Notifikasi Email
        AppSetting::set('email_notifikasi_sesi',        $state['email_notifikasi_sesi'],          'bool');
        AppSetting::set('email_reminder_h1',            $state['email_reminder_h1'],              'bool');

        AuditLogService::log('update_pengaturan', null, 'Pengaturan umum diperbarui');

        Notification::make()
            ->success()
            ->title('Pengaturan berhasil disimpan')
            ->send();
    }
}
