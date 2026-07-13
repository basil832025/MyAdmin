
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

    $pageTitle = $pick($category->name ?? null);
    if ($pageTitle === '') {
        $pageTitle = is_string($title ?? null) ? trim((string) $title) : '';
    }
    if ($pageTitle === '') {
        $pageTitle = 'Блог';
    }

    $seoTitle = $pick($category->meta_title ?? null);
    if ($seoTitle === '') {
        $seoTitle = $pageTitle;
    }
    $seoTitle = trim(html_entity_decode($seoTitle, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

    $seoDescription = $pick($category->meta_description ?? null);
    if ($seoDescription === '') {
        $seoDescription = $pick($category->description ?? null);
    }
    $seoDescription = trim(html_entity_decode($seoDescription, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    if ($seoDescription !== '') {
        $seoDescription = trim(preg_replace('/\s+/u', ' ', strip_tags($seoDescription)));
        if (mb_strlen($seoDescription) > 250) {
            $seoDescription = rtrim(mb_substr($seoDescription, 0, 247)) . '...';
        }
    }

    $seoKeywords = $pick($category->meta_keywords ?? null);
    $seoKeywords = trim(html_entity_decode($seoKeywords, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    $seoKeywords = trim(preg_replace('/\s+/u', ' ', strip_tags($seoKeywords)));
@endphp

@section('title', $seoTitle)
@section('meta_description', $seoDescription)
@if($seoKeywords !== '')
    @section('meta_keywords', $seoKeywords)
@endif

@section('og_title', $seoTitle)
@section('og_description', $seoDescription)
@section('twitter_title', $seoTitle)
@section('twitter_description', $seoDescription)

@section('content')
    <div class="mx-auto desk:w-[1198px]  max-w-full px-6">
        {{-- Хлебные крошки --}}
        <nav class="text-sm text-gray-500 my-4">
            <a href="{{ route('home') }}" class="hover:text-gray-700">{{ st('menu.home','Головна') }}</a>
            <span class="mx-2">→</span>
            <span class="text-gray-700">{{ $pageTitle }}</span>
        </nav>


        <section >
            <h2 class="inline-block font-intro mb-12 text-[40px] md:text-[64px] md:leading-[64px] font-bold text-[#19191A] border-b-2 border-[#FF7500]">
                {{ $pageTitle }}
            </h2>

        {{-- Если нужны табы по подкатегориям, можно вывести тут список категорий --}}

        {{-- Сетка карточек: 3 на десктопе, 2 на планшете, 1 на мобилке --}}
        <div class="grid gap-6 grid-cols-1 md:grid-cols-2 xl:grid-cols-3 mx-auto">
            @forelse ($posts as $post)
                <x-blog.card :post="$post" :categorySlug="$slug" :showDate="$slug !== 'discounts'" />
            @empty
                <p>Немає публікацій у цій категорії.</p>
            @endforelse
        </div>

        {{-- Пагинация --}}
            {{-- Пагинация как на макете --}}
            @if($posts->hasPages())
                <div class="mt-6 flex items-center justify-center gap-2">
                    <a href="{{ $posts->previousPageUrl() ?? '#' }}" class="w-10 h-10 rounded border flex items-center justify-center {{ $posts->onFirstPage() ? 'pointer-events-none opacity-40' : 'hover:border-[#FF7500]' }}">‹</a>
                    @php
                        $current = min($posts->currentPage(), $posts->lastPage());
                        $start = max(1, $current - 2);
                        $end = min($posts->lastPage(), $current + 2);
                    @endphp
                    @foreach($posts->getUrlRange($start, $end) as $page => $url)
                        <a href="{{ $url }}" class="w-10 h-10 rounded border flex items-center justify-center text-sm {{ $page === $posts->currentPage() ? 'bg-[#FF7500] text-white border-[#FF7500]' : 'hover:border-[#FF7500]' }}">
                            {{ $page }}
                        </a>
                    @endforeach
                    <a href="{{ $posts->nextPageUrl() ?? '#' }}" class="w-10 h-10 rounded border flex items-center justify-center {{ $posts->currentPage()===$posts->lastPage() ? 'pointer-events-none opacity-40' : 'hover:border-[#FF7500]' }}">›</a>
                </div>
            @endif

        </section>
    </div>
@endsection
