@props([
    'pageTitle',
])

<div class="space-y-4 text-sm text-gray-700 dark:text-gray-200">
    <p>
        Ini adalah panduan khusus untuk halaman <strong>{{ $pageTitle }}</strong>.
        Halaman rombel digunakan untuk mengelola kelompok kelas dan anggota peserta.
    </p>

    <ul class="list-disc space-y-2 px-6">
        <li>Gunakan tombol <strong>Tambah</strong> untuk membuat rombel baru.</li>
        <li>Gunakan tombol aksi pada baris untuk <strong>mengedit</strong> atau <strong>menghapus</strong> rombel.</li>
        <li>Gunakan fitur filter untuk menemukan rombel berdasarkan nama atau jenjang kelas.</li>
        <li>Pastikan setiap rombel memiliki nama dan guru wali yang sesuai.</li>
    </ul>

    <p>
        Jika Anda membutuhkan bantuan lebih lanjut, silakan hubungi admin atau lihat dokumentasi rombel.
    </p>
</div>
