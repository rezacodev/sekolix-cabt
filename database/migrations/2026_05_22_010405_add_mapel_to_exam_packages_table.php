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
        Schema::table('exam_packages', function (Blueprint $table) {
            $table->unsignedBigInteger('mata_pelajaran_id')->nullable()->after('blueprint_id');
            $table->unsignedBigInteger('kategori_id')->nullable()->after('mata_pelajaran_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exam_packages', function (Blueprint $table) {
            $table->dropColumn(['mata_pelajaran_id', 'kategori_id']);
        });
    }
};
