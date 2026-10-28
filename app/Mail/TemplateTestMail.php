<?php

namespace App\Mail;

use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * "Send test to me" preview of an editable transactional template,
 * rendered with sample data.
 */
class TemplateTestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly EmailTemplate $template,
        public readonly string $renderedSubject,
        public readonly string $renderedBody,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->renderedSubject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.template-preview',
            with: [
                'name'      => $this->template->name,
                'bodyHtml'  => $this->renderedBody,
            ],
        );
    }
}
