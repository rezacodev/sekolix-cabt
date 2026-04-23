@props([
    'pageTitle',
])

<div class="space-y-4 text-sm text-gray-700 dark:text-gray-200">
    <p>
        Ini adalah panduan untuk halaman <strong>{{ $pageTitle }}</strong>.
        Halaman ini memberikan ringkasan kinerja peserta di sesi ujian yang diajar oleh guru.
    </p>

    <ul class="list-disc space-y-2 px-6">
        <li>Pilih sesi ujian yang ingin dilihat untuk menampilkan data rombel dan hasil peserta.</li>
        <li>Gunakan ringkasan nilai, status, dan durasi untuk memantau performa tiap rombel.</li>
        <li>Data ini membantu guru melihat siswa yang belum selesai, nilai tertinggi/terendah, dan jumlah peserta.</li>
    </ul>

    <p>
        Dashboard ini hanya menampilkan sesi ujian yang dibuat oleh Anda sebagai guru dan tidak menggantikan laporan resmi sesi.
    </p>
</div>
