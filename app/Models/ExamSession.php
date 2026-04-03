<?php

namespace App\Models;

use Database\Factories\ExamSessionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ExamSession extends Model
{
    /** @use HasFactory<ExamSessionFactory> */
    use HasFactory;
    const STATUS_DRAFT      = 'draft';
    const STATUS_AKTIF      = 'aktif';
    const STATUS_SELESAI    = 'selesai';
    const STATUS_DIBATALKAN = 'dibatalkan';

    const STATUS_LABELS = [
        self::STATUS_DRAFT      => 'Draft',
        self::STATUS_AKTIF      => 'Aktif',
        self::STATUS_SELESAI    => 'Selesai',
        self::STATUS_DIBATALKAN => 'Dibatalkan',
    ];

    const STATUS_COLORS = [
        self::STATUS_DRAFT      => 'gray',
        self::STATUS_AKTIF      => 'success',
        self::STATUS_SELESAI    => 'info',
        self::STATUS_DIBATALKAN => 'danger',
    ];

    protected $fillable = [
        'exam_package_id',
        'nama_sesi',
        'waktu_mulai',
        'waktu_selesai',
        'status',
        'token_akses',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'waktu_mulai'  => 'datetime',
            'waktu_selesai' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $session) {
            if (Auth::check() && empty($session->created_by)) {
                $session->created_by = Auth::id();
            }
        });
    }

    // ── Relations ────────────────────────────────────────────────────────────

    public function package(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ExamPackage::class, 'exam_package_id');
    }

    public function participants(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ExamSessionParticipant::class, 'exam_session_id');
    }

    public function attempts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ExamAttempt::class, 'exam_session_id');
    }

    public function creator(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function notes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SessionNote::class, 'exam_session_id')->orderBy('created_at');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isAktif(): bool
    {
        return $this->status === self::STATUS_AKTIF;
    }

    public function isSelesai(): bool
    {
        return $this->status === self::STATUS_SELESAI;
    }

    public function isDibatalkan(): bool
    {
        return $this->status === self::STATUS_DIBATALKAN;
    }

    public function canBuka(): bool
    {
        return $this->isDraft();
    }

    public function canTutup(): bool
    {
        return $this->isAktif();
    }
}
