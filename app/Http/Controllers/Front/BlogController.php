<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\BlogComment;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Services\SiteTemplates\SiteTemplateRenderer;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class BlogController extends Controller
{
    public function index(?string $categorySlug = null)
    {
        // дефолтная категория: 'blog'
        $slug = $categorySlug ?: 'blog';

        $category = BlogCategory::query()
            ->where('slug', $slug)
            ->firstOrFail();

        $posts = Blog::query()
            ->with(['category'])         // под заголовок категории
            ->published()
            ->forCategorySlug($slug)
            ->latest('published_at')
            ->paginate(9)
            ->withQueryString();

        // Protect from huge/invalid ?page=... values that can break pagination rendering.
        if ($posts->currentPage() > $posts->lastPage() && $posts->currentPage() > 1) {
            return response()->view(front_view('404'), [], 404);
        }
      //  dd($posts);
        // SEO (при необходимости)
        $title = $category->name ?? 'Блог';
       // dd($title,$category);

        return app(SiteTemplateRenderer::class)->render('pages.blog.index', front_view('pages.blog.index'), compact('category', 'posts', 'title', 'slug'));
    }
    public function show(string $slug)
    {
        $post = Blog::query()
            ->with(['category'])
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        // локализованная дата
        $date = $post->published_at?->locale(app()->getLocale())->isoFormat('D MMM YYYY');

    /*    // «ещё по теме»
        $related = Blog::query()
            ->published()
            ->where('id', '!=', $post->id)
            ->when($post->category_id, fn ($q) => $q->where('category_id', $post->category_id))
            ->latest('published_at')
            ->limit(6)
            ->get();*/

        $title = $post->title;

        return app(SiteTemplateRenderer::class)->render('pages.blog.show', front_view('pages.blog.show'), compact('post', 'date',  'title'));
    }
    public function showInCategory(string $categorySlug, string $postSlug)
    {
        [$orig, $norm] = $this->normalizeSlug($postSlug);

        $category = BlogCategory::where('slug', $categorySlug)->firstOrFail();
        $title = $category->name;
      //  dd($title);
        $post = Blog::with('category')
            ->published()
         //   ->where('category_id', $category->id)
            ->whereIn('slug', [$orig, $norm])
            ->firstOrFail();
        $comments = BlogComment::query()
            ->where('blog_id', $post->id)
            ->whereNull('parent_id')
            ->where('is_approved', true)
            ->with(['children' => fn ($q) => $q->where('is_approved', true)->orderBy('created_at')])
            ->latest()
            ->paginate(10)
            ->withQueryString();

        if ($comments->currentPage() > $comments->lastPage() && $comments->currentPage() > 1) {
            return response()->view(front_view('404'), [], 404);
        }
      //   dd($comments);
        return app(SiteTemplateRenderer::class)->render('pages.blog.show', front_view('pages.blog.show'), [
            'post'   => $post,
            'title'  => $title,
            'comments'  => $comments,
            'date'   => $post->published_at?->locale(app()->getLocale())->isoFormat('D MMM YYYY'),
            'related'=> Blog::published()
                ->where('id', '!=', $post->id)
                ->where('blog_category_id', $category->id)
                ->latest('published_at')
                ->limit(6)->get(),
        ]);
    }
    public function storeComment(Request $request)
    {
        if (trim((string) $request->input('website', '')) !== '') {
            throw ValidationException::withMessages([
                'content' => st('blog.comment_form.spam_detected', 'Виявлено спам. Спробуйте ще раз.'),
            ]);
        }

        $captchaEnabled = (bool) config('services.turnstile.enabled')
            && filled((string) config('services.turnstile.site_key'))
            && filled((string) config('services.turnstile.secret_key'));

        $data = $request->validate([
            'blog_id'      => ['required','integer', Rule::exists('bs_blogs','id')], // таблица постов
            'author_name'  => ['required','string','max:100'],
            'author_email' => ['nullable','email','max:150'],
            'content'      => ['required','string','min:5','max:5000'],
            'parent_id'    => ['nullable','integer', Rule::exists('bs_blog_comments','id')],
            'cf-turnstile-response' => $captchaEnabled ? ['required', 'string'] : ['nullable', 'string'],
            'website' => ['nullable', 'string', 'max:0'],
        ], [
            'cf-turnstile-response.required' => st('blog.comment_form.captcha_required', 'Підтвердіть, що ви не робот.'),
            'website.max' => st('blog.comment_form.spam_detected', 'Виявлено спам. Спробуйте ще раз.'),
        ]);

        if ($captchaEnabled && ! $this->verifyTurnstile($request)) {
            throw ValidationException::withMessages([
                'cf-turnstile-response' => st('blog.comment_form.captcha_failed', 'Помилка перевірки captcha. Спробуйте ще раз.'),
            ]);
        }

        unset($data['cf-turnstile-response'], $data['website']);

        $comment = new BlogComment();
        $comment->fill($data);
        if (auth('web')->check()) {
            $comment->user_id = null;
            $comment->author_name  = auth('web')->user()->name ?? $comment->author_name;
            $comment->author_email = auth('web')->user()->email ?? $comment->author_email;
        }
        // модерация: поставь true, если нужно автоапрувить
        $comment->is_approved = false;
        $comment->save();

        return back()->with(
            'success',
            $comment->is_approved
                ? st('blog.comment_form.success', 'Коментар додано.')
                : st('blog.comment_form.sent_for_moderation', 'Коментар надіслано на модерацію.')
        )->withFragment('comments'); // прокрутка к блоку
    }

    private function verifyTurnstile(Request $request): bool
    {
        $token = trim((string) $request->input('cf-turnstile-response', ''));
        $secret = (string) config('services.turnstile.secret_key');

        if ($token === '' || $secret === '') {
            return false;
        }

        $response = Http::asForm()
            ->timeout(8)
            ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => $secret,
                'response' => $token,
                'remoteip' => $request->ip(),
            ]);

        if (! $response->ok()) {
            return false;
        }

        return (bool) data_get($response->json(), 'success', false);
    }

    private function normalizeSlug(string $slug): array
    {
        $orig = urldecode($slug);
        $norm = Str::of($orig)->trim()->replace(['—','–','-'], '-')->__toString();
        return [$orig, $norm];
    }
}
