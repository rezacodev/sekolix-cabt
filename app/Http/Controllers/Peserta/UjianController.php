<?php

namespace App\Http\Controllers\Peserta;

use App\Http\Controllers\Controller;
use App\Http\Requests\JawabSoalRequest;
use App\Models\AttemptLog;
use App\Models\AttemptQuestion;
use App\Models\ExamAttempt;
use App\Models\ExamSession;
use App\Models\ExamSessionParticipant;
use App\Services\ExamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class UjianController extends Controller
{
    public function __construct(
        private readonly ExamService $examService
    ) {}

    // ─── Halaman konfirmasi ────────────────────────────────────────────────

    public function show(Request $request, int $sesiId): \Illuminate\View\View|RedirectResponse
    {
        $session = ExamSession::with('package')->findOrFail($sesiId);

        // Cek peserta terdaftar
        $participant = ExamSessionParticipant::where('exam_session_id', $sesiId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        // Cek jika ada attempt berlangsung → langsung ke halaman kerjakan
        $activeAttempt = ExamAttempt::where('exam_session_id', $sesiId)
            ->where('user_id', $request->user()->id)
            ->where('status', ExamAttempt::STATUS_BERLANGSUNG)
            ->first();

        if ($activeAttempt) {
            return redirect()->route('ujian.kerjakan', $sesiId);
        }

        $attemptCount = ExamAttempt::where('exam_session_id', $sesiId)
            ->where('user_id', $request->user()->id)
            ->count();

        $package     = $session->package;
        $sisaAttempt = $package->max_pengulangan == 0
            ? null
            : max(0, $package->max_pengulangan - $attemptCount);

        // Batas percobaan habis
        if ($sisaAttempt !== null && $sisaAttempt === 0) {
            return redirect()->route('peserta.dashboard')
                ->withErrors(['attempt' => 'Batas percobaan ujian telah habis.']);
        }

        return view('peserta.konfirmasi', compact('session', 'participant', 'attemptCount', 'sisaAttempt'));
    }

    // ─── Mulai ujian ──────────────────────────────────────────────────────

    public function mulai(Request $request, int $sesiId): RedirectResponse
    {
        $session = ExamSession::with('package')->findOrFail($sesiId);

        // Validasi token jika ada
        if ($session->token_akses) {
            $request->validate([
                'token' => ['required', 'string'],
            ]);

            if (strtolower((string) $request->input('token')) !== strtolower((string) $session->token_akses)) {
                return back()->withErrors(['token' => 'Token akses tidak valid.'])->withInput();
            }
        }

        try {
            $this->examService->mulai($sesiId, $request->user()->id);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('ujian.kerjakan', $sesiId);
    }

    // ─── Halaman pengerjaan ujian ──────────────────────────────────────────

    public function kerjakan(Request $request, int $sesiId): \Illuminate\View\View|RedirectResponse
    {
        $userId = $request->user()->id;

        $attempt = ExamAttempt::where('exam_session_id', $sesiId)
            ->where('user_id', $userId)
            ->where('status', ExamAttempt::STATUS_BERLANGSUNG)
            ->first();

        if (! $attempt) {
            return redirect()->route('ujian.show', $sesiId)
                ->withErrors(['attempt' => 'Tidak ada attempt aktif. Silakan mulai ujian terlebih dahulu.']);
        }

        // Cek waktu habis sebelum tampil — auto-submit server-side
        if ($attempt->sisaWaktuDetik() <= 0) {
            try {
                $this->examService->autoSubmit($attempt->id, \App\Models\AttemptLog::EVENT_TIMEOUT);
            } catch (\Throwable) {}

            $package = $attempt->session->package;
            if ($package->tampilkan_hasil) {
                return redirect()->route('ujian.hasil', $attempt->id)
                    ->with('info', 'Waktu ujian telah habis.');
            }
            return redirect()->route('peserta.dashboard')
                ->with('info', 'Waktu ujian telah habis. Ujian telah dikumpulkan.');
        }

        // Load soal beserta relasi yang diperlukan untuk tampil
        $soalList = AttemptQuestion::with([
                'question.options',
                'question.matches',
                'question.keywords',
            ])
            ->where('attempt_id', $attempt->id)
            ->orderBy('urutan')
            ->get();

        $session = $attempt->session()->with('package')->first();

        $maxTabSwitch       = \App\Models\AppSetting::getInt('max_tab_switch', 3);
        $tabSwitchAction    = \App\Models\AppSetting::getString('tab_switch_action', 'warn');
        $autoSubmitOnMaxTab = \App\Models\AppSetting::getBool('auto_submit_on_max_tab', true);
        $preventCopyPaste   = \App\Models\AppSetting::getBool('prevent_copy_paste', false);
        $preventRightClick  = \App\Models\AppSetting::getBool('prevent_right_click', false);
        $requireFullscreen  = \App\Models\AppSetting::getBool('require_fullscreen', false);

        return view('peserta.ujian', compact(
            'attempt', 'soalList', 'session', 'maxTabSwitch',
            'tabSwitchAction', 'autoSubmitOnMaxTab', 'preventCopyPaste', 'preventRightClick', 'requireFullscreen'
        ));
    }

    // ─── Simpan jawaban (AJAX) ─────────────────────────────────────────────

    public function jawab(JawabSoalRequest $request): JsonResponse
    {
        // IDOR prevention: verify the attempt belongs to the authenticated user
        $attemptId = $request->integer('attempt_id');
        $attempt   = ExamAttempt::where('id', $attemptId)
            ->where('user_id', $request->user()->id)
            ->where('status', ExamAttempt::STATUS_BERLANGSUNG)
            ->first();

        if (! $attempt) {
            return response()->json(['success' => false, 'message' => 'Attempt tidak valid.'], 403);
        }

        try {
            $aq = $this->examService->simpanJawaban(
                attemptId:  $attemptId,
                questionId: $request->integer('question_id'),
                jawaban:    $request->input('jawaban'),
                isRagu:     (bool) $request->input('is_ragu', false),
            );

            $attempt->load('questions');
            $total    = $attempt->questions()->count();
            $terjawab = $attempt->questions->filter(fn ($q) => $q->isDijawab())->count();

            return response()->json([
                'success'         => true,
                'soal_terjawab'   => $terjawab,
                'total'           => $total,
                'sisa_waktu_detik' => max(0, $attempt->sisaWaktuDetik()),
                'status'          => $attempt->status,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors'  => $e->errors(),
            ], 422);
        }
    }

    // ─── Status / sisa waktu (AJAX) ────────────────────────────────────────

    public function status(Request $request, int $attemptId): JsonResponse
    {
        // IDOR prevention: verify the attempt belongs to the authenticated user
        $owned = ExamAttempt::where('id', $attemptId)
            ->where('user_id', $request->user()->id)
            ->exists();

        if (! $owned) {
            return response()->json(['success' => false, 'message' => 'Attempt tidak ditemukan.'], 403);
        }

        try {
            $data = $this->examService->validasiWaktu($attemptId);

            // Tentukan URL redirect setelah waktu habis
            $redirectUrl = null;
            if (($data['sisa_detik'] ?? 1) <= 0) {
                $attempt = ExamAttempt::with('session.package')->find($attemptId);
                $redirectUrl = ($attempt?->session?->package?->tampilkan_hasil)
                    ? route('ujian.hasil', $attemptId)
                    : route('peserta.dashboard');
            }

            return response()->json(array_merge(['success' => true, 'redirect_url' => $redirectUrl], $data));
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Attempt tidak ditemukan.'], 404);
        }
    }

    // ─── Log tab switch (AJAX) ─────────────────────────────────────────────

    public function log(Request $request, int $attemptId): JsonResponse
    {
        $attempt = ExamAttempt::where('id', $attemptId)
            ->where('user_id', $request->user()->id)
            ->where('status', ExamAttempt::STATUS_BERLANGSUNG)
            ->first();

        if (! $attempt) {
            return response()->json(['success' => false], 404);
        }

        // Catat tab switch ke DB (server-side authoritative)
        $this->examService->catatLog($attemptId, AttemptLog::EVENT_TAB_SWITCH, $request->input('detail'));

        // Hitung dari DB — tidak mempercayai counter dari JS
        $tabCount = $attempt->tabSwitchCount();
        $maxTab   = \App\Models\AppSetting::getInt('max_tab_switch', 3);
        $action   = \App\Models\AppSetting::getString('tab_switch_action', 'warn');
        $autoSub  = \App\Models\AppSetting::getBool('auto_submit_on_max_tab', true);

        $submitted = false;
        if ($tabCount >= $maxTab && ($autoSub || $action === 'submit')) {
            try {
                $this->examService->autoSubmit($attemptId, AttemptLog::EVENT_TAB_SWITCH);
                $submitted = true;
            } catch (\Throwable) {}
        }

        return response()->json([
            'success'          => true,
            'tab_switch_count' => $tabCount,
            'submitted'        => $submitted,
        ]);
    }

    // ─── Submit manual ────────────────────────────────────────────────────

    public function submit(Request $request, int $attemptId): RedirectResponse
    {
        $attempt = ExamAttempt::where('id', $attemptId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        try {
            $this->examService->submit($attemptId);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        $attempt  = ExamAttempt::with('session.package')->find($attemptId);
        $package  = $attempt?->session?->package;

        if ($package?->tampilkan_hasil) {
            return redirect()->route('ujian.hasil', $attemptId);
        }

        return redirect()->route('peserta.dashboard')
            ->with('success', 'Ujian berhasil dikumpulkan.');
    }

    // ─── Halaman hasil ────────────────────────────────────────────────────

    public function hasil(Request $request, int $attemptId): \Illuminate\View\View
    {
        $attempt = ExamAttempt::with(['session.package', 'questions.question'])
            ->where('id', $attemptId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        abort_unless($attempt->isSelesai(), 403, 'Ujian belum selesai.');

        // Tampilkan hasil hanya jika paket mengizinkan
        abort_unless($attempt->session->package->tampilkan_hasil, 403, 'Hasil ujian tidak ditampilkan.');

        $soalList = $attempt->questions()->with('question')->orderBy('urutan')->get();

        $ranking = null;
        if (\App\Models\AppSetting::getBool('show_ranking_hasil', false)) {
            $myRank = ExamAttempt::where('exam_session_id', $attempt->exam_session_id)
                ->whereNotNull('nilai_akhir')
                ->where('nilai_akhir', '>', $attempt->nilai_akhir ?? 0)
                ->count() + 1;
            $totalPeserta = ExamAttempt::where('exam_session_id', $attempt->exam_session_id)
                ->whereNotNull('nilai_akhir')
                ->count();
            $ranking = ['rank' => $myRank, 'total' => $totalPeserta];
        }

        return view('peserta.hasil', compact('attempt', 'soalList', 'ranking'));
    }

    // ─── Review jawaban ───────────────────────────────────────────────────

    public function review(Request $request, int $attemptId): \Illuminate\View\View
    {
        $attempt = ExamAttempt::with(['session.package', 'questions.question.options', 'questions.question.matches', 'questions.question.keywords'])
            ->where('id', $attemptId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        abort_unless($attempt->isSelesai(), 403, 'Ujian belum selesai.');
        abort_unless($attempt->session->package->tampilkan_review, 403, 'Review jawaban tidak diizinkan untuk paket ini.');

        $soalList = $attempt->questions()->with([
                'question.options',
                'question.matches',
                'question.keywords',
            ])->orderBy('urutan')->get();

        $showPembahasan = true;
        if (\App\Models\AppSetting::getBool('show_pembahasan_setelah_sesi', false)) {
            $showPembahasan = $attempt->session->status === \App\Models\ExamSession::STATUS_SELESAI;
        }

        return view('peserta.review', compact('attempt', 'soalList', 'showPembahasan'));
    }

    // ─── Upload file URAIAN (AJAX) ─────────────────────────────────────────

    public function uploadFile(Request $request): JsonResponse
    {
        $request->validate([
            'attempt_id'  => ['required', 'integer', 'exists:exam_attempts,id'],
            'question_id' => ['required', 'integer', 'exists:questions,id'],
            'file'        => ['required', 'file', 'max:' . (\App\Models\AppSetting::getInt('max_upload_mb', 5) * 1024), 'mimes:pdf,jpg,jpeg,png'],
        ]);

        $attempt = ExamAttempt::where('id', $request->integer('attempt_id'))
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        abort_unless($attempt->isBerlangsung(), 403, 'Attempt sudah selesai.');

        $path = $request->file('file')->store(
            "uraian/{$attempt->id}",
            'local'  // private disk — not publicly accessible via /storage
        );

        $aq = AttemptQuestion::where('attempt_id', $attempt->id)
            ->where('question_id', $request->integer('question_id'))
            ->firstOrFail();

        // Hapus file lama jika ada
        if ($aq->jawaban_file) {
            Storage::disk('local')->delete($aq->jawaban_file);
        }

        $aq->update([
            'jawaban_file' => $path,
            'waktu_jawab'  => now(),
        ]);

        return response()->json([
            'success' => true,
            'path'    => $path,
            'url'     => route('ujian.file.uraian', [
                'attemptId' => $attempt->id,
                'filename'  => basename($path),
            ]),
        ]);
    }

    // ─── Serve private URAIAN file ────────────────────────────────────────

    public function serveFile(Request $request, int $attemptId, string $filename): \Illuminate\Http\Response|\Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        // Prevent path traversal
        $filename = basename($filename);

        $attempt = ExamAttempt::findOrFail($attemptId);

        // Access control: attempt owner (peserta) or admin/guru (level >= 2)
        $user = $request->user();
        if ($attempt->user_id !== $user->id && $user->level < 2) {
            abort(403);
        }

        // New private disk path (uraian/)
        $privatePath = "uraian/{$attemptId}/{$filename}";
        if (Storage::disk('local')->exists($privatePath)) {
            return response()->file(Storage::disk('local')->path($privatePath));
        }

        // Legacy public disk path for data uploaded before migration (jawaban/)
        $legacyPath = "jawaban/{$attemptId}/{$filename}";
        if (Storage::disk('public')->exists($legacyPath)) {
            return response()->file(Storage::disk('public')->path($legacyPath));
        }

        abort(404);
    }
}
