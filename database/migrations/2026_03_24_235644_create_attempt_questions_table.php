<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attempt_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')->constrained('exam_attempts')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete();
            $table->unsignedSmallInteger('urutan');
            $table->unsignedBigInteger('section_id')->nullable()->index(); // FK applied in create_exam_sections_table
            $table->text('jawaban_peserta')->nullable();
            $table->string('jawaban_file', 255)->nullable();
            $table->decimal('nilai_perolehan', 5, 2)->nullable();
            $table->boolean('is_correct')->nullable();
            $table->boolean('is_ragu')->default(false);
            $table->unsignedInteger('audio_play_count')->default(0);
            $table->dateTime('waktu_jawab')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attempt_questions');
    }
};
