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
        Schema::table('users', function (Blueprint $table) {
            $table->tinyInteger('level')->default(1)->after('name'); // 1=peserta,2=guru,3=admin,4=super_admin
            $table->string('username')->unique()->nullable()->after('level');
            $table->string('nomor_peserta')->unique()->nullable()->after('username');
            $table->foreignId('parent_user_id')->nullable()->constrained('users')->nullOnDelete()->after('nomor_peserta');
            $table->boolean('aktif')->default(true)->after('parent_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['parent_user_id']);
            $table->dropColumn(['level', 'username', 'nomor_peserta', 'parent_user_id', 'aktif']);
        });
    }
};
