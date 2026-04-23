@props([
    'pageTitle',
])

<div class="space-y-4 text-sm text-gray-700 dark:text-gray-200">
    <p>
        Ini adalah panduan untuk halaman <strong>{{ $pageTitle }}</strong>.
        Halaman pengumuman digunakan untuk membuat, mengedit, dan mengelola pengumuman yang ditujukan ke peserta dan rombel.
    </p>

    <ul class="list-disc space-y-2 px-6">
        <li>Gunakan tombol <strong>Tambah</strong> untuk membuat pengumuman baru.</li>
        <li>Pilih target penerima apakah pengumuman ditujukan untuk semua peserta atau hanya rombel tertentu.</li>
        <li>Setel durasi tampilan pengumuman dengan tanggal mulai dan tanggal selesai.</li>
    </ul>

    <p>
        Pengumuman aktif akan tampil kepada peserta sesuai target dan periode yang Anda tentukan.
    </p>
</div>
