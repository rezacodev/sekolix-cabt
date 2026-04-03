<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuestionClozeBlank extends Model
{
  protected $fillable = [
    'question_id',
    'urutan',
    'placeholder',
    'jawaban_benar',
    'keywords_json',
    'case_sensitive',
  ];

  protected function casts(): array
  {
    return [
      'case_sensitive' => 'boolean',
    ];
  }

  public function question(): \Illuminate\Database\Eloquent\Relations\BelongsTo
  {
    return $this->belongsTo(Question::class);
  }
}
