<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamSection extends Model
{
  protected $fillable = [
    'exam_package_id',
    'nama',
    'urutan',
    'durasi_menit',
    'waktu_minimal_menit',
    'acak_soal',
    'acak_opsi',
  ];

  protected function casts(): array
  {
    return [
      'acak_soal'           => 'boolean',
      'acak_opsi'           => 'boolean',
      'durasi_menit'        => 'integer',
      'waktu_minimal_menit' => 'integer',
      'urutan'              => 'integer',
    ];
  }

  public function package(): \Illuminate\Database\Eloquent\Relations\BelongsTo
  {
    return $this->belongsTo(ExamPackage::class, 'exam_package_id');
  }

  public function questions(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
  {
    return $this->belongsToMany(Question::class, 'exam_section_questions', 'section_id', 'question_id')
      ->withPivot('urutan')
      ->orderByPivot('urutan');
  }

  public function questionPivots(): \Illuminate\Database\Eloquent\Relations\HasMany
  {
    return $this->hasMany(ExamSectionQuestion::class, 'section_id')->orderBy('urutan');
  }

  public function sectionStarts(): \Illuminate\Database\Eloquent\Relations\HasMany
  {
    return $this->hasMany(AttemptSectionStart::class, 'section_id');
  }
}
