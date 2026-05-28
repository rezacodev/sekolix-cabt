<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('questions', function (Blueprint $table) {
      $table->tinyInteger('kelas')->unsigned()->nullable()->after('kategori_id');
    });

    Schema::table('categories', function (Blueprint $table) {
      $table->tinyInteger('kelas')->unsigned()->nullable()->after('mata_pelajaran_id');
    });
  }

  public function down(): void
  {
    Schema::table('questions', function (Blueprint $table) {
      $table->dropColumn('kelas');
    });

    Schema::table('categories', function (Blueprint $table) {
      $table->dropColumn('kelas');
    });
  }
};
