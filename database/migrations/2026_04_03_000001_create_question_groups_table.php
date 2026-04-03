<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('question_groups', function (Blueprint $table) {
      $table->id();
      $table->string('judul', 255);
      $table->enum('tipe_stimulus', ['teks', 'gambar', 'audio', 'video', 'tabel'])->default('teks');
      $table->longText('konten');
      $table->text('deskripsi')->nullable();
      $table->unsignedBigInteger('created_by')->nullable();
      $table->timestamp('created_at')->useCurrent();

      $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
    });

    // FK question_group_id on questions — applied here because question_groups did not exist when questions was created
    Schema::table('questions', function (Blueprint $table) {
      $table->foreign('question_group_id')->references('id')->on('question_groups')->nullOnDelete();
    });
  }

  public function down(): void
  {
    Schema::table('questions', function (Blueprint $table) {
      $table->dropForeign(['question_group_id']);
    });
    Schema::dropIfExists('question_groups');
  }
};
