<?php

namespace App\Models;

use Database\Factories\QuestionOptionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class QuestionOption extends Model
{
    /** @use HasFactory<QuestionOptionFactory> */
    use HasFactory;
    public $timestamps = false;

    protected static function boot(): void
    {
        parent::boot();

        static::updating(function (self $model) {
            if ($model->isDirty('gambar_opsi') && $model->getOriginal('gambar_opsi')) {
                Storage::disk('public')->delete($model->getOriginal('gambar_opsi'));
            }
        });

        static::deleted(function (self $model) {
            if ($model->gambar_opsi) {
                Storage::disk('public')->delete($model->gambar_opsi);
            }
        });
    }

    protected $fillable = [
        'question_id',
        'kode_opsi',
        'teks_opsi',
        'gambar_opsi',
        'is_correct',
        'bobot_persen',
        'urutan',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
            'aktif'      => 'boolean',
        ];
    }

    public function question(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
