<?php

namespace App\Mail;

use App\Models\KpPeriod;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PendingScoreReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public array $pendingRow, public KpPeriod $period)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Pengingat Penilaian KP - '.$this->period->name);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.pending-score-reminder');
    }
}
