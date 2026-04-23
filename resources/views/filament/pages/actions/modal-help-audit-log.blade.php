@props([
    'pageTitle',
])

<div class="space-y-4 text-sm text-gray-700 dark:text-gray-200">
    <p>
        Ini adalah panduan untuk halaman <strong>{{ $pageTitle }}</strong>.
        Halaman audit log mencatat semua aktivitas penting di sistem, termasuk login, aksi pengguna, dan perubahan data.
    </p>

    <ul class="list-disc space-y-2 px-6">
        <li>Gunakan filter untuk menemukan aktivitas berdasarkan user, aksi, atau rentang tanggal.</li>
        <li>Klik ekspor untuk mengunduh log dalam format Excel sebagai bukti audit atau dokumentasi.</li>
        <li>Audit log hanya dapat diakses oleh Super Admin untuk menjaga keamanan dan integritas data.</li>
    </ul>

    <p>
        Catatan ini berguna untuk pelacakan kejadian, investigasi kecurangan, dan pemeriksaan administrasi sistem.
    </p>
</div>
