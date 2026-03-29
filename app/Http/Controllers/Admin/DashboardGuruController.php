<?php

namespace App\Http\Controllers\Admin;

use App\Exports\RombelNilaiExport;
use App\Http\Controllers\Controller;
use App\Models\ExamSession;
use App\Models\Rombel;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class DashboardGuruController extends Controller
{
    // ── GET /cabt/dashboard-guru/{session}/{rombel}/export ───────────────────
    public function exportRombel(ExamSession $session, Rombel $rombel)
    {
        $user = Auth::user();

        // Guru can only export their own sessions
        if ($user->level === User::LEVEL_GURU && $session->created_by !== $user->id) {
            abort(403);
        }

        $filename = 'nilai-' . str($rombel->nama)->slug() . '-' . str($session->nama_sesi)->slug() . '.xlsx';

        return Excel::download(new RombelNilaiExport($session, $rombel), $filename);
    }
}
