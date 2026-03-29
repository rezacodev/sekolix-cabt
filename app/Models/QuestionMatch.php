<?php

namespace App\Models;

use Database\Factories\QuestionMatchFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionMatch extends Model
{
    /** @use HasFactory<QuestionMatchFactory> */
    use HasFactory;
    public $timestamps = false;

    protected $fillable = [
        'question_id',
        'premis',
        'respon',
        'urutan',
    ];

    public function question(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
