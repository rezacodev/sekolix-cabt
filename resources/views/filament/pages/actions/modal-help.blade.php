@props([
    'pageTitle',
])

<div class="space-y-4 text-sm text-gray-700 dark:text-gray-200">
    <p>
        Ini adalah panduan singkat untuk halaman <strong>{{ $pageTitle }}</strong>.
        Gunakan tombol bantuan ini untuk melihat informasi fitur utama dan langkah cepat dalam pengelolaan data.
    </p>

    <ul class="list-disc space-y-2 px-6">
        <li>Gunakan tombol <strong>Tambahkan</strong> untuk membuat entri baru.</li>
        <li>Gunakan tombol aksi pada baris untuk <strong>mengedit</strong> atau <strong>menghapus</strong> data.</li>
        <li>Gunakan fitur pencarian dan filter di atas tabel untuk menemukan data dengan cepat.</li>
    </ul>

    <p>
        Jika ada pertanyaan lain, silakan hubungi tim admin atau baca dokumentasi internal untuk alur operasi halaman ini.
    </p>
</div>
