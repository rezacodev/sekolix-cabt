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
    const TIPE_BS       = 'BS';
    const TIPE_CLOZE    = 'CLOZE';

    const TIPE_LABELS = [
        self::TIPE_PG       => 'Pilihan Ganda',
        self::TIPE_PG_BOBOT => 'PG Berbobot',
        self::TIPE_PGJ      => 'PG Jawaban Jamak',
        self::TIPE_JODOH    => 'Menjodohkan',
        self::TIPE_ISIAN    => 'Isian Singkat',
        self::TIPE_URAIAN   => 'Uraian',
        self::TIPE_BS       => 'Benar/Salah',
        self::TIPE_CLOZE    => 'Cloze/Isian Teks',
    ];

    const KESULITAN_LABELS = [
        'mudah'  => 'Mudah',
        'sedang' => 'Sedang',
        'sulit'  => 'Sulit',
    ];

    const VISIBILITAS_PRIVATE  = 'private';
    const VISIBILITAS_INTERNAL = 'internal';
    const VISIBILITAS_PUBLIK   = 'publik';

    const VISIBILITAS_LABELS = [
        self::VISIBILITAS_PRIVATE  => 'Pribadi (Hanya Saya)',
        self::VISIBILITAS_INTERNAL => 'Internal (Semua Guru)',
        self::VISIBILITAS_PUBLIK   => 'Publik (Seluruh Sekolah)',
    ];

    protected $fillable = [
        'question_group_id',
        'group_urutan',
        'curriculum_standard_id',
        'bloom_level',
        'kategori_id',
        'tipe',
        'teks_soal',
        'penjelasan',
        'audio_url',
        'audio_play_limit',
        'audio_auto_play',
        'visibilitas',
        'tingkat_kesulitan',
        'bobot',
        'lock_position',
        'aktif',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'lock_position'    => 'boolean',
            'aktif'            => 'boolean',
            'bobot'            => 'decimal:2',
            'audio_auto_play'  => 'boolean',
            'audio_play_limit' => 'integer',
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

    public function group(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(QuestionGroup::class, 'question_group_id');
    }

    public function standard(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(CurriculumStandard::class, 'curriculum_standard_id');
    }

    public function tags(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'question_tag');
    }

    public function creator(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeAktif($query)
    {
        return $query->where('aktif', true);
    }

    public function scopeStandalone($query)
    {
        return $query->whereNull('question_group_id');
    }

    public function scopeInGroup($query)
    {
        return $query->whereNotNull('question_group_id');
    }

    public function scopeByTipe($query, string $tipe)
    {
        return $query->where('tipe', $tipe);
    }

    public static function tipeHasOptions(string $tipe): bool
    {
        return in_array($tipe, [self::TIPE_PG, self::TIPE_PG_BOBOT, self::TIPE_PGJ, self::TIPE_BS]);
    }

    public static function tipeHasMatches(string $tipe): bool
    {
        return $tipe === self::TIPE_JODOH;
    }

    public static function tipeHasKeywords(string $tipe): bool
    {
        return in_array($tipe, [self::TIPE_ISIAN, self::TIPE_CLOZE]);
    }

    public function clozeBlank(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(QuestionClozeBlank::class)->orderBy('urutan');
    }
}
