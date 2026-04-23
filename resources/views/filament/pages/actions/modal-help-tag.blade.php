@props([
    'pageTitle',
])

<div class="space-y-4 text-sm text-gray-700 dark:text-gray-200">
    <p>
        Ini adalah panduan untuk halaman <strong>{{ $pageTitle }}</strong>.
        Halaman tag soal membantu menandai dan mengelompokkan soal berdasarkan topik atau karakteristik khusus.
    </p>

    <ul class="list-disc space-y-2 px-6">
        <li>Gunakan tombol <strong>Tambah</strong> untuk membuat tag baru.</li>
        <li>Tag cocok digunakan untuk topik, level kesulitan, atau sumber materi.</li>
        <li>Soal dapat diberi lebih dari satu tag untuk memudahkan pencarian dan filter.</li>
    </ul>

    <p>
        Gunakan tag agar soal yang serupa dapat ditemukan lebih cepat tanpa mengubah struktur kategori utama.
    </p>
</div>
