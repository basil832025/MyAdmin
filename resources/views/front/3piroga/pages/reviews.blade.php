@extends(front_view('layouts.app'))
@php
    $total = (int)($stats->total ?? 0);
    $avg   = $total ? round($stats->avg_rating, 1) : 0;

    $buckets = [
        5 => (int)($stats->r5 ?? 0),
        4 => (int)($stats->r4 ?? 0),
        3 => (int)($stats->r3 ?? 0),
        2 => (int)($stats->r2 ?? 0),
        1 => (int)($stats->r1 ?? 0),
    ];
    $percent = fn (int $k) => $total ? round($buckets[$k] * 100 / $total) : 0;

    // параметры круга
    $size = 84; $stroke = 6;
    $r = ($size - $stroke)/2;
    $circ = 2 * M_PI * $r;
    $pct = max(0, min(1, ($avg ?: 0) / 5));
    $dash = $circ * $pct; $gap = $circ - $dash;

    // звезды
    $full = (int) floor($avg);
    $half = (int) (($avg - $full) >= 0.5);
    $empty = 5 - $full - $half;
    $uid = uniqid('star'); // для clipPath
@endphp
@include(front_view('partials.seo.page'), ['page' => $page])
@section('content')
    <div class="mx-auto desk:w-[1198px] p-4  max-w-full">
        {{-- Хлебные крошки --}}
        <nav class="text-sm text-gray-500 my-4">
            <a href="{{ route('home') }}" class="hover:text-gray-700">{{ st('menu.home','Головна') }}</a>
            <span class="mx-2">→</span>
            <span class="text-gray-700">{{$page->title}}</span>
        </nav>

            <h2 class="inline-block mb-3 font-intro leading-[100%] text-[40px] md:text-[64px] md:leading-[64px] font-bold text-[#19191A] border-b-2 border-[#FF7500]">
                {{$page->title}}
            </h2>
    <div x-data="reviewModal('{{ route('reviews.store') }}')" x-cloak>
        <div class="grid grid-cols-1 md:grid-cols-[auto_auto_minmax(0,1fr)] gap-x-[20px] p-4 mt-3 bg-white rounded-lg shadow">

            {{-- 1) КРУГ --}}
            <div class="flex items-center gap-4">
                <svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 {{ $size }} {{ $size }}" class="shrink-0">
                    <g transform="rotate(-90 {{ $size/2 }} {{ $size/2 }})">
                        <circle cx="{{ $size/2 }}" cy="{{ $size/2 }}" r="{{ $r }}"
                                fill="none" stroke="#E8E8E8" stroke-width="{{ $stroke }}" />
                        <circle cx="{{ $size/2 }}" cy="{{ $size/2 }}" r="{{ $r }}"
                                fill="none" stroke="#FF7500" stroke-width="{{ $stroke }}"
                                stroke-linecap="round" stroke-dasharray="{{ $dash }} {{ $gap }}" />
                    </g>
                    <text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle"
                          font-size="28" font-weight="700" fill="#19191A">
                        {{ number_format($avg,1) }}
                    </text>
                </svg>

                <div class="block">
                    {{-- звёзды средней + подпись --}}
                    @php
                        $fullM = (int) floor($avg);
                        $halfM = (int) (($avg - $fullM) >= 0.5);
                        $emptyM = 5 - $fullM - $halfM;
                        $uidM = uniqid('star');
                    @endphp
                    <div class="flex items-center gap-1 text-[#FF7500]">
                        @for($i=0;$i<$fullM;$i++)
                            <svg viewBox="0 0 20 20" class="w-5 h-5"><path d="M10 15.27 16.18 19l-1.64-7.03L20 7.24l-7.19-.61L10 0 7.19 6.63 0 7.24l5.46 4.73L3.82 19z" fill="#FF7500" stroke="#FF7500"/></svg>
                        @endfor
                        @if($halfM)
                            <svg viewBox="0 0 20 20" class="w-5 h-5">
                                <defs><clipPath id="{{ $uidM }}"><rect x="0" y="0" width="10" height="20"/></clipPath></defs>
                                <path d="M10 15.27 16.18 19l-1.64-7.03L20 7.24l-7.19-.61L10 0 7.19 6.63 0 7.24l5.46 4.73L3.82 19z" fill="none" stroke="#FF7500"/>
                                <path d="M10 15.27 16.18 19l-1.64-7.03L20 7.24l-7.19-.61L10 0 7.19 6.63 0 7.24l5.46 4.73L3.82 19z" clip-path="url(#{{ $uidM }})" fill="#FF7500"/>
                            </svg>
                        @endif
                        @for($i=0;$i<$emptyM;$i++)
                            <svg viewBox="0 0 20 20" class="w-5 h-5"><path d="M10 15.27 16.18 19l-1.64-7.03L20 7.24l-7.19-.61L10 0 7.19 6.63 0 7.24l5.46 4.73L3.82 19z" fill="none" stroke="#FF7500"/></svg>
                        @endfor
                    </div>
                    <div class="text-sm text-[#7A7A7A] leading-6">{{ st('reviews.osnova','На основі') }}&nbsp;{{ $total }}&nbsp;{{ st('reviews.ratings','оцінок') }}</div>
                </div>
            </div>

            {{-- 2) КОЛОНКА "ЗВЁЗДЫ + %" (5→1) --}}
            <div class="grid grid-rows-5 gap-2 mt-5">
                @for ($rStar = 5; $rStar >= 1; $rStar--)
                    <div class="flex items-center gap-3">
                        <div class="flex items-center gap-1 text-[#FF7500]">
                            @for($i=1;$i<=5;$i++)
                                <svg viewBox="0 0 20 20" class="w-4 h-4">
                                    <path d="M10 15.27 16.18 19l-1.64-7.03L20 7.24l-7.19-.61L10 0 7.19 6.63 0 7.24l5.46 4.73L3.82 19z"
                                          fill="{{ $i <= $rStar ? '#FF7500' : 'none' }}"
                                          stroke="#FF7500"/>
                                </svg>
                            @endfor
                        </div>
                        <div class="w-10 text-sm text-[#7A7A7A] tabular-nums text-right">{{ $percent($rStar) }}%</div>
                    </div>
                @endfor
            </div>

            {{-- 3) ДЛИННЫЕ ПОЛОСЫ ПРОГРЕССА (в тех же 5 строках) --}}
            <div class="grid grid-rows-5 gap-2 mt-5">
                @for ($rBar = 5; $rBar >= 1; $rBar--)
                    <div class="flex items-center gap-3 mt-3">
                        <div class="flex-1 h-2 bg-[#E9ECEF] rounded">
                            <div class="h-2 bg-[#FF7500] rounded" style="width: {{ $percent($rBar) }}%"></div>
                        </div>
                    </div>
                @endfor
            </div>
        </div>
        {{-- список «как на старом» --}}
        <div class="mt-12 space-y-10 max-w-5xl mx-auto">
            @foreach($reviews as $r)
                <article class="border-b pb-8">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-10 h-10 rounded-full bg-[#F4F4F4] flex items-center justify-center font-semibold text-[#FF7500]">
                        @if($r->avatar_url)
                                <img src="{{ $r->avatar_url }}" class="w-full h-full object-cover" alt="">
                            @else
                                {{ $r->initials ?? 'U' }}
                            @endif


                        </div>
                        <div>
                            <div class="font-semibold">{{ $r->author_name }}</div>
                            <div class="text-xs text-gray-500">{{ optional($r->posted_at)->format('d.m.Y') }}</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-1 mb-3">
                        @for($i=1;$i<=5;$i++)
                            <svg width="18" height="18" viewBox="0 0 24 24" class="{{ $i <= (int)$r->rating ? 'fill-[#FF7500]' : 'fill-gray-300' }}"><path d="M12 .587l3.668 7.431 8.2 1.192-5.934 5.787 1.402 8.167L12 18.896l-7.336 3.868 1.402-8.167L.132 9.21l8.2-1.192z"/></svg>
                        @endfor
                    </div>
                    <p class="leading-7">{{ $r->text }}</p>
                </article>
            @endforeach
        </div>
        {{-- Пагинация как на макете --}}
                @if($reviews->hasPages())
                    <div class="mt-6 flex items-center justify-center gap-2">
                        <a href="{{ $reviews->previousPageUrl() ?? '#' }}" class="w-10 h-10 rounded border flex items-center justify-center {{ $reviews->onFirstPage() ? 'pointer-events-none opacity-40' : 'hover:border-[#FF7500]' }}">‹</a>
                        @php
                            $current = min($reviews->currentPage(), $reviews->lastPage());
                            $start = max(1, $current - 2);
                            $end = min($reviews->lastPage(), $current + 2);
                        @endphp
                        @foreach($reviews->getUrlRange($start, $end) as $page => $url)
                            <a href="{{ $url }}" class="w-10 h-10 rounded border flex items-center justify-center text-sm {{ $page === $reviews->currentPage() ? 'bg-[#FF7500] text-white border-[#FF7500]' : 'hover:border-[#FF7500]' }}">
                                {{ $page }}
                            </a>
                        @endforeach
                        <a href="{{ $reviews->nextPageUrl() ?? '#' }}" class="w-10 h-10 rounded border flex items-center justify-center {{ $reviews->currentPage()===$reviews->lastPage() ? 'pointer-events-none opacity-40' : 'hover:border-[#FF7500]' }}">›</a>
                    </div>
                @endif
        {{-- кнопка оставить отзыв --}}
        <div class="mt-8 flex justify-center">
            <button @click="openReview = true"
                    class="inline-flex items-center justify-center w-[320px] h-[48px] px-6 rounded bg-[#FF7500] text-white font-semibold hover:bg-[#e76b00]">
                {{ st('reviews.leave_review','Залишити відгук') }}
            </button>
        </div>

        {{-- модалка --}}
        <template x-if="openReview">
            <div class="fixed inset-0 z-[100] flex items-center justify-center">
                <div class="absolute inset-0 bg-black/40" @click="openReview=false"></div>

                <div class="relative bg-white rounded-[20px] w-[355px] md:w-[520px] p-6 shadow">
                    <button
                        type="button"
                        class="absolute right-4 top-4 text-3xl leading-none text-[#7A7A7A] hover:text-[#272828]"
                        @click="openReview=false"
                        aria-label="Закрити"
                    >&times;</button>
                    <h3 class="text-center text-[22px] font-bold">{{ st('reviews.leave_review','Залишити відгук') }}</h3>

                    {{-- рейтинг --}}
                    <div class="mt-4 flex justify-center gap-2">
                        <template x-for="i in 5" :key="i">
                            <button type="button" class="w-6 h-6" @click="rating = i">
                                <svg viewBox="0 0 20 20" class="w-6 h-6"
                                     :fill="i <= rating ? '#FF7500' : 'none'" stroke="#FF7500">
                                    <path d="M10 15.27 16.18 19l-1.64-7.03L20 7.24l-7.19-.61L10 0 7.19 6.63 0 7.24l5.46 4.73L3.82 19z"/>
                                </svg>
                            </button>
                        </template>
                    </div>

                    {{-- форма --}}
                    <form x-ref="form" @submit.prevent="submit" class="mt-4 space-y-3">
                        @csrf
                        <input type="hidden" name="location_id" value="1">
                        <input type="hidden" name="rating" :value="rating">
                        <input type="text" name="hp" class="hidden" autocomplete="off"> {{-- honeypot --}}

                        <input x-ref="name" type="text" name="name" placeholder="{{ st('all.name','Ім’я') }}" required
                               class="w-full border border-[#E5E7EB] rounded px-3 py-2 text-base md:text-sm focus:border-[#FF7500]">
                        <div class="text-xs text-red-500" x-text="errors.name"></div>

                        <input x-ref="email" type="email" name="email" placeholder="Email"
                               class="w-full border border-[#E5EE7] rounded px-3 py-2 text-base md:text-sm focus:border-[#FF7500]">
                        <div class="text-xs text-red-500" x-text="errors.email"></div>

                        <textarea x-ref="content" name="content" placeholder="{{ st('reviews.write_a_review','Напишіть відгук...') }}" required
                                  class="w-full h-28 border border-[#E5E7EB] rounded px-3 py-2 text-base md:text-sm resize-none focus:border-[#FF7500]"></textarea>
                        <div class="text-xs text-red-500" x-text="errors.content"></div>

                        <button type="submit" :disabled="sending"
                                class="w-full mt-2 bg-[#FF7500] hover:bg-orange-600 disabled:opacity-60 text-white font-semibold py-3 rounded">
                            <span x-show="!sending">{{ st('reviews.leave_review','Залишити відгук') }}</span>
                            <span x-show="sending">{{ st('reviews.sending','Відправка...') }}</span>
                        </button>
                    </form>
                </div>
            </div>
        </template>

        {{-- «спасибо» --}}
        <template x-if="openThanks">
            <div class="fixed inset-0 z-[110] flex items-center justify-center">
                <div class="absolute inset-0 bg-black/40" @click="openThanks=false"></div>
                <div class="relative bg-white rounded-[20px] w-[355px] md:w-[520px] p-6 shadow text-center">
                    <h3 class="text-[22px] font-bold mb-2">{{ st('reviews.thank_you_review','Дякуємо за ваш відгук!') }}</h3>
                    <p class="text-[13px] text-[#7A7A7A] mb-4">
                        {{ st('reviews.publication48hours','Відгук з’явиться після модерації (до 48 годин).') }}
                    </p>
                    <button @click="openThanks=false"
                            class="inline-flex items-center justify-center w-[260px] h-[44px] rounded bg-[#FF7500] text-white font-semibold hover:bg-[#e76b00]">
                        OK
                    </button>
                </div>
            </div>
        </template>
    </div>
 </div>
@endsection

@push('scripts')
    <script>
        function reviewModal(postUrl) {
            return {
                openReview: false,
                openThanks: false,
                rating: 5,
                sending: false,
                errors: {},
                async submit() {
                    this.errors = {};
                    const name    = this.$refs.name.value.trim();
                    const email   = this.$refs.email.value.trim();
                    const content = this.$refs.content.value.trim();
                    const reEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

                    if (!name)   this.errors.name   = '{{ st('reviews.enter_your_name','Введіть ім’я') }}';
                    if (email && !reEmail.test(email)) this.errors.email = '{{ st('reviews.invalid_email','Некоректний email') }}';
                    if (!content) this.errors.content = '{{ st('reviews.write_a_review','Напишіть відгук') }}';
                    if (Object.keys(this.errors).length) return;

                    this.sending = true;
                    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
                    const fd = new FormData(this.$refs.form);
                    fd.set('rating', this.rating);

                    try {
                        const res = await fetch(postUrl, {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                            body: fd,
                        });
                        if (res.ok) {
                            this.$refs.form.reset();
                            this.rating = 5;
                            this.openReview = false;
                            this.openThanks = true;
                        } else if (res.status === 422) {
                            const data = await res.json();
                            this.errors = data.errors || {};
                        } else {
                            alert('{{ st('reviews.sending_error','Помилка відправлення. Спробуйте пізніше.') }}');
                        }
                    } catch(e) {
                        alert('{{ st('reviews.network_unavailable','Мережа недоступна. Повторіть пізніше.') }}');
                    } finally {
                        this.sending = false;
                    }
                }
            }
        }
    </script>
@endpush
