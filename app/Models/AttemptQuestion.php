<?php

namespace App\Models;

use Database\Factories\AttemptQuestionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttemptQuestion extends Model
{
    /** @use HasFactory<AttemptQuestionFactory> */
    use HasFactory;
    public $timestamps = false;

    protected $fillable = [
        'attempt_id',
        'question_id',
        'urutan',
        'section_id',
        'jawaban_peserta',
        'jawaban_file',
        'nilai_perolehan',
        'is_correct',
        'is_ragu',
        'audio_play_count',
        'waktu_jawab',
    ];

    protected function casts(): array
    {
        return [
            'is_correct'     => 'boolean',
            'is_ragu'        => 'boolean',
            'nilai_perolehan' => 'decimal:2',
            'waktu_jawab'    => 'datetime',
        ];
    }

    public function attempt(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ExamAttempt::class, 'attempt_id');
    }

    public function question(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Question::class, 'question_id');
    }

    public function section(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ExamSection::class, 'section_id');
    }

    public function isDijawab(): bool
    {
        return $this->jawaban_peserta !== null || $this->jawaban_file !== null;
    }
}
