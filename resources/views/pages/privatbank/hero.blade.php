@php
    $badge = strip_tags(
        page_field(
            $pageSlug,
            'pb_badge',
            'ПриватБанк · Оплата частинами'
        ) ?? ''
    );

    $title = strip_tags(
        page_field(
            $pageSlug,
            'pb_title',
            'Смачні осетинські пироги вже сьогодні'
        ) ?? ''
    );

    $titleGreen = strip_tags(
        page_field(
            $pageSlug,
            'pb_title_green',
            'сплачуйте частинами'
        ) ?? ''
    );

    $intro = page_field(
        $pageSlug,
        'pb_intro',
        '<p>Оформлюйте замовлення від <strong>2000 грн</strong> та сплачуйте зручно частинами.</p>'
    );

    $heroImage = trim(
        strip_tags(
            page_field($pageSlug, 'pb_hero_image', '') ?? ''
        )
    );

    $howButton = match (app()->getLocale()) {
        'ru' => 'Как это работает?',
        'en' => 'How does it work?',
        default => 'Як це працює?',
    };

    $quickItems = match (app()->getLocale()) {
        'ru' => [
            ['value' => 'от 2000 грн', 'text' => 'минимальная сумма'],
            ['value' => 'до 3 платежей', 'text' => 'удобная оплата'],
            ['value' => 'Приват24', 'text' => 'подтверждение онлайн'],
        ],
        'en' => [
            ['value' => 'from UAH 2,000', 'text' => 'minimum order'],
            ['value' => 'up to 3 payments', 'text' => 'convenient payment'],
            ['value' => 'Privat24', 'text' => 'online confirmation'],
        ],
        default => [
            ['value' => 'від 2000 грн', 'text' => 'мінімальна сума'],
            ['value' => 'до 3 платежів', 'text' => 'зручна оплата'],
            ['value' => 'Приват24', 'text' => 'підтвердження онлайн'],
        ],
    };
@endphp

<section class="relative isolate overflow-hidden bg-gradient-to-br from-[#fff9f0] via-[#fcfffb] to-[#e9fce8]">

    {{-- Декоративний фон --}}
    <div class="pointer-events-none absolute inset-0 -z-10">
        <div class="absolute -right-40 -top-52 h-[520px] w-[520px] rounded-full bg-green-300/30 blur-[110px]"></div>

        <div class="absolute -bottom-52 -left-44 h-[480px] w-[480px] rounded-full bg-orange-200/30 blur-[110px]"></div>

        <div
            class="absolute inset-0 opacity-[0.025]"
            style="background-image: radial-gradient(#183f20 0.8px, transparent 0.8px); background-size: 24px 24px;"
        ></div>
    </div>

    <div class="relative mx-auto grid max-w-[1198px] items-center gap-12 px-4 py-14 sm:px-6 sm:py-20 lg:min-h-[640px] lg:grid-cols-[1.08fr_0.92fr] lg:px-4 lg:py-20">

        {{-- Ліва частина --}}
        <div>
            @if ($badge !== '')
                <div class="inline-flex items-center gap-2 rounded-full border border-green-100 bg-white px-5 py-2.5 text-sm font-bold text-green-700 shadow-sm">
                    <svg
                        class="h-5 w-5"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <rect x="3" y="5" width="18" height="14" rx="2"></rect>
                        <path d="M3 10h18"></path>
                    </svg>

                    {{ $badge }}
                </div>
            @endif

            <h1 class="mt-7 max-w-[680px] text-[38px] font-black leading-[1.07] tracking-[-0.035em] text-[#171d2d] sm:text-5xl lg:text-[58px]">
                {{ $title }}

                <span class="mt-2 block text-[#19a34a]">
                    {{ $titleGreen }}
                </span>
            </h1>

            <div class="prose prose-slate mt-7 max-w-2xl text-base leading-8 prose-p:my-4 prose-strong:font-extrabold prose-strong:text-[#171d2d] sm:text-lg">
                {!! clean_html(
                    $intro,
                    'safe',
                    null,
                    '<p><br><strong><em><ul><ol><li><a>'
                ) !!}
            </div>

            <div class="mt-8 flex flex-wrap gap-3">
                @foreach ($quickItems as $item)
                    <span class="rounded-full border border-green-100 bg-white px-5 py-3 text-sm font-bold text-green-700 shadow-sm">
                        {{ $item['value'] }}
                    </span>
                @endforeach
            </div>

            <div class="mt-9 flex flex-col gap-4 sm:flex-row">
                <a
                    href="{{ $catalogUrl }}"
                    class="group inline-flex min-h-14 items-center justify-center rounded-full bg-gradient-to-r from-[#77c832] to-[#159447] px-8 py-4 text-base font-extrabold text-white shadow-[0_16px_35px_rgba(22,138,65,0.25)] transition duration-300 hover:-translate-y-1 hover:scale-[1.02] hover:shadow-[0_22px_45px_rgba(22,138,65,0.32)]"
                >
                    {{ $catalogButton }}

                    <svg
                        class="ml-2 h-5 w-5 transition-transform duration-300 group-hover:translate-x-1"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path d="M5 12h14"></path>
                        <path d="m13 6 6 6-6 6"></path>
                    </svg>
                </a>

                <a
                    href="#how-it-works"
                    class="inline-flex min-h-14 items-center justify-center rounded-full border border-green-200 bg-white px-8 py-4 text-base font-extrabold text-[#185c2c] transition duration-300 hover:-translate-y-0.5 hover:bg-green-50"
                >
                    {{ $howButton }}
                </a>
            </div>
        </div>

        {{-- Права частина --}}
        <div class="relative mx-auto w-full max-w-[550px] lg:rotate-[1.5deg] lg:transition-transform lg:duration-500 lg:hover:rotate-0 lg:hover:scale-[1.015]">

            <div class="pointer-events-none absolute -inset-8 -z-10 rounded-full bg-gradient-to-br from-green-300/40 to-lime-200/20 blur-3xl"></div>

            @if ($heroImage !== '')
                <div class="overflow-hidden rounded-[34px] border border-white/90 bg-white/90 p-3 shadow-[0_32px_85px_rgba(22,73,34,0.22)] backdrop-blur-xl">
                    <img
                        src="{{ $heroImage }}"
                        alt="{{ $badge }}"
                        class="h-auto min-h-[380px] w-full rounded-[26px] object-cover"
                    >
                </div>
            @else
                <div class="rounded-[34px] border border-white/90 bg-white/90 p-5 shadow-[0_32px_85px_rgba(22,73,34,0.22)] backdrop-blur-xl sm:p-7">

                    <div class="relative flex min-h-[280px] flex-col justify-between overflow-hidden rounded-[27px] bg-gradient-to-br from-[#126c31] via-[#23943c] to-[#82cb32] p-7 text-white shadow-[0_20px_45px_rgba(18,108,49,0.3)] sm:p-8">

                        <div class="pointer-events-none absolute -right-16 -top-20 h-52 w-52 rounded-full bg-white/15 blur-2xl"></div>

                        <div class="pointer-events-none absolute -bottom-24 -left-16 h-48 w-48 rounded-full bg-lime-200/15 blur-2xl"></div>

                        <div class="relative flex items-center justify-between">
                            <div>
                                <div class="text-xl font-black">
                                    PrivatBank
                                </div>

                                <div class="mt-1 text-xs text-white/80">
                                    {{ match (app()->getLocale()) {
                                        'ru' => 'Оплата частями',
                                        'en' => 'Installment payments',
                                        default => 'Оплата частинами',
                                    } }}
                                </div>
                            </div>

                            <div class="grid h-11 w-14 place-items-center rounded-xl bg-white/90">
                                <div class="h-5 w-8 rounded border border-amber-500/30 bg-amber-200"></div>
                            </div>
                        </div>

                        <div class="relative my-10 text-3xl font-black leading-tight sm:text-4xl">
                            {{ match (app()->getLocale()) {
                                'ru' => 'Оплата частями',
                                'en' => 'Pay in installments',
                                default => 'Оплата частинами',
                            } }}
                        </div>

                        <div class="relative flex items-center justify-between text-sm font-semibold text-white/90">
                            <span>3piroga.ua</span>

                            <span>
                                {{ match (app()->getLocale()) {
                                    'ru' => 'до 3 платежей',
                                    'en' => 'up to 3 payments',
                                    default => 'до 3 платежів',
                                } }}
                            </span>
                        </div>
                    </div>

                    <div class="mt-5 grid gap-3 sm:grid-cols-3">
                        @foreach ($quickItems as $item)
                            <div class="rounded-2xl border border-green-100 bg-green-50 p-4 text-center">
                                <strong class="block text-xl font-black leading-tight text-green-700">
                                    {{ $item['value'] }}
                                </strong>

                                <span class="mt-1 block text-xs leading-5 text-slate-500">
                                    {{ $item['text'] }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</section>