@props([
    'pageTitle',
])

<div class="space-y-4 text-sm text-gray-700 dark:text-gray-200">
    <p>
        Ini adalah panduan untuk halaman <strong>{{ $pageTitle }}</strong>.
        Halaman sesi ujian digunakan untuk menjadwalkan pelaksanaan ujian dan mengelola peserta serta paket ujian.
    </p>

    <ul class="list-disc space-y-2 px-6">
        <li>Gunakan tombol <strong>Tambah</strong> untuk membuat sesi ujian baru.</li>
        <li>Pilih paket ujian, tanggal, dan durasi sesi yang sesuai.</li>
        <li>Setel peserta, jenis pengacakan, dan pengaturan waktu mulai/akhir.</li>
        <li>Pastikan status sesi benar sebelum mempublikasikan atau memulai ujian.</li>
    </ul>

    <p>
        Sesi ujian yang dibuat akan muncul di kalender ujian dan bisa digunakan untuk memonitor peserta serta hasil penilaian.
    </p>
</div>
