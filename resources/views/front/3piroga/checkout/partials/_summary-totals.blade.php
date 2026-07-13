@php
    $itemsTotal = $totals['items_total'] ?? ($totals['total_price'] ?? 0);
    $discount   = $totals['discount']    ?? 0;
    $bonusUsed  = (bool) old('use_bonus', $sessionData['use_bonus'] ?? 1)
        ? old('bonus_amount', $sessionData['bonus_amount'] ?? ($totals['bonus_used'] ?? 0))
        : 0;
    $grandTotal = $totals['grand_total'] ?? max($itemsTotal - $discount - $bonusUsed, 0);
 //   dump($grandTotal);
@endphp

{{-- Итоги --}}
<div class="pt-1 space-y-1.5 text-[14px] leading-[20px]">
    <div class="flex justify-between text-[#272828]">
        <span>{{ st('cart.summary.items', 'Товари') }}</span>
        <span data-checkout-subtotal>
            {{ number_format($itemsTotal, 2, ',', ' ') }}
            {{ st('cart.summary.currency_short', 'грн') }}
        </span>
    </div>

    <div class="flex justify-between text-[#272828]">
        <span>{{ st('cart.summary.discount', 'Скидка') }}</span>

        <span class="flex items-baseline gap-1">
        <span data-checkout-discount>
            {{ number_format($discount, 2, ',', ' ') }}
        </span>
        <span>{{ st('cart.summary.currency_short', 'грн.') }}</span>
    </span>
    </div>

    <div class="flex justify-between text-[#272828]">
        <span>{{ st('cart.summary.bonus', 'Бонуси') }}</span>
        <span data-checkout-bonus>
            {{ number_format($bonusUsed, 2, ',', ' ') }}
        </span>
    </div>
    <div class="flex justify-between text-[#272828]">
        <span>{{ st('cart.dostavka', 'Доставка') }}</span>
        <span>
        <span data-checkout-shipping>0,00</span>
        <span>{{ st('cart.summary.currency_short', 'грн') }}</span>
    </span>
    </div>
    <input type="hidden" name="addr[lat]" id="checkout-addr-lat" value="{{ old('addr.lat', $sessionData['addr_lat'] ?? '') }}">
    <input type="hidden" name="addr[lng]" id="checkout-addr-lng" value="{{ old('addr.lng', $sessionData['addr_lng'] ?? '') }}">
    <input type="hidden" name="delivery_zone" id="checkout-delivery-zone" value="{{ old('delivery_zone', $sessionData['delivery_zone'] ?? '') }}">
    <input type="hidden" name="shipping_price" id="checkout-shipping-price" value="{{ old('shipping_price', $sessionData['shipping_price'] ?? 0) }}">

    <div class="h-px bg-[#F3F4F6] my-2"></div>

    <div class="flex justify-between items-end">
        <span class="text-[16px] leading-[22px] text-[#272828]">
            {{ st('cart.summary.total', 'Всего') }}
        </span>
        <div class="flex items-baseline gap-1 text-[#111827] font-bold" data-checkout-total-wrapper>
            @php
                $uah = floor($grandTotal);
                $kop = sprintf('%02d', (int)round(($grandTotal - $uah) * 100));
            @endphp
            <span class="text-[28px] leading-none tabular-nums" data-checkout-total-uah>
                {{ number_format($uah, 0, ',', ' ') }}
            </span>
            <sup class="text-[16px] leading-none tabular-nums" data-checkout-total-kop>{{ $kop }}</sup>
            <span class="text-[18px] leading-none ml-1">
                {{ st('cart.summary.currency_short', 'грн') }}
            </span>
        </div>
    </div>
</div>
