<?php

namespace App\Models;

use Database\Factories\ExamPackageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamPackage extends Model
{
    /** @use HasFactory<ExamPackageFactory> */
    use HasFactory;
    const GRADING_REALTIME = 'realtime';
    const GRADING_MANUAL   = 'manual';

    const GRADING_LABELS = [
        self::GRADING_REALTIME => 'Realtime',
        self::GRADING_MANUAL   => 'Manual',
    ];

    const NAV_SEKSI_URUT         = 'urut';
    const NAV_SEKSI_URUT_KEMBALI = 'urut_kembali';
    const NAV_SEKSI_BEBAS        = 'bebas';

    const NAV_SEKSI_LABELS = [
        self::NAV_SEKSI_URUT         => 'Urut — wajib selesaikan tiap bagian, tidak bisa kembali',
        self::NAV_SEKSI_URUT_KEMBALI => 'Urut + Kembali — wajib urut, tapi bisa kembali ke bagian sebelumnya',
        self::NAV_SEKSI_BEBAS        => 'Bebas — bisa pindah ke bagian mana saja kapan saja',
    ];

    const NAV_SOAL_BEBAS = 'bebas';
    const NAV_SOAL_MAJU  = 'maju';

    const NAV_SOAL_LABELS = [
        self::NAV_SOAL_BEBAS => 'Bebas — bisa kembali ke soal sebelumnya',
        self::NAV_SOAL_MAJU  => 'Hanya Maju — tidak bisa kembali ke soal sebelumnya',
    ];

    protected $fillable = [
        'nama',
        'deskripsi',
        'durasi_menit',
        'waktu_minimal_menit',
        'acak_soal',
        'acak_opsi',
        'max_pengulangan',
        'tampilkan_hasil',
        'tampilkan_review',
        'grading_mode',
        'created_by',
        'blueprint_id',
        'has_sections',
        'navigasi_seksi',
        'nilai_negatif',
        'nilai_negatif_kosong',
        'nilai_negatif_clamp',
        'waktu_per_soal_detik',
        'waktu_per_soal_navigasi',
    ];

    protected function casts(): array
    {
        return [
            'acak_soal'              => 'boolean',
            'acak_opsi'              => 'boolean',
            'tampilkan_hasil'        => 'boolean',
            'tampilkan_review'       => 'boolean',
            'has_sections'           => 'boolean',
            'nilai_negatif'          => 'decimal:2',
            'nilai_negatif_kosong'   => 'boolean',
            'nilai_negatif_clamp'    => 'boolean',
            'waktu_per_soal_detik'   => 'integer',
        ];
    }

    public function blueprint(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ExamBlueprint::class, 'blueprint_id');
    }

    public function creator(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Questions ordered by urutan via pivot.
     */
    public function questions(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'exam_package_questions')
            ->withPivot('urutan', 'id')
            ->orderByPivot('urutan');
    }

    public function questionPivots(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ExamPackageQuestion::class)->orderBy('urutan');
    }

    public function sections(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ExamSection::class, 'exam_package_id')->orderBy('urutan');
    }

    public function examSessions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ExamSession::class, 'exam_package_id');
    }

    /**
     * True if this package is used in any active/finished exam session.
     */
    public function isSoftLocked(): bool
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('exam_sessions')) {
            return false;
        }

        return \Illuminate\Support\Facades\DB::table('exam_sessions')
            ->where('exam_package_id', $this->id)
            ->whereIn('status', ['aktif', 'selesai'])
            ->exists();
    }
}
