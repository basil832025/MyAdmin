@props([
    'title' => '',
    'products' => [],
])

@if(!empty($products))
    <style>
        .recom-swiper .swiper {
            overflow-x: hidden;
            overflow-y: visible;
            padding-bottom: 8px;
        }

        .recom-swiper .card-fixed > article {
            box-shadow: 1px 4px 8px rgba(0, 0, 0, 0.07);
        }

        @media (max-width: 767px) {
            .recom-swiper .swiper-slide {
                width: auto;
            }
        }
    </style>

    <section class="mt-[40px] md:mt-8 xl:mt-[80px] recom-swiper" x-data x-init="
        new Swiper($refs.sw, {
            slidesPerView: 'auto',
            spaceBetween: 12,
            speed: 360,
            watchOverflow: true,
            grabCursor: true,
            threshold: 4,
            resistanceRatio: 0.65,
            longSwipesRatio: 0.15,
            longSwipesMs: 220,
            touchReleaseOnEdges: true,
            touchStartPreventDefault: false,
            navigation: { nextEl: $refs.next, prevEl: $refs.prev },
            breakpoints: {
                768: { slidesPerView: 2, spaceBetween: 16, speed: 500, resistanceRatio: 0.85 },
                1024: { slidesPerView: 2, spaceBetween: 16, speed: 500 },
                1280: { slidesPerView: 3, spaceBetween: 16, speed: 500 },
            },
        });
    ">
        <h2 class="text-[26px] font-bold text-[#FF7500] mb-4">
            {{ $title }}
        </h2>

        <div class="swiper" x-ref="sw">
            <div class="swiper-wrapper" data-product-grid>
                @foreach($products as $p)
                    <div class="swiper-slide">
                        <div class="card-fixed">
                            <x-product.card
                                :title="$p['title'] ?? 'Товар'"
                                :url="$p['url'] ?? ''"
                                :article="$p['article'] ?? '12345'"
                                :price="$p['price'] ?? '0.00'"
                                :description="$p['card_description'] ?? ($p['description'] ?? '')"
                                :price_no_sale="$p['price_no_sale'] ?? '0.00'"
                                :image="$p['main_image'] ?? '/images/no-image.svg'"
                                :characteristics="$p['characteristics'] ?? []"
                                :rows="$p['variant_rows'] ?? []"
                                :root_id="$p['root_id'] ?? null"
                            />
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="flex justify-center items-center gap-3 mt-4 md:mt-6 mb-2">
            <button x-ref="prev"
                    class="swiper-prev w-[34px] h-[34px] rounded-xl bg-[#FF7500] hover:bg-orange-600 text-white flex items-center justify-center transition">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18">
                    <path fill="currentColor" d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/>
                </svg>
            </button>

            <button x-ref="next"
                    class="swiper-next w-[34px] h-[34px] rounded-xl bg-[#FF7500] hover:bg-orange-600 text-white flex items-center justify-center transition">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18">
                    <path fill="currentColor" d="M8.59 16.59L10 18l6-6-6-6-1.41 1.41L13.17 12z"/>
                </svg>
            </button>
        </div>
    </section>
@endif