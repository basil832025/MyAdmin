<x-mail::message>
# {{ st('checkout.fiscal_receipt.title', 'Фіскальний чек') }}

{{ st('checkout.fiscal_receipt.hello', 'Дякуємо за оплату замовлення.') }}

@php
    $orderNumber = $order->number ?? ('#' . $order->id);
    $receiptUrl = trim((string) ($cashalotLog->receipt_url ?? ''));
    $numFiscal = trim((string) ($cashalotLog->num_fiscal ?? ''));
@endphp

**{{ st('checkout.fiscal_receipt.order', 'Замовлення') }}:** {{ $orderNumber }}

@if ($numFiscal !== '')
**{{ st('checkout.fiscal_receipt.number', 'Фіскальний номер') }}:** {{ $numFiscal }}
@endif

@if ($receiptUrl !== '')
<x-mail::button :url="$receiptUrl">
{{ st('checkout.fiscal_receipt.open', 'Відкрити фіскальний чек') }}
</x-mail::button>

{{ st('checkout.fiscal_receipt.link_fallback', 'Якщо кнопка не працює, відкрийте посилання:') }}

{{ $receiptUrl }}
@endif

{{ st('checkout.fiscal_receipt.signature', 'З повагою, команда «Три Пироги»') }}
</x-mail::message>
