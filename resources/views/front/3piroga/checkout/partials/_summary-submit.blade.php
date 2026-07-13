@php
    $locale = app()->getLocale();
    $privacyUrl = in_array($locale, ['ru', 'en'], true)
        ? url('/' . $locale . '/polityka-konfidentsiinosti')
        : url('/polityka-konfidentsiinosti');
@endphp

{{-- Согласие --}}
<div data-field-wrap="agree">
    <label class="mt-1 flex items-start gap-3 text-[11px] leading-[16px] text-[#4B5563] cursor-pointer">
        <span class="relative inline-flex items-center pt-[2px] checkbox-wrap">
            <input
                type="checkbox"
                name="agree"
                checked
                value="1"
                class="peer sr-only"
                data-required
                data-label="Согласие"
            >

            <span
                class="w-6 h-6 rounded-[4px] border border-[#9CA3AF]
                       peer-checked:bg-[#FF7500] peer-checked:border-[#FF7500]
                       grid place-items-center transition"
            >
                <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="none"
                     xmlns="http://www.w3.org/2000/svg">
                    <path d="M5 13L9 17L19 7"
                          stroke="currentColor" stroke-width="2"
                          stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </span>
        </span>

        <a href="{{ $privacyUrl }}" target="_blank" rel="noopener noreferrer" class="underline hover:no-underline">
            {{ st('cart.agree.text', 'Я согласен с политикой конфиденциальности, пользовательским соглашением и даю разрешение на обработку персональных данных.') }}
        </a>
    </label>

    <p class="mt-1 text-[11px] text-red-500 hidden" data-error-for="agree">
        {{ st('form.required','Це обов’язкове поле') }}
    </p>
</div>

{{-- Кнопка оформления --}}
<button
    type="submit"
    data-checkout-submit
    x-text="paymentMethod === 'liqpay' ? @js(st('cart.actions.pay', 'Перейти к оплате')) : @js(st('cart.actions.checkout', 'Оформить заказ'))"
    class="mt-3 w-full h-[52px] rounded-[12px] bg-[#FF7500] hover:bg-[#e56700]
           text-white text-[18px] leading-[24px] font-semibold
           shadow-[0_4px_12px_rgba(255,117,0,0.35)] transition"
>
    {{ st('cart.actions.checkout', 'Оформить заказ') }}
</button>
