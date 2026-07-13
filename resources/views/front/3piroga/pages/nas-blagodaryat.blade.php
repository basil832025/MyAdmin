{{-- resources/views/pages/show.blade.php --}}
@extends(front_view('layouts.app'))

@php
    $pageTitle = $page->getTitleForLocale() ?? $page->title;
    $pageContent = $page->getContentForLocale() ?? $page->content;
@endphp
@include(front_view('partials.seo.page'), ['page' => $page, 'defaultTitle' => $pageTitle])

@section('content')
    <div class="mx-auto desk:w-[1198px] p-4  max-w-full">
        {{-- Хлебные крошки --}}
        <nav class="text-sm text-gray-500 my-4">
            <a href="{{ route('home') }}" class="hover:text-gray-700">{{ st('menu.home','Головна') }}</a>
            <span class="mx-2">→</span>
            <span class="text-gray-700">{{ $pageTitle }}</span>
        </nav>
        <h2 class="inline-block mb-12 font-intro text-[40px] md:text-[64px] leading-[100%] md:leading-[64px] font-bold text-[#19191A] border-b-2 border-[#FF7500]">
            {{ $pageTitle }}
        </h2>
        <section class="container mx-auto ">
            <div class="grid grid-cols-1 xl:grid-cols-3 md:grid-cols-2 gap-6 place-items-center justify-center mx-auto [&>img]:w-full [&>img]:rounded-lg [&>img]:shadow-md">
                {!! clean_html($pageContent, 'safe',null,'<img>') !!}
            </div>
        </section>
    </div>
@endsection
