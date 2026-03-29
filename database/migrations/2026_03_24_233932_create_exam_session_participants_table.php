<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_session_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_session_id')->constrained('exam_sessions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['belum', 'sedang', 'selesai', 'diskualifikasi'])->default('belum');
            $table->unique(['exam_session_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_session_participants');
    }
};
