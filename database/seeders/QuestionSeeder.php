<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Question;
use App\Models\QuestionKeyword;
use App\Models\QuestionMatch;
use App\Models\QuestionOption;
use App\Models\User;
use Illuminate\Database\Seeder;

class QuestionSeeder extends Seeder
{
    private int $createdBy;

    public function run(): void
    {
        // Gunakan Super Admin sebagai pembuat soal
        $this->createdBy = User::where('level', User::LEVEL_SUPER_ADMIN)->value('id') ?? 1;

        // ─── 1. Buat Struktur Kategori ────────────────────────────────────
        $categories = $this->seedCategories();

        // ─── 2. Buat Soal per Kategori ────────────────────────────────────
        $this->seedMatematika($categories['mtk'], $categories);
        $this->seedBahasaIndonesia($categories['bind'], $categories);
        $this->seedIPA($categories['ipa'], $categories);
        $this->seedSejarah($categories['sej'], $categories);
        $this->seedTIK($categories['tik'], $categories);

        $total = Question::count();
        $this->command->info("QuestionSeeder: {$total} soal berhasil dibuat.");
    }

    // ─────────────────────────────────────────────────────────────────────────
    // KATEGORI
    // ─────────────────────────────────────────────────────────────────────────

    private function seedCategories(): array
    {
        $by = $this->createdBy;

        // Kategori induk
        $mtk   = Category::firstOrCreate(['nama' => 'Matematika'],       ['deskripsi' => 'Soal-soal Matematika SMA',       'created_by' => $by]);
        $bind  = Category::firstOrCreate(['nama' => 'Bahasa Indonesia'],  ['deskripsi' => 'Soal-soal Bahasa Indonesia SMA', 'created_by' => $by]);
        $ipa   = Category::firstOrCreate(['nama' => 'IPA'],               ['deskripsi' => 'Ilmu Pengetahuan Alam',          'created_by' => $by]);
        $sej   = Category::firstOrCreate(['nama' => 'Sejarah'],           ['deskripsi' => 'Sejarah Indonesia & Dunia',      'created_by' => $by]);
        $tik   = Category::firstOrCreate(['nama' => 'TIK'],               ['deskripsi' => 'Teknologi Informasi & Komunikasi','created_by' => $by]);

        // Sub-kategori Matematika
        $mtkAljabar = Category::firstOrCreate(['nama' => 'Aljabar'],      ['parent_id' => $mtk->id, 'deskripsi' => 'Aljabar dasar dan lanjutan', 'created_by' => $by]);
        $mtkGeom    = Category::firstOrCreate(['nama' => 'Geometri'],     ['parent_id' => $mtk->id, 'deskripsi' => 'Bangun datar dan ruang',     'created_by' => $by]);
        $mtkStat    = Category::firstOrCreate(['nama' => 'Statistika'],   ['parent_id' => $mtk->id, 'deskripsi' => 'Statistika dan peluang',     'created_by' => $by]);
        $mtkTrig    = Category::firstOrCreate(['nama' => 'Trigonometri'], ['parent_id' => $mtk->id, 'deskripsi' => 'Fungsi trigonometri',         'created_by' => $by]);

        // Sub-kategori IPA
        $ipaFis = Category::firstOrCreate(['nama' => 'Fisika'],    ['parent_id' => $ipa->id, 'deskripsi' => 'Fisika SMA',  'created_by' => $by]);
        $ipaBio = Category::firstOrCreate(['nama' => 'Biologi'],   ['parent_id' => $ipa->id, 'deskripsi' => 'Biologi SMA', 'created_by' => $by]);
        $ipaKim = Category::firstOrCreate(['nama' => 'Kimia'],     ['parent_id' => $ipa->id, 'deskripsi' => 'Kimia SMA',   'created_by' => $by]);

        // Sub-kategori TIK
        $tikJar = Category::firstOrCreate(['nama' => 'Jaringan Komputer'], ['parent_id' => $tik->id, 'deskripsi' => 'Dasar jaringan',                'created_by' => $by]);
        $tikAlg = Category::firstOrCreate(['nama' => 'Algoritma'],         ['parent_id' => $tik->id, 'deskripsi' => 'Algoritma dan pemrograman',     'created_by' => $by]);

        return compact(
            'mtk', 'bind', 'ipa', 'sej', 'tik',
            'mtkAljabar', 'mtkGeom', 'mtkStat', 'mtkTrig',
            'ipaFis', 'ipaBio', 'ipaKim',
            'tikJar', 'tikAlg',
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HELPER
    // ─────────────────────────────────────────────────────────────────────────

    private function makePG(int $kategoriId, string $teks, array $opsi, string $kunci, string $kesulitan = 'sedang', float $bobot = 1, ?string $penjelasan = null): void
    {
        if (Question::where('teks_soal', $teks)->exists()) {
            return;
        }
        $q = Question::create([
            'kategori_id'       => $kategoriId,
            'tipe'              => Question::TIPE_PG,
            'teks_soal'         => $teks,
            'penjelasan'        => $penjelasan,
            'tingkat_kesulitan' => $kesulitan,
            'bobot'             => $bobot,
            'aktif'             => true,
            'created_by'        => $this->createdBy,
        ]);
        foreach ($opsi as $i => [$kd, $txt]) {
            QuestionOption::create([
                'question_id'  => $q->id,
                'kode_opsi'    => $kd,
                'teks_opsi'    => $txt,
                'is_correct'   => $kd === $kunci,
                'bobot_persen' => 100,
                'urutan'       => $i,
                'aktif'        => true,
            ]);
        }
    }

    private function makePGBobot(int $kategoriId, string $teks, array $opsi, string $kesulitan = 'sedang', float $bobot = 2): void
    {
        // $opsi = [['A', 'teks', bobot_persen, is_correct], ...]
        if (Question::where('teks_soal', $teks)->exists()) {
            return;
        }
        $q = Question::create([
            'kategori_id'       => $kategoriId,
            'tipe'              => Question::TIPE_PG_BOBOT,
            'teks_soal'         => $teks,
            'tingkat_kesulitan' => $kesulitan,
            'bobot'             => $bobot,
            'aktif'             => true,
            'created_by'        => $this->createdBy,
        ]);
        foreach ($opsi as $i => [$kd, $txt, $bobotPersen, $isCorrect]) {
            QuestionOption::create([
                'question_id'  => $q->id,
                'kode_opsi'    => $kd,
                'teks_opsi'    => $txt,
                'is_correct'   => $isCorrect,
                'bobot_persen' => $bobotPersen,
                'urutan'       => $i,
                'aktif'        => true,
            ]);
        }
    }

    private function makePGJ(int $kategoriId, string $teks, array $opsi, array $kunciArr, string $kesulitan = 'sedang', float $bobot = 2, ?string $penjelasan = null): void
    {
        if (Question::where('teks_soal', $teks)->exists()) {
            return;
        }
        $q = Question::create([
            'kategori_id'       => $kategoriId,
            'tipe'              => Question::TIPE_PGJ,
            'teks_soal'         => $teks,
            'penjelasan'        => $penjelasan,
            'tingkat_kesulitan' => $kesulitan,
            'bobot'             => $bobot,
            'aktif'             => true,
            'created_by'        => $this->createdBy,
        ]);
        foreach ($opsi as $i => [$kd, $txt]) {
            QuestionOption::create([
                'question_id'  => $q->id,
                'kode_opsi'    => $kd,
                'teks_opsi'    => $txt,
                'is_correct'   => in_array($kd, $kunciArr),
                'bobot_persen' => 100,
                'urutan'       => $i,
                'aktif'        => true,
            ]);
        }
    }

    private function makeJodoh(int $kategoriId, string $teks, array $pasangan, string $kesulitan = 'sedang', float $bobot = 2): void
    {
        // $pasangan = [['premis', 'respon'], ...]
        if (Question::where('teks_soal', $teks)->exists()) {
            return;
        }
        $q = Question::create([
            'kategori_id'       => $kategoriId,
            'tipe'              => Question::TIPE_JODOH,
            'teks_soal'         => $teks,
            'tingkat_kesulitan' => $kesulitan,
            'bobot'             => $bobot,
            'aktif'             => true,
            'created_by'        => $this->createdBy,
        ]);
        foreach ($pasangan as $i => [$premis, $respon]) {
            QuestionMatch::create([
                'question_id' => $q->id,
                'premis'      => $premis,
                'respon'      => $respon,
                'urutan'      => $i,
            ]);
        }
    }

    private function makeIsian(int $kategoriId, string $teks, array $keywords, string $kesulitan = 'mudah', float $bobot = 1, ?string $penjelasan = null): void
    {
        if (Question::where('teks_soal', $teks)->exists()) {
            return;
        }
        $q = Question::create([
            'kategori_id'       => $kategoriId,
            'tipe'              => Question::TIPE_ISIAN,
            'teks_soal'         => $teks,
            'penjelasan'        => $penjelasan,
            'tingkat_kesulitan' => $kesulitan,
            'bobot'             => $bobot,
            'aktif'             => true,
            'created_by'        => $this->createdBy,
        ]);
        foreach ($keywords as $kw) {
            QuestionKeyword::create(['question_id' => $q->id, 'keyword' => $kw]);
        }
    }

    private function makeUraian(int $kategoriId, string $teks, string $kesulitan = 'sulit', float $bobot = 5, ?string $penjelasan = null): void
    {
        if (Question::where('teks_soal', $teks)->exists()) {
            return;
        }
        Question::create([
            'kategori_id'       => $kategoriId,
            'tipe'              => Question::TIPE_URAIAN,
            'teks_soal'         => $teks,
            'penjelasan'        => $penjelasan,
            'tingkat_kesulitan' => $kesulitan,
            'bobot'             => $bobot,
            'aktif'             => true,
            'created_by'        => $this->createdBy,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // MATEMATIKA
    // ─────────────────────────────────────────────────────────────────────────

    private function seedMatematika(Category $cat, array $cats): void
    {
        $alj = $cats['mtkAljabar']->id;
        $geo = $cats['mtkGeom']->id;
        $sta = $cats['mtkStat']->id;
        $tri = $cats['mtkTrig']->id;

        // --- PG: Aljabar ---
        $this->makePG($alj,
            '<p>Jika <strong>f(x) = 2x² − 3x + 1</strong>, maka nilai f(3) adalah ...</p>',
            [['A','10'], ['B','12'], ['C','14'], ['D','16'], ['E','18']],
            'A', 'mudah', 1,
            '<p>f(3) = 2(9) − 3(3) + 1 = 18 − 9 + 1 = 10</p>'
        );

        $this->makePG($alj,
            '<p>Akar-akar persamaan kuadrat <strong>x² − 5x + 6 = 0</strong> adalah ...</p>',
            [['A','x = 1 dan x = 6'], ['B','x = 2 dan x = 3'], ['C','x = −1 dan x = −6'], ['D','x = −2 dan x = −3'], ['E','x = 2 dan x = −3']],
            'B', 'mudah', 1,
            '<p>x² − 5x + 6 = (x − 2)(x − 3) = 0, sehingga x = 2 atau x = 3</p>'
        );

        $this->makePG($alj,
            '<p>Diketahui deret aritmetika dengan suku pertama <strong>a = 4</strong> dan beda <strong>b = 3</strong>. Suku ke-10 adalah ...</p>',
            [['A','28'], ['B','31'], ['C','34'], ['D','37'], ['E','40']],
            'B', 'sedang', 1,
            '<p>U₁₀ = a + (n−1)b = 4 + 9×3 = 4 + 27 = 31</p>'
        );

        $this->makePG($alj,
            '<p>Nilai dari <strong>log₂ 64</strong> adalah ...</p>',
            [['A','4'], ['B','5'], ['C','6'], ['D','7'], ['E','8']],
            'C', 'mudah', 1,
            '<p>log₂ 64 = log₂ 2⁶ = 6</p>'
        );

        $this->makePG($alj,
            '<p>Jika <strong>2^x = 32</strong>, maka nilai x adalah ...</p>',
            [['A','3'], ['B','4'], ['C','5'], ['D','6'], ['E','7']],
            'C', 'mudah', 1
        );

        $this->makePG($alj,
            '<p>Hasil dari <strong>(3a²b)³</strong> adalah ...</p>',
            [['A','9a⁵b³'], ['B','27a⁵b³'], ['C','9a⁶b³'], ['D','27a⁶b³'], ['E','81a⁶b³']],
            'D', 'sedang', 1
        );

        // --- PG: Geometri ---
        $this->makePG($geo,
            '<p>Luas lingkaran dengan jari-jari <strong>7 cm</strong> adalah ... (π = 22/7)</p>',
            [['A','44 cm²'], ['B','88 cm²'], ['C','154 cm²'], ['D','176 cm²'], ['E','308 cm²']],
            'C', 'mudah', 1,
            '<p>L = πr² = (22/7) × 49 = 154 cm²</p>'
        );

        $this->makePG($geo,
            '<p>Sebuah kubus memiliki panjang rusuk <strong>5 cm</strong>. Volume kubus tersebut adalah ...</p>',
            [['A','25 cm³'], ['B','75 cm³'], ['C','100 cm³'], ['D','125 cm³'], ['E','150 cm³']],
            'D', 'mudah', 1,
            '<p>V = s³ = 5³ = 125 cm³</p>'
        );

        $this->makePG($geo,
            '<p>Diketahui segitiga siku-siku dengan sisi tegak 3 cm dan 4 cm. Panjang sisi miring adalah ...</p>',
            [['A','5 cm'], ['B','6 cm'], ['C','7 cm'], ['D','√25 cm'], ['E','√50 cm']],
            'A', 'mudah', 1,
            '<p>Teorema Pythagoras: c = √(3² + 4²) = √25 = 5 cm</p>'
        );

        $this->makePG($geo,
            '<p>Keliling persegi panjang dengan panjang <strong>12 cm</strong> dan lebar <strong>8 cm</strong> adalah ...</p>',
            [['A','20 cm'], ['B','40 cm'], ['C','96 cm'], ['D','192 cm'], ['E','48 cm']],
            'B', 'mudah', 1
        );

        // --- PG: Statistika ---
        $this->makePG($sta,
            '<p>Data berikut: 4, 6, 8, 8, 10. Nilai <strong>rata-rata (mean)</strong> data tersebut adalah ...</p>',
            [['A','6'], ['B','7'], ['C','7,2'], ['D','8'], ['E','8,5']],
            'C', 'mudah', 1,
            '<p>Mean = (4+6+8+8+10)/5 = 36/5 = 7,2</p>'
        );

        $this->makePG($sta,
            '<p>Dari data 5, 7, 7, 9, 11, nilai <strong>modus</strong> adalah ...</p>',
            [['A','5'], ['B','7'], ['C','8'], ['D','9'], ['E','11']],
            'B', 'mudah', 1,
            '<p>Modus adalah nilai yang paling sering muncul. Nilai 7 muncul 2 kali.</p>'
        );

        $this->makePG($sta,
            '<p>Dari data 3, 5, 7, 9, 11, nilai <strong>median</strong> adalah ...</p>',
            [['A','5'], ['B','6'], ['C','7'], ['D','8'], ['E','9']],
            'C', 'mudah', 1,
            '<p>Data sudah terurut, n=5. Median = data ke-(5+1)/2 = data ke-3 = 7</p>'
        );

        $this->makePG($sta,
            '<p>Pada pelemparan dua dadu, peluang mendapatkan jumlah mata dadu <strong>7</strong> adalah ...</p>',
            [['A','1/36'], ['B','2/36'], ['C','5/36'], ['D','6/36'], ['E','7/36']],
            'D', 'sedang', 1,
            '<p>Pasangan yang berjumlah 7: (1,6),(2,5),(3,4),(4,3),(5,2),(6,1) = 6 kejadian. P = 6/36 = 1/6</p>'
        );

        // --- PG: Trigonometri ---
        $this->makePG($tri,
            '<p>Nilai dari <strong>sin 30°</strong> adalah ...</p>',
            [['A','0'], ['B','1/2'], ['C','√2/2'], ['D','√3/2'], ['E','1']],
            'B', 'mudah', 1
        );

        $this->makePG($tri,
            '<p>Nilai dari <strong>cos 60°</strong> adalah ...</p>',
            [['A','0'], ['B','1/2'], ['C','√2/2'], ['D','√3/2'], ['E','1']],
            'B', 'mudah', 1
        );

        $this->makePG($tri,
            '<p>Nilai dari <strong>tan 45°</strong> adalah ...</p>',
            [['A','0'], ['B','1/2'], ['C','√2/2'], ['D','√3/2'], ['E','1']],
            'E', 'mudah', 1
        );

        // --- PGJ: Matematika ---
        $this->makePGJ($alj,
            '<p>Manakah berikut ini yang merupakan bilangan prima?</p>',
            [['A','1'], ['B','2'], ['C','3'], ['D','9'], ['E','11']],
            ['B', 'C', 'E'], 'sedang', 2,
            '<p>Bilangan prima adalah bilangan yang hanya habis dibagi 1 dan dirinya sendiri. 1 bukan prima. 9 = 3×3, bukan prima.</p>'
        );

        $this->makePGJ($sta,
            '<p>Pernyataan yang BENAR mengenai mean, median, dan modus adalah ...</p>',
            [
                ['A', 'Mean selalu sama dengan median'],
                ['B', 'Modus bisa lebih dari satu nilai'],
                ['C', 'Median tidak dipengaruhi nilai ekstrem'],
                ['D', 'Mean dipengaruhi nilai ekstrem'],
                ['E', 'Median = (data terkecil + data terbesar) / 2'],
            ],
            ['B', 'C', 'D'], 'sedang', 2
        );

        // --- PG_BOBOT: Matematika ---
        $this->makePGBobot($alj,
            '<p>Persamaan <strong>x² − 4 = 0</strong> memiliki penyelesaian ...</p>',
            [
                ['A', 'x = 2',    100, true],
                ['B', 'x = −2',   100, true],
                ['C', 'x = 4',    0,   false],
                ['D', 'x = −4',   0,   false],
                ['E', 'x = 0',    0,   false],
            ],
            'sedang', 2
        );

        // --- JODOH: Matematika ---
        $this->makeJodoh($geo,
            '<p>Jodohkan nama rumus dengan keterangannya:</p>',
            [
                ['Luas persegi panjang',  'p × l'],
                ['Volume kubus',          's³'],
                ['Keliling lingkaran',    '2πr'],
                ['Luas segitiga',         '½ × a × t'],
                ['Luas trapesium',        '½ × (a + b) × t'],
            ],
            'mudah', 2
        );

        $this->makeJodoh($sta,
            '<p>Jodohkan istilah statistika dengan definisinya:</p>',
            [
                ['Mean',   'Nilai rata-rata dari semua data'],
                ['Median', 'Nilai tengah dari data terurut'],
                ['Modus',  'Nilai yang paling sering muncul'],
                ['Range',  'Selisih nilai terbesar dan terkecil'],
            ],
            'mudah', 2
        );

        // --- ISIAN: Matematika ---
        $this->makeIsian($alj,
            '<p>Jika x + y = 10 dan x − y = 4, maka nilai x adalah ...</p>',
            ['7'], 'sedang', 1,
            '<p>x + y = 10 dan x − y = 4. Jumlahkan: 2x = 14, x = 7</p>'
        );

        $this->makeIsian($sta,
            '<p>Banyaknya ruang sampel pada pelemparan sebuah koin dan sebuah dadu adalah ...</p>',
            ['12'], 'mudah', 1,
            '<p>Ruang sampel = 2 × 6 = 12</p>'
        );

        // --- URAIAN: Matematika ---
        $this->makeUraian($alj,
            '<p>Sebuah toko menjual barang seharga Rp80.000 dengan diskon 25%. Kemudian harga setelah diskon dikenakan pajak 10%. Berapakah harga akhir yang harus dibayar? Tunjukkan langkah-langkah penyelesaiannya!</p>',
            'sedang', 5,
            '<p>Diskon 25%: 80.000 × 0,25 = 20.000. Harga setelah diskon: 60.000. Pajak 10%: 60.000 × 0,10 = 6.000. Harga akhir: 66.000.</p>'
        );

        $this->makeUraian($sta,
            '<p>Dari 40 siswa, 15 menyukai Matematika, 20 menyukai IPA, dan 8 menyukai keduanya. Gambarlah diagram Venn dan tentukan: (a) berapa siswa yang hanya menyukai Matematika, (b) berapa yang tidak menyukai keduanya?</p>',
            'sulit', 5
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // BAHASA INDONESIA
    // ─────────────────────────────────────────────────────────────────────────

    private function seedBahasaIndonesia(Category $cat, array $cats): void
    {
        $id = $cat->id;

        $this->makePG($id,
            '<p>Kalimat yang menggunakan ejaan yang benar adalah ...</p>',
            [
                ['A', 'Dia pergi ke-sekolah dengan naik bis.'],
                ['B', 'Ayahku bekerja di Bank Indonesia.'],
                ['C', 'Ibu membeli buah-buahan dipasar.'],
                ['D', 'Kami duduk di-kursi paling depan.'],
                ['E', 'Ia berkata, "aku tidak tahu."'],
            ],
            'B', 'mudah', 1
        );

        $this->makePG($id,
            '<p>Perhatikan paragraf berikut:<br><em>"Hutan adalah paru-paru dunia. Jika hutan terus ditebang, maka kadar oksigen di atmosfer akan berkurang. Hal ini berdampak pada kelangsungan hidup makhluk hidup."</em><br>Ide pokok paragraf tersebut adalah ...</p>',
            [
                ['A', 'Oksigen di atmosfer terus berkurang'],
                ['B', 'Makhluk hidup bergantung pada hutan'],
                ['C', 'Hutan adalah paru-paru dunia'],
                ['D', 'Dampak penebangan hutan bagi makhluk hidup'],
                ['E', 'Pentingnya menjaga kadar oksigen'],
            ],
            'C', 'mudah', 1
        );

        $this->makePG($id,
            '<p>Kata <strong>"antusias"</strong> dalam kalimat "Para peserta sangat antusias mengikuti lomba" bermakna ...</p>',
            [['A','Acuh'], ['B','Kecewa'], ['C','Bersemangat'], ['D','Terpaksa'], ['E','Bingung']],
            'C', 'mudah', 1
        );

        $this->makePG($id,
            '<p>Majas yang terdapat dalam kalimat <strong>"Bulan tersenyum melihat bumi yang tenang"</strong> adalah ...</p>',
            [['A','Hiperbola'], ['B','Personifikasi'], ['C','Metafora'], ['D','Simile'], ['E','Litotes']],
            'B', 'mudah', 1,
            '<p>Personifikasi adalah majas yang memberikan sifat manusia kepada benda mati. "Bulan tersenyum" adalah contoh personifikasi.</p>'
        );

        $this->makePG($id,
            '<p>Penulisan judul karangan yang benar adalah ...</p>',
            [
                ['A', 'Mengenal Budaya dan adat istiadat nusantara'],
                ['B', 'mengenal budaya dan adat istiadat nusantara'],
                ['C', 'Mengenal Budaya dan Adat Istiadat Nusantara'],
                ['D', 'MENGENAL BUDAYA DAN ADAT ISTIADAT NUSANTARA'],
                ['E', 'Mengenal budaya Dan adat Istiadat nusantara'],
            ],
            'C', 'sedang', 1
        );

        $this->makePGJ($id,
            '<p>Manakah yang termasuk ciri-ciri teks eksposisi?</p>',
            [
                ['A', 'Bersifat informatif dan faktual'],
                ['B', 'Menggunakan kata-kata subjektif'],
                ['C', 'Bertujuan meyakinkan pembaca'],
                ['D', 'Disertai argumen dan fakta pendukung'],
                ['E', 'Mengisahkan pengalaman pribadi'],
            ],
            ['A', 'D'], 'sedang', 2
        );

        $this->makeJodoh($id,
            '<p>Jodohkan jenis kata dengan contohnya:</p>',
            [
                ['Kata benda (nomina)',   'buku, meja, sekolah'],
                ['Kata kerja (verba)',    'berlari, membaca, menulis'],
                ['Kata sifat (adjektiva)', 'indah, cepat, besar'],
                ['Kata keterangan (adverbia)', 'sangat, selalu, sudah'],
                ['Kata Hubung (konjungsi)', 'dan, atau, tetapi'],
            ],
            'mudah', 2
        );

        $this->makeIsian($id,
            '<p>Majas yang membandingkan dua hal secara langsung menggunakan kata <em>seperti</em>, <em>bagaikan</em>, atau <em>bagai</em> disebut majas ...</p>',
            ['simile', 'perumpamaan'], 'mudah', 1
        );

        $this->makeIsian($id,
            '<p>Kalimat yang memiliki dua klausa atau lebih yang dihubungkan oleh konjungsi disebut kalimat ...</p>',
            ['majemuk'], 'sedang', 1
        );

        $this->makeUraian($id,
            '<p>Tulislah sebuah paragraf deskripsi (minimal 5 kalimat) yang menggambarkan suasana pagi hari di lingkungan sekolahmu! Perhatikan penggunaan kata baku, ejaan, dan tanda baca yang tepat.</p>',
            'sedang', 5
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // IPA
    // ─────────────────────────────────────────────────────────────────────────

    private function seedIPA(Category $cat, array $cats): void
    {
        $fis = $cats['ipaFis']->id;
        $bio = $cats['ipaBio']->id;
        $kim = $cats['ipaKim']->id;

        // --- FISIKA ---
        $this->makePG($fis,
            '<p>Sebuah benda bergerak dengan kecepatan awal <strong>10 m/s</strong> dan percepatan <strong>2 m/s²</strong> selama <strong>5 detik</strong>. Kecepatan akhirnya adalah ...</p>',
            [['A','15 m/s'], ['B','20 m/s'], ['C','25 m/s'], ['D','30 m/s'], ['E','35 m/s']],
            'B', 'sedang', 1,
            '<p>v = v₀ + at = 10 + 2×5 = 20 m/s</p>'
        );

        $this->makePG($fis,
            '<p>Hukum Newton II menyatakan bahwa gaya yang bekerja pada benda sama dengan ...</p>',
            [
                ['A', 'massa × kecepatan'],
                ['B', 'massa × percepatan'],
                ['C', 'massa × jarak'],
                ['D', 'massa × waktu'],
                ['E', 'percepatan × jarak'],
            ],
            'B', 'mudah', 1,
            '<p>F = m × a (massa dikali percepatan)</p>'
        );

        $this->makePG($fis,
            '<p>Satuan besaran gaya dalam SI adalah ...</p>',
            [['A','Joule'], ['B','Watt'], ['C','Newton'], ['D','Pascal'], ['E','Ampere']],
            'C', 'mudah', 1
        );

        $this->makePG($fis,
            '<p>Sebuah benda dengan massa <strong>2 kg</strong> berada pada ketinggian <strong>10 m</strong> dari tanah (g = 10 m/s²). Energi potensial benda tersebut adalah ...</p>',
            [['A','20 J'], ['B','100 J'], ['C','200 J'], ['D','2000 J'], ['E','20000 J']],
            'C', 'mudah', 1,
            '<p>Ep = mgh = 2 × 10 × 10 = 200 J</p>'
        );

        $this->makePG($fis,
            '<p>Peristiwa yang merupakan contoh perpindahan kalor secara <strong>konveksi</strong> adalah ...</p>',
            [
                ['A', 'Panas matahari sampai ke bumi'],
                ['B', 'Besi yang dipanaskan di ujungnya menjadi panas di ujung lain'],
                ['C', 'Air yang dipanaskan dari bawah bergerak naik'],
                ['D', 'Api unggun yang menghangatkan orang di dekatnya'],
                ['E', 'Logam tembaga yang dipanaskan'],
            ],
            'C', 'sedang', 1,
            '<p>Konveksi adalah perpindahan kalor melalui aliran zat (cair atau gas). Air mendidih adalah contoh klasik konveksi.</p>'
        );

        // --- BIOLOGI ---
        $this->makePG($bio,
            '<p>Organel sel yang berfungsi sebagai tempat berlangsungnya fotosintesis adalah ...</p>',
            [['A','Mitokondria'], ['B','Ribosom'], ['C','Kloroplas'], ['D','Vakuola'], ['E','Nukleus']],
            'C', 'mudah', 1,
            '<p>Kloroplas mengandung klorofil yang berperan dalam fotosintesis.</p>'
        );

        $this->makePG($bio,
            '<p>Proses pembelahan sel yang menghasilkan dua sel anak dengan jumlah kromosom sama dengan induk disebut ...</p>',
            [['A','Meiosis'], ['B','Mitosis'], ['C','Fertilisasi'], ['D','Diferensiasi'], ['E','Proliferasi']],
            'B', 'mudah', 1,
            '<p>Mitosis menghasilkan 2 sel anak yang identik secara genetik dengan sel induk.</p>'
        );

        $this->makePG($bio,
            '<p>Sistem peredaran darah manusia terdiri dari jantung dan pembuluh darah. Pembuluh darah yang membawa darah <strong>dari jantung ke seluruh tubuh</strong> adalah ...</p>',
            [['A','Vena'], ['B','Kapiler'], ['C','Arteri'], ['D','Vena cava'], ['E','Aorta saja']],
            'C', 'mudah', 1
        );

        $this->makePG($bio,
            '<p>Enzim yang dihasilkan pankreas untuk menguraikan protein di usus halus adalah ...</p>',
            [['A','Amilase'], ['B','Lipase'], ['C','Tripsin'], ['D','Pepsin'], ['E','Ptialin']],
            'C', 'sedang', 1,
            '<p>Tripsin adalah enzim protease yang dihasilkan pankreas, bekerja di usus halus.</p>'
        );

        $this->makePGJ($bio,
            '<p>Yang termasuk fungsi sistem pernapasan manusia adalah ...</p>',
            [
                ['A', 'Mengambil oksigen dari udara'],
                ['B', 'Mengeluarkan karbon dioksida'],
                ['C', 'Menyerap sari makanan'],
                ['D', 'Menjaga keseimbangan asam-basa darah'],
                ['E', 'Memompa darah ke seluruh tubuh'],
            ],
            ['A', 'B', 'D'], 'sedang', 2
        );

        $this->makeJodoh($bio,
            '<p>Jodohkan organel sel dengan fungsinya:</p>',
            [
                ['Mitokondria',    'Menghasilkan energi (respirasi sel)'],
                ['Ribosom',        'Sintesis protein'],
                ['Nukleus',        'Pusat pengendali sel (DNA)'],
                ['Vakuola',        'Menyimpan cadangan makanan dan air'],
                ['Membran sel',    'Mengatur keluar masuknya zat'],
            ],
            'mudah', 2
        );

        $this->makeIsian($bio,
            '<p>Proses masuknya cahaya matahari, air, dan CO₂ untuk menghasilkan glukosa dan oksigen pada tumbuhan disebut ...</p>',
            ['fotosintesis', 'fotosintesis (photosynthesis)'], 'mudah', 1
        );

        $this->makeUraian($bio,
            '<p>Jelaskan proses pencernaan makanan dari mulut hingga usus besar! Sebutkan organ-organ yang terlibat dan enzim yang berperan di masing-masing organ!</p>',
            'sedang', 5
        );

        // --- KIMIA ---
        $this->makePG($kim,
            '<p>Unsur dengan nomor atom <strong>8</strong> dan simbol <strong>O</strong> adalah ...</p>',
            [['A','Karbon'], ['B','Nitrogen'], ['C','Oksigen'], ['D','Flour'], ['E','Neon']],
            'C', 'mudah', 1
        );

        $this->makePG($kim,
            '<p>Rumus kimia air adalah ...</p>',
            [['A','CO₂'], ['B','H₂O'], ['C','NaCl'], ['D','CH₄'], ['E','NH₃']],
            'B', 'mudah', 1
        );

        $this->makePG($kim,
            '<p>Reaksi antara asam dan basa yang menghasilkan garam dan air disebut reaksi ...</p>',
            [['A','Redoks'], ['B','Oksidasi'], ['C','Netralisasi'], ['D','Hidrolisis'], ['E','Elektrolisis']],
            'C', 'mudah', 1
        );

        $this->makePG($kim,
            '<p>Larutan yang memiliki pH = 7 bersifat ...</p>',
            [['A','Asam kuat'], ['B','Asam lemah'], ['C','Netral'], ['D','Basa lemah'], ['E','Basa kuat']],
            'C', 'mudah', 1
        );

        $this->makeJodoh($kim,
            '<p>Jodohkan rumus kimia dengan nama senyawanya:</p>',
            [
                ['H₂O',  'Air'],
                ['CO₂',  'Karbon dioksida'],
                ['NaCl', 'Natrium klorida (garam dapur)'],
                ['H₂SO₄', 'Asam sulfat'],
                ['NH₃',  'Amonia'],
            ],
            'mudah', 2
        );

        $this->makeIsian($kim,
            '<p>Ion yang dihasilkan oleh asam dalam air adalah ion ...</p>',
            ['H+', 'H⁺', 'hidronium', 'H3O+'], 'sedang', 1
        );

        $this->makeUraian($kim,
            '<p>Jelaskan perbedaan antara unsur, senyawa, dan campuran! Berikan masing-masing 2 contoh dalam kehidupan sehari-hari!</p>',
            'sedang', 5
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SEJARAH
    // ─────────────────────────────────────────────────────────────────────────

    private function seedSejarah(Category $cat, array $cats): void
    {
        $id = $cat->id;

        $this->makePG($id,
            '<p>Proklamasi Kemerdekaan Indonesia dibacakan oleh Soekarno dan Hatta pada tanggal ...</p>',
            [['A','17 Agustus 1944'], ['B','17 Agustus 1945'], ['C','18 Agustus 1945'], ['D','1 Juni 1945'], ['E','28 Oktober 1928']],
            'B', 'mudah', 1
        );

        $this->makePG($id,
            '<p>Peristiwa Rengasdengklok terjadi pada tanggal <strong>16 Agustus 1945</strong>. Tujuan golongan muda membawa Soekarno-Hatta ke Rengasdengklok adalah ...</p>',
            [
                ['A', 'Menyelamatkan mereka dari ancaman Belanda'],
                ['B', 'Menjauhkan mereka dari pengaruh Jepang dan mendesak proklamasi'],
                ['C', 'Merencanakan serangan ke Jakarta'],
                ['D', 'Menyembunyikan naskah proklamasi'],
                ['E', 'Berunding dengan tentara sekutu'],
            ],
            'B', 'sedang', 1
        );

        $this->makePG($id,
            '<p>Perjanjian yang mengakhiri Perang Dunia II di kawasan Pasifik ditandatangani di atas kapal USS Missouri pada tahun ...</p>',
            [['A','1943'], ['B','1944'], ['C','1945'], ['D','1946'], ['E','1947']],
            'C', 'sedang', 1
        );

        $this->makePG($id,
            '<p>Kerajaan Hindu pertama di Indonesia adalah ...</p>',
            [['A','Majapahit'], ['B','Sriwijaya'], ['C','Kutai'], ['D','Tarumanegara'], ['E','Mataram']],
            'C', 'mudah', 1
        );

        $this->makePG($id,
            '<p>Sumpah Pemuda dikumandangkan pada tanggal ...</p>',
            [['A','17 Agustus 1928'], ['B','28 Oktober 1928'], ['C','20 Mei 1908'], ['D','28 Oktober 1945'], ['E','1 Juni 1945']],
            'B', 'mudah', 1
        );

        $this->makePGJ($id,
            '<p>Yang termasuk dampak penjajahan Belanda terhadap Indonesia adalah ...</p>',
            [
                ['A', 'Rakyat Indonesia menderita kemiskinan akibat tanam paksa'],
                ['B', 'Diperkenalkannya sistem pendidikan modern'],
                ['C', 'Terbentuknya identitas nasional Indonesia'],
                ['D', 'Berdirinya organisasi pergerakan nasional'],
                ['E', 'Meningkatkan kesejahteraan rakyat Indonesia'],
            ],
            ['A', 'B', 'D'], 'sedang', 2
        );

        $this->makeJodoh($id,
            '<p>Jodohkan tokoh dengan perannya dalam kemerdekaan Indonesia:</p>',
            [
                ['Soekarno',       'Proklamator dan Presiden RI pertama'],
                ['Mohammad Hatta', 'Proklamator dan Wakil Presiden RI pertama'],
                ['Sutan Sjahrir',  'Perdana Menteri RI pertama'],
                ['Ahmad Soebardjo', 'Perancang teks proklamasi'],
                ['Fatmawati',      'Penjahit Bendera Merah Putih pertama'],
            ],
            'mudah', 2
        );

        $this->makeIsian($id,
            '<p>Naskah Proklamasi Kemerdekaan Indonesia diketik oleh ...</p>',
            ['Sayuti Melik', 'Sayuti melik'], 'sedang', 1
        );

        $this->makeIsian($id,
            '<p>Organisasi pergerakan nasional pertama di Indonesia yang didirikan pada tahun 1908 adalah ...</p>',
            ['Budi Utomo', 'Boedi Oetomo'], 'mudah', 1
        );

        $this->makeUraian($id,
            '<p>Jelaskan latar belakang terjadinya Sumpah Pemuda 1928 dan jelaskan isi dari Sumpah Pemuda tersebut! Apa dampaknya bagi pergerakan kemerdekaan Indonesia?</p>',
            'sedang', 5
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TIK
    // ─────────────────────────────────────────────────────────────────────────

    private function seedTIK(Category $cat, array $cats): void
    {
        $jar = $cats['tikJar']->id;
        $alg = $cats['tikAlg']->id;

        // --- Jaringan ---
        $this->makePG($jar,
            '<p>Alamat IP yang termasuk kelas C adalah ...</p>',
            [['A','10.0.0.1'], ['B','172.16.0.1'], ['C','192.168.1.1'], ['D','224.0.0.1'], ['E','240.0.0.1']],
            'C', 'sedang', 1,
            '<p>Kelas C menggunakan rentang 192.0.0.0 – 223.255.255.255</p>'
        );

        $this->makePG($jar,
            '<p>Protokol yang digunakan untuk mengirim email adalah ...</p>',
            [['A','HTTP'], ['B','FTP'], ['C','SMTP'], ['D','DNS'], ['E','DHCP']],
            'C', 'mudah', 1,
            '<p>SMTP (Simple Mail Transfer Protocol) digunakan untuk pengiriman email.</p>'
        );

        $this->makePG($jar,
            '<p>Perangkat jaringan yang berfungsi menghubungkan dua jaringan yang berbeda dan memilih jalur terbaik untuk pengiriman data adalah ...</p>',
            [['A','Hub'], ['B','Switch'], ['C','Router'], ['D','Bridge'], ['E','Repeater']],
            'C', 'mudah', 1
        );

        $this->makePG($jar,
            '<p>Model OSI memiliki berapa lapisan (layer)?</p>',
            [['A','4'], ['B','5'], ['C','6'], ['D','7'], ['E','8']],
            'D', 'mudah', 1,
            '<p>Model OSI memiliki 7 lapisan: Physical, Data Link, Network, Transport, Session, Presentation, Application.</p>'
        );

        $this->makePGJ($jar,
            '<p>Yang termasuk protokol pada lapisan Application dalam model OSI adalah ...</p>',
            [
                ['A', 'HTTP'],
                ['B', 'FTP'],
                ['C', 'IP'],
                ['D', 'SMTP'],
                ['E', 'TCP'],
            ],
            ['A', 'B', 'D'], 'sedang', 2
        );

        $this->makeJodoh($jar,
            '<p>Jodohkan singkatan dengan kepanjangannya:</p>',
            [
                ['HTTP',  'HyperText Transfer Protocol'],
                ['DNS',   'Domain Name System'],
                ['IP',    'Internet Protocol'],
                ['MAC',   'Media Access Control'],
                ['LAN',   'Local Area Network'],
            ],
            'mudah', 2
        );

        // --- Algoritma ---
        $this->makePG($alg,
            '<p>Struktur kontrol yang digunakan untuk mengulang serangkaian instruksi berdasarkan kondisi tertentu adalah ...</p>',
            [['A','Sequence'], ['B','Selection'], ['C','Iteration'], ['D','Function'], ['E','Variable']],
            'C', 'mudah', 1
        );

        $this->makePG($alg,
            '<p>Tipe data yang digunakan untuk menyimpan nilai bilangan bulat dalam pemrograman adalah ...</p>',
            [['A','float'], ['B','string'], ['C','boolean'], ['D','integer'], ['E','char']],
            'D', 'mudah', 1
        );

        $this->makePG($alg,
            '<p>Nilai output dari pseudocode berikut adalah berapa?<br><code>x = 5<br>y = 3<br>z = x * y + 2<br>PRINT z</code></p>',
            [['A','10'], ['B','15'], ['C','17'], ['D','25'], ['E','30']],
            'C', 'sedang', 1,
            '<p>z = 5 × 3 + 2 = 15 + 2 = 17</p>'
        );

        $this->makePGJ($alg,
            '<p>Yang merupakan struktur data linear (data tersusun secara berurutan) adalah ...</p>',
            [
                ['A', 'Array'],
                ['B', 'Stack'],
                ['C', 'Tree'],
                ['D', 'Queue'],
                ['E', 'Graph'],
            ],
            ['A', 'B', 'D'], 'sedang', 2
        );

        $this->makeIsian($jar,
            '<p>Kepanjangan dari <strong>IP</strong> dalam konteks jaringan komputer adalah ...</p>',
            ['Internet Protocol'], 'mudah', 1
        );

        $this->makeIsian($alg,
            '<p>Dalam pemrograman, proses yang disebut <em>debugging</em> adalah proses mencari dan memperbaiki ...</p>',
            ['bug', 'kesalahan', 'error', 'bug (kesalahan)'], 'mudah', 1
        );

        $this->makeUraian($jar,
            '<p>Jelaskan perbedaan antara topologi jaringan <strong>Bus</strong>, <strong>Star</strong>, dan <strong>Ring</strong>! Sebutkan kelebihan dan kekurangan masing-masing topologi!</p>',
            'sedang', 5
        );

        $this->makeUraian($alg,
            '<p>Buatlah algoritma (dalam bentuk pseudocode) untuk menghitung nilai rata-rata dari 5 bilangan yang diinput oleh pengguna, kemudian tentukan apakah rata-rata tersebut termasuk kategori "Lulus" (≥70) atau "Tidak Lulus" (&lt;70)!</p>',
            'sedang', 5
        );
    }
}
