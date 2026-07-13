@extends(front_view('layouts.app'))

@section('title','Ваш заказ отправлен')

@section('content')
    @php
        $locale = app()->getLocale();
        $isLocalized = in_array($locale, ['ru', 'en'], true);
        $homeUrl = $isLocalized
            ? route('localized.home', ['locale' => $locale])
            : route('home');
        $profileOrderUrl = $isLocalized
            ? route('localized.profile.orders.show', ['locale' => $locale, 'order' => $order->id])
            : route('profile.orders.show', $order->id);
        $sendEmailUrl = $isLocalized
            ? route('localized.checkout.success.send-email', ['locale' => $locale, 'order' => $order->id])
            : route('checkout.success.send-email', ['order' => $order->id]);
    @endphp

    {{-- Оверлей с затемнением, перекрывает всю страницу --}}
    <div class="fixed inset-0 z-40 bg-black/40 backdrop-blur-sm flex items-center justify-center overflow-y-auto px-3 py-6">
        <div
            class="w-full bg-white rounded-[8px]
               shadow-[0_32px_48px_rgba(0,0,0,0.10)]
               px-5 sm:px-8 pt-8 sm:pt-10 pb-8 sm:pb-10"
            style="max-width: 600px; max-height: calc(100vh - 48px); max-height: calc(100dvh - 48px); overflow-y: auto;"
        >

            {{-- Заголовок --}}
            <div class="text-center mb-6">
                <div class="text-[20px] leading-[24px] font-semibold text-[#6B7280] mb-2">
                    {{ st('order.success.thank_you', 'Спасибо!') }} 🎉
                </div>
                <div class="text-[22px] leading-[26px] font-semibold text-[#111827]">
                    {{ st('order.success.order_sent', 'Ваш заказ отправлен') }}
                </div>
            </div>

            {{-- Текст благодарности вместо картинок --}}
            <div class="text-center mb-6 space-y-3">
                @if($isWorkingHours)
                    {{-- В рабочее время --}}
                    <p class="text-[14px] leading-[20px] text-[#4B5563]">
                        {{ st('order.success.working_hours.thank_you', 'Благодарим Вас за заказ.') }}<br>
                        {{ st('order.success.working_hours.order_number', 'Номер заказа') }} {{ $orderNumber }}<br>
                        {{ st('order.success.working_hours.call_center', 'В течении 15 минут с Вами свяжется оператор колл центра для подтверждения заказа.') }}
                    </p>
                @else
                    {{-- В нерабочее время --}}
                    <p class="text-[14px] leading-[20px] text-[#4B5563]">
                        {{ st('order.success.non_working_hours.thank_you', 'Благодарим Вас за заказ.') }}<br>
                        {{ st('order.success.non_working_hours.order_number', 'Номер заказа') }} {{ $orderNumber }}<br>
                        {{ st('order.success.non_working_hours.call_center', 'С Вами свяжется оператор колл-центра в 08:30 для подтверждения заказа.') }}
                    </p>
                @endif
                <p class="text-[14px] leading-[20px] text-[#4B5563] mt-5">
                    {{ st('order.success.signature', 'С уважением, команда «Три Пироги»') }}
                </p>
            </div>

            {{-- Информация о заказе --}}
            @php
                $total   = (float)($order->grand_total ?? $order->total_price_sale ?? $order->total_price ?? 0);
                $placedAt = $order->placedAt();
                $dateStr = $placedAt?->format('d.m.Y') ?? '';
                $number  = $order->number ?? ('#'.str_pad($order->id, 5, '0', STR_PAD_LEFT));

                $payLabel = $order->payment?->label(app()->getLocale()) ?? '—';
            @endphp


            {{-- Информация о заказе — по центру, как в Figma --}}
            <div class="grid grid-cols-[auto_auto] gap-x-8 gap-y-2 justify-center text-[15px] leading-[18px] text-[#4B5563] mb-6">

                <div class="text-right font-medium text-[#929292]">{{ st('order.success.order_code', 'Код заказа') }}:</div>
                <div class="text-[#111827]">#{{ $order->number }}</div>

                <div class="text-right font-medium text-[#929292]">{{ st('order.success.date', 'Дата') }}:</div>
                <div class="text-[#111827]">
                    {{ $dateStr }}
                </div>

                <div class="text-right font-medium text-[#929292]">{{ st('order.success.amount_to_pay', 'Сумма к оплате') }}:</div>
                <div class="text-[#111827]">{{ $total }} грн</div>

                <div class="text-right font-medium text-[#929292]">{{ st('order.success.payment_method', 'Способ оплаты') }}:</div>
                <div class="text-[#111827]">{{ $payLabel }}</div>

            </div>



            {{-- Кнопка "Вернуться на Главную" — как в Figma --}}
            <div class="mt-4 space-y-3 pb-[40px]">
            <a href="{{ $homeUrl }}"
               class="flex mx-auto items-center justify-center text-center
          w-full h-[40px]
          bg-[#FF7500] text-white font-semibold text-[16px]
          rounded-[6px] shadow
          hover:bg-[#e86a00] transition"
               style="max-width: 448px;">
                {{ st('order.success.back_to_home', 'Вернуться на Главную') }}
            </a>

            @auth
                @if((int) ($order->clients_id ?? 0) > 0)
                    <a href="{{ $profileOrderUrl }}"
                       class="flex mx-auto items-center justify-center text-center
          w-full h-[40px]
          bg-white border border-[#FF7500] text-[#FF7500] font-semibold text-[16px]
          rounded-[6px]
          hover:bg-[#FFF7ED] transition"
                       style="max-width: 448px;">
                        {{ st('order.success.view_order_status', 'Переглянути статус замовлення') }}
                    </a>
                @endif
            @endauth

            {{-- Кнопка "Продублювати заказ на Email" — как в Figma --}}
            <button
                type="button"
                id="send-order-email-btn"
                class="flex mx-auto items-center justify-center text-center
        w-full h-[36px]
        bg-white border border-[#E5E7EB]
        rounded-[6px]
        font-bold text-[14px] leading-none text-[#FF7500]
        shadow-[0_2px_10px_rgba(0,0,0,0.08)]
        hover:bg-[#FFF7ED] transition
        disabled:opacity-50 disabled:cursor-not-allowed"
                style="max-width: 448px;"
                onclick="sendOrderToEmail({{ $order->id }})"
            >
                <span id="send-order-email-text">{{ st('order.success.send_to_email', 'Продублювати заказ на Email') }}</span>
                <span id="send-order-email-loading" class="hidden">{{ st('order.success.sending', 'Відправка...') }}</span>
            </button>
            </div>



        </div>
    </div>

{{-- Модальное окно для уведомления --}}
<div
    id="email-notification-modal"
    x-data="{ show: false, message: '', isSuccess: true }"
    x-show="show"
    x-cloak
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-[100] flex items-center justify-center p-4 pointer-events-none"
    @keydown.escape.window="show = false"
>
    {{-- Фон --}}
    <div
        class="fixed inset-0 bg-black/40 backdrop-blur-sm z-[100]"
        @click="show = false"
        x-show="show"
    ></div>

    {{-- Модальное окно --}}
    <div
        class="relative bg-white rounded-[12px] shadow-xl z-[101] pointer-events-auto
               w-full max-w-[400px] p-6 md:p-8"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
    >
        {{-- Кнопка закрытия --}}
        <button
            type="button"
            @click="show = false"
            class="absolute right-4 top-4 text-gray-400 hover:text-gray-600 transition-colors"
            aria-label="Закрыть"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>

        {{-- Иконка успеха/ошибки --}}
        <div class="flex justify-center mb-4">
            <div
                x-show="isSuccess"
                class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center"
            >
                <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <div
                x-show="!isSuccess"
                class="w-16 h-16 rounded-full bg-red-100 flex items-center justify-center"
            >
                <svg class="w-10 h-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </div>
        </div>

        {{-- Текст сообщения --}}
        <div class="text-center">
            <h3
                class="text-lg md:text-xl font-semibold mb-2"
                :class="isSuccess ? 'text-gray-900' : 'text-red-600'"
                x-text="message"
            ></h3>
        </div>

        {{-- Кнопка закрытия --}}
        <div class="mt-6 flex justify-center">
            <button
                type="button"
                @click="show = false"
                class="px-6 py-2.5 rounded-[6px] bg-[#FF7500] text-white font-semibold text-[16px]
                       hover:bg-[#e56700] transition-colors"
            >
                {{ st('common.ok', 'ОК') }}
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
function showNotification(message, isSuccess = true) {
    const modal = document.getElementById('email-notification-modal');
    if (!modal) return;

    // Ждем, пока Alpine.js инициализируется
    if (typeof Alpine === 'undefined') {
        setTimeout(() => showNotification(message, isSuccess), 100);
        return;
    }

    // Используем Alpine.$data для доступа к данным компонента
    let alpine;
    try {
        alpine = Alpine.$data(modal);
    } catch (e) {
        // Если Alpine еще не инициализирован, ждем
        document.addEventListener('alpine:init', () => {
            setTimeout(() => showNotification(message, isSuccess), 100);
        });
        return;
    }

    if (alpine) {
        alpine.message = message;
        alpine.isSuccess = isSuccess;
        alpine.show = true;

        // Автоматически закрываем через 5 секунд при успехе
        if (isSuccess) {
            setTimeout(() => {
                if (alpine && alpine.show) {
                    alpine.show = false;
                }
            }, 5000);
        }
    } else {
        // Fallback: используем прямой доступ к DOM
        modal.style.display = 'flex';
        const messageEl = modal.querySelector('[x-text="message"]');
        const successIcon = modal.querySelector('[x-show="isSuccess"]');
        const errorIcon = modal.querySelector('[x-show="!isSuccess"]');

        if (messageEl) {
            messageEl.textContent = message;
        }

        if (isSuccess) {
            if (successIcon) successIcon.style.display = 'flex';
            if (errorIcon) errorIcon.style.display = 'none';
        } else {
            if (successIcon) successIcon.style.display = 'none';
            if (errorIcon) errorIcon.style.display = 'flex';
        }

        // Закрытие по клику на фон или кнопку
        const backdrop = modal.querySelector('.fixed.inset-0');
        const closeBtn = modal.querySelector('button[aria-label="Закрыть"]');
        const okBtn = modal.querySelector('button:not([aria-label])');

        const closeModal = () => {
            modal.style.display = 'none';
        };

        if (backdrop) backdrop.onclick = closeModal;
        if (closeBtn) closeBtn.onclick = closeModal;
        if (okBtn) okBtn.onclick = closeModal;

        if (isSuccess) {
            setTimeout(closeModal, 5000);
        }
    }
}

async function sendOrderToEmail(orderId) {
    const btn = document.getElementById('send-order-email-btn');
    const text = document.getElementById('send-order-email-text');
    const loading = document.getElementById('send-order-email-loading');

    if (btn.disabled) return;

    btn.disabled = true;
    text.classList.add('hidden');
    loading.classList.remove('hidden');

    try {
        const response = await fetch(@json($sendEmailUrl), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'Accept': 'application/json',
            },
        });

        const data = await response.json();

        if (data.success) {
            showNotification(
                data.message || '{{ st('order.email.sent_success', 'Замовлення відправлено на email') }}',
                true
            );
            loading.classList.add('hidden');
            text.classList.remove('hidden');
            btn.disabled = true;
            btn.classList.add('opacity-50');
        } else {
            showNotification(
                data.message || '{{ st('order.email.sent_error', 'Помилка відправки email') }}',
                false
            );
            btn.disabled = false;
            text.classList.remove('hidden');
            loading.classList.add('hidden');
        }
    } catch (error) {
        console.error('Error sending email:', error);
        showNotification(
            '{{ st('order.email.sent_error', 'Помилка відправки email') }}',
            false
        );
        btn.disabled = false;
        text.classList.remove('hidden');
        loading.classList.add('hidden');
    }
}
</script>
<script>
    window.dataLayer = window.dataLayer || [];

    window.dataLayer.push({
        event: 'purchase',
        ecommerce: {
            transaction_id: '{{ $order->id }}',
            value: {{ (float)($order->grand_total ?? 0) }},
            currency: '{{ $order->currency ?? "UAH" }}',
            shipping: {{ (float)($order->shipping_price ?? 0) }},
            discount: {{ (float)($order->sale_sum ?? 0) }},
            items: [
                    @foreach($order->items ?? [] as $item)
                {
                    item_id: '{{ $item["product_id"] ?? $item["id"] ?? "" }}',
                    item_name: @json($item["product"]["title"] ?? $item["product"]["name"] ?? $item["product"]["slug"] ?? ""),
                    price: {{ (float)($item["unit_price_effective"] ?? $item["unit_price"] ?? 0) }},
                    quantity: {{ (int)($item["qty"] ?? 1) }},
                    discount: {{ (float)($item["discount_total"] ?? 0) }}
                },
                @endforeach
            ]
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        if (typeof window.eSputnikTrackPurchasedItems !== 'function') {
            console.warn('eSputnik purchase helper is not ready');
            return;
        }

        window.eSputnikTrackPurchasedItems({
            orderNumber: @json((string) $orderNumber),
            currency: @json((string) ($order->currency ?? 'UAH')),
            items: [
                    @foreach($order->items ?? [] as $item)
                {
                    product_key: @json((string) (($item->product?->code2) ?: ($item->product?->parent?->code2) ?: ($item->product?->sku) ?: ($item->product?->parent?->sku) ?: ($item->product_id ?? ''))),
                    price: @json((string) ((float) ($item->unit_price_effective ?? $item->unit_price ?? 0))),
                    quantity: @json((string) ((int) ($item->qty ?? 1))),
                    currency: @json((string) ($item->currency ?? $order->currency ?? 'UAH')),
                },
                @endforeach
            ]
        });
    });
</script>
@endpush
@endsection
