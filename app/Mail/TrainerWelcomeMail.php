<?php

namespace App\Mail;

use App\Models\Trainer;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TrainerWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $trainer;
    public $user;
    public $password;

    /**
     * Create a new message instance.
     */
    public function __construct(Trainer $trainer, User $user, string $password)
    {
        $this->trainer = $trainer;
        $this->user = $user;
        $this->password = $password;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Bienvenue dans notre plateforme - Votre compte formateur',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.trainer-welcome',
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
