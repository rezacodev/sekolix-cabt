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
        Schema::create('rombels', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('kode', 30)->unique();
            $table->year('angkatan')->nullable();
            $table->string('tahun_ajaran', 20)->nullable();
            $table->text('keterangan')->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        // FK rombel_id on users — applied here because rombels did not exist when users was created
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('rombel_id')->references('id')->on('rombels')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['rombel_id']);
        });
        Schema::dropIfExists('rombels');
    }
};
