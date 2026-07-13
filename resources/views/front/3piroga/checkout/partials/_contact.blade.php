<div class="bg-white rounded-xl shadow-[0_2px_10px_rgba(0,0,0,0.08)] p-4">
    <div class="checkout-section-title mb-3 md:mb-4">{{ st('profile.kontaktni-dani', 'Контактні дані') }}</div>

    <div class="flex flex-col gap-4 @auth md:flex-col @else md:flex-row @endauth">
        {{-- ЛЕВАЯ ЧАСТЬ: поля контактов --}}
        <div class="w-full grid gap-3">

            {{-- Имя * --}}
            <div class="input-required" data-field-wrap="contact_name">
                <div class="tp-float-wrap">
                    <input
                        type="text"
                        id="contact_name"
                        name="contact_name"
                        class="tp-float-input"
                        placeholder=" "
                        value="{{ old('contact_name', $sessionData['contact_name'] ?? $client->name ?? '') }}"
                        data-required
                        data-label="{{ st('profile.name', 'Імʼя') }}"
                    >
                    <label for="contact_name" class="tp-float-label">
                        {{ st('profile.name', 'Імʼя') }}<span class="tp-asterisk">*</span>
                    </label>
                </div>

                <p class="tp-error hidden" data-error-for="contact_name">
                    {{ st('form.required','Це обов’язкове поле') }}
                </p>
            </div>

            {{-- Телефон * --}}
            <div class="input-required" data-field-wrap="contact_phone">
                <div class="tp-float-wrap">
                    <input
                        type="tel"
                        id="contact_phone"
                        name="contact_phone"
                        inputmode="numeric"
                        dir="ltr"
                        class="tp-float-input"
                        placeholder=" "
                        value="{{ old('contact_phone', $sessionData['contact_phone'] ?? $client->phone ?? '') }}"
                        data-required
                        data-label="{{ st('profile.phone', 'Телефон') }}"
                        @auth readonly @endauth
                        @guest x-init="window.applyUaPhoneMask($el)" @endguest
                    >
                    <label for="contact_phone" class="tp-float-label">
                        {{ st('profile.phone', 'Телефон') }}<span class="tp-asterisk">*</span>
                    </label>
                </div>

                <p class="tp-error hidden" data-error-for="contact_phone">
                    {{ st('form.required','Це обов’язкове поле') }}
                </p>
            </div>

            {{-- Email (необязательно) --}}
            <div data-field-wrap="contact_email">
                <div class="tp-float-wrap">
                    <input
                        type="email"
                        id="contact_email"
                        name="contact_email"
                        class="tp-float-input"
                        placeholder=" "
                        value="{{ old('contact_email', $sessionData['contact_email'] ?? $client->email ?? '') }}"
                        data-label="Email"
                    >
                    <label for="contact_email" class="tp-float-label">
                        Email <span class="text-[#9CA3AF] font-normal">
                            ({{ st('profile.neobovyazkovo', 'необовʼязково') }})
                        </span>
                    </label>
                </div>
            </div>
        </div>

        {{-- ПРАВАЯ ЧАСТЬ: мини-блок авторизации (только для гостя) --}}
@php
    $locale = app()->getLocale();
    $authUrl = in_array($locale, ['ru', 'en'], true)
        ? route('localized.auth.show', ['locale' => $locale])
        : route('auth.show');
@endphp

@guest
            <div class="w-full md:w-1/2">
                <div class="h-full rounded-[10px] border border-dashed border-[#FBBF77]
                            bg-[#FFF7EB] px-4 py-3 flex flex-col justify-between">
                    <div class="text-[12px] leading-[20px] text-[#272828] mb-3">
                        {{ st('cart.avtoryzuytes-telefon-avtozapovnennya-bonusy', 'Авторизуйтесь за допомогою номера телефону, щоб
                        автоматично заповнити інформацію та мати змогу
                        накопичувати й розраховуватися бонусами') }}.
                    </div>

                    <button
                        type="button"
                        class="h-[40px] w-full rounded-full bg-[#FF7500] text-white
                               text-[14px] font-semibold hover:bg-[#e56700] transition"
                        @click="
                            const authName  = document.getElementById('contact_name')?.value || '';
                            const authPhone = document.getElementById('contact_phone')?.value || '';
                            const authEmail = document.getElementById('contact_email')?.value || '';
                            window.location.href = '{{ $authUrl }}';
                        "
                    >
                        <span>{{ st('auth.login','Увійти') }}</span>
                    </button>
                </div>
            </div>
        @endguest
    </div>
</div>
