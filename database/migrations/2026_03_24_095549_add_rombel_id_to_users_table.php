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
        // Drop foreign key dan kolom parent_user_id
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['parent_user_id']);
            $table->dropColumn('parent_user_id');
        });

        // Tambah kolom rombel_id
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('rombel_id')->nullable()->after('nomor_peserta')->constrained('rombels')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['rombel_id']);
            $table->dropColumn('rombel_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('parent_user_id')->nullable()->constrained('users')->nullOnDelete();
        });
    }
};
