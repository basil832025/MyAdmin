<div
    class="bg-white rounded-xl shadow-[0_2px_10px_rgba(0,0,0,.08)] pt-3 pr-4 pb-3 pl-4 space-y-6"
    x-data="{
        useNew: {{ $useNewInitial ? 'true' : 'false' }},

        // адрес выбран?
        selectedId: null,
        selectedLine: '',
        selectedCity: '',
        showList: true,
        showAll: false,

        parseJson(v) {
            if (!v) return '';
            try { return JSON.parse(v); } catch (e) { return v; }
        },

        init() {
            // НИЧЕГО не выбираем по умолчанию.
            // Если нужно когда-нибудь поддержать восстановление из сессии — можно тут включить логику.
            // Сейчас строго: нет выбранного адреса => показываем список.
            this.showList = true;
            this.selectedId = null;
        },

        selectAddress(el) {
            if (!el) return;
            // защита от рекурсии: если мы сами программно триггерим change,
            // не заходим повторно в selectAddress
            if (el.__selecting) return;
            this.useNew = false;

            this.selectedId = el.value || null;
            this.selectedLine = this.parseJson(el.dataset.line) || '';
            this.selectedCity = this.parseJson(el.dataset.city) || '';

            // после выбора показываем карточку выбранного адреса
            this.showList = false;

            // важно: пусть отработают слушатели пересчёта доставки (document change)
            el.__selecting = true;
            el.dispatchEvent(new Event('change', { bubbles: true }));
            // снимаем флаг на следующем тике
            setTimeout(() => { el.__selecting = false; }, 0);
        },

        openList() {
            this.showList = true;
        },

        clearSelected() {

const checked = document.querySelector('input[name=selected_address_id]:checked');
if (checked) checked.checked = false;

this.selectedId = null;
this.selectedLine = '';
this.selectedCity = '';
this.showList = true;
}
}"
x-show="typeof method !== 'undefined' && method === 'delivery'"
x-cloak
>
    <div class="flex items-center justify-between">
        <div class="checkout-section-title">
            {{ st('cart.delivery.title', 'Адрес доставки') }}
        </div>

        <button
            type="button"
            class="text-[16px] font-medium text-[#FF7500] hover:underline"
            x-show="!useNew && selectedId && !showList"
            x-cloak
            @click="
        showList = true;

        const checked = document.querySelector('input[name=selected_address_id]:checked');
        if (checked) checked.checked = false;

        selectedId = null;
    "
        >
            {{ st('cart.address.change', 'Змінити    ') }}
        </button>

    </div>


    {{-- Сохранённые адреса --}}

    @if($client && $addresses->count())

        {{-- 1) Карточка выбранного адреса  --}}
        <div
            x-show="selectedId && !useNew && !showList"
            x-transition
            x-cloak
            class="border border-[#E5E7EB] rounded-[12px] px-4 py-3"
        >
            <div class="text-[16px] font-medium leading-[20px] text-[#272828]" x-text="selectedLine"></div>
            <div class="text-[14px] text-[#9CA3AF] mt-1" x-text="selectedCity"></div>
        </div>
        @if($addresses->count() > 3)
            <button
                type="button"
                class="text-[14px] font-semibold text-[#FF7500] hover:underline"
                x-show="!useNew && (showList || !selectedId)"
                x-cloak
                @click="showAll = !showAll"
                x-text="showAll ? '{{ st('cart.address.hide_all', 'Сховати адреси') }}' : '{{ st('cart.address.show_all', 'Показати всі адреси') }}'"
            ></button>
        @endif

        <div class="space-y-4" x-show="!useNew && (showList || !selectedId)" x-cloak data-field-wrap="selected_address_id">
            @foreach($addresses as $i => $addr)
                @php

                    $fullLine = trim(
                        ($addr->street
                            ? st('address.parts.street_prefix', 'вулиця').' '.$addr->street
                            : ''
                        ) .
                        ($addr->house
                            ? ', '.st('address.parts.house_short', 'д.').$addr->house
                            : ''
                        ) .
                        ($addr->apartment
                            ? ', '.st('address.parts.apartment_short', 'кв. ').$addr->apartment
                            : ''
                        )
                    );

                    $typeLabel = null;
                    if (!empty($addr->type)) {
                        $map = [
                            'home'    => st('address.type.home', 'Дім'),
                            'work'    => st('address.type.work', 'Робота'),
                            'friends' => st('address.type.friends', 'Друзі'),
                        ];
                        $typeLabel = $map[$addr->type] ?? $addr->type;
                    }
                         $lineForJs = trim($fullLine . ($typeLabel ? " ({$typeLabel})" : ''));
                    $cityForJs = !empty($addr->city) ? $addr->city : '';
                @endphp

                <label
                    class="flex items-start gap-2 cursor-pointer"
                    @click="useNew = false"
                    x-show="showAll || {{ $i }} < 3"
                    x-cloak
                >
                    <input type="radio"
                           name="selected_address_id"
                           value="{{ $addr->id }}"
                           class="tp-radio mt-[3px]"
                           data-lat="{{ $addr->latitude ?? '' }}"
                           data-lng="{{ $addr->longitude ?? '' }}"
                            data-street='@json((string) ($addr->street ?? ""))'
                            data-house='@json((string) ($addr->house ?? ""))'
                            data-line='@json((string) $lineForJs)'
                            data-city='@json((string) $cityForJs)'
                            {{-- ВАЖНО: НЕ ставим checked по умолчанию --}}
                            @change="selectAddress($event.target)"
                    >


                    <span class="leading-5">
                        <span class="text-[16px] leading-[22px] text-[#272828]">
                            {{ $fullLine }}
                            @if($typeLabel) ({{ $typeLabel }}) @endif
                        </span><br>
                        @if(!empty($addr->city))
                            <span class="text-xs text-[#9CA3AF]">{{ $addr->city }}</span>
                        @endif
                    </span>
                </label>
            @endforeach
        </div>

        <input
            id="selected_address_id"
            type="hidden"
            x-model="selectedId"
            data-required
            data-required-if="shipping_method=delivery;use_new_address=0"
        >
        <p class="tp-error hidden mt-1" data-error-for="selected_address_id">
            {{ st('form.required','Це обов’язкове поле') }}
        </p>

        @error('selected_address_id')
        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
        @enderror

        {{-- Управление новым адресом --}}
        {{-- Кнопка открытия формы нового адреса (только когда форма закрыта) --}}
        <div x-show="!useNew" x-cloak>
            <button
                type="button"
                @click="
  useNew = true;
  showList = false;

  const checked = document.querySelector('input[name=selected_address_id]:checked');
  if (checked) {
    checked.checked = false;
    checked.dispatchEvent(new Event('change', { bubbles: true }));
  } else {
    // чтобы точно сработал autosave при переключении на новый адрес
    document.querySelector('input[name=use_new_address]')?.dispatchEvent(new Event('change', { bubbles: true }));
  }
"
                class="flex items-center gap-2 text-[#EF4444] font-semibold text-[14px]
               hover:text-[#DC2626] transition"
            >
                <span class="text-[20px] leading-none">+</span>
                {{ st('cart.address.add_new', 'Додати нову адресу') }}
            </button>
        </div>


    @endif

    {{-- Флаг для бэка: использовать новый адрес или нет --}}
    <input type="hidden" name="shipping_method" :value="method">
    <input type="hidden" name="use_new_address" :value="useNew ? 1 : 0">


    {{-- Поля нового адреса --}}
    <div class="space-y-4" x-show="useNew" x-cloak
         x-data="{ isPrivate: @json((bool) old('addr.is_private_house', !empty($sessionData['addr_is_private_house']))) }"
    >
        {{-- Скрытое поле для города (заполняется автоматически из Google Autocomplete) --}}
        <input type="hidden"
               id="checkout-address-city"
               name="addr[city]"
               value="{{ old('addr.city', $sessionData['addr_city'] ?? '') }}"
        >
        <input type="hidden"
               id="checkout-address-formatted"
               name="addr[formatted_address]"
               value="{{ old('addr.formatted_address', $sessionData['addr_formatted_address'] ?? '') }}"
        >
        <input type="hidden"
               id="checkout-address-place-id"
               name="addr[street_place_id]"
               value="{{ old('addr.street_place_id', $sessionData['addr_street_place_id'] ?? '') }}"
        >

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div class="input-required"
                 x-data="{ focused:false }"
                 data-field-wrap="addr[street]"
            >
                <div class="tp-float-wrap">
                    <input
                        id="checkout-address-street"
                        name="addr[street]"
                        class="tp-float-input"
                        placeholder=" "
                        :disabled="!useNew || (typeof method !== 'undefined' && method === 'pickup')"
                        data-required
                        data-required-if="shipping_method=delivery;use_new_address=1"
                        @focus="focused=true"
                        @blur="focused=false"
                        value="{{ old('addr.street', $sessionData['addr_street'] ?? '') }}"
                    >
                    <label for="checkout-address-street" class="tp-float-label">
                        {{ st('address.form.street', 'Вулиця') }}<span class="tp-asterisk">*</span>
                    </label>
                </div>

                <p class="tp-error hidden" data-error-for="addr[street]">
                    {{ st('form.required','Це обов’язкове поле') }}
                </p>

                @error('addr.street')
                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>


            <div class="input-required"
                 x-data="{ focused:false }"
                 data-field-wrap="addr[house]"
            >
                {{-- ВАЖНО: label внутри рамки --}}
                <div class="tp-float-wrap"
                     :class="{
            'is-focused': focused,
         }"
                >
                    <input
                        id="checkout-address-house"
                        name="addr[house]"
                        class="tp-float-input"
                        placeholder=" " {{-- обязательно пробел, чтобы работал :placeholder-shown --}}
                        :disabled="!useNew || (typeof method !== 'undefined' && method === 'pickup')"
                        data-required
                        data-required-if="shipping_method=delivery;use_new_address=1"
                        @focus="focused=true"
                        @blur="focused=false"
                        value="{{ old('addr.house', $sessionData['addr_house'] ?? '') }}"
                    >

                    <label for="checkout-address-house" class="tp-float-label">
                        {{ st('address.form.house', 'Дім') }}<span class="tp-asterisk">*</span>
                    </label>
                </div>

                <p class="tp-error hidden" data-error-for="addr[house]">
                    {{ st('form.required','Це обов’язкове поле') }}
                </p>

                @error('addr.house')
                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>


          <div x-show="!isPrivate" x-cloak>
            <div class="input-required"
                 x-data="{ focused:false }"
                 data-field-wrap="addr[apartment]"
            >
                <div class="tp-float-wrap">

                    <input
                        id="checkout-address-apartment"
                        name="addr[apartment]"
                        class="tp-float-input"
                        placeholder=" "
                        :disabled="!useNew || (typeof method !== 'undefined' && method === 'pickup') || isPrivate"
                        value="{{ old('addr.apartment', $sessionData['addr_apartment'] ?? '') }}"
                    >
                    <label for="checkout-address-apartment" class="tp-float-label">
                        {{ st('address.form.apartment', 'Квартира') }}
                    </label>
                </div>
            </div>

            </div>
            <div x-show="!isPrivate" x-cloak>
                <div class="input-required"
                     x-data="{ focused:false }"
                     data-field-wrap="addr[porch]"
                >
                    <div class="tp-float-wrap">
                        <input
                            id="checkout-address-porch"
                            name="addr[porch]"
                            class="tp-float-input"
                            placeholder=" "
                            :disabled="!useNew || (typeof method !== 'undefined' && method === 'pickup') || isPrivate"
                            value="{{ old('addr.porch', $sessionData['addr_porch'] ?? '') }}"
                        >
                        <label class="tp-float-label">
                            {{ st('address.form.porch', "Під'їзд") }}
                        </label>

                    </div>

                    <p class="tp-error hidden" data-error-for="addr[porch]">
                        {{ st('form.required','Це обов’язкове поле') }}
                    </p>

                    @error('addr.porch')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>
          <div x-show="!isPrivate" x-cloak>

            <div data-field-wrap="addr[floor]">
                <div class="tp-float-wrap">
                    <input
                        id="checkout-address-floor"
                        name="addr[floor]"
                        class="tp-float-input"
                        placeholder=" "
                        :disabled="!useNew || isPrivate"
                        value="{{ old('addr.floor', $sessionData['addr_floor'] ?? '') }}"
                    >
                    <label for="checkout-address-floor" class="tp-float-label">
                        {{ st('address.form.floor', 'Поверх') }}
                    </label>
                </div>
            </div>
          </div>


            <div x-show="!isPrivate" x-cloak>
                <div data-field-wrap="addr[intercom]">
                    <div class="tp-float-wrap">
                        <input
                            id="checkout-address-intercom"
                            name="addr[intercom]"
                            class="tp-float-input"
                            placeholder=" "
                            :disabled="!useNew || isPrivate"
                            value="{{ old('addr.intercom', $sessionData['addr_intercom'] ?? '') }}"
                        >
                        <label for="checkout-address-intercom" class="tp-float-label">
                            {{ st('address.form.intercom', 'Домофон') }}
                        </label>
                    </div>
                </div>
            </div>
        </div>

        {{--    <div data-field-wrap="addr[comment]">
                <div class="tp-float-wrap">
                    <input
                        id="checkout-address-comment"
                        name="addr[comment]"
                        class="tp-float-input"
                        placeholder=" "
                        :disabled="!useNew"
                        value="{{ old('addr.comment', $sessionData['addr_comment'] ?? '') }}"
                    >
                    <label for="checkout-address-comment" class="tp-float-label">
                        {{ st('address.form.comment', 'Коментар для кур’єра') }}
                    </label>
                </div>
            </div> --}}


            <label class="inline-flex items-center gap-2">
                <input
                    type="checkbox"
                    class="tp-check"
                    name="addr[is_private_house]"
                    value="1"
                    :disabled="!useNew"
                    x-model="isPrivate"
                >
                <span class="text-sm text-gray-700">
            {{ st('address.form.private_house', 'Це приватний будинок') }}
        </span>
            </label>


            {{-- Тип адреса: дом / работа / друзья --}}
        <div
            class="flex flex-wrap gap-2"
            x-data="{ t: '{{ old('addr.type', $sessionData['addr_type'] ?? 'home') }}' }"
        >
            <input type="hidden" name="addr[type]" :value="t" :disabled="!useNew">

            <button type="button"
                    class="h-10 min-w-[72px] px-3 rounded-[12px] text-[14px]"
                    :class="t === 'home'
                        ? 'bg-[#FF7500] text-white'
                        : 'bg-[#F3F4F6] text-[#272828]'"
                    @click="t = 'home'">
                {{ st('address.type.home', 'Дім') }}
            </button>

            <button type="button"
                    class="h-10 min-w-[72px] px-3 rounded-[12px] text-[14px]"
                    :class="t === 'work'
                        ? 'bg-[#FF7500] text-white'
                        : 'bg-[#F3F4F6] text-[#272828]'"
                    @click="t = 'work'">
                {{ st('address.type.work', 'Робота') }}
            </button>

            <button type="button"
                    class="h-10 min-w-[72px] px-3 rounded-[12px] text-[14px]"
                    :class="t === 'friends'
                        ? 'bg-[#FF7500] text-white'
                        : 'bg-[#F3F4F6] text-[#272828]'"
                    @click="t = 'friends'">
                {{ st('address.type.friends', 'Друзі') }}
            </button>
        </div>
        @if($client && $addresses->count())
            <div class="pt-2" x-show="useNew" x-cloak>
                <button type="button"
                        class="text-[#EF4444] text-sm font-medium"
                        @click="useNew=false; resetNewAddress($el)">
                    {{ st('cart.address.do_not_use_new', 'Не використовувати нову адресу') }}
                </button>
            </div>
        @endif


    </div>
</div>
