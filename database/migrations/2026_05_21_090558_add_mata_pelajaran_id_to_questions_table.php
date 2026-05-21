<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->unsignedBigInteger('mata_pelajaran_id')->nullable()->after('kategori_id');
        });

        // Populate existing records from the category relationship
        DB::statement('
            UPDATE questions q
            JOIN categories c ON q.kategori_id = c.id
            SET q.mata_pelajaran_id = c.mata_pelajaran_id
            WHERE q.kategori_id IS NOT NULL
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn('mata_pelajaran_id');
        });
    }
};
