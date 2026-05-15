<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('curriculum_standards', function (Blueprint $table) {
            $table->string('capaian_pembelajaran', 200)->nullable()->after('nama');
            $table->string('materi', 200)->nullable()->after('capaian_pembelajaran');
        });
    }

    public function down(): void
    {
        Schema::table('curriculum_standards', function (Blueprint $table) {
            $table->dropColumn(['capaian_pembelajaran', 'materi']);
        });
    }
};
