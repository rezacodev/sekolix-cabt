<?php

namespace App\Mail;

use App\Models\ExamSession;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SesiDijadwalkanMail extends Mailable implements ShouldQueue
{
  use Queueable, SerializesModels;

  public function __construct(
    public readonly User        $peserta,
    public readonly ExamSession $session,
  ) {}

  public function envelope(): Envelope
  {
    $appName = \App\Models\AppSetting::getString('app_name', config('app.name'));
    return new Envelope(
      subject: "[{$appName}] Anda dijadwalkan dalam ujian: {$this->session->nama_sesi}",
    );
  }

  public function content(): Content
  {
    return new Content(
      view: 'mail.sesi-dijadwalkan',
    );
  }
}
