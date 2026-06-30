<?php

namespace App\Mail;

use Carbon\Carbon;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordFirstSetMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public readonly string $samaccountname,
        public readonly string $ip,
        public readonly Carbon $occurredAt,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Première connexion effectuée : '.$this->samaccountname,
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'mail.password-first-set',
        );
    }
}
