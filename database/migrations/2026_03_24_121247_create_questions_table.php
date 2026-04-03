<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('question_group_id')->nullable()->index(); // FK applied in create_question_groups_table
            $table->unsignedInteger('group_urutan')->nullable();
            $table->unsignedBigInteger('curriculum_standard_id')->nullable()->index(); // FK applied in create_curriculum_standards_table
            $table->enum('bloom_level', ['C1', 'C2', 'C3', 'C4', 'C5', 'C6'])->nullable();
            $table->unsignedBigInteger('kategori_id')->nullable();
            $table->enum('tipe', ['PG', 'PG_BOBOT', 'PGJ', 'JODOH', 'ISIAN', 'URAIAN', 'BS', 'CLOZE']);
            $table->longText('teks_soal');
            $table->text('penjelasan')->nullable();
            $table->string('audio_url', 500)->nullable();
            $table->unsignedTinyInteger('audio_play_limit')->default(0)->comment('0 = tidak dibatasi');
            $table->boolean('audio_auto_play')->default(false);
            $table->enum('visibilitas', ['private', 'internal', 'publik'])->default('private');
            $table->enum('tingkat_kesulitan', ['mudah', 'sedang', 'sulit'])->default('sedang');
            $table->decimal('bobot', 5, 2)->default(1.00);
            $table->boolean('lock_position')->default(false);
            $table->boolean('aktif')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('kategori_id')->references('id')->on('categories')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
