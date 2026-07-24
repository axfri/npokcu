<?php

namespace App\Mail;

use App\Models\ProxyDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProxyDeliveryMail extends Mailable implements ShouldBeEncrypted, ShouldQueueAfterCommit
{
    use Queueable, SerializesModels;

    public function __construct(public ProxyDelivery $delivery)
    {
        $this->delivery->loadMissing(['order', 'orderItem']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Файл по заказу '.$this->delivery->order->order_number,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'mail.deliveries.ready');
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromStorageDisk(
                (string) config('deliveries.disk', 'private'),
                $this->delivery->file_path,
            )
                ->as($this->delivery->original_filename)
                ->withMime('text/plain'),
        ];
    }
}
