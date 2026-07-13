@extends(front_view('layouts.app'))

@include(front_view('partials.seo.page'), ['page' => $page, 'defaultTitle' => 'Наші ресторани'])

@section('content')
    @php
        $pickup = ['time' => 'з 11:30 до 22:30', 'title' => 'Приймаємо замовлення на самовивіз'];
        $delivery = ['time' => 'з 11:45 до 22:30', 'title' => 'Доставляємо замовлення'];

        if (!empty($headerSchedule)) {
            foreach ($headerSchedule as $schedule) {
                if (trim((string) ($schedule['slug'] ?? '')) === 'delivery') {
                    $delivery['time'] = (string) ($schedule['time'] ?? $delivery['time']);
                    $delivery['title'] = (string) ($schedule['title'] ?? $delivery['title']);
                }

                if (trim((string) ($schedule['slug'] ?? '')) === 'pickup') {
                    $pickup['time'] = (string) ($schedule['time'] ?? $pickup['time']);
                    $pickup['title'] = (string) ($schedule['title'] ?? $pickup['title']);
                }
            }
        }

        $phones = collect($headerPhones ?? [])->take(4)->values();
        $phoneColumns = $phones->chunk(2);

        $emailRows = collect((array) data_get($headerLocation, 'emails', []))
            ->filter(fn ($row) => (bool) data_get($row, 'is_active', true))
            ->values();

        $email = (string) ($emailRows->first()['email'] ?? '');
        if ($email === '') {
            $email = (string) (data_get($headerLocation, 'email')
                ?? data_get($headerLocation, 'contact_email')
                ?? '');
        }
    @endphp

    <div class="mx-auto desk:w-[1198px] p-4 max-w-full">
        <nav class="text-sm text-gray-500 my-4">
            <a href="{{ route('home') }}" class="hover:text-gray-700">{{ st('menu.home','Головна') }}</a>
            <span class="mx-2">→</span>
            <span class="text-gray-700">{{ $page->title }}</span>
        </nav>

        <h2 class="inline-block mb-10 font-intro text-[40px] md:text-[64px] leading-[100%] md:leading-[64px] font-bold text-[#19191A] border-b-2 border-[#FF7500]">
            {{ $page->title }}
        </h2>

        <div class="grid grid-cols-1 items-start gap-4 lg:gap-x-10 lg:grid-cols-[minmax(0,446px)_minmax(0,1fr)]">
            <section class="order-2 lg:order-none lg:col-start-1 lg:row-start-1 min-w-0">
                <div class="bg-white rounded-2xl shadow-xl p-4">
                    <div class="flex items-center gap-3 justify-between">
                        <div class="min-w-0">
                            <div class="font-semibold text-lg truncate">{{ $headerLocation->title }}</div>
                            <div class="text-[10px] text-[#9E9E9E] truncate">{{ $headerLocation->address }}</div>
                            @if($email !== '')
                                <a href="mailto:{{ $email }}" class="mt-1 block text-[10px] text-[#9E9E9E] hover:text-[#9E9E9E]">
                                    {{ $email }}
                                </a>
                            @endif
                        </div>
                        <img src="/images/logo_mob.svg" class="w-10 h-10" alt="logo">
                    </div>

                    @if($phones->isNotEmpty())
                        <div class="mt-3 inline-flex items-start text-[10px] text-[#9E9E9E]" style="column-gap: 1cm;">
                            @foreach($phoneColumns as $column)
                                <div class="space-y-2">
                                    @foreach($column as $phone)
                                        <a href="tel:{{ $phone['tel'] }}" class="block hover:text-[#9E9E9E]">
                                            {{ $phone['display'] }}
                                        </a>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="mt-3 grid grid-cols-2 gap-3 text-sm">
                        <div class="rounded-[1px] border border-[#F9FAFB] p-3 shadow-[0_2px_8px_rgba(0,0,0,0.05)]">
                            <div class="text-[#9E9E9E] text-xs text-center">{{ $pickup['title'] }}:</div>
                            <div class="font-semibold mt-1 text-sm text-[#19191A] text-center">{{ $pickup['time'] }}</div>
                        </div>
                        <div class="rounded-[1px] border border-[#F9FAFB] p-3 shadow-[0_2px_8px_rgba(0,0,0,0.05)] flex flex-col h-full">
                            <div class="text-[#9E9E9E] text-xs text-center">{{ $delivery['title'] }}:</div>
                            <div class="font-semibold mt-auto text-sm text-[#19191A] text-center">{{ $delivery['time'] }}</div>
                        </div>
                    </div>
                </div>

                <input id="address-input" type="text" class="hidden" aria-hidden="true" tabindex="-1">
                <div id="price-banner" class="hidden"></div>
            </section>

            <section class="order-1 lg:order-none lg:col-start-2 lg:row-start-1 lg:col-span-1">
                <div class="relative">
                    <div id="map" class="md:h-[560px] h-[216px] w-full rounded-xl overflow-hidden"></div>
                </div>
            </section>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    @php
        $mapLocationForJs = [
            'lat' => data_get($headerLocation ?? null, 'lat'),
            'lng' => data_get($headerLocation ?? null, 'lng'),
            'googleMapLink' => (string) data_get($headerLocation ?? null, 'google_map_link', ''),
            'svgIconUrl' => $headerLocation?->svgImage?->public_url,
        ];

        $zones = \App\Models\DeliveryZone::where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->keyBy('name')
            ->map(function($zone) {
                return [
                    'name' => $zone->name,
                    'color' => $zone->color,
                    'delivery_price' => (float) $zone->delivery_price,
                    'delivery_time_min' => (int) $zone->delivery_time_min,
                    'delivery_time_max' => (int) $zone->delivery_time_max,
                    'free_delivery_from' => (float) $zone->free_delivery_from,
                ];
            });
    @endphp
    <script>
        window.__gmapsLoaded = false;
        window.initMap = function () {
            window.__gmapsLoaded = true;
            if (window.__realInitMap) window.__realInitMap();
        };

        window.MAP_LOCATION = {!! \Illuminate\Support\Js::from($mapLocationForJs) !!};

        window.DELIVERY_ZONES = @json($zones);
    </script>
    @vite(['resources/js/map-cart.js'])
    <script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.key') }}&libraries=places,geometry&callback=initMap" defer></script>
@endpush
