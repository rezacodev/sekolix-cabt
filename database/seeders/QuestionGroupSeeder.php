<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Question;
use App\Models\QuestionGroup;
use App\Models\QuestionOption;
use App\Models\User;
use Illuminate\Database\Seeder;

class QuestionGroupSeeder extends Seeder
{
  private int $createdBy;

  public function run(): void
  {
    $this->createdBy = User::where('level', User::LEVEL_SUPER_ADMIN)->value('id') ?? 1;

    $this->groupTeksBahasaIndonesia();
    $this->groupTabelMatematika();
    $this->groupGambarIPA();

    $total = QuestionGroup::count();
    $this->command->info("QuestionGroupSeeder: {$total} grup soal (stimulus) berhasil dibuat.");
  }

  // ─────────────────────────────────────────────────────────────────────────
  // 1. Teks Bacaan — Bahasa Indonesia
  // ─────────────────────────────────────────────────────────────────────────

  private function groupTeksBahasaIndonesia(): void
  {
    $group = QuestionGroup::firstOrCreate(
      ['judul' => 'Teks Artikel: Dampak Media Sosial'],
      [
        'tipe_stimulus' => 'teks',
        'konten'        => '<p>Media sosial telah mengubah cara manusia berkomunikasi secara mendasar. ' .
          'Di satu sisi, platform seperti Instagram, Twitter, dan TikTok mempermudah penyebaran ' .
          'informasi dan membangun jaringan sosial yang luas. ' .
          'Di sisi lain, berbagai riset menunjukkan korelasi antara penggunaan media sosial ' .
          'yang berlebihan dengan peningkatan tingkat kecemasan, depresi, dan fenomena ' .
          '<em>fear of missing out</em> (FOMO) terutama pada remaja.</p>' .
          '<p>Literasi digital menjadi kunci dalam menghadapi tantangan ini. ' .
          'Pengguna yang memiliki kemampuan literasi digital yang baik mampu memilah ' .
          'konten yang bermanfaat, menghindari jebakan disinformasi, dan menjaga ' .
          'keseimbangan antara dunia virtual dan kehidupan nyata.</p>',
        'deskripsi'     => 'Bacalah teks berikut dengan saksama, kemudian jawab soal-soal di bawah ini.',
        'created_by'    => $this->createdBy,
      ]
    );

    $bindCategory = Category::where('nama', 'Bahasa Indonesia')->value('id') ?? 1;

    $soalData = [
      [
        'urutan'   => 1,
        'teks'     => 'Gagasan utama paragraf pertama teks di atas adalah…',
        'options'  => [
          ['teks' => 'Media sosial menyebabkan depresi pada remaja.',               'benar' => false],
          ['teks' => 'Media sosial mengubah cara manusia berkomunikasi.',            'benar' => true],
          ['teks' => 'Platform media sosial terus berkembang pesat.',                'benar' => false],
          ['teks' => 'Literasi digital penting untuk generasi muda.',                'benar' => false],
        ],
      ],
      [
        'urutan'   => 2,
        'teks'     => 'Istilah <em>fear of missing out</em> (FOMO) dalam teks tersebut merujuk pada…',
        'options'  => [
          ['teks' => 'Rasa takut tertinggal perkembangan teknologi.',                'benar' => false],
          ['teks' => 'Kecemasan karena tidak bisa mengakses internet.',             'benar' => false],
          ['teks' => 'Perasaan khawatir ketinggalan momen atau informasi orang lain.', 'benar' => true],
          ['teks' => 'Dorongan untuk selalu membagikan aktivitas di media sosial.',  'benar' => false],
        ],
      ],
      [
        'urutan'   => 3,
        'teks'     => 'Solusi yang ditawarkan penulis untuk menghadapi dampak negatif media sosial adalah…',
        'options'  => [
          ['teks' => 'Melarang penggunaan media sosial bagi remaja.',               'benar' => false],
          ['teks' => 'Mengembangkan platform media sosial yang lebih aman.',        'benar' => false],
          ['teks' => 'Meningkatkan kemampuan literasi digital.',                    'benar' => true],
          ['teks' => 'Membatasi akses internet di sekolah.',                        'benar' => false],
        ],
      ],
      [
        'urutan'   => 4,
        'teks'     => 'Berdasarkan teks, pernyataan berikut yang BENAR adalah…',
        'options'  => [
          ['teks' => 'Semua pengguna media sosial mengalami dampak negatif.',       'benar' => false],
          ['teks' => 'Riset membuktikan media sosial tidak berdampak pada kesehatan mental.', 'benar' => false],
          ['teks' => 'Pengguna dengan literasi digital tinggi lebih mampu menyaring konten.', 'benar' => true],
          ['teks' => 'FOMO hanya dialami oleh pengguna Twitter dan Instagram.',    'benar' => false],
        ],
      ],
    ];

    $this->buatSoalPg($group, $soalData, $bindCategory);
  }

  // ─────────────────────────────────────────────────────────────────────────
  // 2. Tabel Data — Matematika / Statistika
  // ─────────────────────────────────────────────────────────────────────────

  private function groupTabelMatematika(): void
  {
    $group = QuestionGroup::firstOrCreate(
      ['judul' => 'Tabel Distribusi Frekuensi Nilai Ulangan'],
      [
        'tipe_stimulus' => 'tabel',
        'konten'        => '<table class="w-full text-sm border-collapse">' .
          '<thead><tr class="bg-slate-600 text-white">' .
          '<th class="border border-slate-500 px-3 py-2">Interval Nilai</th>' .
          '<th class="border border-slate-500 px-3 py-2">Frekuensi</th>' .
          '</tr></thead>' .
          '<tbody>' .
          '<tr><td class="border border-slate-500 px-3 py-2 text-center">50 – 59</td><td class="border border-slate-500 px-3 py-2 text-center">4</td></tr>' .
          '<tr><td class="border border-slate-500 px-3 py-2 text-center">60 – 69</td><td class="border border-slate-500 px-3 py-2 text-center">8</td></tr>' .
          '<tr><td class="border border-slate-500 px-3 py-2 text-center">70 – 79</td><td class="border border-slate-500 px-3 py-2 text-center">14</td></tr>' .
          '<tr><td class="border border-slate-500 px-3 py-2 text-center">80 – 89</td><td class="border border-slate-500 px-3 py-2 text-center">10</td></tr>' .
          '<tr><td class="border border-slate-500 px-3 py-2 text-center">90 – 99</td><td class="border border-slate-500 px-3 py-2 text-center">4</td></tr>' .
          '<tr class="font-bold"><td class="border border-slate-500 px-3 py-2 text-center">Jumlah</td><td class="border border-slate-500 px-3 py-2 text-center">40</td></tr>' .
          '</tbody></table>',
        'deskripsi'     => 'Perhatikan tabel distribusi frekuensi nilai ulangan 40 siswa berikut.',
        'created_by'    => $this->createdBy,
      ]
    );

    $statCategory = Category::where('nama', 'Statistika')->value('id') ??
      Category::where('nama', 'Matematika')->value('id') ?? 1;

    $soalData = [
      [
        'urutan'   => 1,
        'teks'     => 'Modus dari data pada tabel di atas terletak pada interval…',
        'options'  => [
          ['teks' => '60 – 69', 'benar' => false],
          ['teks' => '70 – 79', 'benar' => true],
          ['teks' => '80 – 89', 'benar' => false],
          ['teks' => '90 – 99', 'benar' => false],
        ],
      ],
      [
        'urutan'   => 2,
        'teks'     => 'Frekuensi kumulatif untuk interval sampai dengan 79 adalah…',
        'options'  => [
          ['teks' => '22', 'benar' => false],
          ['teks' => '24', 'benar' => false],
          ['teks' => '26', 'benar' => true],
          ['teks' => '28', 'benar' => false],
        ],
      ],
      [
        'urutan'   => 3,
        'teks'     => 'Persentase siswa yang mendapat nilai 80 ke atas adalah…',
        'options'  => [
          ['teks' => '25%', 'benar' => false],
          ['teks' => '30%', 'benar' => false],
          ['teks' => '35%', 'benar' => true],
          ['teks' => '40%', 'benar' => false],
        ],
      ],
    ];

    $this->buatSoalPg($group, $soalData, $statCategory);
  }

  // ─────────────────────────────────────────────────────────────────────────
  // 3. Gambar Diagram — IPA / Biologi
  // ─────────────────────────────────────────────────────────────────────────

  private function groupGambarIPA(): void
  {
    $group = QuestionGroup::firstOrCreate(
      ['judul' => 'Gambar Sistem Peredaran Darah Manusia'],
      [
        'tipe_stimulus' => 'teks',
        'konten'        => '<p class="text-sm italic text-slate-400">[Gambar diagram sistem peredaran darah — tersedia versi produksi]</p>' .
          '<p>Sistem peredaran darah manusia terdiri atas jantung, pembuluh darah (arteri, vena, kapiler), ' .
          'dan darah. Jantung berfungsi sebagai pompa yang mengalirkan darah ke seluruh tubuh. ' .
          'Peredaran besar (sistemik) mengangkut darah beroksigen dari jantung ke seluruh jaringan tubuh, ' .
          'sedangkan peredaran kecil (pulmonal) mengalirkan darah dari jantung ke paru-paru untuk ' .
          'pertukaran gas.</p>',
        'deskripsi'     => 'Perhatikan diagram dan deskripsi sistem peredaran darah berikut.',
        'created_by'    => $this->createdBy,
      ]
    );

    $bioCategory = Category::where('nama', 'Biologi')->value('id') ??
      Category::where('nama', 'IPA')->value('id') ?? 1;

    $soalData = [
      [
        'urutan'   => 1,
        'teks'     => 'Urutan aliran darah pada peredaran darah kecil (pulmonal) yang benar adalah…',
        'options'  => [
          ['teks' => 'Jantung → Paru-paru → Jantung',          'benar' => true],
          ['teks' => 'Jantung → Seluruh tubuh → Jantung',      'benar' => false],
          ['teks' => 'Paru-paru → Kapiler → Vena',             'benar' => false],
          ['teks' => 'Arteri → Kapiler → Paru-paru',           'benar' => false],
        ],
      ],
      [
        'urutan'   => 2,
        'teks'     => 'Fungsi utama pembuluh kapiler dalam sistem peredaran darah adalah…',
        'options'  => [
          ['teks' => 'Memompa darah ke paru-paru.',                         'benar' => false],
          ['teks' => 'Tempat pertukaran zat antara darah dan sel jaringan.', 'benar' => true],
          ['teks' => 'Mengalirkan darah dari jantung ke organ tubuh.',       'benar' => false],
          ['teks' => 'Menyimpan cadangan darah.',                            'benar' => false],
        ],
      ],
    ];

    $this->buatSoalPg($group, $soalData, $bioCategory);
  }

  // ─────────────────────────────────────────────────────────────────────────
  // Helper
  // ─────────────────────────────────────────────────────────────────────────

  private function buatSoalPg(QuestionGroup $group, array $soalData, int $categoryId): void
  {
    foreach ($soalData as $data) {
      $soal = Question::firstOrCreate(
        [
          'teks_soal'         => $data['teks'],
          'question_group_id' => $group->id,
        ],
        [
          'tipe'              => Question::TIPE_PG,
          'tingkat_kesulitan' => 'sedang',
          'bobot'             => 1,
          'aktif'             => true,
          'kategori_id'       => $categoryId,
          'created_by'        => $this->createdBy,
          'group_urutan'      => $data['urutan'],
        ]
      );

      // Only add options if the question was just created (no existing options)
      if ($soal->options()->count() === 0) {
        foreach ($data['options'] as $i => $opt) {
          QuestionOption::create([
            'question_id'  => $soal->id,
            'kode_opsi'    => chr(65 + $i), // A, B, C, D
            'teks_opsi'    => $opt['teks'],
            'is_correct'   => $opt['benar'],
            'bobot_persen' => $opt['benar'] ? 100 : 0,
            'urutan'       => $i,
            'aktif'        => true,
          ]);
        }
      }
    }
  }
}
