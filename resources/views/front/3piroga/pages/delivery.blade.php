{{-- resources/views/delivery.blade.php --}}
@extends(front_view('layouts.app'))

@section('title', 'Доставка і самовивіз')

@section('content')
    @php
        $activePhone = $headerPhonePrimary['display'] ?? config('phones.default');
        $phones =  $headerPhones ?? config('phones.list', []);
        $pickup    =['time'=>'з 11:30 до 22:30', 'title' => 'Приймаємо замовлення на самовивіз'];
        $delivery  =['time'=>'з 11:45 до 22:30', 'title' => 'Доставляємо замовлення'];
        if (!empty($headerSchedule)){
            foreach ($headerSchedule as $Schedule){
                if (trim($Schedule['slug'])=='delivery'){
                    $delivery['time'] = $Schedule['time'];
                    $delivery['title'] = $Schedule['title'];
                }
                   if (trim($Schedule['slug'])=='pickup'){
                    $pickup['time'] = $Schedule['time'];
                    $pickup['title'] = $Schedule['title'];
                }
            }
        }

       // dd($delivery ,$pickup);
        $telHref = fn($p) => 'tel:' . preg_replace('/[^\d+]/', '', $p);

        // Query delivery zones grouped by prefix (Green, Blue, Red, Brown)
        $deliveryZoneGroups = \App\Models\DeliveryZone::where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(function ($zone) {
                $prefix = explode('_', $zone->name)[0]; // Group prefix (Green, Blue, etc.)
                return [
                    'prefix' => $prefix,
                    'name' => $zone->name,
                    'color' => $zone->color,
                    'delivery_price' => (float)$zone->delivery_price,
                    'delivery_time_min' => (int)$zone->delivery_time_min,
                    'delivery_time_max' => (int)$zone->delivery_time_max,
                    'free_delivery_from' => (float)$zone->free_delivery_from,
                ];
            })
            ->groupBy('prefix');

        // Prepare JS data keyed by prefix (matches map-cart.js zoneGroup logic)
        $deliveryZonesForJs = $deliveryZoneGroups->map(fn ($group) => $group->first())
            ->keyBy('prefix')
            ->map(fn ($zone) => [
                'name' => $zone['name'],
                'color' => $zone['color'],
                'delivery_price' => $zone['delivery_price'],
                'delivery_time_min' => $zone['delivery_time_min'],
                'delivery_time_max' => $zone['delivery_time_max'],
                'free_delivery_from' => $zone['free_delivery_from'],
            ]);
    @endphp
    <div class="mx-auto desk:w-[1198px] p-4  max-w-full">
        {{-- Хлебные крошки --}}
        <nav class="text-sm text-gray-500 my-4">
            <a href="{{ route('home') }}" class="hover:text-gray-700">{{ st('menu.home','Головна') }}</a>
            <span class="mx-2">→</span>
            <span class="text-gray-700">{{$page->title}}</span>
        </nav>
        <h2 class="inline-block mb-12 font-intro text-[40px] md:text-[64px] leading-[100%] md:leading-[64px] font-bold text-[#19191A] border-b-2 border-[#FF7500]">
            {{$page->title}}
        </h2>
        <!-- один поток на моб/планшет, две колонки на десктопе -->
        <div class="grid grid-cols-1 items-start gap-y-4 lg:gap-y-0 lg:gap-x-10 lg:grid-cols-[minmax(0,446px)_minmax(0,1fr)]">

        {{-- ЛЕВАЯ КОЛОНКА --}}
        <!-- 1) Заголовки + поле адреса (слева на lg, первым везде) -->
            <section id="delivery-head" class="order-1 lg:col-span-1 min-w-0">
                <h3 class="text-3xl md:text-4xl xl:text-[40px] font-bold mb-2">{{page_field('delivery', 'delivery_terms','Умови доставки') }} </h3>
                <p class="text-[#929292] text-base mb-5">{{page_field('delivery', 'delivery_address_enter','Укажіть адресу доставки або виберіть на карті для визначення часу очікування замовлення') }}</p>

                {{-- Поиск адреса --}}
                <label class="relative block">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 opacity-70">
                    {{-- иконка-гео --}}
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                        <path d="M12 22s7-5.686 7-12a7 7 0 1 0-14 0c0 6.314 7 12 7 12z" stroke="#FF7500" stroke-width="1.5"/>
                        <circle cx="12" cy="10" r="3" stroke="#FF7500" stroke-width="1.5"/>
                    </svg>
                </span>
                    <input
                        id="address-input"
                        type="text"
                        class="w-full pl-10 pr-3 py-3 rounded-xl border border-[#E5E7EB] focus:border-[#FF7500] focus:ring-0 outline-none"
                        placeholder="{{page_field('delivery', 'delivery_enter_address_del','Введіть адресу доставки') }} "
                    />
                </label>

                {{-- Баннер с ценой доставки --}}
                <div id="price-banner" class="mt-4 text-[#FF7500] font-extrabold uppercase leading-snug"></div>
            </section>


        {{-- ПРАВАЯ КОЛОНКА (карта) --}}
        <!-- 2) Карта (вторая на планшете/десктопе) -->
            <section id="delivery-map" class="order-2 lg:col-span-1 lg:row-span-2">
                <div class="relative">
                    <div id="map" class="md:h-[560px] h-[216px]  w-full rounded-xl overflow-hidden"></div>

                    {{-- Карточка графика в правом-низу карты --}}
                    <div class="hidden md:block absolute bottom-4 right-4 pointer-events-none">
                        <div class="pointer-events-auto bg-white/95 backdrop-blur rounded-2xl shadow-xl w-[342px] p-4">
                            <div class="flex items-center gap-3 justify-between">

                                <div class="min-w-0">
                                    <div class="font-semibold text-lg truncate">{{ data_get($headerLocation ?? null, 'title', '') }}</div>
                                    <div class="text-[10px] text-[#9E9E9E] truncate">{{ data_get($headerLocation ?? null, 'address', '') }}</div>
                                </div>
                                <div>
                                    <img src="/images/logo_mob.svg" class="w-10 h-10" alt="">
                                </div>
                            </div>

                            <div class="mt-3 grid grid-cols-2 gap-3 text-sm ">
                                <div class="rounded-[1px] border border-[#F9FAFB] p-3 shadow-[0_2px_8px_rgba(0,0,0,0.05)]">
                                    <div class="text-[#9E9E9E] text-xs text-center">{{$pickup['title']}}:</div>
                                    <div class="font-semibold mt-1 text-sm text-[#19191A] text-center">{{$pickup['time'] }}</div>
                                </div>
                                <div class="rounded-[1px] border border-[#F9FAFB] p-3 shadow-[0_2px_8px_rgba(0,0,0,0.05)]">
                                    <div class="text-[#9E9E9E] text-xs text-center">{{$delivery['title']}}:</div>
                                    <div class="font-semibold mt-1 text-sm text-[#19191A] text-center">{{$delivery['time']}}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Легенда зон под картой (динамическая, как в попапе) --}}
                <div class=" sm:flex flex-wrap items-center gap-x-4 gap-y-2 mt-3 text-xs">
                    @foreach($deliveryZoneGroups as $prefix => $zones)
                        @php $zone = $zones->first(); @endphp
                        <div class="flex items-center mt-3 md:mt-0 md:w-[157px] w-[355px] items-start gap-1 overflow-visible">
                            <span class="w-6 h-6 rounded flex-none mt-[2px]" style="background:{{ $zone['color'] }}"></span>
                            <div class="leading-[1.1]">
                                <span class="text-[13px] mb-1">{{ $zone['delivery_price'] }} UAH · {{ $zone['delivery_time_min'] }}-{{ $zone['delivery_time_max'] }} хв</span>
                                <span class="text-[10px] text-[#929292]">безкоштовно від {{ $zone['free_delivery_from'] }} UAH</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @if(false)
            <!-- 3) Регионы (третьими на планшете, под картой; на lg встанут под заголовками слева) -->
                <section id="delivery-regions" class="order-3 lg:col-span-1 self-end lg:mt-0 w-[446px]">

                    <h3 class="xl:mt-10 text-2xl font-bold">{{page_field('delivery', 'delivery_by_city','') }}</h3>
                    <div class="mt-4 grid grid-cols-1  sm:grid-cols-2 gap-x-12 text-base text-[#929292] leading-8">
                        {!!page_field('delivery', 'delivery_region','') !!}

                    </div>
                </section>
            @endif
        </div>

        <section class="mx-auto w-full desk:w-[1198px]  xl:mt-[80px] mt-[40px]">
            {{-- === СПОСОБЫ ОПЛАТЫ === --}}
            <h3 class="text-3xl md:text-4xl xl:text-[40px] text-[#19191A] font-bold mb-8">{{page_field('delivery', 'delivery_payment','') }}</h3>

            <div class="grid xl:grid-cols-3 grid-cols-1 xl:gap-6 gap-4">
                {{-- Наличными --}}
                <div class="bg-white rounded-2 shadow p-3 flex flex-col items-start xl:h-[140px]">

                    <div class="flex justify-between  w-full">
                        <h5 class="font-semibold text-lg text-[#666666] mb-1">{{page_field('delivery', 'delivery_cash','') }}</h5>
                        <img src="/images/svg/pay-cash.svg" alt="" class="w-6 h-6 shrink-0">
                    </div>
                    <p class="text-[#9E9E9E] leading-4 text-sm  mt-3">
                        {{page_field('delivery', 'delivery_payincash','') }}
                    </p>


                </div>

                {{-- Банковской картой онлайн --}}
                <div class="bg-white rounded-2 shadow p-3 flex flex-col items-start xl:h-[140px] ">

                    <div class="flex justify-between w-full">
                        <h5 class="font-semibold text-lg text-[#666666] mb-1">{{page_field('delivery', 'delivery_By_bank_card_online','') }}</h5>
                        <img src="/images/svg/pay-card-online.svg" alt="" class="w-6 h-6 shrink-0">
                    </div>

                    <p class="text-[#9E9E9E] leading-4 text-sm  mt-3">
                        {{page_field('delivery', 'delivery_When_placing','') }}
                    </p>

                </div>

                {{-- Банковской картой при получении --}}
                <div class="bg-white rounded-2 shadow p-3 flex flex-col items-start xl:h-[140px] ">

                    <div class="flex justify-between  w-full">
                        <h5 class="font-semibold text-lg text-[#666666] leading-[100%] mb-1">{{page_field('delivery', 'delivery_By_bank_card','') }}</h5>
                        <img src="/images/svg/pay-card-pos.svg" alt="" class="w-6 h-6 shrink-0">
                    </div>
                    <p class="text-[#9E9E9E] leading-4 text-sm mt-3">
                        {{page_field('delivery', 'delivery_Payforyour_order_by_bank','') }}
                    </p>


                </div>
            </div>
        </section>


        <section class="mx-auto w-full desk:w-[1198px]  mt-12">
            {{-- === КАК ПОЛУЧИТЬ СВОЙ ЗАКАЗ === --}}
            <h3 class="text-3xl md:text-4xl xl:text-[40px] text-[#19191A] font-bold mb-8"> {{page_field('delivery', 'delivery_Howtoreceive','') }}</h3>

            <div class="grid xl:grid-cols-3 grid-cols-1 xl:gap-6 gap-4">
                {{-- Доставка --}}
                <div class="bg-white rounded-2 shadow p-3 flex flex-col items-start xl:h-[140px] ">

                    <div class="flex  justify-between  w-full">
                        <h5 class="font-semibold text-lg text-[#666666] mb-1">{{page_field('delivery', 'Delivery_Delivery','') }}</h5>
                        <img src="/images/svg/delivery.svg" alt="" class="w-6 h-6 shrink-0">
                    </div>
                    <p class="text-[#9E9E9E] leading-4 text-sm  mt-3">
                        {{page_field('delivery', 'Delivery_Order_in_any_convenient','') }}
                    </p>


                </div>

                {{-- Забрать из ресторана --}}
                <div class="bg-white rounded-2 shadow p-3 flex flex-col items-start  xl:h-[140px] ">

                    <div class="flex justify-between w-full ">
                        <h5 class="font-semibold text-lg text-[#666666] mb-1">{{page_field('delivery', 'Delivery_Pick_up_from','') }}</h5>
                        <img src="/images/svg/pickup.svg" alt="" class="w-6 h-6 shrink-0">
                    </div>
                    <p class="text-[#9E9E9E] leading-4 text-sm  mt-3">
                        {{page_field('delivery', 'Delivery_Pick_up_your_order_at_the_selected','') }}
                    </p>


                </div>

                {{-- Доставка к определенному времени --}}
                <div class="bg-white rounded-2 shadow p-3 flex flex-col items-start  xl:h-[140px] ">

                    <div class="flex justify-between w-full ">
                        <h5 class="font-semibold text-lg text-[#666666] leading-[100%] mb-1">{{page_field('delivery', 'Delivery_at_a_specific_time','') }} </h5>
                        <img src="/images/svg/time.svg" alt="" class="w-6 h-6 shrink-0">
                    </div>
                    <p class="text-[#9E9E9E] leading-4 text-sm  mt-3">
                        {{page_field('delivery', 'Delivery_Choose_by_a_specific_time','') }}
                    </p>


                </div>
            </div>
        </section>

        <section class="mx-auto w-full desk:w-[1198px] mt-12">
            {{-- === ПОВЕРНЕННЯ ТА ВІДШКОДУВАННЯ === --}}
            <h3 class="text-3xl md:text-4xl xl:text-[40px] text-[#19191A] font-bold mb-8">
                {{ page_field('delivery', 'delivery_return', '') }}
            </h3>
            <div class="bg-white rounded-2 shadow p-4 md:p-6">
                <div class="prose max-w-none text-[#666666]
                            prose-p:mb-3
                            prose-ul:list-disc prose-ul:pl-6
                            prose-ol:list-decimal prose-ol:pl-6
                            prose-li:mb-1
                            prose-strong:text-[#19191A] prose-strong:font-semibold">

                    {!! page_field('delivery', 'delivery_return_text', '') !!}
                </div>
            </div>
        </section>

    </div>
@endsection

@push('scripts')
    {{-- jQuery, т.к. твой map-cart.js его использует --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    @php
        $mapLocationForJs = [
            'lat' => data_get($headerLocation ?? null, 'lat'),
            'lng' => data_get($headerLocation ?? null, 'lng'),
            'googleMapLink' => (string) data_get($headerLocation ?? null, 'google_map_link', ''),
            'svgIconUrl' => $headerLocation?->svgImage?->public_url,
        ];
    @endphp
    <script>
        // Заглушка: Google зовёт window.initMap — мы просто ставим флаг.
        // Когда «наш» initMap загрузится — он сам запустится.
        window.__gmapsLoaded = false;
        window.initMap = function () {
            window.__gmapsLoaded = true;
            if (window.__realInitMap) window.__realInitMap();
        };

        window.MAP_LOCATION = {!! \Illuminate\Support\Js::from($mapLocationForJs) !!};

        // Передаем данные зон доставки из базы данных в JavaScript
        // Ключи — префиксы зон (Green, Blue, Red, Brown), как ожидает map-cart.js
        window.DELIVERY_ZONES = @json($deliveryZonesForJs);
        // Debug logging removed for production
    </script>
    {{-- твой JS с полигонами/логикой --}}
    @vite(['resources/js/map-cart.js'])

    {{-- Google Maps + Places + Geometry (callback обязательный) --}}
    <script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.key') }}&libraries=places,geometry&callback=initMap" defer></script>
    <script>
        function updatePriceBanner(area) {
            const el = $('#price-banner');
            if (!el.length) return; // на всякий случай
            if (!area) {
                el.text('На жаль, ваша адреса поза нашою зоною доставки.').css({ color: '#b91c1c' });
                return;
            }
            el.text(
                `ВАРТІСТЬ ДОСТАВКИ ${area.price} UAH. ЧАС ДОСТАВКИ ВІД ${area.time[0]} ДО ${area.time[1]} ХВ. ` +
                `БЕЗКОШТОВНА ДОСТАВКА ПРИ ЗАМОВЛЕННІ ВІД ${area.free} UAH`
            ).css({ color: '#FF7500' });
        }
    </script>
@endpush
