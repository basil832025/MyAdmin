<x-mail::message>
<x-slot name="header">
<x-mail::header :url="config('app.url')">
<img src="{{ asset('images/logo.svg') }}" alt="{{ st('header.logo_alt', 'Три пироги') }}" style="max-height: 56px; width: auto;">
</x-mail::header>
</x-slot>

@php
    $type = (string) ($type ?? '');
    $moderationUrl = (string) ($moderationUrl ?? '');
    $rating = (int) ($review->rating ?? 0);
    $text = trim((string) (($review->content ?? null) ?? ($review->text ?? '')));

    $authorName = trim((string) (($review->name ?? null) ?? ($review->author_name ?? '')));
    $authorEmail = trim((string) (($review->email ?? null) ?? ''));

    $kindLabel = $type === 'product' ? 'відгук до товару' : 'відгук про заклад';

    $productTitle = null;
    if ($type === 'product') {
        $product = $review->product ?? null;
        if ($product) {
            $parent = $product->parent ?? $product;
            $productTitle = $parent->display_name ?? $parent->displayName ?? $parent->title ?? null;
            if (is_array($productTitle)) {
                $productTitle = $productTitle[app()->getLocale()] ?? $productTitle[array_key_first($productTitle)] ?? null;
            }
            $productTitle = $productTitle ? (string) $productTitle : null;
        }
    }

    $stars = $rating > 0 ? str_repeat('★', max(0, min(5, $rating))) : '—';
@endphp

# Новий {{ $kindLabel }}

Надійшов новий відгук і він очікує модерації.

## Дані

**Автор:** {{ $authorName !== '' ? $authorName : '—' }}  
**Email:** {{ $authorEmail !== '' ? $authorEmail : '—' }}  
**Оцінка:** {{ $stars }}

@if($productTitle)
**Товар:** {{ $productTitle }}
@endif

## Текст

{{ $text !== '' ? $text : '—' }}

@if($moderationUrl !== '')
<x-mail::button :url="$moderationUrl">
Перейти до модерації
</x-mail::button>
@endif

</x-mail::message>
