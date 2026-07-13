@php
    /** @var \App\Models\Pages|null $page */
    $page = $page ?? null;
    $defaultTitle = $defaultTitle ?? null;

    $locale = app()->getLocale();
    $fallback = config('translatable.fallback_locale', 'uk');

    $seoTitle = $page?->meta_title[$locale]
        ?? $page?->meta_title[$fallback]
        ?? $page?->getTitleForLocale($locale)
        ?? $defaultTitle
        ?? '';

    $seoDescription = $page?->meta_description[$locale]
        ?? $page?->meta_description[$fallback]
        ?? '';

    $seoKeywords = $page?->meta_keywords[$locale]
        ?? $page?->meta_keywords[$fallback]
        ?? '';
@endphp

@if($seoTitle !== '')
    @section('title', $seoTitle)
@endif
@section('meta_description', $seoDescription)
@section('meta_keywords', $seoKeywords)
