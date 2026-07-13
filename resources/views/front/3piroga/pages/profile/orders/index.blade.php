@php
    $user = auth()->user();
    $status = request()->get('status', 'all'); // all, completed, cancelled

    $ordersQuery = \App\Models\Shop\Order::where('clients_id', $user->id)
        ->where('status', '!=', \App\Enums\OrderStatus::Cart);

    $allCount = \App\Models\Shop\Order::where('clients_id', $user->id)
        ->where('status', '!=', \App\Enums\OrderStatus::Cart)->count();
    $completedCount = \App\Models\Shop\Order::where('clients_id', $user->id)
        ->where('status', \App\Enums\OrderStatus::Delivered)->count();
    $cancelledCount = \App\Models\Shop\Order::where('clients_id', $user->id)
        ->where('status', \App\Enums\OrderStatus::Cancelled)->count();

    if ($status === 'completed') {
        $ordersQuery->where('status', \App\Enums\OrderStatus::Delivered);
    } elseif ($status === 'cancelled') {
        $ordersQuery->where('status', \App\Enums\OrderStatus::Cancelled);
    }

    $orders = $ordersQuery
        ->orderByDesc('dat')
        ->orderByDesc('id')
        ->get();
@endphp

@extends(front_view('layouts.app'))

@section('title', st('profile.orders.title', 'История заказов'))

@section('content')
    <div class="mx-auto desk:w-[1200px] px-4 md:px-6 desk:px-0">
        <div class="xl:grid xl:grid-cols-[240px,1fr] md:gap-6">
            {{-- Левое меню (desktop) --}}
            <aside class="hidden xl:block">
                @include(front_view('pages.menu.profile-menu'))
            </aside>

            {{-- Контент --}}
            <main>
                {{-- Заголовок --}}
                <h1 class="text-[28px] font-bold text-[#19191A] mb-4">
                    {{ st('profile.orders.title', 'История заказов') }}
                </h1>

                {{-- Фильтры-табы --}}
                <div class="flex gap-2 mb-6 justify-start">
                    <a href="?status=all"
                       class="h-[40px] px-3 rounded-[12px] text-[14px] font-medium transition shadow-[0_2px_10px_rgba(0,0,0,0.08)] flex items-center justify-center {{ $status === 'all' ? 'bg-[#FF7500] text-white' : 'bg-white text-[#19191A]' }}">
                        {{ st('profile.orders.all', 'Все') }} {{ $allCount }}
                    </a>
                    <a href="?status=cancelled"
                       class="h-[40px] px-3 rounded-[12px] text-[14px] font-medium transition shadow-[0_2px_10px_rgba(0,0,0,0.08)] flex items-center justify-center {{ $status === 'cancelled' ? 'bg-[#FF7500] text-white' : 'bg-white text-[#19191A]' }}">
                        {{ st('profile.orders.cancelled', 'Отмененные') }} {{ $cancelledCount }}
                    </a>
                    <a href="?status=completed"
                       class="h-[40px] px-3 rounded-[12px] text-[14px] font-medium transition shadow-[0_2px_10px_rgba(0,0,0,0.08)] flex items-center justify-center {{ $status === 'completed' ? 'bg-[#FF7500] text-white' : 'bg-white text-[#19191A]' }}">
                        {{ st('profile.orders.completed', 'Завершены') }} {{ $completedCount }}
                    </a>
                </div>

                {{-- Список заказов --}}
                @if($orders->isEmpty())
                    <div class="text-gray-500 text-center py-8">
                        {{ st('profile.orders.empty', 'Нет заказов') }}
                    </div>
                @else
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                        @foreach($orders as $order)
                            @php
                                $orderDate = $order->placedAt() ?? $order->created_at;
                                $day = $orderDate->format('d');
                                $monthNames = [
                                    '01' => st('profile.bonuses.jan', 'Янв'),
                                    '02' => st('profile.bonuses.feb', 'Фев'),
                                    '03' => st('profile.bonuses.mar', 'Мар'),
                                    '04' => st('profile.bonuses.apr', 'Апр'),
                                    '05' => st('profile.bonuses.may', 'Май'),
                                    '06' => st('profile.bonuses.jun', 'Июн'),
                                    '07' => st('profile.bonuses.jul', 'Июл'),
                                    '08' => st('profile.bonuses.aug', 'Авг'),
                                    '09' => st('profile.bonuses.sep', 'Сен'),
                                    '10' => st('profile.bonuses.oct', 'Окт'),
                                    '11' => st('profile.bonuses.nov', 'Ноя'),
                                    '12' => st('profile.bonuses.dec', 'Дек'),
                                ];
                                $weekdayNames = [
                                    'Mon' => st('profile.bonuses.mon', 'Пн'),
                                    'Tue' => st('profile.bonuses.tue', 'Вт'),
                                    'Wed' => st('profile.bonuses.wed', 'Ср'),
                                    'Thu' => st('profile.bonuses.thu', 'Чт'),
                                    'Fri' => st('profile.bonuses.fri', 'Пт'),
                                    'Sat' => st('profile.bonuses.sat', 'Сб'),
                                    'Sun' => st('profile.bonuses.sun', 'Вс'),
                                ];
                                $month = $monthNames[$orderDate->format('m')] ?? $orderDate->format('M');
                                $weekday = $weekdayNames[$orderDate->format('D')] ?? $orderDate->format('D');

                                $itemsCount = $order->items()->count();
                                $total = $order->grand_total ?? $order->total_price ?? 0;

                                // Бонусы из транзакций
                                $bonusAmount = 0;
                                if ($order->loyaltyTransactions) {
                                    $accrualTransactions = $order->loyaltyTransactions()->where('type', 'accrual')->get();
                                    $bonusAmount = $accrualTransactions->sum('amount');
                                }
                            @endphp
                            <div class="bg-white rounded-xl shadow-[0_2px_10px_rgba(0,0,0,0.08)] p-4 md:p-6">
                                {{-- Дата и номер заказа --}}
                                <div class="flex items-start justify-between mb-4">
                                    <div>
                                        <div class="text-[14px] text-gray-500 mb-1">
                                            {{ $day }} {{ $month }}, {{ $weekday }}
                                        </div>
                                        <div class="text-[16px] font-semibold text-[#19191A]">
                                            #{{ $order->id }}
                                        </div>
                                    </div>
                                    {{-- Статус --}}
                                    @php
                                        $statusColors = $order->status->getFrontendColors();
                                        $statusLabel = $order->status->getLabel();
                                    @endphp
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-[3px] text-[14px] font-medium shadow-[0_2px_4px_rgba(0,0,0,0.1)]"
                                          style="background-color: {{ $statusColors['bg'] }}; color: {{ $statusColors['text'] }};">
                                        <x-order-status-icon :status="$order->status" />
                                        {{ $statusLabel }}
                                    </span>
                                </div>

                                {{-- Детали заказа --}}
                                <div class="text-[13px] leading-[16px] text-[#666666] mb-4">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span>
                                            {{ $order->self_pickup ? st('profile.orders.delivery.pickup', 'Самовывоз') : st('profile.orders.delivery.courier', 'Доставка курьером') }}
                                        </span>
                                        <span>
                                            {{ $order->payment?->label(app()->getLocale()) ?? \App\Enums\PaymentMethodEnum::CARD->label(app()->getLocale()) }}
                                        </span>
                                        <span>
                                            {{ st('profile.orders.order_for', 'Заказ на') }} {{ number_format($total, 0, '.', ' ') }} {{ st('profile.orders.uah', 'грн') }}
                                        </span>
                                        @if($bonusAmount > 0)
                                            <span class="text-[#16A34A]">
                                                +{{ number_format($bonusAmount, 0, '.', ' ') }} {{ st('profile.orders.bonuses', 'бонусов') }}
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                {{-- Кнопки действий --}}
                                <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                                    <div class="text-[14px] text-gray-600">
                                        {{ $itemsCount }} {{ st('profile.orders.items', 'позиций') }}
                                    </div>
                                    <div class="flex gap-3">
                                        <a href="{{ route('profile.orders.show', $order) }}"
                                           class="h-[46px] px-6 rounded-[6px] text-[14px] font-medium text-[#19191A] bg-white border border-gray-300 hover:bg-gray-50 transition shadow-[0_2px_10px_rgba(0,0,0,0.08)] inline-flex items-center justify-center">
                                            {{ st('profile.orders.details', 'Детали') }}
                                        </a>
                                        @php
                                            $locale = app()->getLocale();
                                            $repeatAction = in_array($locale, ['ru', 'en'], true)
                                                ? route('localized.profile.orders.repeat', ['locale' => $locale, 'order' => $order])
                                                : route('profile.orders.repeat', $order);
                                        @endphp
                                        <form action="{{ $repeatAction }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit"
                                                    class="h-[46px] px-6 rounded-[6px] text-[14px] font-medium text-white bg-[#FF7500] hover:bg-orange-600 transition inline-flex items-center justify-center">
                                                {{ st('profile.orders.repeat', 'Повторить заказ') }}
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </main>
        </div>
    </div>
@endsection
