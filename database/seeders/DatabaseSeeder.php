<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AppSettingsSeeder::class,
            UserSeeder::class,
            RombelSeeder::class,
            QuestionSeeder::class,
            QuestionGroupSeeder::class,
            ExamPackageSeeder::class,
            ExamSessionSeeder::class,
            AttemptSeeder::class,
            CurriculumStandardSeeder::class,
            MultiSectionMathSeeder::class,
            // ── Tambahan: harus setelah dependensinya ──────────────────────
            TagSeeder::class,              // depends on: QuestionSeeder
            AnnouncementSeeder::class,     // depends on: UserSeeder, RombelSeeder
            ExamBlueprintSeeder::class,    // depends on: CurriculumStandardSeeder, QuestionSeeder (untuk kategori)
            SessionNoteSeeder::class,      // depends on: ExamSessionSeeder, UserSeeder
        ]);
    }
}
