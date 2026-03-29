<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'              => fake('id_ID')->name(),
            'email'             => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password'          => static::$password ??= Hash::make('password'),
            'remember_token'    => Str::random(10),
            'level'             => User::LEVEL_PESERTA,
            'username'          => fake()->unique()->userName(),
            'nomor_peserta'     => null,
            'rombel_id'         => null,
            'aktif'             => true,
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function peserta(): static
    {
        return $this->state(fn (array $attributes) => [
            'level' => User::LEVEL_PESERTA,
        ]);
    }

    public function guru(): static
    {
        return $this->state(fn (array $attributes) => [
            'level'         => User::LEVEL_GURU,
            'nomor_peserta' => null,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'level'         => User::LEVEL_ADMIN,
            'nomor_peserta' => null,
        ]);
    }

    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'level'         => User::LEVEL_SUPER_ADMIN,
            'nomor_peserta' => null,
        ]);
    }
}
