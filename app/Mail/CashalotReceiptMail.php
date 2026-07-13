<?php

namespace App\Mail;

use App\Models\Shop\CashalotLog;
use App\Models\Shop\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CashalotReceiptMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Order $order,
        public CashalotLog $cashalotLog,
    ) {
        app()->setLocale('uk');
    }

    public function envelope(): Envelope
    {
        $orderNumber = $this->order->number ?? ('#' . $this->order->id);

        return new Envelope(
            subject: 'Фіскальний чек до замовлення ' . $orderNumber,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: front_view('emails.cashalot-receipt'),
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
