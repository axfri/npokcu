<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ExistingAccountOrderMail extends Mailable implements ShouldBeEncrypted, ShouldQueueAfterCommit
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $orderNumber,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Новый заказ закреплён за вашим аккаунтом');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.accounts.existing-order');
    }

    public function attachments(): array
    {
        return [];
    }
}
