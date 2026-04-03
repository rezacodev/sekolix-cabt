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
        Schema::create('exam_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_package_id')->constrained('exam_packages')->cascadeOnDelete();
            $table->string('nama', 255);
            $table->unsignedInteger('urutan')->default(1);
            $table->unsignedInteger('durasi_menit');
            $table->unsignedInteger('waktu_minimal_menit')->default(0);
            $table->boolean('acak_soal')->default(false);
            $table->boolean('acak_opsi')->default(false);
            $table->timestamps();
        });

        // FK section_id on attempt_questions — applied here because exam_sections did not exist when attempt_questions was created
        Schema::table('attempt_questions', function (Blueprint $table) {
            $table->foreign('section_id')->references('id')->on('exam_sections')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attempt_questions', function (Blueprint $table) {
            $table->dropForeign(['section_id']);
        });
        Schema::dropIfExists('exam_sections');
    }
};
