<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamBlueprintItem extends Model
{
  protected $fillable = [
    'blueprint_id',
    'category_id',
    'standard_id',
    'tipe_soal',
    'tingkat_kesulitan',
    'bloom_level',
    'tag_id',
    'jumlah_soal',
    'bobot_per_soal',
    'urutan',
  ];

  protected function casts(): array
  {
    return [
      'jumlah_soal'   => 'integer',
      'bobot_per_soal' => 'decimal:2',
      'urutan'        => 'integer',
    ];
  }

  public function blueprint(): \Illuminate\Database\Eloquent\Relations\BelongsTo
  {
    return $this->belongsTo(ExamBlueprint::class, 'blueprint_id');
  }

  public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
  {
    return $this->belongsTo(Category::class, 'category_id');
  }

  public function standard(): \Illuminate\Database\Eloquent\Relations\BelongsTo
  {
    return $this->belongsTo(CurriculumStandard::class, 'standard_id');
  }

  public function tag(): \Illuminate\Database\Eloquent\Relations\BelongsTo
  {
    return $this->belongsTo(Tag::class, 'tag_id');
  }

  public function getKriteriaLabelAttribute(): string
  {
    $parts = [];
    if ($this->category)           $parts[] = $this->category->nama;
    if ($this->standard)           $parts[] = $this->standard->kode;
    if ($this->tipe_soal)          $parts[] = $this->tipe_soal;
    if ($this->tingkat_kesulitan)  $parts[] = ucfirst($this->tingkat_kesulitan);
    if ($this->bloom_level)        $parts[] = $this->bloom_level;
    if ($this->tag)                $parts[] = '#' . $this->tag->nama;
    return implode(', ', $parts) ?: '(Semua Soal)';
  }

  /**
   * Pick $n random Question IDs matching this item's criteria, excluding $excludeIds.
   */
  public function pickQuestions(int $n, array $excludeIds = []): array
  {
    $query = Question::query()->where('aktif', true);
    if ($this->category_id)        $query->where('kategori_id', $this->category_id);
    if ($this->standard_id)        $query->where('curriculum_standard_id', $this->standard_id);
    if ($this->tipe_soal)          $query->where('tipe', $this->tipe_soal);
    if ($this->tingkat_kesulitan)  $query->where('tingkat_kesulitan', $this->tingkat_kesulitan);
    if ($this->bloom_level)        $query->where('bloom_level', $this->bloom_level);
    if ($this->tag_id)             $query->whereHas('tags', fn($q) => $q->where('tags.id', $this->tag_id));
    if ($excludeIds)               $query->whereNotIn('id', $excludeIds);
    return $query->inRandomOrder()->limit($n)->pluck('id')->all();
  }
}
