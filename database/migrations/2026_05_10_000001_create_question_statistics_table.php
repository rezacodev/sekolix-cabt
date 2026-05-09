<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_statistics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('total_attempts')->default(0);

            // P-value (difficulty index): proporsi peserta yang menjawab benar (0.000–1.000)
            // Interpretasi: <0.2 terlalu sulit, 0.3–0.7 ideal, >0.8 terlalu mudah
            $table->decimal('p_value', 4, 3)->nullable();

            // Discrimination Index (Point-Biserial): korelasi jawaban benar vs skor total
            // Interpretasi: <0.2 jelek, 0.2–0.3 cukup, ≥0.3 baik
            $table->decimal('discrimination_index', 4, 3)->nullable();

            // Distribusi pilihan jawaban per opsi (hanya untuk PG, PG_BOBOT, BS)
            // Format JSON: {"A":{"count":10,"persen":25.0,"correct":true,"teks":"..."}, ...}
            $table->json('distractor_distribution')->nullable();

            // Rata-rata waktu (detik) sejak attempt mulai hingga peserta menjawab soal ini
            $table->decimal('avg_response_seconds', 8, 2)->nullable();

            $table->timestamp('last_calculated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_statistics');
    }
};
