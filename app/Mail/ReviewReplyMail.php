<?php

namespace App\Mail;

use App\Models\Review;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReviewReplyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Review $review,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Haarsingharr responded to your review of '.$this->review->product?->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reviews.reply',
            with: ['review' => $this->review],
        );
    }
}
