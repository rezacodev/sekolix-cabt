<?php

namespace App\Imports;

use App\Models\Category;
use App\Models\MataPelajaran;
use App\Models\Question;
use App\Models\QuestionKeyword;
use App\Models\QuestionMatch;
use App\Models\QuestionOption;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Facades\Excel;

class QuestionsImport implements ToCollection, WithHeadingRow
{
    public array $errors = [];
    public int $imported = 0;

    private array $validTipe = ['PG', 'PG_BOBOT', 'PGJ', 'JODOH', 'ISIAN', 'URAIAN', 'BS', 'CLOZE'];
    private array $validKesulitan = ['mudah', 'sedang', 'sulit'];

    // ── Parse tanpa tulis ke DB (untuk preview) ───────────────────────

    /**
     * Baca file Excel dan kembalikan array baris dengan status valid/invalid
     * tanpa menulis apapun ke database.
     *
     * @return array<int, array{row: array, row_num: int, valid: bool, errors: string[]}>
     */
    public static function parseForPreview(string $filePath): array
    {
        $validTipe      = ['PG', 'PG_BOBOT', 'PGJ', 'JODOH', 'ISIAN', 'URAIAN', 'BS', 'CLOZE'];
        $validKesulitan = ['mudah', 'sedang', 'sulit'];
        $result         = [];

        try {
            $rows = Excel::toCollection(new self(), $filePath)->first() ?? collect();
        } catch (\Throwable $e) {
            throw new \RuntimeException('Gagal membaca file Excel: ' . $e->getMessage(), 0, $e);
        }

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $rawArr    = $row->toArray();

            // Lewati baris yang seluruh kolomnya kosong (cek sebelum normalisasi)
            $nonEmpty = array_filter($rawArr, fn($v) => $v !== null && trim((string) $v) !== '');
            if (empty($nonEmpty)) {
                continue;
            }

            $data   = self::normalizeRow($rawArr);
            $errors = [];

            // Validasi dasar
            $validator = Validator::make($data, [
                'tipe_soal' => ['required', 'in:' . implode(',', $validTipe)],
                'teks_soal' => ['required', 'string', 'min:3'],
                'kesulitan' => ['required', 'in:' . implode(',', $validKesulitan)],
                'bobot'     => ['nullable', 'numeric', 'min:0'],
            ], [
                'tipe_soal.required' => 'tipe_soal wajib diisi.',
                'tipe_soal.in'       => "tipe_soal harus salah satu dari: " . implode(', ', $validTipe) . ".",
                'teks_soal.required' => 'teks_soal wajib diisi.',
                'teks_soal.min'      => 'teks_soal terlalu pendek.',
                'kesulitan.required' => 'kesulitan wajib diisi.',
                'kesulitan.in'       => 'kesulitan harus: mudah, sedang, atau sulit.',
                'bobot.numeric'      => 'bobot harus berupa angka.',
                'bobot.min'          => 'bobot tidak boleh negatif.',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $msg) {
                    $errors[] = $msg;
                }
            }

            // Validasi aturan per tipe soal (hanya jika tipe valid)
            if (empty($errors) || ! $validator->errors()->has('tipe_soal')) {
                $tipe  = strtoupper(trim($data['tipe_soal'] ?? ''));
                $kunci = strtoupper(trim($data['kunci'] ?? ''));

                if (in_array($tipe, ['PG', 'PG_BOBOT', 'PGJ'])) {
                    if (empty($data['opsi_a']) || empty($data['opsi_b'])) {
                        $errors[] = "Tipe {$tipe}: opsi_a dan opsi_b wajib diisi.";
                    }
                    if (empty($kunci)) {
                        $errors[] = "Tipe {$tipe}: kolom kunci wajib diisi (huruf opsi, misal: A atau A,C).";
                    } else {
                        $kunciArr = array_map('trim', explode(',', $kunci));
                        $validHuruf = ['A', 'B', 'C', 'D', 'E'];
                        foreach ($kunciArr as $k) {
                            if (! in_array($k, $validHuruf)) {
                                $errors[] = "Tipe {$tipe}: kunci '{$k}' tidak valid. Gunakan A–E.";
                            }
                        }
                    }
                }

                if ($tipe === 'BS') {
                    if (! in_array($kunci, ['B', 'S'])) {
                        $errors[] = "Tipe BS: kolom kunci harus 'B' (Benar) atau 'S' (Salah).";
                    }
                }

                if ($tipe === 'ISIAN' && empty($kunci)) {
                    $errors[] = "Tipe ISIAN: kolom kunci wajib diisi (kata kunci jawaban).";
                }
            }

            $result[] = [
                'row'     => $data,
                'row_num' => $rowNumber,
                'valid'   => empty($errors),
                'errors'  => $errors,
            ];
        }

        return $result;
    }

    /**
     * Import baris-baris valid yang sudah diparsing, tanpa re-parse file.
     */
    public static function importValidRows(array $parsedRows, int $userId): array
    {
        $imported   = 0;
        $errors     = [];
        $kategoriMap = Category::pluck('id', 'nama');

        foreach ($parsedRows as $item) {
            if (! $item['valid']) {
                continue;
            }

            $data = $item['row'];

            try {
                // Resolve mata_pelajaran_id
                $mapelId = null;
                if (! empty($data['mata_pelajaran'])) {
                    $mapelId = MataPelajaran::where('nama', $data['mata_pelajaran'])->value('id');
                }

                // Resolve/buat kategori
                $kategoriId = null;
                if (! empty($data['kategori'])) {
                    $kategoriId = $kategoriMap->get($data['kategori']);
                    if (! $kategoriId) {
                        $cat = Category::create(['nama' => $data['kategori'], 'mata_pelajaran_id' => $mapelId]);
                        $kategoriId = $cat->id;
                        $kategoriMap->put($data['kategori'], $kategoriId);
                    }
                }

                $question = Question::create([
                    'kategori_id'       => $kategoriId,
                    'tipe'              => $data['tipe_soal'],
                    'teks_soal'         => $data['teks_soal'],
                    'tingkat_kesulitan' => $data['kesulitan'],
                    'bobot'             => $data['bobot'] ?? 1,
                    'aktif'             => true,
                    'created_by'        => $userId,
                    'audio_url'         => $data['audio_url'] ?: null,
                ]);

                self::createRelatedRecords($question, $data);

                $imported++;
            } catch (\Throwable $e) {
                $errors[] = "Baris {$item['row_num']}: " . $e->getMessage();
            }
        }

        return ['imported' => $imported, 'errors' => $errors];
    }

    // ── ToCollection (legacy, digunakan oleh ImportService) ──────────

    public function collection(Collection $rows): void
    {
        $kategoriMap = Category::pluck('id', 'nama');

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $data      = self::normalizeRow($row->toArray());

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
                    $mapelId = ! empty($data['mata_pelajaran'])
                        ? MataPelajaran::where('nama', $data['mata_pelajaran'])->value('id')
                        : null;
                    $cat = Category::create(['nama' => $data['kategori'], 'mata_pelajaran_id' => $mapelId]);
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
                'audio_url'         => $data['audio_url'] ?: null,
            ]);

            self::createRelatedRecords($question, $data);

            $this->imported++;
        }
    }

    // ── Helper: buat opsi/kunci/keyword dari data baris ──────────────

    private static function normalizeRow(array $row): array
    {
        return [
            'tipe_soal'      => strtoupper(trim($row['tipe_soal'] ?? '')),
            'teks_soal'      => trim($row['teks_soal'] ?? ''),
            'mata_pelajaran' => trim($row['mata_pelajaran'] ?? ''),
            'kategori'       => trim($row['kategori'] ?? ''),
            'kesulitan'      => strtolower(trim($row['kesulitan'] ?? 'sedang')),
            'bobot'          => $row['bobot'] ?? 1,
            'opsi_a'         => trim($row['opsi_a'] ?? ''),
            'opsi_b'         => trim($row['opsi_b'] ?? ''),
            'opsi_c'         => trim($row['opsi_c'] ?? ''),
            'opsi_d'         => trim($row['opsi_d'] ?? ''),
            'opsi_e'         => trim($row['opsi_e'] ?? ''),
            'kunci'          => strtoupper(trim($row['kunci'] ?? '')),
            'audio_url'      => trim($row['audio_url'] ?? ''),
        ];
    }

    private static function createRelatedRecords(Question $question, array $data): void
    {
        $tipe  = $data['tipe_soal'];
        $kunci = $data['kunci'];

        // Opsi untuk PG/PG_BOBOT/PGJ
        if (in_array($tipe, ['PG', 'PG_BOBOT', 'PGJ'])) {
            $kunciArr = array_map('trim', explode(',', $kunci));
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
                    'question_id'  => $question->id,
                    'kode_opsi'    => $kode,
                    'teks_opsi'    => $teks,
                    'is_correct'   => in_array($kode, $kunciArr),
                    'bobot_persen' => 100,
                    'urutan'       => $urutan++,
                    'aktif'        => true,
                ]);
            }
        }

        // Opsi B/S untuk tipe BS
        if ($tipe === 'BS') {
            QuestionOption::create([
                'question_id'  => $question->id,
                'kode_opsi'    => 'B',
                'teks_opsi'    => 'Benar',
                'is_correct'   => $kunci === 'B',
                'bobot_persen' => 100,
                'urutan'       => 0,
                'aktif'        => true,
            ]);
            QuestionOption::create([
                'question_id'  => $question->id,
                'kode_opsi'    => 'S',
                'teks_opsi'    => 'Salah',
                'is_correct'   => $kunci === 'S',
                'bobot_persen' => 100,
                'urutan'       => 1,
                'aktif'        => true,
            ]);
        }

        // Keyword untuk ISIAN
        if ($tipe === 'ISIAN' && ! empty($kunci)) {
            foreach (array_map('trim', explode(',', $kunci)) as $kw) {
                if (! empty($kw)) {
                    QuestionKeyword::create([
                        'question_id' => $question->id,
                        'keyword'     => $kw,
                    ]);
                }
            }
        }
    }
}
