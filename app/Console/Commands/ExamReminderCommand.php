<?php

namespace App\Console\Commands;

use App\Mail\ReminderUjianMail;
use App\Models\AppSetting;
use App\Models\ExamSession;
use App\Models\ExamSessionParticipant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class ExamReminderCommand extends Command
{
  protected $signature   = 'exam:reminder';
  protected $description = 'Kirim email reminder H-1 ke peserta yang memiliki ujian besok.';

  public function handle(): int
  {
    if (! AppSetting::getBool('email_reminder_h1', false)) {
      $this->info('Reminder H-1 dinonaktifkan di pengaturan. Tidak ada email yang dikirim.');
      return self::SUCCESS;
    }

    $tomorrow = now()->addDay()->toDateString();

    // Sesi yang mulai besok (tanggal waktu_mulai = besok)
    $sessions = ExamSession::with(['participants.user', 'package'])
      ->where('status', ExamSession::STATUS_AKTIF)
      ->whereDate('waktu_mulai', $tomorrow)
      ->get();

    $sent = 0;

    foreach ($sessions as $session) {
      foreach ($session->participants as $participant) {
        $user = $participant->user;
        if (! $user || ! $user->email) {
          continue;
        }
        // Hanya peserta yang belum mulai
        if ($participant->status !== ExamSessionParticipant::STATUS_BELUM) {
          continue;
        }

        Mail::to($user->email)->queue(new ReminderUjianMail($user, $session));
        $sent++;
      }
    }

    $this->info("Reminder terkirim: {$sent} email ke {$sessions->count()} sesi.");
    return self::SUCCESS;
  }
}
