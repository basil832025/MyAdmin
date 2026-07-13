@extends(front_view('layouts.app'))

@section('title', st('checkout.liqpay.order_title', 'Оплата замовлення №').' '.$order->id)

@section('content')
    @php
        $locale = app()->getLocale();
        $isLocalized = in_array($locale, ['ru', 'en'], true);
        $saveEmailAction = $isLocalized
            ? route('localized.checkout.pay.liqpay.email', ['locale' => $locale, 'order' => $order])
            : route('checkout.pay.liqpay.email', ['order' => $order]);

        $receiptEmailHint = match (app()->getLocale()) {
            'ru' => 'На этот email будет отправлен фискальный чек.',
            'en' => 'Fiscal receipt will be sent to this email.',
            default => 'На цей email буде надіслано фіскальний чек.',
        };
        $receiptEmailText = $receiptEmailHint;
    @endphp

    <div class="mx-auto desk:w-[1208px] p-2 max-w-full">
        <h1 class="text-2xl font-semibold mb-4">
            {{ st('checkout.liqpay.order_title', 'Оплата замовлення №') }} {{ $order->id }}
        </h1>

        <div class="mb-4 text-[16px]">
            {{ st('checkout.liqpay.amount_to_pay', 'До сплати') }}:
            <strong>{{ number_format($order->grand_total, 2, ',', ' ') }} {{ st('cart.summary.currency_short', 'грн') }}</strong>
        </div>

        @if (session('success'))
            <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->has('contact_email'))
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                {{ $errors->first('contact_email') }}
            </div>
        @endif

        @if ($emailRequired)
            <div class="mb-4 rounded-xl border border-orange-200 bg-orange-50 p-4">
                <div class="mb-2 text-sm font-medium text-[#272828]">
                    {{ $receiptEmailHint }}
                </div>

                <form method="POST" action="{{ $saveEmailAction }}" class="space-y-3">
                    @csrf
                    <div>
                        <label for="contact_email" class="mb-1 block text-sm font-medium text-[#272828]">
                            Email *
                        </label>
                        <input
                            id="contact_email"
                            name="contact_email"
                            type="email"
                            required
                            value="{{ old('contact_email', $clientEmail ?? '') }}"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:border-[#FF7500]"
                        >
                    </div>

                    <button
                        type="submit"
                        class="inline-flex h-10 items-center rounded-full bg-[#FF7500] px-5 text-sm font-semibold text-white transition hover:bg-[#e56700]"
                    >
                        {{ st('checkout.liqpay.save_email_and_continue', 'Зберегти email та продовжити') }}
                    </button>
                </form>
            </div>
        @endif

        <div class="bg-white rounded-xl p-4 shadow">
            @if ($emailRequired)
                <p class="text-sm text-[#6B7280]">
                    {{ st('checkout.liqpay.email_required_before_pay', 'Щоб перейти до оплати, спочатку вкажіть email для надсилання фіскального чека.') }}
                </p>
            @else
                <div class="mb-3 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-[#6B7280]">
                    <span>{{ $receiptEmailText }}</span>
                    <span class="font-semibold text-[#FF7500]">{{ $clientEmail }}</span>
                    <button
                        type="button"
                        id="change-email-open-btn"
                        class="ml-1 font-semibold underline decoration-blue-600 underline-offset-2"
                        style="color: #2563eb;"
                    >
                        {{ st('checkout.liqpay.change_email', 'Змінити email') }}
                    </button>
                </div>
                {!! $liqpayForm !!}
            @endif
        </div>

        <p class="mt-4 text-sm text-gray-500">
            {{ st('checkout.liqpay.return_after_success', 'Після успішної оплати ви будете автоматично повернуті на сторінку замовлення.') }}
        </p>
    </div>

    <div
        id="change-email-modal"
        class="fixed inset-0 z-[110] hidden items-center justify-center p-4"
        aria-hidden="true"
    >
        <div id="change-email-backdrop" class="absolute inset-0 bg-black/40"></div>

        <div class="relative z-[111] overflow-hidden rounded-2xl bg-white shadow-xl w-[440px] max-w-[92vw]">
            <div class="border-b border-gray-100 px-6 py-4">
                <div class="text-lg font-semibold text-[#272828]">
                    {{ st('checkout.liqpay.change_email_title', 'Змінити email') }}
                </div>
                <div class="mt-1 text-sm text-[#6B7280]">
                    {{ $receiptEmailHint }}
                </div>
            </div>

            <form method="POST" action="{{ $saveEmailAction }}" class="px-6 py-5">
                @csrf
                <div class="mb-4">
                    <label for="modal_contact_email" class="mb-1 block text-sm font-medium text-[#272828]">
                        Email *
                    </label>
                    <input
                        id="modal_contact_email"
                        name="contact_email"
                        type="email"
                        required
                        value="{{ old('contact_email', $clientEmail ?? '') }}"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:border-[#FF7500]"
                    >
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-gray-100 pt-4">
                    <button
                        type="button"
                        id="change-email-cancel-btn"
                        class="inline-flex h-10 items-center rounded-full border border-gray-300 px-4 text-sm font-semibold text-[#272828]"
                    >
                        {{ st('common.cancel', 'Скасувати') }}
                    </button>
                    <button
                        type="submit"
                        class="inline-flex h-10 items-center rounded-full bg-[#FF7500] px-5 text-sm font-semibold text-white transition hover:bg-[#e56700]"
                    >
                        {{ st('common.save', 'Зберегти') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            (function () {
                const modal = document.getElementById('change-email-modal');
                const openBtn = document.getElementById('change-email-open-btn');
                const cancelBtn = document.getElementById('change-email-cancel-btn');
                const backdrop = document.getElementById('change-email-backdrop');

                if (!modal || !openBtn) {
                    return;
                }

                const open = () => {
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                    modal.setAttribute('aria-hidden', 'false');
                };

                const close = () => {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                    modal.setAttribute('aria-hidden', 'true');
                };

                openBtn.addEventListener('click', open);
                cancelBtn?.addEventListener('click', close);
                backdrop?.addEventListener('click', close);
                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape') {
                        close();
                    }
                });
            })();
        </script>
    @endpush
@endsection
