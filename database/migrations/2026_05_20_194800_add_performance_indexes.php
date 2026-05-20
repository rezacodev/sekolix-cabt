<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function indexExists(string $table, string $indexName): bool
    {
        $conn   = Schema::getConnection();
        $dbName = $conn->getDatabaseName();
        $count  = $conn->table('information_schema.statistics')
            ->where('table_schema', $dbName)
            ->where('table_name', $table)
            ->where('index_name', $indexName)
            ->count();
        return $count > 0;
    }

    public function up(): void
    {
        // exam_attempts: query paling sering (cek attempt aktif per user per sesi)
        Schema::table('exam_attempts', function (Blueprint $table) {
            if (! $this->indexExists('exam_attempts', 'idx_attempts_session_user_status')) {
                $table->index(['exam_session_id', 'user_id', 'status'], 'idx_attempts_session_user_status');
            }
        });

        // attempt_questions: query saat kerjakan & simpan jawaban
        Schema::table('attempt_questions', function (Blueprint $table) {
            if (! $this->indexExists('attempt_questions', 'idx_aq_attempt_question')) {
                $table->index(['attempt_id', 'question_id'], 'idx_aq_attempt_question');
            }
            if (! $this->indexExists('attempt_questions', 'idx_aq_attempt_section')) {
                $table->index(['attempt_id', 'section_id'], 'idx_aq_attempt_section');
            }
        });

        // attempt_logs: query saat cek log per attempt
        Schema::table('attempt_logs', function (Blueprint $table) {
            if (! $this->indexExists('attempt_logs', 'idx_logs_attempt_event')) {
                $table->index(['attempt_id', 'event_type'], 'idx_logs_attempt_event');
            }
        });

        // exam_session_participants: cek peserta terdaftar
        Schema::table('exam_session_participants', function (Blueprint $table) {
            if (! $this->indexExists('exam_session_participants', 'idx_esp_session_user')) {
                $table->index(['exam_session_id', 'user_id'], 'idx_esp_session_user');
            }
        });
    }

    public function down(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->dropIndex('idx_attempts_session_user_status');
        });

        Schema::table('attempt_questions', function (Blueprint $table) {
            $table->dropIndex('idx_aq_attempt_question');
            $table->dropIndex('idx_aq_attempt_section');
        });

        Schema::table('attempt_logs', function (Blueprint $table) {
            $table->dropIndex('idx_logs_attempt_event');
        });

        Schema::table('exam_session_participants', function (Blueprint $table) {
            $table->dropIndex('idx_esp_session_user');
        });
    }
};
