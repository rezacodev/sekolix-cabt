<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_blueprints', function (Blueprint $table) {
            $table->string('jenis_ujian', 100)->nullable()->after('total_soal');
            $table->string('kelas', 50)->nullable()->after('jenis_ujian');
            $table->string('bab', 100)->nullable()->after('kelas');
            $table->string('penyusun', 200)->nullable()->after('bab');
            $table->string('tahun_ajaran', 20)->nullable()->after('penyusun');
        });
    }

    public function down(): void
    {
        Schema::table('exam_blueprints', function (Blueprint $table) {
            $table->dropColumn(['jenis_ujian', 'kelas', 'bab', 'penyusun', 'tahun_ajaran']);
        });
    }
};
