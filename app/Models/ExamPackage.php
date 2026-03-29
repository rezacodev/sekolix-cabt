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
    ];

    protected function casts(): array
    {
        return [
            'acak_soal'        => 'boolean',
            'acak_opsi'        => 'boolean',
            'tampilkan_hasil'  => 'boolean',
            'tampilkan_review' => 'boolean',
        ];
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
