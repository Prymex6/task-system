<?php

namespace App\Mail;

use App\Models\Tenant\Proposal;
use App\Models\Tenant\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

/**
 * Sends a proposal to the client with its PDF attached.
 */
class ProposalMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Proposal $proposal) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('messages.proposal_subject', ['title' => $this->proposal->title]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.proposal',
            with: ['company' => Setting::get('company_name', config('app.name'))],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $this->proposal->loadMissing('client', 'items');

        $pdf = Pdf::loadView('pdf.proposal', ['proposal' => $this->proposal]);

        return [
            Attachment::fromData(fn () => $pdf->output(), 'propozycja-' . Str::slug($this->proposal->title) . '.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
