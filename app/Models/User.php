<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'level',
        'username',
        'nomor_peserta',
        'rombel_id',
        'aktif',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'aktif' => 'boolean',
        ];
    }

    // Level constants
    const LEVEL_PESERTA    = 1;
    const LEVEL_GURU       = 2;
    const LEVEL_ADMIN      = 3;
    const LEVEL_SUPER_ADMIN = 4;

    public static function levelLabels(): array
    {
        return [
            self::LEVEL_PESERTA     => 'Peserta',
            self::LEVEL_GURU        => 'Guru',
            self::LEVEL_ADMIN       => 'Admin',
            self::LEVEL_SUPER_ADMIN => 'Super Admin',
        ];
    }

    public function getLevelLabelAttribute(): string
    {
        return self::levelLabels()[$this->level] ?? 'Unknown';
    }

    // Relasi
    /**
     * Rombel tempat peserta ini terdaftar.
     */
    public function rombel(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Rombel::class, 'rombel_id');
    }

    /**
     * Rombel yang diampu guru ini (many-to-many via rombel_guru).
     */
    public function rombelsAmpu(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Rombel::class, 'rombel_guru', 'user_id', 'rombel_id');
    }

    // Scope
    public function scopeByLevel($query, int $level)
    {
        return $query->where('level', $level);
    }

    public function scopeAktif($query)
    {
        return $query->where('aktif', true);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->aktif && $this->level >= self::LEVEL_GURU;
    }
}
