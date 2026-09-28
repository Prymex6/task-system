<?php

namespace App\Mail;

use App\Models\Tenant\Invitation;
use App\Models\Tenant\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class TeamInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $acceptUrl;

    public function __construct(
        public Invitation $invitation,
        public User $invitedBy,
    ) {
        $this->acceptUrl = URL::to('/invitation/' . $invitation->token);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('messages.invitation_subject', ['app' => config('app.name')]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.team-invitation',
        );
    }
}
