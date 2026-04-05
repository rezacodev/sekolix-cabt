<?php

namespace App\Models;

use Database\Factories\RombelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rombel extends Model
{
    /** @use HasFactory<RombelFactory> */
    use HasFactory;
    protected $fillable = [
        'nama',
        'kode',
        'angkatan',
        'tahun_ajaran',
        'keterangan',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'aktif'    => 'boolean',
            'angkatan' => 'integer',
        ];
    }

    /**
     * Guru yang mengampu rombel ini (many-to-many via rombel_guru).
     */
    public function gurus(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'rombel_guru', 'rombel_id', 'user_id')
            ->where('level', User::LEVEL_GURU);
    }

    /**
     * Peserta yang terdaftar di rombel ini (many-to-many via rombel_peserta).
     */
    public function peserta(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'rombel_peserta', 'rombel_id', 'user_id')
            ->where('level', User::LEVEL_PESERTA);
    }

    /**
     * Semua user (guru maupun peserta) yang terkait rombel via pivot.
     */
    public function users(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'rombel_guru', 'rombel_id', 'user_id');
    }

    public function scopeAktif($query)
    {
        return $query->where('aktif', true);
    }
}
