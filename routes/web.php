<?php

use App\Http\Controllers\Admin\DashboardGuruController;
use App\Http\Controllers\Admin\KalenderController;
use App\Http\Controllers\Admin\MonitorController;
use App\Http\Controllers\Admin\PrintController;
use App\Http\Controllers\PdfController;
use App\Http\Controllers\Admin\SessionNoteController;
use App\Http\Controllers\Admin\SoalController;
use App\Http\Controllers\Peserta\DashboardController;
use App\Http\Controllers\Peserta\LivescoreController;
use App\Http\Controllers\Peserta\PortofolioController;
use App\Http\Controllers\Peserta\UjianController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Admin routes (level >= 3 handled by Filament canAccessPanel)
Route::middleware(['auth', 'check.level:3'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/soal/search', [SoalController::class, 'search'])->name('soal.search');
});

// Monitor & Dashboard Guru routes (level >= 2: Guru dan Admin)
Route::middleware(['auth', 'check.level:2'])->prefix('cabt')->name('cabt.')->group(function () {
    Route::get('/sesi/{session}/monitor/data',              [MonitorController::class, 'data'])->name('monitor.data');
    Route::post('/sesi/{session}/paksa-keluar/{userId}',   [MonitorController::class, 'paksaKeluar'])->name('monitor.paksa-keluar');
    Route::post('/sesi/{session}/catatan',                 [SessionNoteController::class, 'store'])->name('session.catatan.store');
    Route::get('/dashboard-guru/{session}/{rombel}/export', [DashboardGuruController::class, 'exportRombel'])->name('guru.rombel.export');
    Route::get('/kalender/data',                           [KalenderController::class, 'data'])->name('kalender.data');
});

// Livescore — akses dikontrol via AppSetting (show_livescore & livescore_public)
Route::prefix('sesi')->name('livescore.')->group(function () {
    Route::get('/{session}/livescore',       [LivescoreController::class, 'show'])->name('show');
    Route::get('/{session}/livescore/data',  [LivescoreController::class, 'data'])->name('data');
});

// Laporan print routes (level >= 2: Guru dan Admin)
Route::middleware(['auth', 'check.level:2'])->prefix('cabt/laporan')->name('laporan.')->group(function () {
    Route::get('/sesi/{session}/cetak/nilai',         [PrintController::class, 'nilai'])->name('cetak.nilai');
    Route::get('/sesi/{session}/cetak/daftar-hadir',  [PrintController::class, 'daftarHadir'])->name('cetak.daftar-hadir');
    Route::get('/sesi/{session}/cetak/berita-acara',  [PrintController::class, 'beritaAcara'])->name('cetak.berita-acara');

    // PDF download routes
    Route::get('/sesi/{session}/pdf/nilai',          [PdfController::class, 'nilai'])->name('pdf.nilai');
    Route::get('/sesi/{session}/pdf/daftar-hadir',   [PdfController::class, 'daftarHadir'])->name('pdf.daftar-hadir');
    Route::get('/sesi/{session}/pdf/berita-acara',   [PdfController::class, 'beritaAcara'])->name('pdf.berita-acara');
    Route::get('/sesi/{session}/pdf/kartu-peserta',  [PdfController::class, 'kartupeserta'])->name('pdf.kartu-peserta');
});

// Blueprint kisi-kisi print (level >= 2)
Route::middleware(['auth', 'check.level:2'])
    ->get('/cabt/blueprint/{blueprint}/cetak', [PrintController::class, 'blueprint'])
    ->name('blueprint.cetak');

// Serve private URAIAN files — owner (peserta) or admin/guru (level >= 2)
Route::middleware('auth')->get('/file/uraian/{attemptId}/{filename}', [UjianController::class, 'serveFile'])
    ->name('ujian.file.uraian')
    ->where('filename', '[^/]+');

// Peserta routes (level 1 = peserta)
Route::middleware(['auth', 'check.level:1', 'check.maintenance', 'check.session.timeout'])->group(function () {
    Route::get('/peserta', [DashboardController::class, 'index'])->name('peserta.dashboard');
    Route::get('/peserta/portofolio', [PortofolioController::class, 'index'])->name('peserta.portofolio');

    Route::prefix('ujian')->name('ujian.')->middleware('check.ip.whitelist')->group(function () {
        Route::get('/{sesiId}', [UjianController::class, 'show'])->name('show');
        Route::post('/{sesiId}/mulai', [UjianController::class, 'mulai'])->name('mulai');
        Route::get('/{sesiId}/kerjakan', [UjianController::class, 'kerjakan'])->name('kerjakan');
        Route::post('/jawab', [UjianController::class, 'jawab'])->name('jawab')->middleware('throttle:120,1');
        Route::post('/upload-file', [UjianController::class, 'uploadFile'])->name('upload-file');
        Route::get('/{attemptId}/status', [UjianController::class, 'status'])->name('status');
        Route::post('/{attemptId}/log', [UjianController::class, 'log'])->name('log');
        Route::post('/{attemptId}/submit', [UjianController::class, 'submit'])->name('submit');
        Route::post('/{attemptId}/seksi/{sectionId}/selesai', [UjianController::class, 'selesaikanSeksi'])->name('seksi.selesai');
        Route::get('/{attemptId}/hasil', [UjianController::class, 'hasil'])->name('hasil');
        Route::get('/{attemptId}/review', [UjianController::class, 'review'])->name('review');
        Route::post('/audio/{questionId}/play', [UjianController::class, 'audioPlay'])->name('audio.play')->middleware('throttle:300,1');
    });
});

require __DIR__ . '/auth.php';
