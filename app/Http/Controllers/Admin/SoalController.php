<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Question;
use Illuminate\Http\Request;

class SoalController extends Controller
{
    /**
     * Search soal untuk digunakan oleh PaketResource (AJAX).
     * GET /admin/soal/search
     */
    public function search(Request $request)
    {
        $query = Question::query()->aktif();

        if ($search = $request->get('q')) {
            $query->where('teks_soal', 'like', "%{$search}%");
        }

        if ($tipe = $request->get('tipe')) {
            $query->where('tipe', $tipe);
        }

        if ($kategoriId = $request->get('kategori_id')) {
            $query->where('kategori_id', $kategoriId);
        }

        if ($kesulitan = $request->get('kesulitan')) {
            $query->where('tingkat_kesulitan', $kesulitan);
        }

        $soal = $query->with('category')
            ->select('id', 'tipe', 'teks_soal', 'tingkat_kesulitan', 'bobot', 'kategori_id')
            ->limit(50)
            ->get()
            ->map(fn ($q) => [
                'id'          => $q->id,
                'tipe'        => $q->tipe,
                'tipe_label'  => Question::TIPE_LABELS[$q->tipe] ?? $q->tipe,
                'teks_soal'   => strip_tags($q->teks_soal),
                'kesulitan'   => $q->tingkat_kesulitan,
                'bobot'       => $q->bobot,
                'kategori'    => $q->category?->nama,
            ]);

        return response()->json($soal);
    }
}
