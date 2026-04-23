@props([
    'pageTitle',
])

<div class="space-y-4 text-sm text-gray-700 dark:text-gray-200">
    <p>
        Ini adalah panduan untuk halaman <strong>{{ $pageTitle }}</strong>.
        Halaman grading digunakan untuk melihat daftar penilaian dan memproses nilai siswa untuk sesi ujian.
    </p>

    <ul class="list-disc space-y-2 px-6">
        <li>Gunakan tabel ini untuk menemukan sesi ujian yang sudah dinilai atau masih dalam proses.</li>
        <li>Gunakan action yang tersedia untuk membuka detail penilaian atau mengoreksi nilai.</li>
        <li>Perhatikan status penilaian dan jumlah soal yang sudah diperiksa sebelum menyelesaikan grading.</li>
    </ul>

    <p>
        Jika grading tidak selesai, pastikan semua soal sudah dinilai dan simpan perubahan nilai secara benar.
    </p>
</div>
