<?php

namespace App\Mail;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Order $order,
        public readonly bool $attachInvoice = false,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Haarsingharr order {$this->order->number} — confirmation",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.orders.confirmation',
            with: ['order' => $this->order],
        );
    }

    public function attachments(): array
    {
        if (! $this->attachInvoice) {
            return [];
        }

        $pdf = Pdf::loadView('admin.orders.invoice', [
            'order' => $this->order->loadMissing(['items', 'addresses', 'user']),
        ]);

        return [
            Attachment::fromData(fn () => $pdf->output(), "invoice-{$this->order->number}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
