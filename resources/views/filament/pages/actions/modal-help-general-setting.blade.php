@props([
    'pageTitle',
])

<div class="space-y-4 text-sm text-gray-700 dark:text-gray-200">
    <p>
        Ini adalah panduan untuk halaman <strong>{{ $pageTitle }}</strong>.
        Halaman ini berisi pengaturan umum aplikasi yang memengaruhi tampilan, keamanan, dan pengalaman ujian.
    </p>

    <ul class="list-disc space-y-2 px-6">
        <li>Gunakan bagian Identitas Aplikasi untuk mengubah nama sistem, nama sekolah, dan logo header laporan.</li>
        <li>Atur autentikasi dan sesi untuk mengelola batas login, timeout, dan perlindungan anti-kecurangan.</li>
        <li>Periksa pengaturan notifikasi jika ingin mengaktifkan email pengingat dan notifikasi ujian.</li>
    </ul>

    <p>
        Semua perubahan diterapkan secara global dan dapat memengaruhi peserta serta guru di seluruh sistem.
    </p>
</div>
