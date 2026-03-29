<?php

namespace App\Models;

use Database\Factories\ExamAttemptFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamAttempt extends Model
{
    /** @use HasFactory<ExamAttemptFactory> */
    use HasFactory;
    const STATUS_BERLANGSUNG  = 'berlangsung';
    const STATUS_SELESAI      = 'selesai';
    const STATUS_TIMEOUT      = 'timeout';
    const STATUS_DISKUALIFIKASI = 'diskualifikasi';

    const STATUS_LABELS = [
        self::STATUS_BERLANGSUNG    => 'Berlangsung',
        self::STATUS_SELESAI        => 'Selesai',
        self::STATUS_TIMEOUT        => 'Timeout',
        self::STATUS_DISKUALIFIKASI => 'Diskualifikasi',
    ];

    protected $fillable = [
        'exam_session_id',
        'user_id',
        'waktu_mulai',
        'waktu_selesai',
        'status',
        'nilai_akhir',
        'jumlah_benar',
        'jumlah_salah',
        'jumlah_kosong',
        'attempt_ke',
    ];

    protected function casts(): array
    {
        return [
            'waktu_mulai'   => 'datetime',
            'waktu_selesai' => 'datetime',
            'nilai_akhir'   => 'decimal:2',
        ];
    }

    // ── Relations ────────────────────────────────────────────────────────────

    public function session(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ExamSession::class, 'exam_session_id');
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function questions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AttemptQuestion::class, 'attempt_id')->orderBy('urutan');
    }

    public function logs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AttemptLog::class, 'attempt_id');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    public function isBerlangsung(): bool
    {
        return $this->status === self::STATUS_BERLANGSUNG;
    }

    public function isSelesai(): bool
    {
        return in_array($this->status, [self::STATUS_SELESAI, self::STATUS_TIMEOUT, self::STATUS_DISKUALIFIKASI]);
    }

    /** Sisa waktu dalam detik berdasarkan waktu server. Negatif = sudah habis. */
    public function sisaWaktuDetik(): int
    {
        $package = $this->session?->package;
        if (! $package || ! $this->waktu_mulai) {
            return 0;
        }
        $durasi  = $package->durasi_menit * 60;
        $elapsed = (int) round(now()->diffInSeconds($this->waktu_mulai, false) * -1);
        return $durasi - $elapsed;
    }

    public function tabSwitchCount(): int
    {
        return $this->logs()->where('event_type', AttemptLog::EVENT_TAB_SWITCH)->count();
    }
}
