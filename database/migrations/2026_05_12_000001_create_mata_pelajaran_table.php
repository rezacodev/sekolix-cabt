<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('mata_pelajaran', function (Blueprint $table) {
      $table->id();
      $table->string('nama', 100)->unique();
      $table->string('kode', 20)->nullable();
      $table->enum('jenjang', ['SD', 'SMP', 'SMA', 'SMK', 'Umum'])->default('Umum');
      $table->text('keterangan')->nullable();
      $table->boolean('aktif')->default(true);
      $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
      $table->timestamps();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('mata_pelajaran');
  }
};
