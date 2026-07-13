<?php

namespace App\Mail;

use App\Models\EstablishmentReview;
use App\Models\Shop\ProductReview;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReviewNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  'product'|'establishment'  $type
     */
    public function __construct(
        public string $type,
        public ProductReview|EstablishmentReview $review,
        public string $moderationUrl,
    ) {
    }

    public function envelope(): Envelope
    {
        $kind = $this->type === 'product'
            ? 'товар'
            : 'заведение';

        return new Envelope(
            subject: 'Новый отзыв (' . $kind . ')',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: front_view('emails.review-notification'),
        );
    }
}
