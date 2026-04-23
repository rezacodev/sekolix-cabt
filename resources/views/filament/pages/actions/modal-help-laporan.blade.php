@props([
    'pageTitle',
])

<div class="space-y-4 text-sm text-gray-700 dark:text-gray-200">
    <p>
        Ini adalah panduan untuk halaman <strong>{{ $pageTitle }}</strong>.
        Halaman ini menampilkan laporan hasil ujian per sesi dan menyediakan akses ke rekap nilai serta statistik.
    </p>

    <ul class="list-disc space-y-2 px-6">
        <li>Gunakan action <strong>Bandingkan Sesi</strong> untuk melihat perbandingan antar sesi ujian.</li>
        <li>Klik detail laporan untuk melihat rekap nilai, kehadiran, dan statistik soal per sesi.</li>
        <li>Gunakan filter status dan rentang tanggal untuk menemukan sesi ujian tertentu.</li>
    </ul>

    <p>
        Pastikan sesi ujian yang dipilih sudah selesai atau aktif agar data laporan dapat ditampilkan dengan lengkap.
    </p>
</div>
