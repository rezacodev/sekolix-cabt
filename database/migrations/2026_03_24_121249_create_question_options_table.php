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
        Schema::create('question_options', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('question_id');
            $table->string('kode_opsi', 5);
            $table->longText('teks_opsi');
            $table->boolean('is_correct')->default(false);
            $table->tinyInteger('bobot_persen')->default(100)->unsigned();
            $table->integer('urutan')->default(0);
            $table->boolean('aktif')->default(true);

            $table->foreign('question_id')->references('id')->on('questions')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('question_options');
    }
};
