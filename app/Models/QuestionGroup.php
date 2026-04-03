<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class QuestionGroup extends Model
{
  public $timestamps = false;

  const TIPE_STIMULUS_LABELS = [
    'teks'   => 'Teks',
    'gambar' => 'Gambar',
    'audio'  => 'Audio',
    'video'  => 'Video',
    'tabel'  => 'Tabel',
  ];

  protected $fillable = [
    'judul',
    'tipe_stimulus',
    'konten',
    'deskripsi',
    'created_by',
  ];

  protected static function booted(): void
  {
    static::creating(function (QuestionGroup $group) {
      if (empty($group->created_by) && Auth::check()) {
        $group->created_by = Auth::id();
      }
    });
  }

  public function questions(): \Illuminate\Database\Eloquent\Relations\HasMany
  {
    return $this->hasMany(Question::class)->orderBy('group_urutan');
  }

  public function creator(): \Illuminate\Database\Eloquent\Relations\BelongsTo
  {
    return $this->belongsTo(User::class, 'created_by');
  }
}
