<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

class UserInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public readonly string $invitedEmail,
        public readonly string $inviterName,
        public readonly string $companyName,
        public readonly string $inviteLink,
        public readonly Carbon $expiresAt
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "You're invited to join {$this->companyName}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.user-invitation',
            text: 'emails.user-invitation-text',
            with: [
                'invitedEmail' => $this->invitedEmail,
                'inviterName' => $this->inviterName,
                'companyName' => $this->companyName,
                'inviteLink' => $this->inviteLink,
                'expiresAt' => $this->expiresAt,
                'logoUrl' => config('invitation.email.logo_url'),
                'primaryColor' => config('invitation.email.primary_color'),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
