#!/usr/bin/env bash
# ==============================================================
# start.sh — Script Startup Sekolix CABT
# ==============================================================
#
# Script ini dijalankan setiap kali container Railway dimulai
# (termasuk saat pertama deploy dan setiap redeploy).
#
# Urutan eksekusi:
#   1. Cek APP_KEY
#   2. Siapkan direktori storage
#   3. Cache konfigurasi Laravel
#   4. Jalankan migrasi database
#   5. Buat storage symlink
#   6. Mulai web server
#
# PORTING KE PLATFORM LAIN:
#   - Render.com  : Tambahkan "bash start.sh" sebagai Start Command
#   - Fly.io      : Panggil script ini dari CMD di Dockerfile
#   - VPS/Linux   : Jalankan langsung: bash start.sh
#
# Line endings HARUS LF (Unix). Jika di Windows, pastikan .gitattributes
# sudah mengatur "* text=auto eol=lf" sebelum commit file ini.
# ==============================================================

# Hentikan script jika ada perintah yang gagal.
# Ini mencegah server berjalan dalam keadaan tidak konsisten.
set -e

echo "╔══════════════════════════════════════════╗"
echo "║       Sekolix CABT — Starting Up         ║"
echo "╚══════════════════════════════════════════╝"

# Pastikan working directory adalah root project Laravel.
# Railway Nixpacks mendeploy ke /app secara default.
# Tanpa ini, perintah "php artisan ..." tidak bisa menemukan file artisan.
cd /app

# --------------------------------------------------------------
# LANGKAH 1: Cek APP_KEY
# --------------------------------------------------------------
# APP_KEY adalah kunci enkripsi untuk session, cookie, dan data
# sensitif lainnya. WAJIB di-set di environment variables Railway
# sebelum deploy pertama.
#
# Cara mendapatkan APP_KEY:
#   Di mesin lokal: php artisan key:generate --show
#   Lalu copy hasilnya ke Railway Dashboard → Variables → APP_KEY
# --------------------------------------------------------------
echo ""
echo "=== [1/6] Memeriksa APP_KEY ==="
if [ -z "$APP_KEY" ]; then
    echo "⚠  APP_KEY belum di-set di environment variables!"
    echo "   Generating key sementara untuk session ini..."
    echo "   PENTING: Set APP_KEY di Railway Dashboard agar session tidak reset saat redeploy."
    php artisan key:generate --force
    echo "   APP_KEY berhasil di-generate (sementara)."
else
    echo "✓  APP_KEY sudah di-set."
fi

# --------------------------------------------------------------
# LANGKAH 2: Siapkan direktori storage
# --------------------------------------------------------------
# Container baru mungkin tidak memiliki struktur direktori yang
# diperlukan Laravel. Kita buat semuanya di sini.
#
# CATATAN PENTING tentang file upload (soal URAIAN):
# Railway menggunakan "ephemeral storage" — file AKAN HILANG
# saat container di-restart atau redeploy.
#
# Untuk menyimpan file upload secara permanen:
#   → Tambahkan Railway Volume dan mount ke /app/storage/app
#   → Atau gunakan cloud storage (Cloudflare R2, AWS S3, dsb.)
# --------------------------------------------------------------
echo ""
echo "=== [2/6] Mempersiapkan direktori storage ==="
mkdir -p storage/framework/cache/data
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/app/public
mkdir -p storage/app/private
mkdir -p bootstrap/cache
chmod -R 775 storage bootstrap/cache
echo "✓  Direktori storage siap."

# --------------------------------------------------------------
# LANGKAH 3: Migrasi & seed database
# --------------------------------------------------------------
# HARUS dijalankan SEBELUM config:cache, karena beberapa artisan
# command saat caching bisa mencoba query ke DB.
# migrate:fresh menghapus semua tabel dan membuat ulang dari awal,
# lalu seeder mengisi data demo. --force wajib di production.
# --------------------------------------------------------------
echo ""
echo "=== [3/6] Migrasi & seed database ==="
php artisan migrate:fresh --seed --force
echo "✓  Migrasi & seeder selesai."

# --------------------------------------------------------------
# LANGKAH 4: Cache konfigurasi Laravel
# --------------------------------------------------------------
# Dijalankan SETELAH migrate agar DB sudah siap jika ada service
# provider yang query database saat bootstrap.
# "|| true" pada tiap perintah agar satu kegagalan tidak
# menghentikan seluruh startup (cache bersifat opsional/performa).
# --------------------------------------------------------------
echo ""
echo "=== [4/6] Men-cache konfigurasi Laravel ==="
php artisan config:cache && echo "   ✓ config" || echo "   ⚠ config cache gagal (dilanjutkan)"
php artisan route:cache  && echo "   ✓ routes" || echo "   ⚠ route cache gagal (dilanjutkan)"
php artisan view:cache   && echo "   ✓ views"  || echo "   ⚠ view cache gagal (dilanjutkan)"
php artisan event:cache  && echo "   ✓ events" || echo "   ⚠ event cache gagal (dilanjutkan)"
echo "✓  Cache selesai."

# --------------------------------------------------------------
# LANGKAH 5: Buat storage symlink
# --------------------------------------------------------------
# Membuat symlink public/storage → storage/app/public
# agar file yang diupload bisa diakses via URL.
#
# "|| true" memastikan script tidak berhenti jika symlink sudah ada.
# --------------------------------------------------------------
echo ""
echo "=== [5/6] Membuat storage symlink ==="
php artisan storage:link 2>/dev/null || echo "   (Storage link sudah ada, lanjut...)"
echo "✓  Storage link siap."

# --------------------------------------------------------------
# LANGKAH 6: Jalankan web server
# --------------------------------------------------------------
# Railway menyuplai PORT secara otomatis via environment variable $PORT.
# Default: 8080 jika $PORT tidak di-set (untuk testing lokal).
#
# php artisan serve cocok untuk demo/staging.
# Untuk production skala besar dengan banyak concurrent user,
# pertimbangkan setup nginx + php-fpm.
#
# "exec" menggantikan proses bash dengan PHP sehingga Railway
# bisa mengirim sinyal (SIGTERM) langsung ke PHP saat shutdown.
# --------------------------------------------------------------
echo ""
echo "=== [6/6] Memulai web server ==="
PORT="${PORT:-8080}"
echo "✓  Server berjalan di http://0.0.0.0:${PORT}"
echo ""
echo "╔══════════════════════════════════════════╗"
echo "║         Sekolix CABT Siap! 🚀            ║"
echo "╚══════════════════════════════════════════╝"
echo ""

exec php artisan serve --host=0.0.0.0 --port="${PORT}"
