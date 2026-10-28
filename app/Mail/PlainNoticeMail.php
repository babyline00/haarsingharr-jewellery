<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Simple plain-text notice mailable — used for the settings "send a test
 * email" button and other generic background notifications.
 */
class PlainNoticeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $noticeSubject,
        public readonly string $body,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->noticeSubject);
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.plain-notice',
            with: ['body' => $this->body],
        );
    }
}
