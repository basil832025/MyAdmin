@extends(front_view('layouts.app'))

@php
    $locale = app()->getLocale();

    $pick = function ($value) use ($locale): string {
        if (is_array($value)) {
            $v = $value[$locale] ?? $value['uk'] ?? (count($value) ? reset($value) : null);
            return is_string($v) ? trim($v) : '';
        }

        return is_string($value) ? trim($value) : '';
    };

    $pageTitle = $pick($post->title ?? null);
    if ($pageTitle === '') {
        $pageTitle = is_string($title ?? null) ? trim((string) $title) : '';
    }

    $seoTitle = $pick($post->meta_title ?? null);
    if ($seoTitle === '') {
        $seoTitle = $pageTitle;
    }
    $seoTitle = trim(html_entity_decode($seoTitle, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

    $seoDescription = $pick($post->meta_description ?? null);
    if ($seoDescription === '') {
        $seoDescription = $pick($post->anons ?? null);
    }
    if ($seoDescription === '') {
        $seoDescription = $pick($post->content ?? null);
    }
    $seoDescription = trim(html_entity_decode($seoDescription, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    if ($seoDescription !== '') {
        $seoDescription = trim(preg_replace('/\s+/u', ' ', strip_tags($seoDescription)));
        if (mb_strlen($seoDescription) > 250) {
            $seoDescription = rtrim(mb_substr($seoDescription, 0, 247)) . '...';
        }
    }

    $seoKeywords = $pick($post->meta_keywords ?? null);
    if ($seoKeywords === '') {
        $seoKeywords = $pick($post->category?->meta_keywords ?? null);
    }
    $seoKeywords = trim(html_entity_decode($seoKeywords, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    $seoKeywords = trim(preg_replace('/\s+/u', ' ', strip_tags($seoKeywords)));

    $hero = $post->detail_image_url ?? $post->preview_image_url ?? '/images/no-image.svg';
@endphp

@section('title', $seoTitle)
@section('meta_description', $seoDescription)
@if($seoKeywords !== '')
    @section('meta_keywords', $seoKeywords)
@endif

@section('og_type', 'article')
@section('og_title', $seoTitle)
@section('og_description', $seoDescription)
@section('og_image', $hero)
@section('twitter_title', $seoTitle)
@section('twitter_description', $seoDescription)
@section('twitter_image', $hero)

@section('content')
    <div class="container mx-auto px-4 md:px-6 lg:px-8">

        @php
            $prefix = in_array($locale, ['ru', 'en'], true) ? '/' . $locale : '';
        @endphp

        {{-- Хлебные крошки --}}
        <nav class="text-sm mb-4 text-[#929292]">
            <a href="{{ url($prefix !== '' ? $prefix : '/') }}" class="hover:underline">{{ __('Головна') }}</a>
            <span class="mx-2">•</span>
            <a href="{{ url($prefix . '/' . ($post->category?->slug ?? 'blog')) }}">{{$title}}</a>
            <span class="mx-2">•</span>
            <span class="truncate inline-block align-bottom max-w-[60vw] md:max-w-none">
            {{ $post->title }}
        </span>
        </nav>

        {{-- Заголовок --}}
        <h1 class="text-3xl md:text-4xl font-bold leading-tight mb-4">
            {{ $post->title }}
        </h1>

        {{-- Мета-инфо: дата (бейдж), автор, категория --}}
        <div class="flex flex-wrap items-center gap-4 text-sm mb-6">
            {{--
            @if($date)
                <span class="inline-flex items-center gap-2 text-[11px] font-semibold
                     bg-[#FC791A] text-white rounded-[5px] px-2 py-1">
                    {{ $date }}
                </span>
            @endif

            <div class="inline-flex items-center gap-2 text-[#9E9E9E]">
                <svg width="14" height="14" viewBox="0 0 24 24" class="fill-[#FC791A]">
                    <path d="M12 12c2.76 0 5-2.24 5-5S14.76 2 12 2 7 4.24 7 7s2.24 5 5 5zm0 2c-3.33 0-10 1.67-10 5v3h20v-3c0-3.33-6.67-5-10-5z"/>
                </svg>
                <span>{{ $post->author_name ?? 'Admin' }}</span>
            </div>
            --}}

            @if($post->category?->title)
                <div class="inline-flex items-center gap-2 text-[#9E9E9E]">
                    <svg width="14" height="14" viewBox="0 0 24 24" class="fill-[#FC791A]">
                        <path d="M21.41 11.58l-9-9A2 2 0 0 0 11 2H4a2 2 0 0 0-2 2v7a2 2 0 0 0 .59 1.41l9 9a2 2 0 0 0 2.82 0l7-7a2 2 0 0 0 0-2.83zM7.5 8A1.5 1.5 0 1 1 9 6.5 1.5 1.5 0 0 1 7.5 8z"/>
                    </svg>
                    <span>{{ $post->category->title }}</span>
                </div>
            @endif
        </div>

        {{-- Большое изображение --}}
        <figure class="rounded-[20px] overflow-hidden mb-8 text-center">
            <img src="{{ $hero }}" alt="{{ $post->title }}" class="mx-auto w-auto object-cover h-auto md:h-[362px] rounded-[20px]">
        </figure>

        {{-- Контент --}}
        <article
            class="
    prose prose-neutral prose-img:rounded-[20px] prose-figure:rounded-[20px] prose-figure:overflow-hidden
    text-base prose-p:text-[16px] prose-li:text-[16px]
    max-w-[760px] md:max-w-[860px] lg:max-w-[980px] xl:max-w-[1100px]
    mx-auto                                  <!-- остаётся по центру, но шире -->
    prose-p:leading-7
  ">
            {!! $post->content !!}
        </article>
        {{-- ========== Комментарии ========== --}}
        {{-- ===== ФОРМА КОММЕНТАРИЯ ===== --}}
        <section id="comment-form"
                 x-data="{ replyingTo:null, replyingName:'', setReply(id,name){ this.replyingTo=id; this.replyingName=name; $refs.parent.value=id; $refs.content.focus(); } }">
            <h2 class="text-xl md:text-2xl font-semibold mb-4">
                {{ __('Коментарі') }} ({{ $comments->total() }})
            </h2>

            {{-- список --}}
            <div class="space-y-6">
                @forelse($comments as $c)
                    <div class="bg-white rounded-2xl border border-[#EFEFEF] p-4">
                        <div class="flex items-start gap-3">
                            {{-- аватар-инициалы --}}
                            <div class="w-10 h-10 rounded-full bg-[#FFEFE3] flex items-center justify-center text-[#FF7500] font-semibold">
                                {{ mb_substr($c->author_name,0,1) }}
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center justify-between">
                                    <div class="font-semibold">{{ $c->author_name }}</div>
                                    <time class="text-xs text-[#9E9E9E]">
                                        {{ $c->created_at?->locale(app()->getLocale())->isoFormat('D MMM YYYY') }}
                                    </time>
                                </div>
                                <div class="mt-2 text-sm leading-6 text-[#333]">
                                    {{ $c->content }}
                                </div>
                                <button
                                    type="button"
                                    class="mt-3 text-sm text-[#27AE60] inline-flex items-center gap-1"
                                    x-data
                                    @click="
                                const f = document.getElementById('comment-form');
                                f.querySelector('input[name=parent_id]').value='{{ $c->id }}';
                                f.scrollIntoView({behavior:'smooth', block:'center'});
                                f.querySelector('textarea').focus();
                            ">
                                    {{-- иконка-ответ --}}
                                    <svg width="16" height="16" viewBox="0 0 24 24" class="fill-current"><path d="M10 9V5l-7 7 7 7v-4.1c5 0 8.5 1.6 11 5.1-1-5-4-10-11-10z"/></svg>
                                    {{ st('reviews.reply', 'Відповісти') }}
                                </button>

                                {{-- дети --}}
                                @if($c->children->isNotEmpty())
                                    <div class="mt-4 pl-6 border-l border-[#EFEFEF] space-y-4">
                                        @foreach($c->children as $ch)
                                            <div>
                                                <div class="flex items-center justify-between">
                                                    <div class="font-semibold text-sm">{{ $ch->author_name }}</div>
                                                    <time class="text-xs text-[#9E9E9E]">
                                                        {{ $ch->created_at?->locale(app()->getLocale())->isoFormat('D MMM YYYY') }}
                                                    </time>
                                                </div>
                                                <div class="mt-1 text-sm leading-6 text-[#333]">
                                                    {{ $ch->content }}
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-[#9E9E9E]">{{ __('Поки немає коментарів. Будьте першим!') }}</p>
                @endforelse
            </div>

            {{-- пагинация --}}
            <div class="mt-6">
                {{ $comments->fragment('comments')->links() }}
            </div>

            {{-- флеш --}}
            @if(session('success'))
                <div class="mt-6 p-3 rounded-lg bg-green-50 text-green-700">
                    {{ session('success') }}
                </div>
            @endif
            <div class="bg-white rounded-md border border-[#EFEFEF]
              shadow-[0_2px_10px_rgba(0,0,0,0.08)]
              px-4 py-3 md:px-4 md:py-3">

                <h3 class="text-[22px] md:text-[24px] font-semibold mb-4">
                    {{ st('blog.comment_form.title', 'Оставить комментарий') }}
                </h3>

                {{-- бейдж «ответ на …» --}}
                <template x-if="replyingTo">
                    <div class="mb-4 flex items-center justify-between rounded-md bg-[#FFF3E8] px-3 py-2 text-sm">
                        <div>
                            {{ st('blog.comment_form.reply_to', 'Ответ на') }}: <span class="font-semibold" x-text="replyingName"></span>
                        </div>
                        <button type="button" class="text-[#FF7500] hover:underline"
                                @click="replyingTo=null; replyingName=''; $refs.parent.value=''">
                            {{ st('profile.cancel', 'Отменить') }}
                        </button>
                    </div>
                </template>
                @php
                    $input = 'w-full h-12 rounded-[10px] border border-[#E6E6E6] bg-white px-4
                              placeholder:text-[#9E9E9E]
                              outline-none focus:outline-none focus-visible:outline-none
                              ring-0 focus:ring-2 focus:ring-[#FF7500] focus:border-[#FF7500]
                              shadow-none focus:shadow-none appearance-none';
                @endphp
                <form id="comment-form" action="{{ route('blog.comments.store') }}" method="post" class="grid gap-6">
                    @csrf
                    <input type="hidden" name="blog_id" value="{{ $post->id }}">
                    <input type="hidden" name="parent_id" x-ref="parent" value="">
                    {{-- honeypot --}}
                    <input type="text" name="website" class="hidden" tabindex="-1" autocomplete="off">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="author_name" class="block mb-1 text-sm font-medium text-[#333]">
                                {{ st('profile.name', 'Имя') }} <span class="text-red-500">*</span>
                            </label>
                            <input id="author_name" name="author_name"
                                   placeholder="{{ st('reviews.enter_your_name', 'Введите имя') }}"
                                   class="{{ $input }}" />
                            @error('author_name')<div class="text-red-600 text-sm mt-1">{{ $message }}</div>@enderror
                        </div>

                        <div>
                            <label for="author_email" class="block text-sm font-medium text-[#333]">
                                Email ({{ st('profile.neobovyazkovo', 'необязательно') }})
                            </label>
                            <input id="author_email" name="author_email"
                                   value="{{ old('author_email', auth('web')->user()->email ?? '') }}"
                                   placeholder="Email ({{ st('profile.neobovyazkovo', 'необязательно') }})"
                                   class="{{ $input }}">
                            @error('author_email')<div class="text-red-600 text-sm mt-1">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div>
                        <label for="content" class="sr-only">{{ st('cart.addons.kitchen.placeholder', 'Комментарий') }}</label>
                        <textarea id="content" name="content" x-ref="content" rows="5"
                                  placeholder="{{ st('cart.addons.kitchen.placeholder', 'Комментарий') }}…"
                                  class="w-full rounded-[10px] border border-[#E6E6E6] bg-white
                         px-4 py-3 placeholder:text-[#9E9E9E]
                         focus:outline-none focus:ring-2 focus:ring-[#FF7500] focus:border-[#FF7500]">{{ old('content') }}</textarea>
                        @error('content')<div class="text-red-600 text-sm mt-1">{{ $message }}</div>@enderror
                    </div>

                    @if(config('services.turnstile.enabled') && filled(config('services.turnstile.site_key')))
                        <div class="flex justify-center">
                            <div class="cf-turnstile" data-sitekey="{{ config('services.turnstile.site_key') }}"></div>
                        </div>
                        @error('cf-turnstile-response')<div class="text-red-600 text-sm text-center -mt-3">{{ $message }}</div>@enderror
                    @endif

                    <div class="flex items-center justify-center md:justify-center">
                        <button type="submit"
                                class="inline-flex items-center justify-center h-12 px-8 rounded-xl
                       bg-[#FF7500] text-white font-semibold
                       shadow-[0_2px_0_rgba(0,0,0,0.12)]
                       hover:opacity-95 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#FF7500]">
                            {{ st('blog.comment_form.submit', 'Отправить') }}
                        </button>
                    </div>
                </form>
            </div>

            @if(config('services.turnstile.enabled') && filled(config('services.turnstile.site_key')))
                @once
                    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
                @endonce
            @endif
        </section>
        {{-- один раз на странице добавь слушатель (можно рядом с формой) --}}
        <script>
            document.addEventListener('click', function (e) {
                // Ответить
                const btn = e.target.closest('[data-reply]');
                if (btn) {
                    const form = document.getElementById('comment-form');
                    if (!form) return;

                    form.querySelector('input[name="parent_id"]').value = btn.dataset.id || '';
                    const badge = form.querySelector('[data-replying]');
                    if (badge) {
                        badge.classList.remove('hidden');
                        const nameEl = badge.querySelector('[data-name]');
                        if (nameEl) nameEl.textContent = btn.dataset.name || '';
                    }

                    form.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    const textarea = form.querySelector('textarea[name="content"]') || form.querySelector('textarea');
                    if (textarea) textarea.focus();
                }

                // Отменить ответ
                const cancel = e.target.closest('[data-reply-cancel]');
                if (cancel) {
                    const form = document.getElementById('comment-form');
                    if (!form) return;
                    form.querySelector('input[name="parent_id"]').value = '';
                    const badge = form.querySelector('[data-replying]');
                    if (badge) badge.classList.add('hidden');
                }
            });
        </script>



    </div>
@endsection
