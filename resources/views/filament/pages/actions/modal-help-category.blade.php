@props([
    'pageTitle',
])

<div class="space-y-4 text-sm text-gray-700 dark:text-gray-200">
    <p>
        Ini adalah panduan untuk halaman <strong>{{ $pageTitle }}</strong>.
        Halaman kategori soal digunakan untuk mengelompokkan soal agar pencarian dan pemfilteran menjadi lebih mudah.
    </p>

    <ul class="list-disc space-y-2 px-6">
        <li>Gunakan tombol <strong>Tambah</strong> untuk membuat kategori soal baru.</li>
        <li>Berikan nama kategori yang singkat dan jelas, misalnya <em>Pilihan Ganda</em> atau <em>Essay</em>.</li>
        <li>Kategori dapat membantu saat menyusun paket soal, import, atau filter bank soal.</li>
    </ul>

    <p>
        Pastikan kategori hanya berisi satu jenis atau grup soal yang konsisten agar laporan dan penggunaan soal lebih terorganisir.
    </p>
</div>
