<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AutoCreatedAccountMail extends Mailable implements ShouldBeEncrypted, ShouldQueueAfterCommit
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $email,
        public readonly string $temporaryPassword,
        public readonly string $orderNumber,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Данные для входа в ПРОКСИ.НЕТ');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.accounts.auto-created');
    }

    public function attachments(): array
    {
        return [];
    }
}
