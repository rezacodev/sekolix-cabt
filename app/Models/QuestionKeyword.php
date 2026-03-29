<?php

namespace App\Models;

use Database\Factories\QuestionKeywordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionKeyword extends Model
{
    /** @use HasFactory<QuestionKeywordFactory> */
    use HasFactory;
    public $timestamps = false;

    protected $fillable = [
        'question_id',
        'keyword',
    ];

    public function question(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
