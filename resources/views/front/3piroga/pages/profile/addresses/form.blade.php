@extends(front_view('layouts.app'))

@section('title', $address->exists ? st('profile.addresses.edit_title', 'Редагувати адресу') : st('profile.addresses.create_title', 'Додати адресу'))

@section('content')
    @php
        $locale = app()->getLocale();
        $isLocalized = in_array($locale, ['ru', 'en'], true);
        $storeUrl = $isLocalized ? route('localized.profile.addresses.store', ['locale' => $locale]) : route('profile.addresses.store');
        $formUrl = $address->exists
            ? ($isLocalized
                ? route('localized.profile.addresses.update', ['locale' => $locale, 'address' => $address])
                : route('profile.addresses.update', ['address' => $address]))
            : $storeUrl;
        $cancelUrl = $isLocalized ? route('localized.profile.addresses.index', ['locale' => $locale]) : route('profile.addresses.index');
    @endphp
    <div class="mx-auto desk:w-[1200px] px-4 md:px-6 desk:px-0">
        <h1 class="sr-only md:not-sr-only md:text-[28px] md:leading-8 font-bold text-[#19191A] md:mb-4">
            {{ $address->exists ? st('profile.addresses.edit_title', 'Редагувати адресу') : st('profile.addresses.create_title', 'Додати адресу') }}
        </h1>

        <div class="xl:grid xl:grid-cols-[240px,1fr] md:gap-6">
            {{-- Левое меню (desktop) --}}
            <aside class="hidden xl:block">
                @include(front_view('pages.menu.profile-menu'))
            </aside>

            {{-- Контент --}}
            <main>
                <div class="bg-white rounded-[6px] ring-1 ring-black/10 p-4 md:p-6">
                    <form action="{{ $formUrl }}"
                          method="POST">
                        @csrf
                        @if($address->exists)
                            @method('PUT')
                        @endif
                        <input type="hidden"
                               id="profile-address-id"
                               name="address_id"
                               value="{{ $address->exists ? $address->id : '' }}">

                        <input type="hidden"
                               id="profile-address-google-selected"
                               value="{{ $address->exists && ($address->latitude || $address->longitude) ? '1' : '0' }}">
                        <div class="space-y-4">
                            {{-- Город --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    {{ st('address.form.city', 'Місто') }}
                                </label>
                                <input type="text"
                                       name="city"
                                       value="{{ old('city', $address->city) }}"
                                       class="w-full h-[46px] rounded-[6px] border border-[#E5E7EB] px-4
                                              text-[16px] leading-[22px]
                                              focus:outline-none focus:ring-2 focus:ring-[#FF7500]/20 focus:border-[#FF7500]
                                              transition">
                                @error('city')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                {{-- Улица --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        {{ st('address.form.street', 'Вулиця') }} <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text"
                                           id="profile-address-street"
                                           name="street"
                                           required
                                           value="{{ old('street', $address->street) }}"
                                           class="w-full h-[46px] rounded-[6px] border border-[#E5E7EB] px-4
                                                  text-[16px] leading-[22px]
                                                  focus:outline-none focus:ring-2 focus:ring-[#FF7500]/20 focus:border-[#FF7500]
                                                  transition">
                                    @error('street')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Дом --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        {{ st('address.form.house', 'Дім') }} <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text"
                                           id="profile-address-house"
                                           name="house"
                                           required
                                           value="{{ old('house', $address->house) }}"
                                           class="w-full h-[46px] rounded-[6px] border border-[#E5E7EB] px-4
                                                  text-[16px] leading-[22px]
                                                  focus:outline-none focus:ring-2 focus:ring-[#FF7500]/20 focus:border-[#FF7500]
                                                  transition">
                                    @error('house')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Квартира --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        {{ st('address.form.apartment', 'Квартира') }}
                                    </label>
                                    <input type="text"
                                           name="apartment"
                                           value="{{ old('apartment', $address->apartment) }}"
                                           class="w-full h-[46px] rounded-[6px] border border-[#E5E7EB] px-4
                                                  text-[16px] leading-[22px]
                                                  focus:outline-none focus:ring-2 focus:ring-[#FF7500]/20 focus:border-[#FF7500]
                                                  transition">
                                    @error('apartment')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Домофон --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        {{ st('address.form.intercom', 'Домофон') }}
                                    </label>
                                    <input type="text"
                                           name="intercom"
                                           value="{{ old('intercom', $address->intercom) }}"
                                           class="w-full h-[46px] rounded-[6px] border border-[#E5E7EB] px-4
                                                  text-[16px] leading-[22px]
                                                  focus:outline-none focus:ring-2 focus:ring-[#FF7500]/20 focus:border-[#FF7500]
                                                  transition">
                                    @error('intercom')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Этаж --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        {{ st('address.form.floor', 'Поверх') }}
                                    </label>
                                    <input type="number"
                                           name="floor"
                                           value="{{ old('floor', $address->floor) }}"
                                           class="w-full h-[46px] rounded-[6px] border border-[#E5E7EB] px-4
                                                  text-[16px] leading-[22px]
                                                  focus:outline-none focus:ring-2 focus:ring-[#FF7500]/20 focus:border-[#FF7500]
                                                  transition">
                                    @error('floor')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Подъезд --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        {{ st('address.form.porch', "Під'їзд") }}
                                    </label>
                                    <input type="text"
                                           name="entrance"
                                           value="{{ old('entrance', $address->entrance) }}"
                                           class="w-full h-[46px] rounded-[6px] border border-[#E5E7EB] px-4
                                                  text-[16px] leading-[22px]
                                                  focus:outline-none focus:ring-2 focus:ring-[#FF7500]/20 focus:border-[#FF7500]
                                                  transition">
                                    @error('entrance')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            {{-- Скрытые поля для координат и дополнительной информации Google Places --}}
                            <input type="hidden"
                                   id="profile-address-lat"
                                   name="latitude"
                                   value="{{ old('latitude', $address->latitude) }}">
                            <input type="hidden"
                                   id="profile-address-lng"
                                   name="longitude"
                                   value="{{ old('longitude', $address->longitude) }}">
                            <input type="hidden"
                                   id="profile-address-place-id"
                                   name="street_place_id"
                                   value="{{ old('street_place_id', $address->street_place_id) }}">
                            <input type="hidden"
                                   id="profile-address-formatted"
                                   name="formatted_address"
                                   value="{{ old('formatted_address', $address->formatted_address) }}">

                            {{-- Тип адреса --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    {{ st('address.form.type', 'Тип адреси') }}
                                </label>
                                <select name="type"
                                        class="w-full h-[46px] rounded-[6px] border border-[#E5E7EB] px-4
                                               text-[16px] leading-[22px]
                                               focus:outline-none focus:ring-2 focus:ring-[#FF7500]/20 focus:border-[#FF7500]
                                               transition">
                                    <option value="">{{ st('address.form.type_select', 'Оберіть тип') }}</option>
                                    <option value="home" {{ old('type', $address->type) === 'home' ? 'selected' : '' }}>
                                        {{ st('address.type.home', 'Дім') }}
                                    </option>
                                    <option value="work" {{ old('type', $address->type) === 'work' ? 'selected' : '' }}>
                                        {{ st('address.type.work', 'Робота') }}
                                    </option>
                                    <option value="friends" {{ old('type', $address->type) === 'friends' ? 'selected' : '' }}>
                                        {{ st('address.type.friends', 'Друзі') }}
                                    </option>
                                </select>
                                @error('type')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Примечание --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    {{ st('address.form.note', 'Примітка') }}
                                </label>
                                <textarea name="note"
                                          rows="3"
                                          class="w-full rounded-[6px] border border-[#E5E7EB] px-4 py-2
                                                 text-[16px] leading-[22px]
                                                 focus:outline-none focus:ring-2 focus:ring-[#FF7500]/20 focus:border-[#FF7500]
                                                 transition">{{ old('note', $address->note) }}</textarea>
                                @error('note')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Частный дом --}}
                            <div>
                                <label class="inline-flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox"
                                           name="is_private_house"
                                           value="1"
                                           {{ old('is_private_house', $address->is_private_house) ? 'checked' : '' }}
                                           class="w-4 h-4 text-[#FF7500] border-gray-300 rounded
                                                  focus:ring-[#FF7500] focus:ring-2">
                                    <span class="text-sm text-gray-700">
                                        {{ st('address.form.private_house', 'Це приватний будинок') }}
                                    </span>
                                </label>
                            </div>

                            {{-- Кнопки --}}
                            <div class="flex items-center gap-4 pt-4 border-t">
                                <button type="submit"
                                        class="px-6 py-2 bg-[#FF7500] text-white rounded-lg hover:bg-orange-600 transition">
                                    {{ $address->exists ? st('profile.addresses.update', 'Оновити') : st('profile.addresses.save', 'Зберегти') }}
                                </button>
                                <a href="{{ $cancelUrl }}"
                                   class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                                    {{ st('profile.addresses.cancel', 'Скасувати') }}
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>

    @push('scripts')
    {{-- jQuery необходим для map-cart.js --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    {{-- Загружаем Google Maps API синхронно перед map-cart.js --}}
    <script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.key') }}&libraries=places,geometry"></script>
    {{-- Загружаем map-cart.js для доступа к deliveryAreas --}}
    @vite(['resources/js/map-cart.js'])
    <script>
    // Инициализация автозаполнения адреса для профиля с фильтрацией по зонам доставки
                (function() {
        function initProfileAutocomplete() {
            if (typeof window.initAddressAutocomplete === 'undefined' || typeof window.deliveryAreas === 'undefined') {
                setTimeout(initProfileAutocomplete, 200);
                return;
            }

            // Создаем скрытую карту для проверки зон доставки
            const hiddenMapDiv = document.createElement('div');
            hiddenMapDiv.id = 'hidden-map-for-profile';
            hiddenMapDiv.style.cssText = 'display: none; width: 1px; height: 1px; position: absolute; left: -9999px;';
            document.body.appendChild(hiddenMapDiv);

            let hiddenMap = null;
            let resolveAreaByLatLng = null;

            try {
                hiddenMap = new google.maps.Map(hiddenMapDiv, {
                    center: { lat: 50.4590851, lng: 30.4182548 },
                    zoom: 11,
                    disableDefaultUI: true,
                });
            } catch (e) {
                console.error('Ошибка создания скрытой карты:', e);
                return;
            }

            // Создаем полигоны зон доставки
            const deliveryAreas = window.deliveryAreas;
            if (deliveryAreas) {
                for (const key in deliveryAreas) {
                    if (!deliveryAreas[key].polygon && deliveryAreas[key].area) {
                        deliveryAreas[key].polygon = new google.maps.Polygon({
                            path: deliveryAreas[key].area,
                            geodesic: true,
                            map: null,
                        });
                    }
                }
            }

            // Создаем функцию проверки зон
            if (typeof window.resolveAreaByLatLng !== 'undefined') {
                resolveAreaByLatLng = window.resolveAreaByLatLng;
            } else if (deliveryAreas) {
                resolveAreaByLatLng = function(latLng) {
                    for (const key in deliveryAreas) {
                        if (deliveryAreas[key].polygon &&
                            google.maps.geometry.poly.containsLocation(latLng, deliveryAreas[key].polygon)) {
                            return deliveryAreas[key];
                        }
                    }
                    return null;
                };
            }

            // Используем ту же логику, что и на checkout - с фильтрацией по зонам доставки
            if (resolveAreaByLatLng && hiddenMap) {
                window.initAddressAutocomplete({
                    streetInputId: 'profile-address-street',
                    houseInputId: 'profile-address-house',
                    cityInputSelector: 'input[name="city"]',
                    kyivOnly: true, // Ограничиваем поиск только Киевом
                    filterByDeliveryZone: true, // Включаем фильтрацию по зонам доставки
                    checkDeliveryZone: resolveAreaByLatLng,
                    map: hiddenMap,
                    googleMapsKey: window.GOOGLE_MAPS_API_KEY,
                    onPlaceSelected: function(data) {
                        if (!data || !data.place || !data.place.geometry || !data.place.geometry.location) return;
                        const place = data.place;
                        const loc = place.geometry.location;

                        const latInput  = document.getElementById('profile-address-lat');
                        const lngInput  = document.getElementById('profile-address-lng');
                        const faInput   = document.getElementById('profile-address-formatted');
                        const pidInput  = document.getElementById('profile-address-place-id');

                        if (latInput && lngInput) {
                            latInput.value = loc.lat();
                            lngInput.value = loc.lng();
                        }
                        if (faInput) {
                            faInput.value = place.formatted_address || '';
                        }
                        if (pidInput) {
                            pidInput.value = place.place_id || '';
                        }
                        const selectedInput = document.getElementById('profile-address-google-selected');
                        if (selectedInput) {
                            selectedInput.value = '1';
                        }
                    },
                });
            } else {
                // Fallback: используем стандартное автозаполнение без фильтрации
                window.initAddressAutocomplete({
                    streetInputId: 'profile-address-street',
                    houseInputId: 'profile-address-house',
                    cityInputSelector: 'input[name="city"]',
                    kyivOnly: true,
                    filterByDeliveryZone: false,
                    googleMapsKey: window.GOOGLE_MAPS_API_KEY,
                    onPlaceSelected: function(data) {
                        if (!data || !data.place || !data.place.geometry || !data.place.geometry.location) return;
                        const place = data.place;
                        const loc = place.geometry.location;

                        const latInput  = document.getElementById('profile-address-lat');
                        const lngInput  = document.getElementById('profile-address-lng');
                        const faInput   = document.getElementById('profile-address-formatted');
                        const pidInput  = document.getElementById('profile-address-place-id');

                        if (latInput && lngInput) {
                            latInput.value = loc.lat();
                            lngInput.value = loc.lng();
                        }
                        if (faInput) {
                            faInput.value = place.formatted_address || '';
                        }
                        if (pidInput) {
                            pidInput.value = place.place_id || '';
                        }
                        const selectedInput = document.getElementById('profile-address-google-selected');
                        if (selectedInput) {
                            selectedInput.value = '1';
                        }
                    },
                });
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(initProfileAutocomplete, 500);
            });
        } else {
            setTimeout(initProfileAutocomplete, 500);
        }
    })();
    document.addEventListener('submit', function (e) {
        const form = e.target;

        if (!form.querySelector('#profile-address-street')) {
            return;
        }

        const addressId = document.getElementById('profile-address-id')?.value || '';
        const selected = document.getElementById('profile-address-google-selected')?.value || '0';
        const lat = document.getElementById('profile-address-lat')?.value || '';
        const lng = document.getElementById('profile-address-lng')?.value || '';

        if (addressId && selected === '1' && lat && lng) {
            e.stopImmediatePropagation();
            return true;
        }
    }, true);
    </script>
    @endpush
@endsection
