<?php

namespace App\Mail;

use App\Models\Shop\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderClientMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Order $order,
        public string $mailLocale = 'uk',
    ) {
        $this->mailLocale = in_array($this->mailLocale, ['uk', 'ru', 'en'], true)
            ? $this->mailLocale
            : 'uk';

        app()->setLocale($this->mailLocale);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        app()->setLocale($this->mailLocale);

        $subjectPrefix = match ($this->mailLocale) {
            'ru' => 'Ваш заказ №',
            'en' => 'Your order #',
            default => 'Ваше замовлення №',
        };

        $subject = $subjectPrefix . ($this->order->number ?? $this->order->id);

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        app()->setLocale($this->mailLocale);

        return new Content(
            markdown: front_view('emails.order-client'),
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
