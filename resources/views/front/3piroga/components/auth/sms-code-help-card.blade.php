@php
    $supportPhoneDisplay = st('auth.sms_help_phone', '097 898 4333');
    $supportPhoneTel = 'tel:+380978984333';
@endphp

<div class="mt-8 md:mt-10 rounded-2xl border border-[#FFD7B0] bg-[#FFF3E8] px-4 py-4 md:px-6 md:py-5 shadow-[0_8px_22px_rgba(255,117,0,0.08)]">
    <div class="flex items-center gap-4 md:gap-5">
        <a
            href="{{ $supportPhoneTel }}"
            class="flex h-20 w-20 shrink-0 items-center justify-center rounded-full border border-[#FFD7B0] bg-white text-[#FF7500] shadow-[0_6px_18px_rgba(255,117,0,0.16)]"
            aria-label="{{ st('auth.sms_help_call_label', 'Зателефонувати') }} {{ $supportPhoneDisplay }}"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12" viewBox="0 0 64 64" aria-hidden="true">
                <path fill="currentColor" d="M22.1 16.2c-1.5-1.8-4.2-1.8-5.8-.1l-3.7 4c-1.1 1.2-1.5 3-1 4.6 4.2 13.4 14.4 23.5 27.7 27.7 1.6.5 3.4.1 4.6-1l4-3.7c1.7-1.6 1.7-4.3-.1-5.8l-5.4-4.5c-1.4-1.2-3.5-1.2-4.9 0l-2.8 2.3c-4.4-2.2-8.1-5.9-10.3-10.3l2.3-2.8c1.2-1.4 1.2-3.5 0-4.9l-4.6-5.5Z"/>
                <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="4" d="M37 15c6.6 1.8 10.8 6 12.7 12.7"/>
                <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="4" d="M39.2 5.7c11.1 2.7 18.4 10 21.1 21.1"/>
            </svg>
        </a>

        <div class="min-w-0">
            <div class="inline-flex items-start text-[19px] md:text-[21px] font-extrabold leading-tight text-gray-950">
                <span class="whitespace-nowrap">{{ st('auth.sms_help_title', 'Не прийшов код?') }}</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="ml-1 -mt-3 h-8 w-8 shrink-0 text-[#FF7500]" viewBox="0 0 32 32" fill="none" aria-hidden="true" style="transform: rotate(28deg); transform-origin: center;">
                    <path d="M9 13 5 9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                    <path d="M15 9V3" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                    <path d="M21 13 27 8" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                </svg>
            </div>
            <div class="mt-1 text-sm md:text-base leading-snug text-gray-900">
                {{ st('auth.sms_help_call_us', 'Зателефонуйте нам на') }}
            </div>
            <a href="{{ $supportPhoneTel }}" class="mt-1 block text-2xl md:text-3xl font-extrabold leading-tight text-[#FF7500]">
                {{ $supportPhoneDisplay }}
            </a>
            <div class="text-sm md:text-base leading-snug text-gray-900">
                {{ st('auth.sms_help_we_create_order', 'і ми оформимо ваше замовлення в телефонному режимі.') }}
            </div>
        </div>
    </div>
</div>
