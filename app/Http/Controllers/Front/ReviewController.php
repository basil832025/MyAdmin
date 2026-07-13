<?php
// app/Http/Controllers/Front/ReviewController.php
namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Mail\ReviewNotificationMail;
use App\Models\EstablishmentReview;
use App\Models\Pages;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{
    public function index()
    {
        $locationId = 1;
        $slug='feedbacks';
        $page = Pages::query()
            ->where('slug', $slug)
            ->firstOrFail();
        // список для страницы (с пагинацией, чтобы работал твой блок пагинации)
        $reviews = EstablishmentReview::query()
            ->active()->forLocation($locationId)->newest()
            ->paginate(10);

        // Invalid/out-of-range page should show our 404 page.
        if ($reviews->currentPage() > $reviews->lastPage() && $reviews->currentPage() > 1) {
            return response()->view(front_view('404'), [], 404);
        }

        // агрегаты для шапки (средняя, проценты по звёздам)
        $stats = EstablishmentReview::query()
            ->active()->forLocation($locationId)
            ->selectRaw('
                COUNT(*) as total,
                AVG(rating) as avg_rating,
                SUM(CASE WHEN rating=5 THEN 1 ELSE 0 END) as r5,
                SUM(CASE WHEN rating=4 THEN 1 ELSE 0 END) as r4,
                SUM(CASE WHEN rating=3 THEN 1 ELSE 0 END) as r3,
                SUM(CASE WHEN rating=2 THEN 1 ELSE 0 END) as r2,
                SUM(CASE WHEN rating=1 THEN 1 ELSE 0 END) as r1
            ')
            ->first();

        return view(front_view('pages.reviews'), compact('reviews', 'stats','page'));
    }

    public function store(Request $request)
    {
        // honeypot
        if ($request->filled('hp')) {
            return response()->json(['ok' => true]);
        }

        $v = Validator::make($request->all(), [
            'name'    => ['required','string','max:100'],
            'email'   => ['nullable','email','max:150'], // в модель не пишем, просто валидируем
            'content' => ['required','string','min:10','max:3000'],
            'rating'  => ['required','integer','min:1','max:5'],
            'location_id' => ['nullable','integer'],
        ], [], [
            'name' => __('Ім’я'),
            'content' => __('Відгук'),
            'rating' => __('Оцінка'),
        ]);

        if ($v->fails()) {
            return response()->json(['errors' => $v->errors()], 422);
        }

        $locationId = (int)($request->input('location_id') ?: 1);

        $review = EstablishmentReview::create([
            'author_name' => $request->string('name'),
            'text'        => $request->string('content'),
            'rating'      => (int) $request->input('rating', 5),
            'location_id' => $locationId,
            'is_active'   => false,   // на модерацию
            'posted_at'   => now(),
        ]);

        // Notify admins (same recipients as order notifications)
        try {
            $notificationEmails = config('notifications.order_notification_email', []);
            if (is_string($notificationEmails)) {
                $notificationEmails = array_filter(array_map('trim', explode(',', $notificationEmails)));
            }

            if (! empty($notificationEmails)) {
                $moderationUrl = \App\Filament\Resources\EstablishmentReviewResource::getUrl('edit', [
                    'record' => $review,
                ]);

                Mail::to($notificationEmails)->send(new ReviewNotificationMail(
                    type: 'establishment',
                    review: $review,
                    moderationUrl: $moderationUrl,
                ));
            }
        } catch (\Throwable $e) {
            Log::error('Failed to send review notification email: '.$e->getMessage(), [
                'type' => 'establishment',
                'review_id' => $review->id ?? null,
            ]);
        }

        return response()->json(['ok' => true]);
    }
}
