@props([
    'pageTitle',
])

<div class="space-y-4 text-sm text-gray-700 dark:text-gray-200">
    <p>
        Ini adalah panduan untuk halaman <strong>{{ $pageTitle }}</strong>.
        Halaman paket ujian digunakan untuk mengelola set soal, pengaturan waktu, dan konfigurasi ujian.
    </p>

    <ul class="list-disc space-y-2 px-6">
        <li>Gunakan tombol <strong>Tambah</strong> untuk membuat paket ujian baru.</li>
        <li>Isi nama paket, deskripsi, dan pilih blueprint jika ingin generate soal dari kisi-kisi.</li>
        <li>Atur pengacakan soal dan waktu pengerjaan sesuai kebutuhan ujian.</li>
        <li>Gunakan relasi untuk menambahkan soal ke paket atau menambahkan seksi ujian.</li>
    </ul>

    <p>
        Paket ujian yang sudah dibuat dapat digunakan untuk membuat sesi ujian dan melihat hasil penilaian nanti.
    </p>
</div>
