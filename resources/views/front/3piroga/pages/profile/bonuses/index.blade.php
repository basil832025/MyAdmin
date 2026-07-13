@php
    $user = auth()->user();
    $loyaltyAccount = \App\Models\Shop\LoyaltyAccount::where('client_id', $user->id)->first();
    $balance = $loyaltyAccount ? $loyaltyAccount->balance : 0;
    $transactions = $loyaltyAccount 
        ? $loyaltyAccount->transactions()->with('order')->orderByDesc('created_at')->get() 
        : collect();
@endphp

@extends(front_view('layouts.app'))

@section('title', st('profile.bonuses.title', 'Бонуси'))

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
                    {{ st('profile.bonuses.title', 'Бонуси') }}
                </h1>

                {{-- Карточка с балансом бонусов --}}
                <div class="rounded-[12px] p-6 mb-6 shadow-[0_2px_10px_rgba(0,0,0,0.08)]" 
                     style="background: linear-gradient(to bottom, rgba(253, 221, 167, 0.2), rgba(192, 65, 3, 0.1));">
                    <div class="flex flex-col gap-6">
                        <div class="text-[16px] text-[#19191A]">
                            {{ st('profile.bonuses.on_account', 'На рахунку') }}
                        </div>
                        <div>
                            <span class="text-[40px] leading-[44px] font-bold text-[#DC2626]">
                                {{ number_format($balance, 0, '.', ' ') }}
                            </span>
                            <span class="text-[40px] leading-[44px] font-bold text-[#19191A]">
                                {{ ' ' . st('profile.bonuses.bonuses', 'Бонусів') }}
                            </span>
                        </div>
                    <a href="/bonus" class="text-[14px] text-[#19191A] underline hover:text-[#FF7500] transition">
                        {{ st('profile.bonuses.rules', 'Правила нарахування') }}
                    </a>
                    </div>
                </div>

                {{-- История транзакций --}}
                <div class="mb-6">
                    <h2 class="text-[20px] font-semibold text-[#19191A] mb-4">
                        {{ st('profile.bonuses.history', 'Історія') }}
                    </h2>

                    @if($transactions->isEmpty())
                        <div class="text-gray-500 text-center py-8">
                            {{ st('profile.bonuses.no_transactions', 'Немає транзакцій') }}
                        </div>
                    @else
                        <div class="space-y-4">
                            @foreach($transactions as $transaction)
                                @php
                                    $amount = $transaction->amount;
                                    $isPositive = $amount > 0;
                                    $amountFormatted = ($isPositive ? '+' : '') . number_format($amount, 0, '.', ' ');
                                    $date = $transaction->order
                                        ? ($transaction->order->placedAt() ?? $transaction->order->created_at ?? $transaction->created_at)
                                        : $transaction->created_at;
                                    // Форматируем дату: день месяц, день недели
                                    $day = $date->format('d');
                                    $monthNames = [
                                        '01' => st('profile.bonuses.jan', 'Січ'),
                                        '02' => st('profile.bonuses.feb', 'Лют'),
                                        '03' => st('profile.bonuses.mar', 'Бер'),
                                        '04' => st('profile.bonuses.apr', 'Кві'),
                                        '05' => st('profile.bonuses.may', 'Тра'),
                                        '06' => st('profile.bonuses.jun', 'Чер'),
                                        '07' => st('profile.bonuses.jul', 'Лип'),
                                        '08' => st('profile.bonuses.aug', 'Сер'),
                                        '09' => st('profile.bonuses.sep', 'Вер'),
                                        '10' => st('profile.bonuses.oct', 'Жов'),
                                        '11' => st('profile.bonuses.nov', 'Лис'),
                                        '12' => st('profile.bonuses.dec', 'Гру'),
                                    ];
                                    $weekdayNames = [
                                        'Mon' => st('profile.bonuses.mon', 'Пн'),
                                        'Tue' => st('profile.bonuses.tue', 'Вт'),
                                        'Wed' => st('profile.bonuses.wed', 'Ср'),
                                        'Thu' => st('profile.bonuses.thu', 'Чт'),
                                        'Fri' => st('profile.bonuses.fri', 'Пт'),
                                        'Sat' => st('profile.bonuses.sat', 'Сб'),
                                        'Sun' => st('profile.bonuses.sun', 'Нд'),
                                    ];
                                    $month = $monthNames[$date->format('m')] ?? $date->format('M');
                                    $weekday = $weekdayNames[$date->format('D')] ?? $date->format('D');
                                    // Формируем описание на основе типа транзакции
                                    $typeDescriptions = [
                                        'accrual' => st('profile.bonuses.purchase', 'Покупка'),
                                        'spend' => st('profile.bonuses.spend', 'Списання'),
                                        'expire' => st('profile.bonuses.expire', 'Термін дії сплив'),
                                        'adjustment' => st('profile.bonuses.manual_transaction', 'Ручна транзакція'),
                                        'reverse' => st('profile.bonuses.reverse', 'Скасування'),
                                    ];
                                    $description = $typeDescriptions[$transaction->type] ?? st('profile.bonuses.transaction', 'Транзакція');
                                @endphp
                                <div class="flex items-center py-3 border-b border-gray-200">
                                    {{-- Левая колонка: Описание --}}
                                    <div class="flex-1">
                                        <div class="text-[16px] font-medium text-[#19191A]">
                                            {{ $description }}
                                        </div>
                                    </div>
                                    {{-- Средняя колонка: Дата --}}
                                    <div class="flex-1 text-center">
                                        <div class="text-[14px] text-gray-500">
                                            {{ $day }} {{ $month }}, {{ $weekday }}
                                        </div>
                                    </div>
                                    {{-- Правая колонка: Сумма --}}
                                    <div class="flex-1 text-right">
                                        <div class="text-[16px] font-semibold {{ $isPositive ? 'text-[#27AE60]' : 'text-[#EF4444]' }}">
                                            {{ $amountFormatted }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Синяя пунктирная линия снизу --}}
                <div class="border-t-2 border-dashed border-blue-300 mt-6"></div>
            </main>
        </div>
    </div>
@endsection

