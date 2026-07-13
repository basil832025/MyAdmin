{{-- resources/views/pages/show.blade.php --}}
@extends(front_view('layouts.app'))

@include(front_view('partials.seo.page'), ['page' => $page])

@section('content')
    <div class="mx-auto desk:w-[1198px] p-4  max-w-full">
        {{-- Хлебные крошки --}}
        <nav class="text-sm text-gray-500 my-4">
            <a href="{{ route('home') }}" class="hover:text-gray-700">{{ st('menu.home','Головна') }}</a>
            <span class="mx-2">→</span>
            <span class="text-gray-700">{{$page->title}}</span>
        </nav>
        <h2 class="inline-block mb-12 font-intro text-[40px] md:text-[64px] leading-[100%] md:leading-[64px] font-bold text-[#19191A] border-b-2 border-[#FF7500]">
            {{$page->title}}
        </h2>
    <section class="container mx-auto">
        <div class="prose max-w-none">
            {!! $page->content !!}
        </div>
    </section>
    </div>
@endsection
