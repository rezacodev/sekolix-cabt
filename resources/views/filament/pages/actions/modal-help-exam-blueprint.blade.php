@props([
    'pageTitle',
])

<div class="space-y-4 text-sm text-gray-700 dark:text-gray-200">
    <p>
        Ini adalah panduan untuk halaman <strong>{{ $pageTitle }}</strong>.
        Halaman kisi-kisi / blueprint digunakan untuk membuat struktur penilaian ujian berdasarkan materi dan bobot.
    </p>

    <ul class="list-disc space-y-2 px-6">
        <li>Gunakan tombol <strong>Tambah</strong> untuk membuat kisi-kisi ujian baru.</li>
        <li>Setiap kisi-kisi harus terhubung ke KD/CP yang relevan.</li>
        <li>Isi kolom <strong>Nama</strong> dan <strong>Deskripsi</strong> agar kisi-kisi mudah dikenali oleh guru dan admin.</li>
        <li>Gunakan relation manager untuk menambahkan baris kisi-kisi dan mengatur bobot atau kategori.</li>
    </ul>

    <p>
        Setelah dibuat, kisi-kisi dapat digunakan untuk mencetak blueprint dan men-generate soal sesuai komposisi materi.
    </p>
</div>
