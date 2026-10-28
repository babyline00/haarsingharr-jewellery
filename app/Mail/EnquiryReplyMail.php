<?php

namespace App\Mail;

use App\Models\ContactEnquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Staff reply to a contact-form enquiry, emailed to the customer.
 */
class EnquiryReplyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly ContactEnquiry $enquiry,
        public readonly string $reply,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Re: '.($this->enquiry->subject ?: 'Your enquiry to Haarsingharr.pk'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.enquiries.reply',
            with: [
                'enquiry' => $this->enquiry,
                'reply'   => $this->reply,
            ],
        );
    }
}
