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
        Schema::create('exam_packages', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->text('deskripsi')->nullable();
            $table->unsignedInteger('durasi_menit');
            $table->unsignedInteger('waktu_minimal_menit')->default(0);
            $table->boolean('acak_soal')->default(false);
            $table->boolean('acak_opsi')->default(false);
            $table->unsignedInteger('max_pengulangan')->default(1);
            $table->boolean('tampilkan_hasil')->default(true);
            $table->boolean('tampilkan_review')->default(false);
            $table->enum('grading_mode', ['realtime', 'manual'])->default('realtime');
            $table->decimal('nilai_negatif', 4, 2)->default(0.00);
            $table->boolean('nilai_negatif_kosong')->default(false);
            $table->boolean('nilai_negatif_clamp')->default(true);
            $table->unsignedSmallInteger('waktu_per_soal_detik')->default(0);
            $table->enum('waktu_per_soal_navigasi', ['bebas', 'maju'])->default('bebas');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('blueprint_id')->nullable()->index(); // FK applied in create_exam_blueprints_table
            $table->boolean('has_sections')->default(false);
            $table->string('navigasi_seksi', 20)->default('urut');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_packages');
    }
};
