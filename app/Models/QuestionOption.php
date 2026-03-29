<?php

namespace App\Models;

use Database\Factories\QuestionOptionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionOption extends Model
{
    /** @use HasFactory<QuestionOptionFactory> */
    use HasFactory;
    public $timestamps = false;

    protected $fillable = [
        'question_id',
        'kode_opsi',
        'teks_opsi',
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
