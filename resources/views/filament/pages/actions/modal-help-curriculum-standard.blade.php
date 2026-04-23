@props([
    'pageTitle',
])

<div class="space-y-4 text-sm text-gray-700 dark:text-gray-200">
    <p>
        Ini adalah panduan untuk halaman <strong>{{ $pageTitle }}</strong>.
        Halaman ini menyediakan pengelolaan KD/CP dengan input kode, deskripsi, dan kategori kompetensi.
    </p>

    <ul class="list-disc space-y-2 px-6">
        <li>Gunakan tombol <strong>Tambah</strong> untuk membuat entri KD/CP baru.</li>
        <li>Isi <strong>Kode KD/CP</strong> agar mudah dicari dan terhubung dengan kisi-kisi.</li>
        <li>Gunakan <strong>Deskripsi / Rumusan KD/CP</strong> untuk menjelaskan kompetensi yang diharapkan.</li>
        <li>Pastikan setiap KD/CP sesuai dengan standar kurikulum dan jenjang kelas yang benar.</li>
    </ul>

    <p>
        Jika perlu, gunakan fitur pencarian atau filter untuk menemukan KD/CP berdasarkan kode atau kata kunci daftar kompetensi.
    </p>
</div>
