<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamBlueprint extends Model
{
  protected $fillable = [
    'nama',
    'mata_pelajaran',
    'deskripsi',
    'total_soal',
    'created_by',
  ];

  protected function casts(): array
  {
    return [
      'total_soal' => 'integer',
    ];
  }

  public function items(): \Illuminate\Database\Eloquent\Relations\HasMany
  {
    return $this->hasMany(ExamBlueprintItem::class, 'blueprint_id')->orderBy('urutan');
  }

  public function creator(): \Illuminate\Database\Eloquent\Relations\BelongsTo
  {
    return $this->belongsTo(User::class, 'created_by');
  }

  public function packages(): \Illuminate\Database\Eloquent\Relations\HasMany
  {
    return $this->hasMany(ExamPackage::class, 'blueprint_id');
  }

  /**
   * Returns array: [item_id => ['butuh' => N, 'tersedia' => N], ...]
   */
  public function validateStock(): array
  {
    $result = [];
    foreach ($this->items as $item) {
      $query = Question::query()->where('aktif', true);
      if ($item->category_id)        $query->where('kategori_id', $item->category_id);
      if ($item->standard_id)        $query->where('curriculum_standard_id', $item->standard_id);
      if ($item->tipe_soal)          $query->where('tipe', $item->tipe_soal);
      if ($item->tingkat_kesulitan)  $query->where('tingkat_kesulitan', $item->tingkat_kesulitan);
      if ($item->bloom_level)        $query->where('bloom_level', $item->bloom_level);
      if ($item->tag_id)             $query->whereHas('tags', fn($q) => $q->where('tags.id', $item->tag_id));
      $result[$item->id] = [
        'butuh'     => $item->jumlah_soal,
        'tersedia'  => $query->count(),
        'label'     => $item->kriteria_label,
      ];
    }
    return $result;
  }
}
