<?php

namespace App\Models;

use Database\Factories\QuestionMatchFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class QuestionMatch extends Model
{
    /** @use HasFactory<QuestionMatchFactory> */
    use HasFactory;
    public $timestamps = false;

    protected static function boot(): void
    {
        parent::boot();

        static::updating(function (self $model) {
            if ($model->isDirty('gambar_premis') && $model->getOriginal('gambar_premis')) {
                Storage::disk('public')->delete($model->getOriginal('gambar_premis'));
            }
            if ($model->isDirty('gambar_respon') && $model->getOriginal('gambar_respon')) {
                Storage::disk('public')->delete($model->getOriginal('gambar_respon'));
            }
        });

        static::deleted(function (self $model) {
            if ($model->gambar_premis) {
                Storage::disk('public')->delete($model->gambar_premis);
            }
            if ($model->gambar_respon) {
                Storage::disk('public')->delete($model->gambar_respon);
            }
        });
    }

    protected $fillable = [
        'question_id',
        'premis',
        'gambar_premis',
        'respon',
        'gambar_respon',
        'urutan',
    ];

    public function question(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
