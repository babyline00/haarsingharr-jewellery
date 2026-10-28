<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

/**
 * Scheduled / on-demand admin report: period summary in the body,
 * XLSX sheet of the rows attached.
 *
 * Note: promoted properties must not collide with Mailable's own
 * $from/$subject/$to internals — hence $periodFrom/$periodTo.
 */
class ReportEmailMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $type,
        public readonly Carbon $periodFrom,
        public readonly Carbon $periodTo,
        public readonly string $attachmentBinary,
    ) {}

    public function envelope(): Envelope
    {
        $title = ucfirst(str_replace('-', ' ', $this->type));

        return new Envelope(
            subject: "[Report] {$title} — {$this->periodFrom->format('M j')} to {$this->periodTo->format('M j, Y')}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reports.report-email',
            with: [
                'title' => ucfirst(str_replace('-', ' ', $this->type)),
                'from'  => $this->periodFrom->format('M j, Y'),
                'to'    => $this->periodTo->format('M j, Y'),
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->attachmentBinary)
                ->as($this->type.'-report-'.now()->format('Ymd').'.xlsx')
                ->withMime('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
        ];
    }
}
