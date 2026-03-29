<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttemptLog extends Model
{
    public $timestamps = false;

    const EVENT_TAB_SWITCH = 'tab_switch';
    const EVENT_BLUR       = 'blur';
    const EVENT_SUBMIT     = 'submit';
    const EVENT_TIMEOUT    = 'timeout';
    const EVENT_KICK       = 'kick';

    protected $fillable = [
        'attempt_id',
        'event_type',
        'detail',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function attempt(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ExamAttempt::class, 'attempt_id');
    }
}
