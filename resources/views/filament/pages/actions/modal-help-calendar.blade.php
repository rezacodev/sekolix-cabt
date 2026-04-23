@props([
    'pageTitle',
])

<div class="space-y-4 text-sm text-gray-700 dark:text-gray-200">
    <p>
        Ini adalah panduan untuk halaman <strong>{{ $pageTitle }}</strong>.
        Halaman kalender ujian menampilkan jadwal sesi ujian dalam tampilan kalender untuk memudahkan perencanaan.
    </p>

    <ul class="list-disc space-y-2 px-6">
        <li>Gunakan kalender untuk melihat sesi ujian berdasarkan tanggal dan waktu.</li>
        <li>Periksa detail sesi dengan mengklik hari atau event sesi pada kalender.</li>
        <li>Pastikan sesi yang direncanakan sudah menggunakan paket ujian dan pengaturan peserta yang benar.</li>
    </ul>

    <p>
        Kalender ini berguna untuk memantau jadwal ujian dan memastikan tidak terjadi bentrok antara sesi yang berbeda.
    </p>
</div>
