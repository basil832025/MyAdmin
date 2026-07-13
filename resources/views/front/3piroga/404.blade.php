@extends(front_view('layouts.app'))

@section('title', '404 — Сторінку не знайдено')

@section('content')
    <section class="relative "> {{-- безопасный отступ от хедера pt-24 sm:pt-28 lg:pt-32 pb-12 sm:pb-16 lg:pb-24--}}
        <div class="container mx-auto px-4">
            <div class="mx-auto w-full max-w-[1000px]">
                {{-- Холст: поведение по брейкпоинтам --}}
                <div class="relative mx-auto max-w-[914px] aspect-[4/3] sm:aspect-[3/2] md:aspect-[914/500]">

                    {{-- Цифры 404 (без инлайн размеров, только Tailwind) --}}
                    <div class="absolute inset-0 flex items-center justify-center gap-[0.06em] pointer-events-none z-10 select-none text-[#FF7500]"
                         style="font-family:'Pacifico',cursive; font-weight:500;">
          <span class="block leading-none
                        text-[200px] md:text-[400px] lg:text-[500px]">4</span>
                        <span class="block leading-none
                        text-[200px] md:text-[400px] lg:text-[500px]">0</span>
                        <span class="block leading-none
                       text-[200px] md:text-[400px] lg:text-[500px]">4</span>
                    </div>

                    {{-- Пиріг — центр, размер по брейкпоинтам; под хедер не залезет, т.к. нет отрицательных маржинов --}}
                    <img
                        src="{{ asset('images/404.png') }}"
                        alt="Пиріг"
                        class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-[27%] z-20
                 h-auto object-contain
                 w-[54%] md:w-[54%] lg:w-[54%]
                 drop-shadow-[0_8px_16px_rgba(0,0,0,0.12)]"
                        loading="eager" decoding="async"
                    >
                </div>

                {{-- Текст и кнопки --}}
                <div class="mt-10 text-center space-y-4">
                    <h1 class="text-2xl sm:text-3xl font-semibold">Упс! Такої сторінки не існує</h1>
                    <p class="text-gray-500">Перейдіть на головну або скористайтеся каталогом.</p>
                    <div class="flex items-center justify-center gap-3">
                        <a href="{{ url('/') }}"
                           class="inline-flex items-center rounded-xl px-5 py-3 text-white
                    bg-[#FF7500] hover:bg-[#ff8d2e] transition">
                            На головну
                        </a>
                        <a href="{{ route('catalog.index') }}"
                           class="inline-flex items-center rounded-xl px-5 py-3 text-gray-700
                    border border-gray-300 hover:bg-gray-50 transition">
                            До каталогу
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>


@endsection
