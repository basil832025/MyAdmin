@php
    $locale = app()->getLocale();
    $selectedLocationId = old('pickup_location_id', $locations->first()->id ?? null);
@endphp

<div
    class="bg-white rounded-xl shadow-[0_2px_10px_rgba(0,0,0,.08)] pt-3 pr-4 pb-3 pl-4 space-y-4"
    x-show="typeof method !== 'undefined' && method === 'pickup'"
    x-cloak
    x-data="{
        openMap() {
            const input = document.querySelector('input[name=pickup_location_id]:checked');
            if (!input) return;

            const googleLink = input.dataset.googleLink;
            const lat = input.dataset.lat;
            const lng = input.dataset.lng;
            const address = input.dataset.address;

            let url = null;

            // 1) Приоритет — готовая ссылка из админки
            if (googleLink) {
                url = googleLink;
            } else if (lat && lng) {
                // 2) Если ссылки нет — собираем URL по координатам
                url = `https://www.google.com/maps?q=${lat},${lng}`;
            } else if (address) {
                // 3) Фоллбек — поиск по адресу
                url = `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(address)}`;
            }

            if (url) {
                window.open(url, '_blank');
            }
        }
    }"
>
    <div class="flex items-center justify-between">
        <div class="checkout-section-title">
            {{ st('cart.pickup.title', 'Адреси ресторанів') }}
        </div>

        <button type="button"
                class="inline-flex items-center gap-1 text-[13px] leading-[18px] text-[#FF7500] hover:underline"
                @click="openMap()">
            {{-- иконка маркера --}}
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                 xmlns="http://www.w3.org/2000/svg">
                <path d="M19 9C19 8.08075 18.8189 7.1705 18.4672 6.32122C18.1154 5.47194 17.5998 4.70026 16.9497 4.05025C16.2997 3.40024 15.5281 2.88463 14.6788 2.53284C13.8295 2.18106 12.9193 2 12 2C11.0807 2 10.1705 2.18106 9.32122 2.53284C8.47194 2.88463 7.70026 3.40024 7.05025 4.05025C6.40024 4.70026 5.88463 5.47194 5.53284 6.32122C5.18106 7.1705 5 8.08075 5 9C5 10.387 5.409 11.677 6.105 12.765H6.097L12 22L17.903 12.765H17.896C18.6169 11.6416 19.0001 10.3348 19 9ZM12 12C11.2044 12 10.4413 11.6839 9.87868 11.1213C9.31607 10.5587 9 9.79565 9 9C9 8.20435 9.31607 7.44129 9.87868 6.87868C10.4413 6.31607 11.2044 6 12 6C12.7956 6 13.5587 6.31607 14.1213 6.87868C14.6839 7.44129 15 8.20435 15 9C15 9.79565 14.6839 10.5587 14.1213 11.1213C13.5587 11.6839 12.7956 12 12 12Z"
                      fill="#FF7500"/>
            </svg>

            <span>
                {{ st('cart.pickup.show_on_map', 'Показати на карті') }}
            </span>
        </button>
    </div>

    <div class="space-y-3 text-[14px] leading-[20px] text-[#272828]">
        @forelse($locations as $loc)
            @php
                // Город
                $cityData = $loc->city;
                if (is_array($cityData)) {
                    $city = $cityData[$locale] ?? reset($cityData);
                } else {
                    $city = $cityData;
                }

                // Адрес
                $addrData = $loc->address;
                if (is_array($addrData)) {
                    $addr = $addrData[$locale] ?? reset($addrData);
                } else {
                    $addr = $addrData;
                }

                $parts = [];
                if (is_string($addr) && $addr !== '')   $parts[] = $addr;
                $line = implode(', ', $parts);

                // График работы из schedule
                $scheduleStr = null;
                $scheduleRaw = $loc->schedule;

                if (is_array($scheduleRaw)) {
                    $channel = null;

                    foreach ($scheduleRaw as $item) {
                        $slug = trim($item['slug'] ?? ($item['data']['slug'] ?? ''));
                        if ($slug === 'pickup') {
                            $channel = $item;
                            break;
                        }
                    }

                    if (!$channel && isset($scheduleRaw[0])) {
                        $channel = $scheduleRaw[0];
                    }

                    if ($channel) {
                        $timeNode = $channel['time'] ?? ($channel['data']['time'] ?? null);

                        if (is_array($timeNode)) {
                            $langNode = $timeNode[$locale] ?? reset($timeNode);

                            if (is_array($langNode)) {
                                $scheduleStr = $langNode[$locale] ?? reset($langNode);
                            } elseif (is_string($langNode)) {
                                $scheduleStr = $langNode;
                            }
                        } elseif (is_string($timeNode)) {
                            $scheduleStr = $timeNode;
                        }
                    }
                }

                if (!is_string($scheduleStr) || $scheduleStr === '') {
                    $scheduleStr = null;
                }
            @endphp

            <label class="flex items-start gap-2 cursor-pointer">
                <input
                    type="radio"
                    name="pickup_location_id"
                    value="{{ $loc->id }}"
                    class="tp-radio mt-[3px]"
                    @checked($selectedLocationId == $loc->id)
                data-lat="{{ $loc->lat }}"
                data-lng="{{ $loc->lng }}"
                data-address="{{ $line }}"
                data-google-link="{{ $loc->google_map_link }}"
                >
                <span>
                    <span class="block">
                        {{ $line ?: st('cart.pickup.fallback', 'Точка самовивозу') . ' #' . $loc->id }}
                    </span>

                    @if($scheduleStr)
                        <span class="block text-[12px] leading-[16px] text-[#9CA3AF]">
                            {{ $scheduleStr }}
                        </span>
                    @endif
                </span>
            </label>
        @empty
            <div class="text-sm text-gray-500">
                {{ st('cart.pickup.empty', 'Поки немає доступних ресторанів для самовивозу.') }}
            </div>
        @endforelse
    </div>
</div>
