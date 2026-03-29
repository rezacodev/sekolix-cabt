# Sekolix CABT — Computer Assisted Based Test

![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?logo=laravel&logoColor=white)
![Filament](https://img.shields.io/badge/Filament-v3-FDB44B?logo=laravel&logoColor=white)
![Tests](https://img.shields.io/badge/tests-275%20passed-brightgreen)
![License](https://img.shields.io/badge/license-MIT-blue)

Sistem **Computer Assisted Based Test (CAT)** berbasis web untuk keperluan ujian sekolah. Mendukung 6 tipe soal, pengacakan soal & opsi, timer server-authoritative, anti-kecurangan multi-lapis, manual grading, laporan lengkap, dan livescore real-time.

---

## Daftar Isi

1. [Tentang Proyek](#1-tentang-proyek)
2. [Teknologi](#2-teknologi)
3. [Persyaratan Sistem](#3-persyaratan-sistem)
4. [Instalasi](#4-instalasi)
5. [Konfigurasi](#5-konfigurasi)
6. [Level Pengguna](#6-level-pengguna)
7. [Tipe Soal](#7-tipe-soal)
8. [Fitur Lengkap](#8-fitur-lengkap)
   - [8.1 Autentikasi & Keamanan Sesi](#81-autentikasi--keamanan-sesi)
   - [8.2 Manajemen User](#82-manajemen-user)
   - [8.3 Rombongan Belajar (Rombel)](#83-rombongan-belajar-rombel)
   - [8.4 Bank Soal](#84-bank-soal)
   - [8.5 Paket Ujian](#85-paket-ujian)
   - [8.6 Sesi Ujian](#86-sesi-ujian)
   - [8.7 Pengerjaan Ujian (Core)](#87-pengerjaan-ujian-core)
   - [8.8 Anti-Kecurangan](#88-anti-kecurangan)
   - [8.9 Manual Grading URAIAN](#89-manual-grading-uraian)
   - [8.10 Hasil & Review Jawaban](#810-hasil--review-jawaban)
   - [8.11 Livescore Real-time](#811-livescore-real-time)
   - [8.12 Monitor Real-time](#812-monitor-real-time)
   - [8.13 Dashboard Guru](#813-dashboard-guru)
   - [8.14 Laporan & Cetak](#814-laporan--cetak)
   - [8.15 General Setting](#815-general-setting)
9. [Kalkulasi Nilai](#9-kalkulasi-nilai)
10. [Struktur Database](#10-struktur-database)
11. [Struktur Direktori](#11-struktur-direktori)
12. [API & Endpoint Penting](#12-api--endpoint-penting)
13. [Menjalankan Test](#13-menjalankan-test)
14. [Deployment](#14-deployment)
15. [Lisensi](#15-lisensi)

---

## 1. Tentang Proyek

Sekolix CABT adalah aplikasi ujian berbasis komputer (Computer Assisted Test) yang dirancang untuk lingkungan sekolah. Sistem ini memisahkan antarmuka **admin/guru** (dibangun dengan Filament v3) dan antarmuka **peserta** (custom Blade + Alpine.js) untuk memberikan kontrol penuh atas pengalaman ujian.

**Kemampuan utama:**
- Mendukung 6 tipe soal termasuk URAIAN dengan upload file
- Timer ujian sepenuhnya dikendalikan server (anti-manipulasi)
- Peserta dapat melanjutkan ujian dari komputer berbeda tanpa kehilangan jawaban
- Anti-kecurangan multi-lapis (server-side + client-side + jaringan)
- Semua parameter anti-kecurangan dan tampilan dapat dikonfigurasi via panel admin tanpa perlu deploy ulang

---

## 2. Teknologi

| Lapisan | Teknologi |
|---|---|
| **Backend** | Laravel 12, PHP 8.2+ |
| **Admin Panel** | Filament v3 |
| **Frontend Peserta** | Blade, Alpine.js 3, Axios 1.x |
| **Styling** | Tailwind CSS 4, `@tailwindcss/forms` |
| **Build Tool** | Vite |
| **Database** | MySQL 8+ / MariaDB |
| **Auth** | Laravel Breeze (peserta, session-based) + Filament Auth (admin) |
| **Import/Export** | `maatwebsite/excel` ^3.1, Filament Export Action |
| **Rich Text** | Filament RichEditor (Trix) — admin; Quill.js 2 (CDN) — display soal peserta |
| **Render Matematika** | MathJax 3 (CDN) |
| **Testing** | PHPUnit 11.5.55 |

**Dependencies Composer (production):**
```
laravel/framework ^12.0
filament/filament ^3.3
maatwebsite/excel ^3.1
intervention/image ^3.10
spatie/laravel-permission ^6.10
spatie/laravel-query-builder ^6.3
```

**Dependencies NPM:**
```
alpinejs ^3.14
axios ^1.11
tailwindcss ^4.0
@tailwindcss/forms ^0.5
@tailwindcss/vite ^4.0
chart.js ^4.4
```

---

## 3. Persyaratan Sistem

| Komponen | Versi Minimum |
|---|---|
| PHP | 8.2 |
| Composer | 2.x |
| Node.js | 18.x |
| MySQL | 8.0 / MariaDB 10.6 |
| Ekstensi PHP | pdo_mysql, mbstring, openssl, tokenizer, xml, ctype, json, bcmath, gd, zip |

---

## 4. Instalasi

```bash
# 1. Clone repositori
git clone <url-repo> sekolix-cabt
cd sekolix-cabt

# 2. Install dependensi PHP
composer install

# 3. Install dependensi Node.js
npm install

# 4. Salin file environment
cp .env.example .env

# 5. Generate application key
php artisan key:generate

# 6. Konfigurasi database di .env (lihat Bagian 5)

# 7. Jalankan migrasi & seeder
php artisan migrate --seed

# 8. Buat symlink storage
php artisan storage:link

# 9. Build aset frontend
npm run build

# 10. Jalankan server lokal
php artisan serve
```

Akses aplikasi di `http://localhost:8000`.

---

## 5. Konfigurasi

### File `.env` — Parameter Penting

```env
APP_NAME="Sekolix CABT"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sekolix_cabt
DB_USERNAME=root
DB_PASSWORD=

# Storage untuk upload URAIAN (private disk)
FILESYSTEM_DISK=local

# Session
SESSION_DRIVER=database
SESSION_LIFETIME=120

# Queue (opsional, untuk proses regrade besar)
QUEUE_CONNECTION=sync
```

### General Setting (via Panel Admin)

Setelah login sebagai Super Admin, navigasi ke **Pengaturan** di panel `/cabt`. Semua konfigurasi berikut dapat diubah tanpa deploy ulang:

| Kelompok | Parameter |
|---|---|
| **Identitas Aplikasi** | Nama aplikasi, logo, footer |
| **Autentikasi** | `login_max_attempts`, `login_lockout_minutes`, `allow_multi_login` |
| **Anti-Kecurangan** | `max_tab_switch`, `tab_switch_action`, `prevent_copy_paste`, `prevent_right_click`, `require_fullscreen`, `auto_submit_on_max_tab` |
| **Upload** | `max_upload_mb` (default 5) |
| **Session Ujian** | `session_timeout_minutes` |
| **Jaringan** | `ip_whitelist` (opsional, pisah koma) |
| **Tampilan Hasil** | `tampilkan_nilai`, `tampilkan_ranking`, `allow_review` |
| **Livescore** | `livescore_public` (akses tanpa login) |

---

## 6. Level Pengguna

| Level | Peran | Panel Admin | Keterangan |
|---|---|---|---|
| **1** | Peserta | ❌ | Hanya dapat mengakses antarmuka ujian di `/peserta` |
| **2** | Guru | ✅ (terbatas) | Kelola soal & paket milik sendiri, lihat laporan rombel diampu, monitor sesi |
| **3** | Admin | ✅ | Kelola semua user, rombel, sesi ujian, laporan |
| **4** | Super Admin | ✅ (penuh) | Semua akses admin + General Setting |

---

## 7. Tipe Soal

| Kode | Nama | Deskripsi |
|---|---|---|
| `PG` | Pilihan Ganda | Satu opsi benar; bobot penuh jika benar |
| `PG_BOBOT` | PG Berbobot | Tiap opsi memiliki persentase bobot; nilai proporsional |
| `PGJ` | PG Jawaban Majemuk | Lebih dari satu opsi benar; nilai proporsional |
| `JODOH` | Menjodohkan | Pasangkan kolom kiri–kanan; nilai proporsional per pasangan |
| `ISIAN` | Isian Singkat | Keyword matching case-insensitive |
| `URAIAN` | Uraian / Essay | Input teks bebas + upload file; penilaian manual oleh guru |

---

## 8. Fitur Lengkap

### 8.1 Autentikasi & Keamanan Sesi

- Login via username atau email dengan password
- Redirect otomatis berdasarkan level setelah login
- Rate limiting login: maksimum 5 percobaan per 15 menit (konfigurabel)
- Satu sesi aktif per peserta (`CheckSingleSession` middleware) — dapat dinonaktifkan via setting `allow_multi_login`
- Logout membersihkan semua data sesi
- Token akses unik per sesi ujian (case-insensitive); wajib dimasukkan peserta sebelum mulai

### 8.2 Manajemen User

- CRUD user dengan level 1–4
- Import massal dari Excel (nama, username, password, level, rombel)
- Export data user ke Excel/PDF
- Reset password bulk atau individual
- Assign peserta ke rombel (many-to-many)
- Paksa logout sesi peserta (admin/guru dapat invalidate sesi aktif peserta)

### 8.3 Rombongan Belajar (Rombel)

- CRUD rombel (nama kelas, tahun ajaran, dll.)
- Assign guru wali (many-to-many, satu rombel bisa punya beberapa guru)
- Assign peserta ke rombel
- Rombel digunakan sebagai filter pada laporan dan sesi ujian

### 8.4 Bank Soal

- Buat soal 6 tipe dengan antarmuka berbeda per tipe (Filament dynamic form)
- Editor soal kaya (Filament RichEditor / Trix) — mendukung bold, italic, tabel, gambar
- Render matematika via MathJax (LaTeX inline `\[...\]`)
- Setiap soal memiliki: kategori, tingkat kesulitan, bobot, pembahasan, `lock_position`
- `lock_position = true` → soal tidak ikut diacak (posisi tetap pada urutan asli)
- Import soal massal dari Excel dengan template
- Soft-delete soal (soal terhapus tidak merusak data attempt lama)
- Cari soal via endpoint `/admin/soal/search` (digunakan Filament form di paket)

### 8.5 Paket Ujian

- CRUD paket ujian: nama, deskripsi, durasi (menit), waktu minimal submit
- Konfigurasi: acak urutan soal, acak opsi jawaban (PG/PGJ)
- Batas pengulangan (`max_pengulangan`; `0` = tidak terbatas)
- Mode penilaian: `otomatis` (langsung) atau `manual` (nilai null sampai admin trigger regrade)
- Tampilkan hasil dan/atau review setelah sesi selesai (konfigurabel per paket)
- **Soft-lock paket:** jika paket digunakan di sesi aktif/selesai, soal tidak bisa ditambah/dihapus; metadata tetap bisa diedit

### 8.6 Sesi Ujian

- Buat sesi dengan memilih paket ujian
- Status: `draft` → `aktif` → `selesai`
- Token akses unik per sesi
- Assign peserta per rombel atau individual
- Buka/tutup manual (admin/guru) di luar alur status utama
- Admin dapat membuka ulang sesi selesai untuk peserta yang belum mengerjakan

### 8.7 Pengerjaan Ujian (Core)

- Peserta masuk konfirmasi → input token → mulai
- **Timer server-authoritative:** `sisa_waktu = (waktu_mulai + durasi×60) − now()`, disinkronkan ke server setiap 60 detik
- Warning modal saat ≤ 300 detik dan ≤ 60 detik tersisa
- Auto-submit saat timer habis
- Navigasi soal bebas (non-linear)
- Tandai soal sebagai "ragu-ragu"
- Palet soal berwarna: belum dijawab (abu), dijawab (hijau), ragu-ragu (kuning)
- **Auto-save jawaban:** setiap perubahan dikirim via AJAX (debounce 300ms, retry 3× dengan delay 1–3 detik), toast notifikasi "Tersimpan ✓" / "Gagal menyimpan"
- Upload file untuk soal URAIAN (max 5 MB, MIME: JPEG/PNG/PDF)
- **Resume otomatis:** peserta bisa pindah komputer/browser; saat buka halaman ujian kembali, sistem otomatis redirect ke attempt yang masih berlangsung dengan sisa waktu yang benar
- Tombol Submit disabled selama masa waktu minimal belum tercapai

### 8.8 Anti-Kecurangan

**Server-Side (prioritas tinggi):**

| Mekanisme | Detail |
|---|---|
| Tab switch counter | `exam_attempts.tab_switch_count` di-increment server-side via `POST /ujian/{id}/log` |
| Aksi tab switch | Setting `tab_switch_action`: `log` / `lock` (peserta terkunci) / `submit` (auto-submit) |
| IDOR prevention | Semua endpoint attempt memvalidasi `attempt->user_id === Auth::id()` |
| File URAIAN private | Simpan di `storage/app/private`; akses via signed URL (`temporaryUrl`, valid 10 menit) |
| Waktu dari server | Timer tidak bisa dimanipulasi client |

**Client-Side:**

| Mekanisme | Aktif Jika |
|---|---|
| Disable copy-paste | `prevent_copy_paste = true` |
| Disable klik kanan | `prevent_right_click = true` |
| Fullscreen enforcement | `require_fullscreen = true` — keluar fullscreen = tab switch event |
| Scramble ID soal | Question ID di-encrypt AES-256 di HTML; server decrypt sebelum proses |
| Watermark peserta | Nama + nomor peserta overlay transparan di area soal |
| DevTools detection | Deteksi via selisih `outerHeight - innerHeight > 160` |
| Anti-print CSS | `@media print { visibility: hidden }` selalu aktif di halaman ujian |
| Beacon API | Log tab switch menggunakan `navigator.sendBeacon()` agar request terkirim meski tab berpindah |

**Jaringan:**

| Mekanisme | Detail |
|---|---|
| IP Whitelist | Middleware `CheckIpWhitelist`; daftar IP pisah koma via setting `ip_whitelist` |
| Rate limiting | Login: `throttle:5,15`; AJAX jawab: `throttle:120,1`; log: `throttle:60,1` |
| CSRF | Token di semua form & header AJAX |

### 8.9 Manual Grading URAIAN

- Antrian soal URAIAN yang belum dinilai (filter per sesi/paket)
- Input nilai per soal per peserta (0 – bobot soal)
- Setelah semua soal URAIAN dinilai, trigger `ScoringService::regrade()` untuk hitung ulang nilai akhir
- Admin juga dapat trigger regrade manual kapan saja (berguna setelah edit kunci jawaban)

### 8.10 Hasil & Review Jawaban

**Halaman Hasil (`/ujian/{id}/hasil`):**
- Nilai akhir (skala 0–100), jumlah benar/salah/kosong, durasi pengerjaan
- Ranking peserta dalam sesi (opsional, konfigurabel)
- Jika `grading_mode = manual`, nilai akhir ditampilkan setelah regrade selesai

**Halaman Review (`/ujian/{id}/review`):**
- Tampilkan semua soal + jawaban peserta + kunci jawaban + pembahasan
- Dapat dibatasi: hanya muncul setelah sesi ditutup (konfigurabel per paket)

### 8.11 Livescore Real-time

- Leaderboard peserta berdasarkan nilai sementara (auto-graded) atau nilai akhir
- Polling otomatis setiap 15 detik via Alpine.js
- Animasi transisi perubahan posisi ranking
- Mode publik (tanpa login) atau hanya untuk yang login — konfigurabel via `livescore_public`
- URL: `/sesi/{session}/livescore`

### 8.12 Monitor Real-time

- Admin/Guru dapat memantau semua peserta aktif dalam satu sesi
- Data per peserta: soal terjawab, sisa waktu (detik), jumlah tab switch, status
- Polling otomatis setiap 10 detik
- Tombol "Paksa Keluar" per peserta (dengan konfirmasi) — invalidate attempt, redirect peserta ke dashboard
- URL data: `/cabt/sesi/{session}/monitor/data`

### 8.13 Dashboard Guru

- Rekap rombel yang diampu: jumlah peserta, nilai rata-rata per sesi
- Tabel nilai peserta per sesi dengan filter rombel
- Export rekap nilai per rombel ke Excel (`.xlsx`)
- Akses terbatas: guru hanya melihat rombel yang ditugaskan kepadanya

### 8.14 Laporan & Cetak

| Laporan | Format |
|---|---|
| Rekap nilai semua peserta | Excel, PDF |
| Rekap kehadiran (hadir/tidak hadir) | Excel, PDF |
| Statistik soal (distribusi pilihan opsi) | Excel |
| Berita acara ujian | Cetak (print-friendly) |
| Daftar hadir | Cetak |
| Kartu peserta | Cetak |

URL cetak:
```
/cabt/laporan/sesi/{session}/cetak/nilai
/cabt/laporan/sesi/{session}/cetak/daftar-hadir
/cabt/laporan/sesi/{session}/cetak/berita-acara
/cabt/dashboard-guru/{session}/{rombel}/export
```

### 8.15 General Setting

Semua pengaturan disimpan di tabel `app_settings` (key-value). Dapat dikelola via Filament Panel tanpa perlu menyentuh kode.

Lebih dari 20 parameter tersedia, mencakup:
- Identitas aplikasi (nama, logo, tagline)
- Konfigurasi autentikasi (rate limit, multi-login)
- Parameter anti-kecurangan (semua mekanisme di atas)
- Pengaturan upload (batas ukuran file)
- Kontrol tampilan hasil & review
- Visibilitas livescore

---

## 9. Kalkulasi Nilai

### Formula Umum

```
nilai_akhir = (jumlah nilai_perolehan_per_soal / jumlah bobot_max_per_soal) × 100
```

### Nilai Per Tipe Soal

| Tipe | Formula Nilai |
|---|---|
| `PG` | Benar → bobot soal; Salah/Kosong → 0 |
| `PG_BOBOT` | `bobot_soal × (bobot_persen_opsi / 100)` |
| `PGJ` | `bobot_soal × (jumlah_benar_dipilih / total_kunci_benar)`, minimal 0 |
| `JODOH` | `bobot_soal × (pasangan_benar / total_pasangan)` |
| `ISIAN` | Keyword match case-insensitive → bobot soal; lainnya → 0 |
| `URAIAN` | Input manual guru (0 – bobot_soal) |

### Regrade

`ScoringService::regrade($attemptId)` menghitung ulang semua `nilai_perolehan` dan memperbarui `nilai_akhir` di `exam_attempts`. Dipanggil otomatis setelah manual grading URAIAN selesai, atau secara manual oleh admin.

---

## 10. Struktur Database

| Tabel | Deskripsi |
|---|---|
| `users` | Data user semua level; kolom `level` (1–4), `nomor_peserta`, `status_sesi` |
| `rombels` | Data rombongan belajar |
| `rombel_guru` | Pivot guru ↔ rombel (many-to-many) |
| `categories` | Kategori soal hierarkis (`parent_id`) |
| `questions` | Soal semua tipe; kolom `tipe`, `konten`, `bobot`, `lock_position`, `pembahasan` |
| `question_options` | Opsi jawaban PG/PG_BOBOT/PGJ per soal |
| `question_matches` | Pasangan menjodohkan (kiri ↔ kanan) |
| `question_keywords` | Keyword jawaban soal ISIAN |
| `exam_packages` | Paket ujian; kolom `durasi_menit`, `max_pengulangan`, `grading_mode`, dsb. |
| `exam_package_questions` | Pivot paket ↔ soal dengan urutan |
| `exam_sessions` | Sesi ujian; kolom `status`, `token_akses`, `paket_id` |
| `exam_session_participants` | Peserta yang ditugaskan ke sesi |
| `exam_attempts` | Riwayat pengerjaan per peserta per sesi; kolom `waktu_mulai`, `nilai_akhir`, `tab_switch_count`, `status` |
| `attempt_questions` | Soal yang diacak per attempt + `jawaban_peserta`, `nilai_perolehan` |
| `attempt_logs` | Log event anti-kecurangan (tab switch, fullscreen exit, dsb.) |
| `app_settings` | Key-value store konfigurasi aplikasi |

---

## 11. Struktur Direktori

```
app/
├── Filament/Resources/        ← Panel admin (Filament v3)
│   ├── UserResource.php
│   ├── SoalResource.php
│   ├── KategoriResource.php
│   ├── PaketResource.php
│   ├── SesiResource.php
│   ├── GradingResource.php
│   ├── LaporanResource.php
│   └── SettingResource.php
├── Http/
│   ├── Controllers/Peserta/
│   │   ├── DashboardController.php
│   │   ├── UjianController.php
│   │   └── LivescoreController.php
│   ├── Middleware/
│   │   ├── CheckLevel.php          ← Cek level minimum per route
│   │   ├── CheckSingleSession.php  ← Satu sesi aktif per peserta
│   │   └── CheckIpWhitelist.php
│   └── Requests/                   ← Form Request Validation
├── Models/                         ← 14 Eloquent models
└── Services/
    ├── ExamService.php             ← mulai, submit, validasi sesi
    ├── ScoringService.php          ← kalkulasi nilai + regrade
    ├── ShuffleService.php          ← pengacakan soal & opsi (lock_position)
    ├── ImportService.php           ← import soal & user dari Excel
    └── ReportService.php           ← generate laporan & export

resources/views/
├── layouts/
│   ├── peserta.blade.php
│   └── ujian.blade.php             ← Layout full-screen tanpa navbar
└── peserta/
    ├── dashboard.blade.php
    ├── konfirmasi.blade.php
    ├── ujian.blade.php
    ├── hasil.blade.php
    ├── review.blade.php
    └── livescore.blade.php

tests/
├── Feature/
│   ├── Auth/                       ← Test autentikasi & rate limiting
│   ├── Admin/                      ← Test fitur panel admin
│   ├── Exam/                       ← Test alur pengerjaan ujian (core)
│   ├── Peserta/                    ← Test dashboard, livescore
│   └── Security/                   ← Test access control, anti-kecurangan
└── Unit/
```

---

## 12. API & Endpoint Penting

### Peserta

| Method | URL | Deskripsi |
|---|---|---|
| `GET` | `/peserta` | Dashboard peserta |
| `GET` | `/ujian/{sesiId}` | Konfirmasi & input token |
| `POST` | `/ujian/{sesiId}/mulai` | Mulai / lanjutkan attempt |
| `GET` | `/ujian/{sesiId}/kerjakan` | Halaman pengerjaan ujian |
| `POST` | `/ujian/jawab` | Simpan jawaban (AJAX, throttle 120/min) |
| `POST` | `/ujian/{id}/log` | Log event anti-kecurangan (AJAX, throttle 60/min) |
| `GET` | `/ujian/{id}/status` | Sync sisa waktu dari server (AJAX) |
| `POST` | `/ujian/{id}/submit` | Submit ujian |
| `GET` | `/ujian/{id}/hasil` | Halaman hasil |
| `GET` | `/ujian/{id}/review` | Review jawaban |
| `GET` | `/file/uraian/{id}/{filename}` | Serve file URAIAN (signed URL, private) |

### Livescore

| Method | URL | Deskripsi |
|---|---|---|
| `GET` | `/sesi/{session}/livescore` | Halaman livescore |
| `GET` | `/sesi/{session}/livescore/data` | Data JSON livescore (polling) |

### Admin (Filament `/cabt`)

| Method | URL | Deskripsi |
|---|---|---|
| `GET` | `/cabt/sesi/{session}/monitor/data` | Data JSON peserta aktif (polling) |
| `POST` | `/cabt/sesi/{session}/paksa-keluar/{userId}` | Paksa keluar peserta |
| `GET` | `/cabt/laporan/sesi/{session}/cetak/nilai` | Cetak rekap nilai |
| `GET` | `/cabt/laporan/sesi/{session}/cetak/daftar-hadir` | Cetak daftar hadir |
| `GET` | `/cabt/laporan/sesi/{session}/cetak/berita-acara` | Cetak berita acara |
| `GET` | `/cabt/dashboard-guru/{session}/{rombel}/export` | Export Excel guru |
| `GET` | `/admin/soal/search` | Pencarian soal (autocomplete Filament) |

### Contoh Response Monitor

```json
{
  "success": true,
  "data": {
    "total": 30,
    "sedang": 22,
    "selesai": 5,
    "belum": 3,
    "list": [
      {
        "id": 1,
        "nama": "Budi Santoso",
        "nomor_peserta": "001",
        "soal_terjawab": 20,
        "total_soal": 50,
        "sisa_waktu_detik": 1200,
        "status": "sedang",
        "tab_switch_count": 1
      }
    ]
  }
}
```

---

## 13. Menjalankan Test

Test menggunakan SQLite `:memory:` sehingga tidak mempengaruhi database utama.

```bash
# Jalankan semua test
php artisan test

# Jalankan dengan output verbose
php artisan test --verbose

# Jalankan test spesifik
php artisan test tests/Feature/Exam/FullExamFlowTest.php

# Jalankan dengan coverage (butuh Xdebug atau PCOV)
php artisan test --coverage
```

**Status test saat ini: 275 tests, 566 assertions — semua hijau ✅**

Cakupan test meliputi:
- Autentikasi & rate limiting
- Access control per level (peserta/guru/admin/super admin)
- Alur lengkap ujian (mulai → jawab → submit → hasil → review)
- Anti-kecurangan (tab switch, IP whitelist, path traversal upload)
- Import soal & user dari Excel
- Kalkulasi nilai semua tipe soal
- Livescore & monitor real-time
- General Setting
- Laporan & export

---

## 14. Deployment

### Optimasi Production

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
npm run build
```

### Permission Storage

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### Contoh Konfigurasi Nginx

```nginx
server {
    listen 80;
    server_name cabt.example.com;
    root /var/www/sekolix-cabt/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### Checklist Deployment

- [ ] `.env` production dikonfigurasi (`APP_ENV=production`, `APP_DEBUG=false`)
- [ ] `APP_KEY` sudah di-generate
- [ ] Database sudah dibuat dan `php artisan migrate --force` dijalankan
- [ ] `php artisan storage:link` dijalankan
- [ ] Direktori `storage/` writable oleh web server
- [ ] HTTPS dikonfigurasi (Let's Encrypt / SSL cert)
- [ ] Backup database dijadwalkan

---

## 15. Lisensi

Proyek ini dilisensikan di bawah [MIT License](https://opensource.org/licenses/MIT).
