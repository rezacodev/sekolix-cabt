@props([
    'pageTitle',
])

<div class="space-y-4 text-sm text-gray-700 dark:text-gray-200">
    <p>
        Ini adalah panduan untuk halaman <strong>{{ $pageTitle }}</strong>.
        Halaman grup soal digunakan untuk mengelompokkan soal stimulus yang dipakai bersama dalam satu paket.
    </p>

    <ul class="list-disc space-y-2 px-6">
        <li>Gunakan tombol <strong>Tambah</strong> untuk membuat grup soal baru.</li>
        <li>Grup soal cocok untuk soal yang memiliki stimulus atau konteks yang sama.</li>
        <li>Setelah membuat grup, tambahkan soal ke dalam grup menggunakan form relasi di halaman soal.</li>
    </ul>

    <p>
        Pastikan setiap grup soal diberi nama yang jelas agar guru bisa memahami stimulus dan konteks yang dipilih.
    </p>
</div>
