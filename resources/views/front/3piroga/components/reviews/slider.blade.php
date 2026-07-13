@props(['items' => collect()])

<link rel="stylesheet" href="https://unpkg.com/swiper@10/swiper-bundle.min.css" />

<section class="my-12">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-3xl font-bold">Відгуки</h2>
        <div class="flex items-center gap-2">
            <button class="rev-prev w-10 h-10 rounded-full border border-[#FF7500] grid place-items-center">
                <svg width="18" height="18" viewBox="0 0 24 24" class="stroke-[#FF7500]"><path d="M15 18l-6-6 6-6" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
            <button class="rev-next w-10 h-10 rounded-full bg-[#FF7500] text-white grid place-items-center shadow">
                <svg width="18" height="18" viewBox="0 0 24 24"><path d="M9 6l6 6-6 6" stroke="white" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
        </div>
    </div>

    <div class="swiper reviews-swiper">
        <div class="swiper-wrapper">
            @foreach($items as $it)
                <div class="swiper-slide">
                    <div class="relative px-6 pt-12 pb-8 rounded-2xl bg-[#FFF4E8] shadow-sm max-w-4xl mx-auto">
                        {{-- Аватар --}}
                        <div class="absolute -top-10 left-1/2 -translate-x-1/2">
                            @if($it->avatar_url)
                                <img src="{{ $it->avatar_url }}" alt="{{ $it->author_name }}"
                                     class="w-20 h-20 rounded-full object-cover ring-4 ring-white/70">
                            @else
                                <div class="w-20 h-20 rounded-full bg-gray-300 ring-4 ring-white/70 grid place-items-center text-xl font-semibold">
                                    {{ mb_substr($it->author_name,0,1) }}
                                </div>
                            @endif
                        </div>

                        {{-- Цифровой рейтинг (как в макете «99» – можно скрыть) --}}
                        <div class="text-center text-[#FF7500] text-3xl font-semibold mb-2">
                            {{ number_format(($it->rating ?? 0), 0) }}
                        </div>

                        {{-- Текст --}}
                        <div class="text-center text-[#333] leading-7 max-w-3xl mx-auto">
                            {{ $it->text }}
                        </div>

                        {{-- Звёзды --}}
                        @php
                            $r = (int) ($it->rating ?? 0);
                        @endphp
                        <div class="mt-4 flex justify-center gap-1">
                            @for($i=1;$i<=5;$i++)
                                @if($i <= $r)
                                    <svg width="22" height="22" viewBox="0 0 24 24" class="fill-[#FF7500]"><path d="M12 .587l3.668 7.431 8.2 1.192-5.934 5.787 1.402 8.167L12 18.896l-7.336 3.868 1.402-8.167L.132 9.21l8.2-1.192z"/></svg>
                                @else
                                    <svg width="22" height="22" viewBox="0 0 24 24" class="fill-gray-300"><path d="M12 .587l3.668 7.431 8.2 1.192-5.934 5.787 1.402 8.167L12 18.896l-7.336 3.868 1.402-8.167L.132 9.21l8.2-1.192z"/></svg>
                                @endif
                            @endfor
                        </div>

                        {{-- Автор + дата --}}
                        <div class="mt-2 text-center">
                            <div class="text-lg font-semibold">{{ $it->author_name }}</div>
                            @if($it->posted_at)
                                <div class="text-sm text-gray-500">{{ $it->posted_at->format('d.m.Y') }}</div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Точки --}}
        <div class="swiper-pagination mt-6 !relative"></div>
    </div>
</section>

<script src="https://unpkg.com/swiper@10/swiper-bundle.min.js"></script>
<script>
    // можно в jQuery-стиле инициализировать после DOMReady
    document.addEventListener('DOMContentLoaded', function () {
        new Swiper('.reviews-swiper', {
            slidesPerView: 1,
            spaceBetween: 24,
            loop: {{ $items->count() > 1 ? 'true' : 'false' }},
            navigation: { nextEl: '.rev-next', prevEl: '.rev-prev' },
            pagination: { el: '.swiper-pagination', clickable: true },
            breakpoints: {
                1024: { slidesPerView: 1 },
                1280: { slidesPerView: 1 },
            },
        });
    });
</script>
