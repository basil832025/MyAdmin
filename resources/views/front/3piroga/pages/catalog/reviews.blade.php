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
<div class="mx-auto desk:w-[1198px] w-[357px] md:w-[736px] max-w-full">

<div x-data="reviewModal('{{ route('product.reviews.store', $product) }}')" x-cloak>
<section id="reviews" class="mt-10 xl:w-[1198px] md:w-[736px] mx-auto px-2">
    <h2 class="text-[22px] font-bold mb-4">{{ st('reviews.reviews','Відгуки') }} </h2>

        {{-- Шапка: круг + колонка "звёзды+%" + длинные полосы --}}


    <div class="grid grid-cols-1 md:grid-cols-[auto_auto_minmax(0,1fr)] gap-x-[20px] p-4 bg-white rounded-lg shadow">

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


    {{-- Элементы отзывов --}}
    <div class="mt-6 space-y-4">
        @forelse($reviews as $rv)
            <article class="p-4 bg-white rounded-lg shadow">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-full bg-[#F4F4F4] flex items-center justify-center font-semibold text-[#FF7500]">
                        {{ $rv->initials ?? 'U' }}
                    </div>
                    <div class="flex-1">
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 justify-between">
                            <div class="font-semibold">{{ $rv->name }}</div>
                            <div class="text-xs text-[#9E9E9E]">
                                {{ \Carbon\Carbon::parse($rv->created_at)->locale(app()->getLocale())->isoFormat('D MMMM YYYY') }}
                            </div>
                        </div>
                        <div class="mt-1 flex items-center gap-1 text-[#FF7500]">
                            @for($i=1;$i<=5;$i++)
                                <svg class="w-4 h-4 {{ $i <= $rv->rating ? '' : 'opacity-30' }}" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M10 15.27L16.18 19l-1.64-7.03L20 7.24l-7.19-.61L10 0 7.19 6.63 0 7.24l5.46 4.73L3.82 19z"/>
                                </svg>
                            @endfor
                        </div>
                        <div class="mt-2 text-sm leading-6 text-[#19191A]">
                            {{ $rv->content }}
                        </div>
                        <button type="button" class="inline-flex items-center gap-1 mt-2 text-xs text-[#7A7A7A] hover:text-[#FF7500]"><svg width="15" height="12" viewBox="0 0 15 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M5.73598 1.81261C5.76083 1.80167 5.78803 1.79716 5.81508 1.79948C5.84213 1.8018 5.86817 1.81088 5.8908 1.82589C5.91342 1.8409 5.93191 1.86135 5.94457 1.88537C5.95723 1.9094 5.96364 1.93621 5.96323 1.96336V3.26049C5.96323 3.40967 6.02249 3.55275 6.12798 3.65824C6.23347 3.76373 6.37654 3.82299 6.52573 3.82299C7.2761 3.82299 8.79035 3.82861 10.2382 4.74774C11.3452 5.44974 12.477 6.72774 13.1576 9.10824C12.0101 8.00236 10.6995 7.40274 9.55198 7.08436C8.84665 6.88956 8.12179 6.77409 7.39085 6.74011C7.09166 6.72697 6.79197 6.72998 6.4931 6.74911H6.47848L6.47285 6.75024H6.47173L6.52573 7.31049L6.46948 6.75024C6.33063 6.76419 6.20193 6.82926 6.10837 6.93279C6.01482 7.03633 5.96308 7.17094 5.96323 7.31049V8.60761C5.96323 8.72911 5.83948 8.80561 5.73598 8.75836L1.25398 5.45874C1.23876 5.44745 1.22299 5.43694 1.20673 5.42724C1.18226 5.41254 1.16202 5.39176 1.14797 5.36692C1.13392 5.34208 1.12653 5.31403 1.12653 5.28549C1.12653 5.25695 1.13392 5.2289 1.14797 5.20406C1.16202 5.17922 1.18226 5.15844 1.20673 5.14374C1.223 5.13404 1.23877 5.12353 1.25398 5.11224L5.73598 1.81261ZM7.08823 7.85724C7.16473 7.85724 7.2491 7.86061 7.3391 7.86399C7.82735 7.88649 8.50235 7.96074 9.2516 8.16886C10.7434 8.58286 12.5096 9.52111 13.6841 11.6339C13.7476 11.7479 13.8488 11.8363 13.9704 11.8839C14.0919 11.9315 14.2262 11.9354 14.3503 11.8948C14.4744 11.8543 14.5805 11.7718 14.6504 11.6616C14.7204 11.5514 14.7498 11.4203 14.7337 11.2907C14.2117 7.11699 12.612 4.92099 10.8412 3.79824C9.4406 2.90949 7.99835 2.73849 7.08823 2.70586V1.96336C7.08833 1.73169 7.0259 1.50429 6.90752 1.30514C6.78913 1.106 6.61919 0.942502 6.41562 0.831904C6.21206 0.721306 5.98241 0.667708 5.75091 0.676765C5.51942 0.685822 5.29466 0.757199 5.10035 0.883364L0.607101 4.19086C0.421341 4.30682 0.268145 4.46814 0.161936 4.65963C0.0557275 4.85113 0 5.06651 0 5.28549C0 5.50447 0.0557275 5.71984 0.161936 5.91134C0.268145 6.10284 0.421341 6.26416 0.607101 6.38011L5.10035 9.68761C5.29466 9.81378 5.51942 9.88516 5.75091 9.89421C5.98241 9.90327 6.21206 9.84967 6.41562 9.73907C6.61919 9.62848 6.78913 9.46498 6.90752 9.26584C7.0259 9.06669 7.08833 8.83929 7.08823 8.60761V7.85724Z" fill="#16A34A"/>
                            </svg>{{ st('reviews.reply','Відповісти') }}
                            </button>
                    </div>
                </div>
            </article>
        @empty
            <div class="text-sm text-[#7A7A7A]">{{ st('reviews.no_reviews','Поки що немає відгуків — станьте першим!') }}</div>
        @endforelse
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

    <div class="mt-6 flex justify-center">
        <button @click="openReview = true"
                class="inline-flex items-center justify-center w-[343px] h-[48px] px-6 rounded bg-[#FF7500] text-white font-semibold hover:bg-[#e76b00]">
            {{ st('reviews.leave_review','Залишити відгук') }}
        </button>

    </div>
</section>

{{-- Модалка --}}


    <template x-if="openReview">
        <div class="fixed inset-0 z-[100] flex items-center justify-center">
            <div class="absolute inset-0 bg-black/40" @click="openReview=false"></div>

            <div class="relative bg-white rounded-[20px] w-[355px] md:w-[540px] p-[30px] shadow">
                <button
                    type="button"
                    class="absolute right-4 top-4 text-3xl leading-none text-[#7A7A7A] hover:text-[#272828]"
                    @click="openReview=false"
                    aria-label="Закрити"
                >&times;</button>
                <h3 class="text-center text-[22px] font-bold">{{ st('reviews.leave_review','Залишити відгук') }}</h3>
                <p class="mt-2 text-xs text-center text-[#7A7A7A]">{{ st('reviews.share_your','Поділіться своїми враженнями про наші осетинські пироги!') }}
                    </p>

                {{-- Рейтинг --}}
                <div class="mt-4 flex justify-center gap-3">
                    <template x-for="i in 5" :key="i">
                        <button type="button" class="w-6 h-6" @click="rating = i">
                            <svg viewBox="0 0 20 20" class="w-6 h-6"
                                 :fill="i <= rating ? '#FF7500' : 'none'" stroke="#FF7500">
                                <path d="M10 15.27 16.18 19l-1.64-7.03L20 7.24l-7.19-.61L10 0 7.19 6.63 0 7.24l5.46 4.73L3.82 19z"/>
                            </svg>
                        </button>
                    </template>
                </div>

                {{-- Форма --}}
                <form x-ref="form" @submit.prevent="submit" class="mt-4 space-y-3">
                    @csrf
                    <input type="hidden" name="rating" :value="rating">
                    <input type="text" name="hp" class="hidden" autocomplete="off"> {{-- honeypot --}}

                    <input  x-ref="name" type="text" name="name" placeholder="{{ st('all.name','Ім’я') }}" required
                           class="w-full border border-[#E5E7EB] rounded px-3 py-2 text-base md:text-sm focus:border-[#FF7500]">
                    <div class="text-xs text-red-500" x-text="errors.name"></div>

                    <input  x-ref="email" type="email" name="email" placeholder="Email" required
                           class="w-full border border-[#E5E7EB] rounded px-3 py-2 text-base md:text-sm focus:border-[#FF7500]">
                    <div class="text-xs text-red-500" x-text="errors.email"></div>

                    <textarea x-ref="content" name="content" placeholder="{{ st('reviews.write_a_review','Напишіть відгук...') }}" required
                              class="w-full h-28 border border-[#E5E7EB] rounded px-3 py-2 text-base md:text-sm resize-none focus:border-[#FF7500]"></textarea>
                    <div class="text-xs text-red-500" x-text="errors.content"></div>

                    <button type="submit"
                            :disabled="sending"
                            class="w-full mt-2 bg-[#FF7500] hover:bg-orange-600 disabled:opacity-60 text-white font-semibold py-3 rounded">
                        <span x-show="!sending">{{ st('reviews.leave_review','Залишити відгук') }}</span>
                        <span x-show="sending">{{ st('reviews.sending','Відправка...') }}</span>
                    </button>

                    <p class="text-[10px] text-[#9E9E9E] text-center">{{ st('reviews.hourspublication','Усі відгуки на Три пироги перевіряються протягом 48 годин перед публікацією, щоб забезпечити їхню достовірність і точність.') }}</p>
                </form>

            </div>
        </div>
    </template>
    {{-- НОВАЯ модалка «Спасибо» --}}
    <template x-if="openThanks">
        <div class="fixed inset-0 z-[110] flex items-center justify-center">
            <div class="absolute inset-0 bg-black/40" @click="openThanks=false"></div>

            <div class="relative bg-white rounded-[20px] w-[355px] md:w-[540px] p-[30px] shadow text-center">
                <button class="absolute right-4 top-4 text-5xl leading-none" @click="openThanks=false">×</button>

                {{-- Иконка-звезда --}}
                <div class="mx-auto w-[136px] h-[136px] mb-4">
                    <svg viewBox="0 0 120 120" class="w-full h-full">
                        <path d="M60 6l15.4 31.2 34.6 5-25 24.3 5.9 34.5L60 85.6 29.1 101l5.9-34.5L10 42.2l34.6-5L60 6z"
                              fill="#FF7500"/>
                        <!-- улыбка -->
                        <path d="M40 70c5 8 35 8 40 0" stroke="#fff" stroke-width="6" stroke-linecap="round" fill="none"/>
                    </svg>
                </div>

                <h3 class="text-[22px] font-bold mb-2">{{ st('reviews.thank_you_review','Дякуємо за ваш відгук!') }}</h3>
                <p class="text-[13px] text-[#7A7A7A] leading-5 mb-5">
                    {{ st('reviews.publication48hours','Усі відгуки на Три пироги перевіряються протягом 48 годин перед публікацією, щоб забезпечити їхню достовірність і точність.') }}
                </p>

                <button @click="openThanks=false"
                        class="inline-flex items-center justify-center w-[260px] md:w-[300px] h-[44px] rounded bg-[#FF7500] text-white font-semibold hover:bg-[#e76b00]">
                    {{ st('reviews.continue_search','Продовжити пошук') }}
                </button>
            </div>
        </div>
    </template>
</div>
</div>


<script>
    function reviewModal(postUrl) {
        return {
            openReview: false,
            openThanks: false,
            rating: 5,
            sending: false,
            success: false,
            errors: {},
            async submit() {
                this.errors = {};
                // простая фронт-валидация
                const name    = this.$refs.name.value.trim();
                const email   = this.$refs.email.value.trim();
                const content = this.$refs.content.value.trim();
                const reEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

                if (!name)   this.errors.name   = '{{ st('reviews.enter_your_name','Введіть ім’я') }}';
                if (!email)  this.errors.email  = '{{ st('reviews.enter_your_email','Введіть email') }}';
                else if (!reEmail.test(email)) this.errors.email = '{{ st('reviews.invalid_email','Некоректний email') }}';
                if (!content) this.errors.content = '{{ st('reviews.write_a_review','Напишіть відгук') }}';
                if (Object.keys(this.errors).length) return;
                this.sending = true; this.errors = {}; this.success = false;

                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
                const fd = new FormData(this.$refs.form); // @csrf уже внутри
                fd.set('rating', this.rating);

                try {
                    const res = await fetch(postUrl, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                        body: fd,
                    });

                    if (res.ok) {
                        this.success = true;
                        this.$refs.form.reset();
                        this.rating = 5;
                        this.openReview = false;   // закрываем форму
                        this.openThanks  = true;   // открываем «спасибо»
                        // по желанию: this.openReview = false;
                        // по желанию: dispatchEvent(new CustomEvent('reviews:created'));
                    } else if (res.status === 422) {
                        const data = await res.json();
                        this.errors = data.errors || {};
                    } else {
                        alert('{{ st('reviews.sending_error','Помилка відправлення. Спробуйте пізніше.') }}');
                    }
                } catch (e) {
                    alert('{{ st('reviews.network_unavailable','Мережа недоступна. Повторіть пізніше.') }}');
                } finally {
                    this.sending = false;
                }
            }
        }
    }
</script>
