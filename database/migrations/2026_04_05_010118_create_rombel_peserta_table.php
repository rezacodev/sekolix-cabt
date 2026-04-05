<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rombel_peserta', function (Blueprint $table) {
            $table->foreignId('rombel_id')->constrained('rombels')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->primary(['rombel_id', 'user_id']);
        });

        // Migrate existing rombel_id assignments from users table into pivot
        DB::table('users')
            ->where('level', 1) // LEVEL_PESERTA
            ->whereNotNull('rombel_id')
            ->orderBy('id')
            ->each(function ($user) {
                DB::table('rombel_peserta')->insertOrIgnore([
                    'rombel_id' => $user->rombel_id,
                    'user_id'   => $user->id,
                ]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rombel_peserta');
    }
};
