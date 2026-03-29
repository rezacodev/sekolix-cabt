<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamSessionParticipant extends Model
{
    public $timestamps = false;

    const STATUS_BELUM          = 'belum';
    const STATUS_SEDANG         = 'sedang';
    const STATUS_SELESAI        = 'selesai';
    const STATUS_DISKUALIFIKASI = 'diskualifikasi';

    const STATUS_LABELS = [
        self::STATUS_BELUM          => 'Belum Mulai',
        self::STATUS_SEDANG         => 'Sedang Mengerjakan',
        self::STATUS_SELESAI        => 'Selesai',
        self::STATUS_DISKUALIFIKASI => 'Diskualifikasi',
    ];

    const STATUS_COLORS = [
        self::STATUS_BELUM          => 'gray',
        self::STATUS_SEDANG         => 'warning',
        self::STATUS_SELESAI        => 'success',
        self::STATUS_DISKUALIFIKASI => 'danger',
    ];

    protected $fillable = [
        'exam_session_id',
        'user_id',
        'status',
    ];

    public function session(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ExamSession::class, 'exam_session_id');
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
