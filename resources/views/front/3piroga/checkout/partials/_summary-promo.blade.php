<div class="space-y-2"
     x-data="promoComponent"
>
    <div class="checkout-section-title">
        {{ st('cart.promo.title', 'У вас є промокод?') }}
    </div>

    <div class="text-[13px] leading-[18px] text-[#6B7280]">
        {{ st('cart.promo.subtitle', 'Додайте свій промокод для миттєвої знижки') }}
    </div>

    <div class="relative">
        <input
            type="text"
            x-model="coupon"
            placeholder="{{ st('cart.promo.placeholder', 'Код купона') }}"
            class="w-full h-[52px] pr-[52px] rounded-[8px] border border-[#E5E7EB] px-4
                   text-[16px] leading-[22px] placeholder:text-[#9CA3AF]
                   focus:outline-none focus:ring-2 focus:ring-[#FF7500]/60"
        >

        <button
            type="button"
            @click="apply()"
            class="absolute right-[6px] top-1/2 -translate-y-1/2
                   w-10 h-10 rounded-[8px] bg-[#FF7500] text-white
                   grid place-items-center hover:bg-[#e56700] transition"
        >
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                 stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M5 12H19"/>
                <path d="M13 6L19 12L13 18"/>
            </svg>
        </button>
    </div>

    {{-- ошибка --}}
    <template x-if="error">
        <div class="text-sm text-red-600" x-text="error"></div>
    </template>

    {{-- успешный промокод --}}
    <template x-if="discount > 0">
        <div class="text-sm font-semibold text-green-600">
            {{ st('cart.promo.applied_prefix', 'Застосовано промокод') }}
            <span x-text="coupon"></span> —
            {{ st('cart.promo.discount_word', 'знижка') }}
            <span x-text="discount"></span>
            {{ st('cart.summary.currency_short', 'грн') }}
        </div>
    </template>

    {{-- скрытое поле отправки --}}
    <input type="hidden" name="coupon_applied" :value="applied ? coupon : ''">
    <input type="hidden" name="coupon" x-model="coupon">
</div>
