<?php

use App\Models\ExamAttempt;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->boolean('is_auto_submit')->default(false)->after('status');
        });

        // Backfill: attempts yang timeout/diskualifikasi dianggap auto-submit
        DB::table('exam_attempts')
            ->whereIn('status', [ExamAttempt::STATUS_TIMEOUT, ExamAttempt::STATUS_DISKUALIFIKASI])
            ->update(['is_auto_submit' => true]);
    }

    public function down(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->dropColumn('is_auto_submit');
        });
    }
};
