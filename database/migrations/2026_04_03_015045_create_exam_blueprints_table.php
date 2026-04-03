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
        Schema::create('exam_blueprints', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('mata_pelajaran', 100)->nullable();
            $table->text('deskripsi')->nullable();
            $table->unsignedInteger('total_soal')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // FK blueprint_id on exam_packages — applied here because exam_blueprints did not exist when exam_packages was created
        Schema::table('exam_packages', function (Blueprint $table) {
            $table->foreign('blueprint_id')->references('id')->on('exam_blueprints')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exam_packages', function (Blueprint $table) {
            $table->dropForeign(['blueprint_id']);
        });
        Schema::dropIfExists('exam_blueprints');
    }
};
