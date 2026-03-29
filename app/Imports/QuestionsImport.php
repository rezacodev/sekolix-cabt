<?php

namespace App\Imports;

use App\Models\Category;
use App\Models\Question;
use App\Models\QuestionKeyword;
use App\Models\QuestionMatch;
use App\Models\QuestionOption;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class QuestionsImport implements ToCollection, WithHeadingRow
{
    public array $errors = [];
    public int $imported = 0;

    private array $validTipe = ['PG', 'PG_BOBOT', 'PGJ', 'JODOH', 'ISIAN', 'URAIAN'];
    private array $validKesulitan = ['mudah', 'sedang', 'sulit'];

    public function collection(Collection $rows): void
    {
        // Cache kategori nama → id
        $kategoriMap = Category::pluck('id', 'nama');

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            $data = [
                'tipe_soal'    => strtoupper(trim($row['tipe_soal'] ?? '')),
                'teks_soal'    => trim($row['teks_soal'] ?? ''),
                'kategori'     => trim($row['kategori'] ?? ''),
                'kesulitan'    => strtolower(trim($row['kesulitan'] ?? 'sedang')),
                'bobot'        => $row['bobot'] ?? 1,
                'opsi_a'       => trim($row['opsi_a'] ?? ''),
                'opsi_b'       => trim($row['opsi_b'] ?? ''),
                'opsi_c'       => trim($row['opsi_c'] ?? ''),
                'opsi_d'       => trim($row['opsi_d'] ?? ''),
                'opsi_e'       => trim($row['opsi_e'] ?? ''),
                'kunci'        => strtoupper(trim($row['kunci'] ?? '')),
            ];

            $validator = Validator::make($data, [
                'tipe_soal' => ['required', 'in:' . implode(',', $this->validTipe)],
                'teks_soal' => ['required', 'string'],
                'kesulitan' => ['required', 'in:' . implode(',', $this->validKesulitan)],
                'bobot'     => ['numeric', 'min:0'],
            ]);

            if ($validator->fails()) {
                $this->errors[] = "Baris {$rowNumber}: " . implode(' | ', $validator->errors()->all());
                continue;
            }

            // Resolve kategori_id
            $kategoriId = null;
            if (!empty($data['kategori'])) {
                $kategoriId = $kategoriMap->get($data['kategori']);
                if (!$kategoriId) {
                    // Buat kategori baru jika belum ada
                    $cat = Category::create(['nama' => $data['kategori']]);
                    $kategoriId = $cat->id;
                    $kategoriMap->put($data['kategori'], $kategoriId);
                }
            }

            $question = Question::create([
                'kategori_id'       => $kategoriId,
                'tipe'              => $data['tipe_soal'],
                'teks_soal'         => $data['teks_soal'],
                'tingkat_kesulitan' => $data['kesulitan'],
                'bobot'             => $data['bobot'],
                'aktif'             => true,
                'created_by'        => Auth::id(),
            ]);

            // Buat opsi jawaban untuk tipe PG/PG_BOBOT/PGJ
            if (in_array($data['tipe_soal'], ['PG', 'PG_BOBOT', 'PGJ'])) {
                $kunciArr = array_map('trim', explode(',', $data['kunci']));
                $opsiMap  = [
                    'A' => $data['opsi_a'],
                    'B' => $data['opsi_b'],
                    'C' => $data['opsi_c'],
                    'D' => $data['opsi_d'],
                    'E' => $data['opsi_e'],
                ];
                $urutan = 0;
                foreach ($opsiMap as $kode => $teks) {
                    if (empty($teks)) {
                        continue;
                    }
                    QuestionOption::create([
                        'question_id' => $question->id,
                        'kode_opsi'   => $kode,
                        'teks_opsi'   => $teks,
                        'is_correct'  => in_array($kode, $kunciArr),
                        'bobot_persen' => 100,
                        'urutan'      => $urutan++,
                        'aktif'       => true,
                    ]);
                }
            }

            // Buat kata kunci untuk tipe ISIAN
            if ($data['tipe_soal'] === 'ISIAN' && !empty($data['kunci'])) {
                $keywords = array_map('trim', explode(',', $data['kunci']));
                foreach ($keywords as $kw) {
                    if (!empty($kw)) {
                        QuestionKeyword::create([
                            'question_id' => $question->id,
                            'keyword'     => $kw,
                        ]);
                    }
                }
            }

            $this->imported++;
        }
    }
}
