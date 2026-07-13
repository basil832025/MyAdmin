@extends('layouts.app')

@include('partials.seo.page', ['page' => $page])

@section('full_width', 'true')
@section('page', 'privatbank-installments')

@php
    $pageSlug = 'oplata-chastynamy-pryvatbank';

    $locale = app()->getLocale();

    $defaultCatalogUrl = $locale === 'uk'
        ? '/pies'
        : '/' . $locale . '/pies';

    $catalogUrl = strip_tags(
        page_field($pageSlug, 'pb_catalog_url', $defaultCatalogUrl) ?? $defaultCatalogUrl
    );

    $catalogButton = strip_tags(
        page_field($pageSlug, 'pb_catalog_button', 'Перейти до каталогу')
        ?? 'Перейти до каталогу'
    );
@endphp

@section('content')
    <div class="overflow-hidden bg-[#f8faf5] text-[#171d2d]">

        @include('pages.privatbank.hero')

        @include('pages.privatbank.benefits')

        @include('pages.privatbank.conditions')

        @include('pages.privatbank.steps')

        @include('pages.privatbank.faq')

        @include('pages.privatbank.cta')

    </div>
@endsection