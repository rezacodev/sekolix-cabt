# Sekolix CABT — Computer Assisted Based Test

![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?logo=laravel&logoColor=white)
![Filament](https://img.shields.io/badge/Filament-v3-FDB44B?logo=laravel&logoColor=white)
![Tests](https://img.shields.io/badge/tests-275%2B%20passed-brightgreen)
![License](https://img.shields.io/badge/license-MIT-blue)

Sistem **Computer Assisted Based Test (CAT)** berbasis web untuk keperluan ujian sekolah. Mendukung **8 tipe soal** (termasuk Benar/Salah dan Cloze), soal kelompok/stimulus, pengacakan soal & opsi, poin negatif, timer per soal, multi-section exam, anti-kecurangan multi-lapis, manual grading, laporan lengkap dengan PDF & grafik, livescore real-time, audit log, notifikasi email, pengumuman, dan portofolio peserta.

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
   - [8.6 Sesi Ujian & Kalender](#86-sesi-ujian--kalender)
   - [8.7 Pengerjaan Ujian (Core)](#87-pengerjaan-ujian-core)
   - [8.8 Anti-Kecurangan](#88-anti-kecurangan)
   - [8.9 Manual Grading URAIAN](#89-manual-grading-uraian)
   - [8.10 Hasil & Review Jawaban](#810-hasil--review-jawaban)
   - [8.11 Livescore Real-time](#811-livescore-real-time)
   - [8.12 Monitor Real-time & Catatan Pengawas](#812-monitor-real-time--catatan-pengawas)
   - [8.13 Dashboard Guru & Portofolio Peserta](#813-dashboard-guru--portofolio-peserta)
   - [8.14 Laporan & Cetak](#814-laporan--cetak)
   - [8.15 Kurikulum, Tag & Kisi-kisi](#815-kurikulum-tag--kisi-kisi)
   - [8.16 Multi-Section Exam](#816-multi-section-exam)
   - [8.17 Notifikasi Email & Pengumuman](#817-notifikasi-email--pengumuman)
   - [8.18 Audit Log](#818-audit-log)
   - [8.19 General Setting](#819-general-setting)
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
- Mendukung **8 tipe soal** termasuk Benar/Salah, Cloze, dan URAIAN dengan upload file
- **Soal kelompok/stimulus** — beberapa soal satu bacaan/gambar/audio/video; layout split-panel di halaman ujian
- **Soal audio** — upload file audio atau URL, batas jumlah play, auto-play
- Timer ujian sepenuhnya dikendalikan server (anti-manipulasi), tersedia juga timer per soal
- **Multi-section exam** — satu paket dibagi beberapa bagian dengan durasi masing-masing
- **Poin negatif** — pengurangan nilai untuk jawaban salah (opsional, dikonfigurasi per paket)
- Peserta dapat melanjutkan ujian dari komputer berbeda tanpa kehilangan jawaban
- Anti-kecurangan multi-lapis (server-side + client-side + jaringan)
- **Kisi-kisi (blueprint) ujian** — rancang distribusi soal per KD/tipe/kesulitan/Bloom
- **Kolaborasi bank soal** — soal dapat dibagi antar guru (private / internal / publik)
- **Audit log** — setiap aksi sensitif admin tercatat beserta IP dan user agent
- **PDF langsung** — export laporan nilai, daftar hadir, berita acara, dan kartu peserta (dengan QR code)
- Semua parameter anti-kecurangan dan tampilan dapat dikonfigurasi via panel admin tanpa deploy ulang

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
| **PDF** | `barryvdh/laravel-dompdf` |
| **QR Code** | `simplesoftwareio/simple-qrcode` v4.2 |
| **Rich Text** | Filament RichEditor (Trix) — admin; Quill.js 2 (CDN) — display soal peserta |
| **Render Matematika** | MathJax 3 (CDN) |
| **Grafik** | Chart.js 4 |
| **Kalender** | FullCalendar v6 (CDN) |
| **Testing** | PHPUnit 11.5.55 |

**Dependencies Composer (production):**
```
laravel/framework ^12.0
filament/filament ^3.3
maatwebsite/excel ^3.1
intervention/image ^3.10
spatie/laravel-permission ^6.10
spatie/laravel-query-builder ^6.3
barryvdh/laravel-dompdf
simplesoftwareio/simple-qrcode ^4.2
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

# Queue — gunakan database/redis untuk notifikasi email
QUEUE_CONNECTION=sync

# Email (untuk notifikasi sesi & reminder)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS="noreply@sekolah.sch.id"
MAIL_FROM_NAME="${APP_NAME}"
```

### General Setting (via Panel Admin)

Setelah login sebagai Super Admin, navigasi ke **Pengaturan** di panel `/cabt`. Semua konfigurasi berikut dapat diubah tanpa deploy ulang:

| Kelompok | Parameter |
|---|---|
| **Identitas Aplikasi** | `app_name`, `school_name`, `school_logo_url` |
| **Autentikasi** | `login_max_attempts`, `login_lockout_minutes`, `allow_multi_login`, `session_timeout_minutes` |
| **Anti-Kecurangan** | `max_tab_switch`, `tab_switch_action`, `prevent_copy_paste`, `prevent_right_click`, `require_fullscreen`, `auto_submit_on_max_tab` |
| **Upload** | `max_upload_mb` (default 5), `max_audio_mb` (default 20) |
| **Tampilan Hasil** | `tampilkan_nilai`, `tampilkan_ranking`, `allow_review`, `show_ranking_hasil`, `show_pembahasan_setelah_sesi` |
| **Livescore** | `livescore_public` (akses tanpa login) |
| **Notifikasi Email** | `email_notifikasi_sesi`, `email_reminder_h1` |
| **Jaringan** | `ip_whitelist` (opsional, pisah koma) |

---

## 6. Level Pengguna

| Level | Peran | Panel Admin | Keterangan |
|---|---|---|---|
| **1** | Peserta | --- | Hanya dapat mengakses antarmuka ujian di `/peserta` |
| **2** | Guru | terbatas | Kelola soal & paket milik sendiri + soal internal/publik; lihat laporan rombel diampu; monitor sesi |
| **3** | Admin | ya | Kelola semua user, rombel, sesi ujian, laporan |
| **4** | Super Admin | penuh | Semua akses admin + General Setting + Audit Log |

---

## 7. Tipe Soal

| Kode | Nama | Deskripsi |
|---|---|---|
| `PG` | Pilihan Ganda | Satu opsi benar; bobot penuh jika benar |
| `PG_BOBOT` | PG Berbobot | Tiap opsi memiliki persentase bobot; nilai proporsional |
| `PGJ` | PG Jawaban Majemuk | Lebih dari satu opsi benar; nilai proporsional |
| `BS` | Benar / Salah | Dua opsi tetap (B/S); tidak diacak; scoring identik PG |
| `JODOH` | Menjodohkan | Pasangkan kolom kiri-kanan; nilai proporsional per pasangan |
| `ISIAN` | Isian Singkat | Keyword matching case-insensitive |
| `CLOZE` | Cloze / Isian dalam Teks | Beberapa blank `[1][2]...` dalam teks; nilai proporsional per blank |
| `URAIAN` | Uraian / Essay | Input teks bebas + upload file; penilaian manual oleh guru |

Semua tipe soal mendukung: MathJax (LaTeX), gambar dalam soal (RichEditor), pembahasan, bloom level (C1-C6), standar KD/CP, dan tag.

---

## 8. Fitur Lengkap

### 8.1 Autentikasi & Keamanan Sesi

- Login via username atau email dengan password
- Redirect otomatis berdasarkan level setelah login
- Rate limiting login: maksimum N percobaan per T menit (konfigurabel via `app_settings`)
- Satu sesi aktif per peserta (`CheckSingleSession` middleware) — dapat dinonaktifkan via `allow_multi_login`
- Session timeout idle peserta (konfigurabel via `session_timeout_minutes`)
- Logout membersihkan semua data sesi
- Token akses unik per sesi ujian (case-insensitive); wajib dimasukkan peserta sebelum mulai

### 8.2 Manajemen User

- CRUD user dengan level 1-4
- Import massal dari Excel (nama, username, password, level, rombel)
- Export data user ke Excel
- Reset password bulk atau individual
- Assign peserta ke rombel
- Paksa logout sesi peserta (admin/guru dapat invalidate sesi aktif peserta)
- ToggleColumn aktif/nonaktif langsung dari tabel
- Bulk action: toggle aktif massal, reset password massal

### 8.3 Rombongan Belajar (Rombel)

- CRUD rombel (nama kelas, tahun ajaran, angkatan, dll.)
- Assign guru wali (many-to-many, satu rombel bisa punya beberapa guru)
- Assign peserta ke rombel
- Rombel digunakan sebagai filter pada laporan, sesi ujian, dan pengumuman

### 8.4 Bank Soal

- Buat soal 8 tipe dengan antarmuka berbeda per tipe (Filament dynamic form)
- Editor soal kaya (Filament RichEditor / Trix) — mendukung bold, italic, tabel, gambar
- Render matematika via MathJax (LaTeX inline `\[...\]`)
- Setiap soal memiliki: kategori, tingkat kesulitan, bobot, pembahasan, `lock_position`, bloom level (C1-C6), standar KD/CP, tag
- `lock_position = true` — soal tidak ikut diacak (posisi tetap pada urutan asli)

**Soal Audio:**
- Upload file audio (MP3/OGG/WAV, batas `max_audio_mb`) atau input URL eksternal
- Batas jumlah play (`audio_play_limit`; 0 = unlimited) — ditegakkan server-side
- Auto-play saat soal pertama kali tampil (`audio_auto_play`)
- Player Alpine.js dengan tracking `playCount`; player di-disable setelah batas tercapai

**Soal Kelompok / Stimulus:**
- Buat grup soal dengan stimulus: teks, gambar, audio, video, atau tabel
- Soal-soal dalam satu grup ditampilkan dengan layout split-panel (40% stimulus kiri, 60% soal kanan)
- Pengacakan tetap mempertahankan urutan soal dalam grup; grup diacak sebagai unit
- Mobile: tab toggle "Materi / Soal" dengan Alpine.js

**Kolaborasi Bank Soal:**
- Visibilitas soal: `private` (hanya pembuat), `internal` (dapat dilihat semua guru), `publik` (dapat dilihat semua)
- Guru hanya dapat edit/hapus soal milik sendiri; soal `internal`/`publik` milik guru lain hanya bisa dilihat dan dipakai
- Bulk action: "Jadikan Internal" dan "Publish ke Publik" (Admin)

**Import & Manajemen:**
- Import soal massal dari Excel dengan template (mendukung semua tipe + kolom `audio_url`)
- Soft-delete soal (soal terhapus tidak merusak data attempt lama)
- Filter soal: tipe, kategori, kesulitan, bloom level, standar KD/CP, tag, visibilitas
- Auto-suggest tag saat input (bisa buat tag baru)

### 8.5 Paket Ujian

- CRUD paket ujian: nama, deskripsi, durasi (menit), waktu minimal submit
- Konfigurasi: acak urutan soal, acak opsi jawaban (PG/PGJ/PGBobot — tipe BS tidak diacak)
- Batas pengulangan (`max_pengulangan`; `0` = tidak terbatas)
- Mode penilaian: `otomatis` atau `manual`
- Tampilkan hasil dan/atau review setelah sesi selesai (konfigurabel per paket)
- **Poin negatif** (opsional): `nilai_negatif` per soal salah; opsi kurangi jawaban kosong; opsi clamp (nilai tidak < 0)
- **Timer per soal** (opsional): `waktu_per_soal_detik`; navigasi `bebas` atau `maju` (soal yang dilewati terkunci)
- **Multi-section** (opsional): bagi paket menjadi beberapa bagian dengan durasi dan pengacakan masing-masing
- **Kisi-kisi (blueprint)**: hubungkan paket ke blueprint, atau generate soal otomatis dari blueprint
- **Soft-lock paket:** jika paket digunakan di sesi aktif/selesai, soal tidak bisa ditambah/dihapus

### 8.6 Sesi Ujian & Kalender

- Buat sesi dengan memilih paket ujian
- Status: `draft` -> `aktif` -> `selesai` (+ `dibatalkan`)
- Token akses unik per sesi (case-insensitive)
- Assign peserta per rombel atau individual
- Buka/tutup manual di luar alur status utama

**Kalender Ujian:**
- Halaman kalender interaktif (FullCalendar v6) di panel admin
- Tampilkan semua sesi dalam grid bulan/minggu; klik event -> edit sesi
- Warna: hijau=aktif, abu=draft, biru=selesai
- Dashboard peserta menampilkan daftar sesi 7 hari ke depan

### 8.7 Pengerjaan Ujian (Core)

- Peserta masuk konfirmasi -> input token -> mulai
- **Timer server-authoritative:** `sisa_waktu = (waktu_mulai + durasi x 60) - now()`, disinkronkan ke server setiap 60 detik
- **Timer per soal:** countdown terpisah; mode `maju` otomatis pindah soal dan mengunci soal yang sudah dilewati
- **Multi-section:** header menampilkan badge bagian + timer seksi; saat seksi selesai, soal dikunci dan seksi berikutnya dimulai
- Warning modal saat <= 300 detik dan <= 60 detik tersisa
- Auto-submit saat timer habis atau tab switch melebihi batas
- Navigasi soal bebas (non-linear, kecuali mode navigasi maju)
- Tandai soal sebagai "ragu-ragu"
- Palet soal berwarna: belum dijawab (abu), dijawab (hijau), ragu-ragu (kuning)
- **Auto-save jawaban:** setiap perubahan dikirim via AJAX (debounce 300ms, retry 3x dengan delay 1-3 detik), toast notifikasi "Tersimpan v" / "Gagal menyimpan"
- Upload file untuk soal URAIAN (max `max_upload_mb` MB, MIME: JPEG/PNG/PDF) — file disimpan di disk private
- **Resume otomatis:** peserta bisa pindah komputer/browser; sistem otomatis redirect ke attempt berlangsung dengan sisa waktu yang benar
- Banner peringatan poin negatif di halaman konfirmasi jika `nilai_negatif > 0`
- Pengumuman aktif dari admin/guru tampil di dashboard peserta (banner dengan dismiss per pengumuman)

### 8.8 Anti-Kecurangan

**Server-Side (prioritas tinggi):**

| Mekanisme | Detail |
|---|---|
| Tab switch counter | `exam_attempts.tab_switch_count` di-increment server-side via `POST /ujian/{id}/log` |
| Aksi tab switch | Setting `tab_switch_action`: `log` / `lock` (peserta terkunci) / `submit` (auto-submit) |
| IDOR prevention | Semua endpoint attempt memvalidasi `attempt->user_id === Auth::id()` |
| File URAIAN private | Simpan di `storage/app/private`; akses via auth-protected route, sanitasi path traversal |
| Waktu dari server | Timer tidak bisa dimanipulasi client |
| Batas play audio | `audio_play_count` di `attempt_questions` di-increment server-side; server reject setelah melebihi batas |

**Client-Side:**

| Mekanisme | Aktif Jika |
|---|---|
| Disable copy-paste | `prevent_copy_paste = true` |
| Disable klik kanan | `prevent_right_click = true` |
| Fullscreen enforcement | `require_fullscreen = true` — keluar fullscreen = tab switch event |
| Watermark peserta | Nama + nomor peserta overlay transparan di area soal |
| DevTools detection | Deteksi via selisih `outerHeight - innerHeight > 160` |
| Anti-print CSS | `@media print { visibility: hidden }` selalu aktif di halaman ujian |
| Beacon API | Log tab switch menggunakan `navigator.sendBeacon()` agar request terkirim meski tab berpindah |
| Disable user-select | Class `exam-soal-text` + body global `user-select: none` |

**Jaringan:**

| Mekanisme | Detail |
|---|---|
| IP Whitelist | Middleware `CheckIpWhitelist`; daftar IP pisah koma via setting `ip_whitelist` |
| Rate limiting | Login: `throttle:login`; AJAX jawab: `throttle:120,1`; log: `throttle:60,1` |
| CSRF | Token di semua form & header AJAX |

### 8.9 Manual Grading URAIAN

- Antrian soal URAIAN yang belum dinilai (filter per sesi/paket)
- Input nilai per soal per peserta (0 - bobot soal)
- Setelah semua soal URAIAN dinilai, trigger `ScoringService::regrade()` untuk hitung ulang nilai akhir
- Admin juga dapat trigger regrade manual kapan saja (berguna setelah edit kunci jawaban)

### 8.10 Hasil & Review Jawaban

**Halaman Hasil:**
- Nilai akhir (skala 0-100), jumlah benar/salah/kosong, durasi pengerjaan
- Ranking peserta dalam sesi (opsional, konfigurabel)
- Jika `grading_mode = manual`, nilai akhir ditampilkan setelah regrade selesai

**Halaman Review:**
- Tampilkan semua soal + jawaban peserta + kunci jawaban + pembahasan
- Dapat dibatasi: hanya muncul setelah sesi ditutup (konfigurabel per paket)

### 8.11 Livescore Real-time

- Leaderboard peserta berdasarkan nilai sementara (auto-graded) atau nilai akhir
- Polling otomatis setiap 15 detik via Alpine.js
- Animasi transisi perubahan posisi ranking; medal emoji top 3
- Mode publik (tanpa login) atau hanya untuk yang login — konfigurabel via `livescore_public`
- URL: `/sesi/{session}/livescore`

### 8.12 Monitor Real-time & Catatan Pengawas

**Monitor Real-time:**
- Admin/Guru memantau semua peserta aktif dalam satu sesi
- Data per peserta: soal terjawab, sisa waktu (detik), jumlah tab switch, status, nilai sementara, rank
- Polling otomatis setiap 10 detik
- Tombol "Paksa Keluar" per peserta (dengan konfirmasi) — invalidate attempt, redirect peserta ke dashboard
- Stat cards: total, sedang, selesai, belum, diskualifikasi

**Catatan Pengawas:**
- Panel catatan di halaman Monitor Sesi (Livewire)
- Pengawas (level >= 2) dapat menambah catatan teks per sesi
- Daftar catatan ditampilkan sebagai card (jam + nama penulis + isi)
- Catatan pengawas tampil di cetak berita acara

### 8.13 Dashboard Guru & Portofolio Peserta

**Dashboard Guru:**
- Rekap rombel yang diampu: jumlah peserta, nilai rata-rata per sesi
- Tabel nilai peserta per sesi dengan filter rombel; reactive re-render via `wire:model.live`
- Export rekap nilai per rombel ke Excel (`.xlsx`)
- Akses read-only untuk User & Rombel

**Portofolio Peserta:**
- Peserta dapat melihat riwayat semua ujian yang pernah diikuti
- Tabel riwayat: nama ujian, tanggal, nilai (color-coded), benar/salah/kosong, durasi, link review
- Grafik garis nilai vs waktu (Chart.js)
- Statistik ringkasan: rata-rata, tertinggi, terendah, total ujian
- Guru/Admin dapat melihat portofolio peserta via Filament Page

### 8.14 Laporan & Cetak

| Laporan | Format |
|---|---|
| Rekap nilai semua peserta | Excel, PDF, Cetak |
| Rekap kehadiran (hadir/tidak hadir) | Excel, PDF, Cetak |
| Statistik soal (distribusi pilihan opsi, P-value) | Excel |
| Berita acara ujian (+ catatan pengawas) | Cetak, PDF |
| Daftar hadir | Cetak, PDF |
| Kartu peserta (dengan QR code nomor peserta) | PDF (grid 4 per A4) |
| Rekap kecurangan (tab switch, auto-submit, kick) | Excel, tabel Filament |
| Komparasi antar sesi (nilai A vs B, tren) | Excel, scatter chart |
| Distribusi nilai (histogram bar chart) | Tampilan di Filament Page |

**URL cetak/PDF:**
```
/cabt/laporan/sesi/{id}/cetak/nilai
/cabt/laporan/sesi/{id}/cetak/daftar-hadir
/cabt/laporan/sesi/{id}/cetak/berita-acara
/cabt/laporan/sesi/{id}/pdf/nilai
/cabt/laporan/sesi/{id}/pdf/daftar-hadir
/cabt/laporan/sesi/{id}/pdf/berita-acara
/cabt/laporan/sesi/{id}/pdf/kartu-peserta
/cabt/dashboard-guru/{session}/{rombel}/export
```

### 8.15 Kurikulum, Tag & Kisi-kisi

**Standar Kurikulum (KD/CP):**
- CRUD standar kurikulum: kode, nama, mata pelajaran, jenjang (SD/SMP/SMA/SMK), kurikulum (K13/Merdeka/Internasional), kelas, tingkat kognitif
- Setiap soal dapat dikaitkan ke satu standar KD/CP + bloom level (C1-C6)
- Filter bank soal berdasarkan standar, mata pelajaran, bloom level, kurikulum

**Tag Soal:**
- Buat tag bebas (HOTS, LOTS, Numerasi, Literasi, dll.)
- Setiap soal bisa memiliki banyak tag (many-to-many)
- Auto-suggest dari DB; Tag Management Page khusus di panel admin
- Filter multi-select tag di bank soal

**Blueprint / Kisi-kisi Ujian:**
- Buat kisi-kisi: nama, mata pelajaran, total soal, deskripsi
- Item kisi-kisi: kriteria (kategori, standar, tipe soal, kesulitan, bloom, tag) + jumlah soal + bobot per soal
- Action "Validasi Stok": cek bank soal per kriteria — tampilkan `{tersedia} / {butuh}` per baris
- Action "Generate dari Blueprint" di paket ujian: pilih blueprint -> random-pick otomatis per kriteria
- Cetak kisi-kisi formal (tabel No | Standar | Tipe | Tingkat | Bloom | Jumlah | Bobot)

### 8.16 Multi-Section Exam

- Toggle `has_sections` di paket ujian untuk mengaktifkan mode multi-bagian
- CRUD bagian (seksi): nama, urutan, durasi menit, waktu minimal, acak soal, acak opsi
- Soal diassign per bagian; pengacakan soal dalam satu bagian independent
- **Di halaman ujian:** badge "Bagian X: [nama]" + timer seksi terpisah di header
- Saat waktu seksi habis: modal "Bagian selesai" + tombol "Lanjut ke Bagian Berikutnya"
- Soal seksi yang sudah dilewati terkunci (tidak bisa diisi ulang)
- Nilai akhir tetap dihitung dari semua bagian (formula tidak berubah)

### 8.17 Notifikasi Email & Pengumuman

**Notifikasi Email:**
- Email otomatis ke peserta saat di-assign ke sesi ujian (`SesiDijadwalkanMail`)
- Reminder H-1 sebelum ujian via Artisan Command `exam:reminder` (dijadwalkan via Laravel Scheduler pukul 07.00)
- Aktif/nonaktif via setting `email_notifikasi_sesi` dan `email_reminder_h1`

**Pengumuman ke Peserta:**
- Buat pengumuman: judul, isi, tipe (info/warning/penting), target (semua/per rombel), tanggal mulai/selesai, aktif
- Pengumuman aktif tampil sebagai banner di dashboard peserta (warna per tipe)
- Dismiss per pengumuman via Alpine.js + `localStorage`
- Pengumuman yang sudah kadaluarsa tidak tampil otomatis

### 8.18 Audit Log

- Setiap aksi sensitif admin tercatat: user, aksi, model, deskripsi, IP address, user agent
- Titik-titik yang dicatat: reset password user, paksa logout, hapus soal, buka/tutup sesi, simpan general setting, hapus rombel
- Halaman Audit Log di panel admin (hanya Super Admin):
  - Tabel: tanggal, user, aksi (badge), model, deskripsi, IP
  - Filter: per user, per aksi, date range
  - Export Excel

### 8.19 General Setting

Semua pengaturan disimpan di tabel `app_settings` (key-value). Dapat dikelola via Filament Panel tanpa menyentuh kode.

Lebih dari 25 parameter tersedia, mencakup:
- Identitas aplikasi (nama, nama sekolah, logo)
- Konfigurasi autentikasi (rate limit, multi-login, session timeout)
- Parameter anti-kecurangan (semua mekanisme di atas)
- Pengaturan upload (batas ukuran file umum dan audio)
- Kontrol tampilan hasil & review
- Visibilitas livescore
- Notifikasi email
- Whitelist IP

---

## 9. Kalkulasi Nilai

### Formula Umum

```
nilai_akhir = (jumlah nilai_perolehan_per_soal / jumlah bobot_max_per_soal) x 100
```

### Nilai Per Tipe Soal

| Tipe | Formula Nilai |
|---|---|
| `PG` | Benar -> bobot soal; Salah/Kosong -> 0 |
| `PG_BOBOT` | `bobot_soal x (bobot_persen_opsi / 100)` |
| `PGJ` | `bobot_soal x (jumlah_benar_dipilih / total_kunci_benar)`, minimal 0 |
| `BS` | Identik PG: Benar -> bobot soal; Salah/Kosong -> 0 |
| `JODOH` | `bobot_soal x (pasangan_benar / total_pasangan)` |
| `ISIAN` | Keyword match case-insensitive -> bobot soal; lainnya -> 0 |
| `CLOZE` | `bobot_soal x (blank_benar / total_blank)`, minimal 0 |
| `URAIAN` | Input manual guru (0 - bobot_soal) |

### Poin Negatif (opsional, per paket)

Jika `nilai_negatif > 0` di paket:
- Jawaban salah: `nilai_perolehan -= nilai_negatif x bobot_soal`
- Jawaban kosong: dikurangi juga jika `nilai_negatif_kosong = true`
- Jika `nilai_negatif_clamp = true`: nilai per soal tidak bisa negatif (floor di 0)

### Regrade

`ScoringService::regrade($attemptId)` menghitung ulang semua `nilai_perolehan` dan memperbarui `nilai_akhir` di `exam_attempts`. Dipanggil otomatis setelah manual grading URAIAN selesai, atau secara manual oleh admin.

---

## 10. Struktur Database

| Tabel | Deskripsi |
|---|---|
| `users` | Data user semua level; kolom `level` (1-4), `nomor_peserta`, `rombel_id` |
| `rombels` | Rombongan belajar |
| `rombel_guru` | Pivot guru - rombel (many-to-many) |
| `categories` | Kategori soal hierarkis (`parent_id`) |
| `curriculum_standards` | Standar KD/CP; kolom `kode`, `jenjang`, `kurikulum`, `bloom_level` |
| `tags` | Tag soal bebas (`nama unique`) |
| `question_groups` | Stimulus/bacaan untuk soal kelompok; `tipe_stimulus` (teks/gambar/audio/video/tabel) |
| `questions` | Soal semua tipe; kolom tambahan: `tipe` (8 nilai), `bloom_level`, `curriculum_standard_id`, `question_group_id`, `audio_url`, `audio_play_limit`, `audio_auto_play`, `visibilitas` |
| `question_options` | Opsi jawaban PG/PG_BOBOT/PGJ/BS per soal |
| `question_matches` | Pasangan menjodohkan (kiri - kanan) |
| `question_keywords` | Keyword jawaban soal ISIAN |
| `question_cloze_blanks` | Blank soal CLOZE; kolom `urutan`, `placeholder`, `jawaban_benar`, `keywords_json`, `case_sensitive` |
| `question_tag` | Pivot soal - tag |
| `exam_blueprints` | Master kisi-kisi ujian; kolom `nama`, `mata_pelajaran`, `total_soal` |
| `exam_blueprint_items` | Baris kisi-kisi; kriteria (kategori, standar, tipe, kesulitan, bloom, tag) + jumlah + bobot |
| `exam_packages` | Paket ujian; kolom `durasi_menit`, `max_pengulangan`, `grading_mode`, `nilai_negatif`, `waktu_per_soal_detik`, `has_sections`, `blueprint_id` |
| `exam_package_questions` | Pivot paket - soal dengan urutan |
| `exam_sections` | Bagian (seksi) dalam paket multi-section; kolom `nama`, `urutan`, `durasi_menit` |
| `exam_section_questions` | Pivot seksi - soal dengan urutan |
| `exam_sessions` | Sesi ujian; kolom `status`, `token_akses`, `paket_id` |
| `exam_session_participants` | Peserta yang ditugaskan ke sesi |
| `exam_attempts` | Riwayat pengerjaan per peserta per sesi; kolom `nilai_akhir`, `tab_switch_count`, `status` |
| `attempt_questions` | Soal yang diacak per attempt + `jawaban_peserta`, `nilai_perolehan`, `audio_play_count`, `section_id` |
| `attempt_section_starts` | Waktu mulai per seksi per attempt |
| `attempt_logs` | Log event anti-kecurangan (tab switch, fullscreen exit, dsb.) |
| `session_notes` | Catatan pengawas per sesi; kolom `exam_session_id`, `user_id`, `catatan` |
| `announcements` | Pengumuman ke peserta; kolom `tipe`, `target`, `rombel_id`, `tanggal_mulai`, `tanggal_selesai`, `aktif` |
| `app_settings` | Key-value store konfigurasi aplikasi |
| `audit_logs` | Log aksi sensitif admin; kolom `action`, `model_type`, `model_id`, `ip_address`, `user_agent` |

---

## 11. Struktur Direktori

```
app/
+-- Filament/
|   +-- Pages/
|   |   +-- GeneralSetting.php
|   |   +-- KalenderUjian.php
|   |   +-- DashboardGuru.php
|   |   +-- MonitorSesi.php
|   |   +-- LaporanNilai.php
|   |   +-- LaporanKehadiran.php
|   |   +-- StatistikSoal.php
|   |   +-- KomparasiSesi.php
|   |   +-- LaporanKecurangan.php
|   |   +-- PesertaPortfolio.php
|   |   +-- TagManagement.php
|   |   +-- AuditLogPage.php
|   +-- Resources/
|       +-- UserResource.php
|       +-- RombelResource.php
|       +-- QuestionResource.php
|       +-- QuestionGroupResource.php
|       +-- CategoryResource.php
|       +-- CurriculumStandardResource.php
|       +-- ExamPackageResource.php
|       +-- ExamSessionResource.php
|       +-- ExamBlueprintResource.php
|       +-- GradingResource.php
|       +-- LaporanResource.php
+-- Http/
|   +-- Controllers/
|   |   +-- Peserta/
|   |   |   +-- DashboardController.php
|   |   |   +-- UjianController.php
|   |   |   +-- LivescoreController.php
|   |   |   +-- PortofolioController.php
|   |   +-- PrintController.php
|   |   +-- PdfController.php
|   +-- Middleware/
|   |   +-- CheckLevel.php
|   |   +-- CheckSingleSession.php
|   |   +-- CheckIpWhitelist.php
|   |   +-- CheckSessionTimeout.php
|   +-- Requests/
+-- Models/
|   +-- (User, Question, ExamSession, ExamAttempt, ...)
|   +-- QuestionClozeBlank.php
|   +-- QuestionGroup.php
|   +-- CurriculumStandard.php
|   +-- Tag.php
|   +-- ExamBlueprint.php
|   +-- ExamBlueprintItem.php
|   +-- ExamSection.php
|   +-- SessionNote.php
|   +-- Announcement.php
|   +-- AuditLog.php
+-- Services/
    +-- ExamService.php
    +-- ScoringService.php
    +-- ShuffleService.php
    +-- ImportService.php
    +-- ReportService.php
    +-- AuditLogService.php

resources/views/
+-- layouts/
|   +-- peserta.blade.php
|   +-- ujian.blade.php
+-- peserta/
    +-- dashboard.blade.php
    +-- konfirmasi.blade.php
    +-- ujian.blade.php
    +-- _soal-card.blade.php
    +-- hasil.blade.php
    +-- review.blade.php
    +-- livescore.blade.php
    +-- portofolio.blade.php

database/seeders/
+-- DatabaseSeeder.php
+-- AppSettingsSeeder.php
+-- UserSeeder.php
+-- RombelSeeder.php
+-- QuestionSeeder.php
+-- QuestionGroupSeeder.php
+-- ExamPackageSeeder.php
+-- ExamSessionSeeder.php
+-- AttemptSeeder.php
+-- CurriculumStandardSeeder.php
+-- MultiSectionMathSeeder.php
+-- TagSeeder.php
+-- AnnouncementSeeder.php
+-- ExamBlueprintSeeder.php
+-- SessionNoteSeeder.php

tests/
+-- Feature/
|   +-- Auth/
|   +-- Admin/
|   +-- Exam/
|   +-- Peserta/
|   +-- Security/
+-- Unit/
    +-- Services/
```

---

## 12. API & Endpoint Penting

### Peserta

| Method | URL | Deskripsi |
|---|---|---|
| `GET` | `/peserta` | Dashboard peserta |
| `GET` | `/peserta/portofolio` | Riwayat semua ujian peserta |
| `GET` | `/ujian/{sesiId}` | Konfirmasi & input token |
| `POST` | `/ujian/{sesiId}/mulai` | Mulai / lanjutkan attempt |
| `GET` | `/ujian/{sesiId}/kerjakan` | Halaman pengerjaan ujian |
| `POST` | `/ujian/jawab` | Simpan jawaban (AJAX, throttle 120/min) |
| `POST` | `/ujian/{id}/log` | Log event anti-kecurangan (AJAX, throttle 60/min) |
| `GET` | `/ujian/{id}/status` | Sync sisa waktu dari server (AJAX) |
| `POST` | `/ujian/{id}/submit` | Submit ujian |
| `GET` | `/ujian/{id}/hasil` | Halaman hasil |
| `GET` | `/ujian/{id}/review` | Review jawaban |
| `GET` | `/file/uraian/{id}/{filename}` | Serve file URAIAN (private, auth required) |
| `POST` | `/ujian/audio/{questionId}/play` | Increment audio play count (AJAX) |
| `POST` | `/ujian/{attemptId}/seksi/{sectionId}/selesai` | Selesaikan bagian (multi-section) |

### Livescore

| Method | URL | Deskripsi |
|---|---|---|
| `GET` | `/sesi/{session}/livescore` | Halaman livescore |
| `GET` | `/sesi/{session}/livescore/data` | Data JSON livescore (polling) |

### Admin (Filament `/cabt`)

| Method | URL | Deskripsi |
|---|---|---|
| `GET` | `/cabt/kalender/data` | Data JSON events kalender (FullCalendar) |
| `GET` | `/cabt/sesi/{session}/monitor/data` | Data JSON peserta aktif (polling) |
| `POST` | `/cabt/sesi/{session}/paksa-keluar/{userId}` | Paksa keluar peserta |
| `POST` | `/cabt/sesi/{id}/catatan` | Tambah catatan pengawas |
| `GET` | `/cabt/laporan/sesi/{id}/cetak/nilai` | Cetak rekap nilai |
| `GET` | `/cabt/laporan/sesi/{id}/cetak/daftar-hadir` | Cetak daftar hadir |
| `GET` | `/cabt/laporan/sesi/{id}/cetak/berita-acara` | Cetak berita acara |
| `GET` | `/cabt/laporan/sesi/{id}/pdf/{tipe}` | Export PDF (nilai/daftar-hadir/berita-acara/kartu-peserta) |
| `GET` | `/cabt/dashboard-guru/{session}/{rombel}/export` | Export Excel nilai per rombel |
| `GET` | `/cabt/blueprint/{id}/cetak` | Cetak kisi-kisi formal |
| `GET` | `/admin/soal/search` | Pencarian soal (autocomplete Filament) |

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

Cakupan test meliputi:
- Autentikasi & rate limiting
- Access control per level (peserta/guru/admin/super admin)
- Alur lengkap ujian (mulai -> jawab -> submit -> hasil -> review)
- Anti-kecurangan (tab switch, IP whitelist, path traversal upload, audio play limit)
- Import soal & user dari Excel
- Kalkulasi nilai semua tipe soal (termasuk BS, CLOZE, poin negatif)
- Soal kelompok/stimulus (split-panel, urutan dalam grup terjaga)
- Multi-section (section_id di attempt_questions, peralihan seksi)
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

### Scheduler (Notifikasi Email)

Tambahkan cron job di server untuk menjalankan Laravel Scheduler:

```bash
* * * * * cd /var/www/sekolix-cabt && php artisan schedule:run >> /dev/null 2>&1
```

Scheduler menjalankan `exam:reminder` setiap hari pukul 07.00 untuk mengirim reminder H-1.

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
- [ ] Cron job scheduler terpasang (untuk notifikasi email reminder)
- [ ] Queue worker berjalan jika `QUEUE_CONNECTION != sync` (untuk notifikasi email)
- [ ] Backup database dijadwalkan

---

## 15. Lisensi

Proyek ini dilisensikan di bawah [MIT License](https://opensource.org/licenses/MIT).
