<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuestionStatistic extends Model
{
    protected $fillable = [
        'question_id',
        'total_attempts',
        'p_value',
        'discrimination_index',
        'distractor_distribution',
        'avg_response_seconds',
        'last_calculated_at',
    ];

    protected function casts(): array
    {
        return [
            'p_value'                 => 'decimal:3',
            'discrimination_index'    => 'decimal:3',
            'avg_response_seconds'    => 'decimal:2',
            'distractor_distribution' => 'array',
            'last_calculated_at'      => 'datetime',
        ];
    }

    public function question(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    // ── Helpers untuk interpretasi ────────────────────────────────────────────

    public function pValueLabel(): string
    {
        if ($this->p_value === null) return 'N/A';
        return match (true) {
            (float) $this->p_value < 0.2                                  => 'Sangat Sulit',
            (float) $this->p_value < 0.3                                  => 'Sulit',
            (float) $this->p_value <= 0.7                                 => 'Sedang',
            (float) $this->p_value <= 0.8                                 => 'Mudah',
            default                                                        => 'Sangat Mudah',
        };
    }

    public function discriminationLabel(): string
    {
        if ($this->discrimination_index === null) return 'N/A';
        return match (true) {
            (float) $this->discrimination_index < 0.2  => 'Jelek',
            (float) $this->discrimination_index < 0.3  => 'Cukup',
            (float) $this->discrimination_index < 0.4  => 'Baik',
            default                                     => 'Sangat Baik',
        };
    }

    public function pValueColor(): string
    {
        if ($this->p_value === null) return 'gray';
        return match (true) {
            (float) $this->p_value < 0.2 || (float) $this->p_value > 0.8 => 'danger',
            (float) $this->p_value < 0.3 || (float) $this->p_value > 0.7 => 'warning',
            default                                                         => 'success',
        };
    }

    public function discriminationColor(): string
    {
        if ($this->discrimination_index === null) return 'gray';
        return match (true) {
            (float) $this->discrimination_index < 0.2 => 'danger',
            (float) $this->discrimination_index < 0.3 => 'warning',
            default                                    => 'success',
        };
    }
}
