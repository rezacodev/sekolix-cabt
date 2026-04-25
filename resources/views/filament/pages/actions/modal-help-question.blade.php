@props([
    'pageTitle',
])

<div class="space-y-4 text-sm text-gray-700 dark:text-gray-200">
    <p>
        Ini adalah panduan khusus untuk halaman <strong>{{ $pageTitle }}</strong>.
        Halaman ini berisi bank soal yang dapat ditambah, diimpor, diedit, dan dikelola detailnya.
    </p>

    <ul class="list-disc space-y-2 px-6">
        <li>Gunakan tombol <strong>Template Excel</strong> untuk mengunduh format impor soal.</li>
        <li>Gunakan tombol <strong>Import Excel</strong> untuk memasukkan soal secara massal dari file .xlsx.</li>
        <li>Gunakan tombol <strong>Create</strong> untuk menambahkan soal baru secara manual.</li>
        <li>Gunakan menu <strong>Pengaturan Bank Soal</strong> untuk membuka halaman <strong>Kategori Soal</strong>, <strong>Grup Soal</strong>, dan <strong>Tag Soal</strong>.</li>
        <li>Gunakan tombol aksi pada baris untuk <strong>mengedit</strong> atau <strong>menghapus</strong> soal.</li>
        <li>Gunakan filter dan pencarian untuk mencari soal berdasarkan teks soal, tipe, kategori, atau tag.</li>
    </ul>

    <p>
        Perhatikan kategori, KD/CP, dan jenis soal agar bank soal tetap terstruktur dan mudah digunakan untuk pembuatan paket ujian.
    </p>
</div>
