@props([
    'pageTitle',
])

<div class="space-y-4 text-sm text-gray-700 dark:text-gray-200">
    <p>
        Ini adalah panduan khusus untuk halaman <strong>{{ $pageTitle }}</strong>.
        Pada halaman pengguna, Anda dapat melakukan pengelolaan data user secara langsung, termasuk impor dan ekspor Excel.
    </p>

    <ul class="list-disc space-y-2 px-6">
        <li>Gunakan tombol <strong>Template Excel</strong> untuk mengunduh format file import.</li>
        <li>Gunakan tombol <strong>Import Excel</strong> untuk memuat data user secara massal.</li>
        <li>Gunakan tombol <strong>Export Excel</strong> untuk mengunduh daftar user.</li>
        <li>Gunakan tombol <strong>Tambah</strong> untuk membuat user baru.</li>
        <li>Gunakan tombol aksi pada baris untuk <strong>mengedit</strong> atau <strong>menghapus</strong> user.</li>
    </ul>

    <p>
        Pastikan file Excel sesuai format sebelum mengimpor. Jika mengalami masalah import, cek kembali kolom dan format data.
    </p>
</div>
