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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 150);
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->text('deskripsi')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('parent_id')->references('id')->on('categories')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
