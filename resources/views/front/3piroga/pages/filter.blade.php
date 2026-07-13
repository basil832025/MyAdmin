@extends(front_view('layouts.app'))

@section('title', $title ?? st('filter.title', 'Результати фільтру'))

@section('content')
    <div
        x-data="{ filterOpen: false }"
        class="mx-auto desk:w-[1198px] w-[357px] md:w-[736px] max-w-full"
    >
        {{-- верхняя панель: кнопка Фільтр + сортировка --}}
        <div class="flex items-center justify-between mb-6">
            {{-- кнопка фильтра (точно как в каталоге) --}}
            <button type="button"
                    @click="filterOpen = true"
                    class="w-10 md:w-[132px] h-10 rounded-[12px] border border-[#E5E7EB] bg-white
                           px-3 inline-flex items-center gap-2 justify-center">
                <img src="{{ asset('images/filter.svg') }}"
                     alt=""
                     class="w-[22px] h-[19px]"
                     aria-hidden="true">
                <span class="hidden md:block font-bold text-[16px] leading-none text-[#19191A]">
                    {{ st('all.filter','Фільтр') }}
                </span>
            </button>

            {{-- сортировка — тот же компонент, что и на категории --}}
            <x-ui.sort-dropdown />
        </div>

        {{-- модальное окно фильтров (наш общий partial) --}}
        @include(front_view('product.filter-panel'))

        {{-- Заголовок страницы --}}
        <h1 class="mt-2 text-3xl font-semibold">
            {{ $title ?? st('filter.title', 'Результати фільтру') }}
        </h1>

        {{-- Если по фильтру ничего не нашли --}}
        @if($items->isEmpty())
            <p class="mt-8 text-sm text-gray-500">
                {{ st('filter.empty', 'За вибраними фільтрами товари не знайдено.') }}
            </p>
        @else
            <section class="max-w-screen-xl mx-auto mt-12">
                <div class="grid grid-cols-1 md:grid-cols-2 desk:grid-cols-3 gap-4 desk:gap-12 md:gap-8">
                    @foreach($items as $p)
                        @php
                            $pid = $p['root_id'] ?? null;
                            $isFav = $pid ? in_array($pid, $favoriteIds ?? [], true) : false;
                        @endphp
                        <x-product.card
                            :product-id="$pid"
                            :is-favorite="$isFav"
                            :title="$p['title'] ?? st('search.product_default_title', 'Товар')"
                            :url="$p['url'] ?? ''"
                            :article="$p['article'] ?? '12345'"
                            :price="$p['price'] ?? '0.00'"
                            :description="$p['card_description'] ?? ($p['description'] ?? '')"
                            :price_no_sale="$p['old_price'] ?? $p['price_no_sale'] ?? null"
                            :image="$p['main_image'] ?? '/images/no-image.svg'"
                            :characteristics="$p['characteristics'] ?? []"
                            :rows="$p['variant_rows'] ?? []"
                            :root_id="$p['root_id'] ?? null"
                        />
                    @endforeach
                </div>
            </section>
        @endif
    </div>
@endsection
