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
        Schema::create('curriculum_standards', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 50);
            $table->text('nama');
            $table->string('mata_pelajaran', 100);
            $table->enum('jenjang', ['SD', 'SMP', 'SMA', 'SMK']);
            $table->enum('kurikulum', ['K13', 'Merdeka', 'Internasional']);
            $table->string('kelas', 20)->nullable();
            $table->enum('tingkat_kognitif', ['C1', 'C2', 'C3', 'C4', 'C5', 'C6'])->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
        });

        // FK curriculum_standard_id on questions — applied here because curriculum_standards did not exist when questions was created
        Schema::table('questions', function (Blueprint $table) {
            $table->foreign('curriculum_standard_id')->references('id')->on('curriculum_standards')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropForeign(['curriculum_standard_id']);
        });
        Schema::dropIfExists('curriculum_standards');
    }
};
