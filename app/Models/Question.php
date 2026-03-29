<?php

namespace App\Models;

use Database\Factories\QuestionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    /** @use HasFactory<QuestionFactory> */
    use HasFactory;
    const TIPE_PG       = 'PG';
    const TIPE_PG_BOBOT = 'PG_BOBOT';
    const TIPE_PGJ      = 'PGJ';
    const TIPE_JODOH    = 'JODOH';
    const TIPE_ISIAN    = 'ISIAN';
    const TIPE_URAIAN   = 'URAIAN';

    const TIPE_LABELS = [
        self::TIPE_PG       => 'Pilihan Ganda',
        self::TIPE_PG_BOBOT => 'PG Berbobot',
        self::TIPE_PGJ      => 'PG Jawaban Jamak',
        self::TIPE_JODOH    => 'Menjodohkan',
        self::TIPE_ISIAN    => 'Isian Singkat',
        self::TIPE_URAIAN   => 'Uraian',
    ];

    const KESULITAN_LABELS = [
        'mudah'  => 'Mudah',
        'sedang' => 'Sedang',
        'sulit'  => 'Sulit',
    ];

    protected $fillable = [
        'kategori_id',
        'tipe',
        'teks_soal',
        'penjelasan',
        'tingkat_kesulitan',
        'bobot',
        'lock_position',
        'aktif',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'lock_position' => 'boolean',
            'aktif'         => 'boolean',
            'bobot'         => 'decimal:2',
        ];
    }

    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Category::class, 'kategori_id');
    }

    public function options(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(QuestionOption::class)->orderBy('urutan');
    }

    public function matches(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(QuestionMatch::class)->orderBy('urutan');
    }

    public function keywords(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(QuestionKeyword::class);
    }

    public function creator(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeAktif($query)
    {
        return $query->where('aktif', true);
    }

    public function scopeByTipe($query, string $tipe)
    {
        return $query->where('tipe', $tipe);
    }

    public static function tipeHasOptions(string $tipe): bool
    {
        return in_array($tipe, [self::TIPE_PG, self::TIPE_PG_BOBOT, self::TIPE_PGJ]);
    }

    public static function tipeHasMatches(string $tipe): bool
    {
        return $tipe === self::TIPE_JODOH;
    }

    public static function tipeHasKeywords(string $tipe): bool
    {
        return $tipe === self::TIPE_ISIAN;
    }
}
