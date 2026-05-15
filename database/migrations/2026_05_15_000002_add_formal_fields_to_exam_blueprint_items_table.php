<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_blueprint_items', function (Blueprint $table) {
            $table->string('capaian_pembelajaran', 200)->nullable()->after('urutan');
            $table->string('materi', 200)->nullable()->after('capaian_pembelajaran');
            $table->text('indikator')->nullable()->after('materi');
            $table->string('nomor_soal', 100)->nullable()->after('indikator');
        });
    }

    public function down(): void
    {
        Schema::table('exam_blueprint_items', function (Blueprint $table) {
            $table->dropColumn(['capaian_pembelajaran', 'materi', 'indikator', 'nomor_soal']);
        });
    }
};
