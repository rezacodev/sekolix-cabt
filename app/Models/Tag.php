<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
  public $timestamps = false;

  protected $fillable = ['nama'];

  public function questions(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
  {
    return $this->belongsToMany(Question::class, 'question_tag');
  }
}
