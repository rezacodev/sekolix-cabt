<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_matches', function (Blueprint $table) {
            $table->string('gambar_premis', 500)->nullable()->after('premis');
            $table->string('gambar_respon', 500)->nullable()->after('respon');
        });
    }

    public function down(): void
    {
        Schema::table('question_matches', function (Blueprint $table) {
            $table->dropColumn(['gambar_premis', 'gambar_respon']);
        });
    }
};
