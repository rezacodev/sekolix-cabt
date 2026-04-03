<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamSectionQuestion extends Model
{
  public $timestamps = false;

  protected $fillable = [
    'section_id',
    'question_id',
    'urutan',
  ];
}
