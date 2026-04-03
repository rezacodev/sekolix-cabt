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
        Schema::create('exam_blueprint_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blueprint_id')->constrained('exam_blueprints')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('standard_id')->nullable()->constrained('curriculum_standards')->nullOnDelete();
            $table->string('tipe_soal', 20)->nullable();
            $table->string('tingkat_kesulitan', 20)->nullable();
            $table->enum('bloom_level', ['C1', 'C2', 'C3', 'C4', 'C5', 'C6'])->nullable();
            $table->foreignId('tag_id')->nullable()->constrained('tags')->nullOnDelete();
            $table->unsignedInteger('jumlah_soal')->default(1);
            $table->decimal('bobot_per_soal', 5, 2)->default(1.00);
            $table->unsignedInteger('urutan')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_blueprint_items');
    }
};
