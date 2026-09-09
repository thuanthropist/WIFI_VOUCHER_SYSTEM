<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DailyRevenueReport extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $day,
        public float|string $revenue,
        public int $paymentsCount,
        public int $vouchersIssued,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Daily Revenue Report — {$this->day}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.daily-revenue-report',
        );
    }

    /**
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
