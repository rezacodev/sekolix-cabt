# ==============================================================
# Procfile — Konfigurasi proses untuk Heroku / Render / Railway
# ==============================================================
#
# Format Procfile digunakan oleh beberapa platform hosting:
#   - Heroku      : dibaca otomatis
#   - Render.com  : set sebagai "Start Command": bash start.sh
#   - Railway     : Railway lebih prefer nixpacks.toml [start] cmd,
#                   tapi Procfile juga bisa dipakai sebagai fallback
#
# Jenis proses:
#   web    : proses utama yang menerima HTTP traffic (wajib ada 1)
#   release: dijalankan sebelum web (cocok untuk migrasi di Heroku)
#
# Untuk Railway, nixpacks.toml [start] cmd adalah yang diutamakan.
# Procfile ini disediakan untuk kompatibilitas dengan platform lain.
# ==============================================================

# Proses web utama: jalankan start.sh yang menangani semua setup
web: bash start.sh
