{{-- resources/views/pages/about.blade.php --}}
@extends(front_view('layouts.app'))

@include(front_view('partials.seo.page'), ['page' => $page])
@php
    $text = page_field('about', 'about_zagl','Ми печемо з душею, як для своїх') ?? ''; // строка из базы, например: "Мы печём с душой, как для своих"
    $parts = explode(' ', $text, 2);
    $btnText = page_field('about', 'about_btn_text', 'Познакомиться с меню');
    $btnUrl  = page_field('about', 'about_btn_url', '/pies');
@endphp
@section('content')
    <div class="mx-auto desk:w-[1198px] p-4  max-w-full">
        {{-- Хлебные крошки --}}
        <nav class="text-sm text-gray-500 my-4">
            <a href="{{ route('home') }}" class="hover:text-gray-700">{{ st('menu.home','Головна') }}</a>
            <span class="mx-2">→</span>
            <span class="text-gray-700">{{$page->title}}</span>
        </nav>
    <section >
        <h2 class="inline-block font-intro text-[40px] leading-[100%] md:text-[64px] md:leading-[64px] font-bold text-[#19191A] border-b-2 border-[#FF7500]">
            {{$page->title}}
        </h2>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-12 items-start">

            <!-- Левая колонка (текст) -->
            <div class="text-[#19191A]  text-[30px] md:text-[36px]  xl:text-[40px] leading-[22px] ">

                <h3 class="text-[30px] leading-[30px] md:text-[36px] md:leading-[36px] xl:text-[40px] xl:leading-[44px]"> @if(count($parts) > 1)
                        <span class="text-[#FF7500]">{{ $parts[0] }}</span> {{ $parts[1] }}
                    @else
                        <span class="text-[#19191A]">{{ $text }}</span>
                    @endif</h3>
                <div class="mt-6 md:mt-8 text-base leading-[22px] ">
                    {!! clean_html($page->content, 'safe',null,'<p><img><ul><li>') !!}

                </div>
                    <a href="{{ $btnUrl }}"
                   class="inline-block mt-6 bg-[#FF7500] text-lg hover:bg-orange-600 text-white font-semibold px-6 py-3  rounded transition">
                    {{ st('about.go_to_menu', 'Перейти в меню') }}
                 </a>
            </div>

            <!-- Правая колонка (картинки) -->
            <div class="grid grid-cols-2 gap-4 mt-8 md:mt-0">
                <div class="col-span-2">
                    <img src="{{ page_field('about', 'about_img1') }}" alt="Пирог" class="rounded-lg w-full object-cover w-[640px] h-[330px]">
                </div>
                <div>
                    <img src="{{ page_field('about', 'about_img2') }}" alt="Пирог" class="rounded-lg w-full object-cover w-[322px] h-[194px]">
                </div>
                <div>
                    <img src="{{ page_field('about', 'about_img3') }}" alt="Пирог" class="rounded-lg w-full object-cover w-[322px] h-[194px]">
                </div>
            </div>

        </div>
    </section>
    <section class=" mt-[120px]">
        <div class="container mx-auto lg:px-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 text-center justify-items-center md:justify-items-stretch">

                <!-- 1 -->
                <div class="flex flex-col items-center justify-between text-center
            bg-[#FFF9ED] rounded-[12px]
            p-6 md:p-8
      w-[355px] h-[286px] md:h-[300px] lg:w-[281px] lg:h-[322px]
                shadow-sm">
                    <img src="/images/svg/chef.svg" alt="" class="mb-4 w-[120px]">
                    <h2 class="text-[40px] font-bold">{{page_field('about', 'about_15','15 років+') }}</h2>
                    <div class="text-[26px] font-bold leading-[32px] text-[#19191A]">{{page_field('about', 'about_15_txt','З любов’ю пироги') }}</div>

                    @php

                    //    $p = \App\Models\Pages::where('slug','about')->first();
                    //     dd(array_keys($p->fields ?? []),data_get($p->fields, 'about_img1'),data_get($p->fields, 'about_15.uk'));
                    @endphp


                </div>

                <!-- 2 -->
                <div class="flex flex-col items-center justify-between text-center
            bg-[#FFF9ED] rounded-[12px]
            p-6 md:p-8
    w-[355px] h-[286px] md:h-[300px] lg:w-[281px] lg:h-[322px]
                  shadow-sm">
                    <img src="/images/svg/pie.svg" alt="" class="mb-4 w-[120px]">
                    <h2 class="text-[40px] font-bold">{{page_field('about', 'about_8000','15 років+') }}</h2>
                    <div class="text-[26px] font-bold leading-[32px] text-[#19191A]">{{page_field('about', 'about_8000_txt','З любов’ю пироги') }}</div>

                </div>

                <!-- 3 -->
                <div class="flex flex-col items-center justify-between text-center
            bg-[#FFF9ED] rounded-[12px]
            p-6 md:p-8
      w-[355px] h-[286px] md:h-[300px] lg:w-[281px] lg:h-[322px]
                shadow-sm">
                    <img src="/images/svg/star.svg" alt="" class="mb-4 w-[120px]">
                    <h2 class="text-[40px] font-bold">{{page_field('about', 'about_4_7stars','4,7 зірок') }}</h2>
                    <div class="text-[26px] font-bold leading-[32px] text-[#19191A]">{{page_field('about', 'about_4_7stars_txt','Середня оцінка клієнтів') }}</div>

                </div>

                <!-- 4 -->
                <div class="flex flex-col items-center justify-between text-center
            bg-[#FFF9ED] rounded-[12px]
            p-6 md:p-8
          w-[355px] h-[286px] md:h-[300px] lg:w-[281px] lg:h-[322px]
              shadow-sm">
                    <img src="/images/svg/plate.svg" alt="" class="mb-4 w-[120px]">
                    <h2 class="text-[40px] font-bold">{{page_field('about', 'about_repeat','4,7 зірок') }}</h2>
                    <div class="text-[26px] font-bold leading-[32px] text-[#19191A]">{{page_field('about', 'about_repeat_txt','Середня оцінка клієнтів') }}</div>


                </div>

            </div>
        </div>
    </section>
    </div>
    <section class="mx-auto desk:w-[1343px] w-[357px] md:w-[736px] max-w-full mt-[120px]">
        <div class="container mx-auto px-4">

            <div
                class="relative flex flex-col items-center gap-6
                   md:grid md:grid-cols-2 md:auto-rows-auto
                   lg:grid-cols-[1fr_minmax(0,590px)_1fr]">

                {{-- ===== КОНТЕНТ ===== --}}
                <div
                    class="order-1 text-center
                       md:col-span-2 md:row-start-1
                       lg:col-span-1 lg:col-start-2 lg:row-start-1">
                    <h3 class="font-bold text-3xl sm:text-4xl md:text-[36px] lg:text-[40px] leading-tight tracking-tight">
                        {{ page_field('about', 'about_title', 'Тысячи лет вкуса и традиций') }}
                    </h3>

                    <p class="mt-4 max-w-[720px] mx-auto text-sm sm:text-base text-gray-600">
                        {{ page_field('about', 'about_subtitle', 'Осетинские пироги — это не просто еда...') }}
                    </p>

                    {{-- Кнопка ТОЛЬКО для lg+ (внутри контента) --}}
                    <div class="mt-6 hidden lg:block">
                        <a href="{{ $btnUrl }}"
                           class="inline-flex items-center justify-center rounded-xl px-6 py-3
                              text-white font-semibold shadow-md bg-[#FF7500] hover:brightness-110
                              focus:outline-none focus:ring-4 focus:ring-orange-200 transition">
                            {{ $btnText }}
                        </a>
                    </div>
                </div>

                {{-- ===== ЛЕВАЯ ПИЦЦА ===== --}}
                <div
                    class="order-2 w-full
                       md:col-start-1 md:row-start-2
                       lg:col-start-1 lg:row-start-1">
                    <div class="relative mx-auto w-full max-w-[520px]">
                        <img
                            class="mx-auto w-full max-w-[319px] md:max-w-none md:h-[300px] md:w-[319px]"
                            src="{{ page_field('about', 'about_left_img') }}"
                            alt="{{ page_field('about', 'about_img_left_alt', 'Пиріг з інгредієнтами') }}">
                    </div>
                </div>

                {{-- ===== ПРАВАЯ ПИЦЦА ===== --}}
                <div
                    class="order-3 w-full
                       md:col-start-2 md:row-start-2
                       lg:col-start-3 lg:row-start-1">
                    <div class="relative md:ml-auto w-full max-w-[420px]">
                        <img
                            class="mx-auto w-full max-w-[391px] md:max-w-none md:h-[300px] md:w-[391px]"
                            src="{{ page_field('about', 'about_right_img') }}"
                            alt="{{ page_field('about', 'about_img_right_alt', 'Пиріг з лососем') }}">
                    </div>
                </div>

                {{-- Кнопка ТОЛЬКО для мобилы/планшета — под двумя пиццами --}}
                <div class="order-4 mt-2 md:col-span-2 md:row-start-3 text-center md:block lg:hidden">
                    <a href="{{ $btnUrl }}"
                       class="inline-flex items-center justify-center rounded-xl px-6 py-3
                          text-white font-semibold shadow-md bg-[#FF7500] hover:brightness-110
                          focus:outline-none focus:ring-4 focus:ring-orange-200 transition">
                        {{ $btnText }}
                    </a>
                </div>
            </div>

            {{-- Нижний текст --}}
            <div class="mt-10 sm:mt-12 lg:mt-14">
                <div class="mx-auto max-w-[1050px] text-center text-gray-700 space-y-5 leading-relaxed">
                    <p>{!! page_field('about', 'about_text_down', 'Осетинские пироги — это древняя традиция...') !!}</p>
                </div>
            </div>
        </div>
    </section>




@endsection
