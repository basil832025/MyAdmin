@props([
'title' => 'Товар',
'url' => '',
'price' => '0.00',
'description' => '',
'article' => null,

'productId' => null,     // может прийти из презентера (id базового продукта)
'root_id'   => null,     // если презентер отдаёт root_id
'isFavorite' => false,

'price_no_sale' => null,
'image' => '/images/no-image.svg',
'characteristics' => [],
'rows' => [],
])

@php
    static $productCardLabels = null;

    if ($productCardLabels === null) {
        $productCardLabels = [
            'discount' => st('product.badges.discount', 'Знижка'),
            'badgeLabels' => [
                'is_spicy' => st('product.badges.is_spicy', 'Гострий'),
                'is_new' => st('product.badges.is_new', 'Новинка'),
                'is_promo' => st('product.badges.is_promo', 'Акція'),
                'is_hit' => st('product.badges.is_hit', 'Хіт'),
                'is_vegan' => st('product.badges.is_vegan', 'Веган'),
                'is_product_of_day' => st('product.badges.is_product_of_day', 'Пиріг дня'),
            ],
            'sku_label' => st('product.sku_label', 'Артикул'),
        ];
    }

    // ЕДИНЫЙ ID, по которому ставим/снимаем избранное
    $pid = $productId ?? $root_id ?? ($rows[0]['product_id'] ?? null);
    $productKey = trim((string) (($rows[0]['product_key'] ?? null) ?: ($rows[0]['article'] ?? null) ?: ($pid ?? '')));

    // Подготовка карты цен для всех вариантов
    $priceMap = [];
    $badgeMap = [];
    $manualDiscountMap = [];
    $initialDiscount = null;
    $initialProductId = (string)($rows[0]['product_id'] ?? ($pid ?: ''));

    // Проверяем все варианты на наличие старой цены
    if (!empty($rows)) {
        foreach ($rows as $row) {
            $rowId = (string)($row['product_id'] ?? '');
            $rowPrice = (float)($row['price'] ?? 0);
            // Проверяем old_price более тщательно
            $rowOldPrice = null;
            if (isset($row['old_price'])) {
                $oldPriceVal = $row['old_price'];
                if ($oldPriceVal !== null && $oldPriceVal !== '' && $oldPriceVal !== 0) {
                    $rowOldPrice = (float)$oldPriceVal;
                }
            }

            $priceMap[$rowId] = [
                'price' => $rowPrice,
                'old'   => $rowOldPrice,
            ];

            $manualDiscountMap[$rowId] = isset($row['manual_discount_percent']) && $row['manual_discount_percent'] !== null && $row['manual_discount_percent'] !== ''
                ? (float) $row['manual_discount_percent']
                : null;

            $badgeMap[$rowId] = [
                'is_spicy' => (bool)($row['is_spicy'] ?? false),
                'is_new' => (bool)($row['is_new'] ?? false),
                'is_promo' => (bool)($row['is_promo'] ?? false),
                'is_hit' => (bool)($row['is_hit'] ?? false),
                'is_vegan' => (bool)($row['is_vegan'] ?? false),
                'is_product_of_day' => (bool)($row['is_product_of_day'] ?? false),
            ];

            // Рассчитываем скидку только для начального варианта
            if ($rowId === $initialProductId) {
                if ($manualDiscountMap[$rowId] !== null) {
                    $initialDiscount = (float) $manualDiscountMap[$rowId];
                } elseif ($rowOldPrice && $rowOldPrice > 0 && $rowPrice && $rowPrice > 0 && $rowOldPrice > $rowPrice) {
                    $initialDiscount = round((($rowOldPrice - $rowPrice) / $rowOldPrice) * 100);
                }
            }
        }
    }

    // Если не нашли скидку в вариантах, используем значения из пропсов (для обратной совместимости)
    if ($initialDiscount === null && $price_no_sale && $price_no_sale > 0 && $price && $price > 0) {
        $oldPrice = (float)$price_no_sale;
        $currentPrice = (float)$price;
        if ($oldPrice > $currentPrice) {
            $initialDiscount = round((($oldPrice - $currentPrice) / $oldPrice) * 100);
        }
    }

    // Признак карточки с одним вариантом (для внутренних отступов)
    $rowsCount = count($rows ?? []);
    $isSingleVariant = $rowsCount <= 1;
    $discountLabel = $productCardLabels['discount'];
@endphp

<article
    x-data="{
        prices: @js($priceMap),
        badges: @js($badgeMap),
        manualDiscounts: @js($manualDiscountMap),
        discountLabel: @js($discountLabel),
        badgeLabels: @js($productCardLabels['badgeLabels']),
        discountPercent: @js($initialDiscount ?? null),
        activeBadges: [],
        rootId: @js($pid),
        init() {
            const initialProductId = @js((string)($rows[0]['product_id'] ?? ($pid ?: '')));
            if (initialProductId) {
                this.updateDiscount(initialProductId);
                this.updateBadges(initialProductId);
            }
            this.$nextTick(() => {
                const rowsSelectorContainer = this.$el.querySelector('.rows-selector-container');
                if (rowsSelectorContainer) {
                    const rowsSelector = rowsSelectorContainer.querySelector('[x-data]');
                    if (rowsSelector && window.Alpine) {
                        try {
                            const selectorData = window.Alpine.$data(rowsSelector);
                            if (selectorData && typeof selectorData.$watch === 'function') {
                                selectorData.$watch('selected', (newVal) => {
                                    if (newVal) {
                                        this.updateDiscount(String(newVal));
                                        this.updateBadges(String(newVal));
                                    }
                                });
                                if (selectorData.selected) {
                                    this.updateDiscount(String(selectorData.selected));
                                    this.updateBadges(String(selectorData.selected));
                                }
                            }
                        } catch(e) {
                            // Silent error handling
                        }
                    }
                }
            });
        },
        updateDiscount(productId) {
            if (!productId) {
                this.discountPercent = null;
                return;
            }
            const priceData = this.prices[String(productId)];
            const manualDiscount = this.manualDiscounts[String(productId)] ?? null;
            if (manualDiscount !== null && manualDiscount !== undefined && manualDiscount !== '') {
                this.discountPercent = Number(manualDiscount);
                return;
            }
            if (!priceData) {
                this.discountPercent = null;
                return;
            }
            const oldPrice = priceData.old;
            const currentPrice = priceData.price;

            if (oldPrice !== null && oldPrice !== undefined && oldPrice > 0 && currentPrice && currentPrice > 0 && oldPrice > currentPrice) {
                this.discountPercent = Math.round(((oldPrice - currentPrice) / oldPrice) * 100);
            } else {
                this.discountPercent = null;
            }
        },
        buildBadges(flags) {
            if (!flags) {
                return [];
            }

            const items = [];

            if (flags.is_spicy) {
                items.push({ key: 'is_spicy', color: '#FF0013', textColor: '#FFFFFF', label: this.badgeLabels.is_spicy });
            }
            if (flags.is_new) {
                items.push({ key: 'is_new', color: '#B91C1C', textColor: '#FFFFFF', label: this.badgeLabels.is_new });
            }
            if (flags.is_promo) {
                items.push({ key: 'is_promo', color: '#FF7500', textColor: '#FFFFFF', label: this.badgeLabels.is_promo });
            }
            if (flags.is_hit) {
                items.push({ key: 'is_hit', color: '#FFD700', textColor: '#19191A', label: this.badgeLabels.is_hit });
            }
            if (flags.is_vegan) {
                items.push({ key: 'is_vegan', color: '#27AE60', textColor: '#FFFFFF', label: this.badgeLabels.is_vegan });
            }
            if (flags.is_product_of_day) {
                items.push({ key: 'is_product_of_day', color: '#5D4037', textColor: '#FFFFFF', label: this.badgeLabels.is_product_of_day });
            }

            return items;
        },
        updateBadges(productId) {
            const flags = this.badges[String(productId)] ?? null;
            this.activeBadges = this.buildBadges(flags);
        },
        handleVariantSelected(event) {
            if (event && event.detail && event.detail.productId) {
                const productId = String(event.detail.productId);
                if (this.prices[productId]) {
                    this.updateDiscount(productId);
                }
                this.updateBadges(productId);
            }
        }
    }"
    @variant-selected="handleVariantSelected($event)"
    class="w-full h-full max-w-[355px] md:max-w-none flex flex-col rounded-[12px] bg-white desk:gap-8 md:gap-4 p-3 shadow-[0_8px_20px_rgba(0,0,0,0.05)]"
    data-product-card
    @if($pid) data-product-id="{{ $pid }}" @endif
>
    <a href="{{ $url }}">
        <div class="relative w-full h-[220px] overflow-hidden rounded-[12px]">
            <img src="{{ $image }}" alt="{{ $title }}" class="h-full w-full object-cover">
            <div class="absolute right-[10px] top-[10px] z-10 flex flex-col items-end gap-1">
                <span
                    x-show="discountPercent !== null && discountPercent > 0"
                    x-text="discountLabel + ' -' + discountPercent + '%'"
                    x-cloak
                    class="rounded-[3px] bg-[#B91C1C] px-[10px] py-[4px] text-white font-intro font-bold text-[14px] leading-[16px]">
                </span>
                <template x-for="badge in activeBadges" :key="badge.key">
                    <span
                        x-text="badge.label"
                        :style="`background:${badge.color};color:${badge.textColor};`"
                        class="rounded-[3px] px-[10px] py-[4px] font-intro font-bold text-[14px] leading-[16px]"
                    ></span>
                </template>
            </div>
        </div>
    </a>

    <div class="pt-4 {{ $isSingleVariant ? 'pb-2' : 'pb-3' }} flex-1 flex flex-col">
        <div data-card-meta>
            <div class="flex items-start justify-between gap-2">
                <h5 class="flex-1 min-w-0 font-intro font-bold text-[16px] leading-[22px] text-neutral-700 break-words">
                    {{ $title }}
                </h5>

                @if($pid)
                        <x-ui.favorite-button
                        :product-id="$pid"
                        :product-key="$productKey"
                        :post-url="route('favorite.toggle', $pid)"
                        :active="$isFavorite"
                        color="#FF7500"
                    />
                @endif
            </div>

            <p class="w-full font-intro text-[13px] leading-[16px] text-[#C04103] break-words">
                {{ $productCardLabels['sku_label'] }}: {{ $article ?? '123456' }}
            </p>

            <div class="w-full font-intro text-[13px] leading-[16px] text-[#A9A9A9] break-words mb-1">
                {!! $description !!}
            </div>
        </div>

        @if(!empty($rows))
            <div class="rows-selector-container mt-auto">
                <x-ui.rows-selector
                    :rows="$rows"
                    :characteristics="$characteristics"
                    :root-id="$pid"
                    :default-price="$price"
                    :default-old-price="$price_no_sale"
                    cart-text="{{ st('product.addcart','Додати в кошик') }}"
                />
            </div>
        @endif
    </div>
</article>
