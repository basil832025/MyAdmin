@props([
'title' => 'Хіти',
// сейчас можно передавать простой массив; позже будет коллекция моделей
'items' => [],
'favoriteIds' => [],
// опционально «Показати все»
'moreUrl' => null,
'titleClass' => '',
'titleUnderline' => true,
'titleAs' => 'h2',
'titleSize' => 'default',
])

<section class="max-w-screen-xl mx-auto">
    <div class="flex items-end justify-between ">
        <x-product.section-title :as="$titleAs" :class="$titleClass" :underline="$titleUnderline" :size="$titleSize">{{ $title }}</x-product.section-title>

        @if ($moreUrl)
            <a href="{{ $moreUrl }}" class="text-[#FF7500] hover:underline">Показати все</a>
        @endif
    </div>

    <!-- отступ 32px до сетки  -->
    <div class="md:mt-8 mt-6 grid grid-cols-1 gap-4 desk:gap-12 md:gap-8 md:grid-cols-2 desk:grid-cols-3 items-stretch" data-product-grid>
        @forelse ($items as $p)
            @php
                $pid    = $p['root_id'] ?? null;                    // из презентера
                $isFav  = $pid ? in_array($pid, $favoriteIds, true) : false;
             //   dd($p,$isFav,$pid);
            @endphp
            <x-product.card
                :product-id="$pid"
                :is-favorite="$isFav"
                :title="$p['title'] ?? 'Товар'"
                :url="$p['url'] ?? ''"
                :article="$p['article'] ?? '12345'"
                :price="$p['price'] ?? '0.00'"
                :description="$p['card_description'] ?? ($p['description'] ?? '')"
                :price_no_sale="$p['old_price'] ?? $p['price_no_sale'] ?? null"
                :image="$p['main_image'] ?? '/images/no-image.svg'"
                :characteristics="$p['characteristics'] ?? []"   {{-- 👈 добавили --}}
                :rows="$p['variant_rows'] ?? []"
                :root_id="$p['root_id'] ?? null"   {{-- 👈 --}}
            />
        @empty
            @for ($i=0; $i<6; $i++)

            @endfor
        @endforelse
    </div>
</section>
