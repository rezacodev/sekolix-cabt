<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->unsignedTinyInteger('kkm')->default(70)
                ->after('token_akses')
                ->comment('Kriteria Ketuntasan Minimal (0-100)');
            $table->unsignedTinyInteger('kkm_klasikal')->default(65)
                ->after('kkm')
                ->comment('% min peserta benar per soal untuk dianggap tuntas klasikal');
        });
    }

    public function down(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->dropColumn(['kkm', 'kkm_klasikal']);
        });
    }
};
