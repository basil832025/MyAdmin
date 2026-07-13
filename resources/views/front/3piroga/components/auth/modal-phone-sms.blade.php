<div
    x-show="isPhoneSms && open"
    x-transition.opacity
    x-cloak
    class="fixed inset-0 z-[9999] flex items-center justify-center p-4 pointer-events-none"
>
    <div class="relative z-[10000] w-full max-w-[1440px] h-[900px] max-h-[90vh] bg-white rounded-[24px] shadow-2xl overflow-visible my-4 pointer-events-auto">

        {{-- Кнопка закрытия --}}
        <button class="absolute right-6 top-6 text-3xl z-50 text-gray-600 hover:text-gray-800 transition-colors"
                @click="open=false">&times;</button>

        {{-- Обертка: мобилка колонка, десктоп РАЗВОРОТ ряда --}}
        <div class="w-full h-full flex flex-col md:flex-row-reverse">

            {{-- Картинка: мобилка сверху, десктоп справа --}}
            <div class="md:flex-1 md:flex md:items-center md:justify-end">
                {{-- Mobile: отступы 24px и высота 180px --}}
                <div class="md:hidden px-6 pt-6">
                    <div class="h-[180px] w-full overflow-hidden rounded-[20px] bg-[#F6E6C6]">
                        <img
                            src="{{ asset('images/svg/auth_right.png') }}"
                            alt=""
                            class="h-full w-full object-cover"
                            draggable="false"
                        >
                    </div>
                </div>

                {{-- Desktop --}}
                <div class="hidden md:flex w-full h-full max-w-[680px] pr-[48px] pt-[32px] pb-[32px] lg:w-[660px]">
                    <div class="w-full flex-1 overflow-hidden rounded-[32px] bg-[#F6E6C6]">
                        <img
                            src="{{ asset('images/svg/auth_right.png') }}"
                            alt=""
                            class="h-full w-full object-cover"
                            draggable="false"
                        >
                    </div>
                </div>

            </div>

            {{-- Форма: мобилка снизу, десктоп слева --}}
            <div class="flex-1 flex flex-col justify-between p-4 md:p-8 lg:p-12 xl:p-16 overflow-visible">
            {{-- Логотип и текст сверху --}}
            <div class="mb-4 md:mb-8">
                <div class="flex items-center gap-2 md:gap-3 mb-4 md:mb-6">
                    <img src="{{ asset('images/logo.svg') }}" alt="{{ st('header.logo_alt', 'Три пироги') }}" class="h-8 md:h-12 w-auto">
                        <span class="inline-flex items-center gap-1 md:gap-2 text-xs md:text-sm">
                            <img src="{{ asset('images/fire.svg') }}" class="w-4 h-4 md:w-5 md:h-5 mx-auto" alt="">
                            {{ st('header.wood-fired',"Готуємо в дров'яній печі!") }}
                        </span>
                </div>

            </div>

            {{-- Форма входа --}}
            <div class="flex-1 flex flex-col justify-center max-w-md pt-8">
                {{-- Первый шаг: Ввод телефона --}}
                <div x-show="!loginPhoneSmsSent" x-cloak>
                    <p class="text-sm md:text-lg lg:text-xl text-gray-700 mb-6 md:mb-8 leading-relaxed">
                        {{ st('auth.enter_phone_for_sms', 'Введите номер телефона, на этот номер поступит звонок или SMS:') }}
                    </p>

                    {{-- Поле телефона (код страны + номер) --}}
                    <div class="space-y-4 mb-6 md:mb-8">
                        <div class="flex gap-2 md:gap-3">
                            {{-- Код страны с флагом (только Украина, без выпадающего списка) --}}
                            <div class="flex items-center gap-1 md:gap-2 px-2 md:px-4 py-3 md:py-4 text-sm md:text-lg border-2 rounded-lg min-w-[100px] md:min-w-[140px]"
                                 :class="(typeof loginPhoneSmsError !== 'undefined' && loginPhoneSmsError) ? 'border-red-400' : 'border-gray-300'"
                            >
                                <div class="shrink-0 w-4 h-3 md:w-6 md:h-[18px]" style="transform: scale(0.85); transform-origin: left center;">
                                    <svg width="100%" height="100%" viewBox="0 0 24 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <rect width="24" height="9" fill="#005BBB"/>
                                        <rect y="9" width="24" height="9" fill="#FFD700"/>
                                    </svg>
                                </div>
                                <span class="font-medium text-xs md:text-base">+380</span>
                            </div>

                            {{-- Поле номера телефона --}}
                            <div class="flex-1 relative">
                                <input
                                    id="login-phone-sms-new"
                                    type="tel"
                                    x-model="loginPhoneSmsData.phoneNumber"
                                    x-init="
                                        if (!loginPhoneSmsData.phoneNumber) loginPhoneSmsData.phoneNumber = '';
                                        if (typeof Inputmask !== 'undefined' && !$el.__phoneNumberMasked) {
                                            const im = new Inputmask({
                                                mask: '99 999 99 99',
                                                placeholder: ' ',
                                                showMaskOnHover: false,
                                                showMaskOnFocus: true,
                                                clearIncomplete: true,
                                            });
                                            im.mask($el);
                                            $el.__phoneNumberMasked = true;
                                        }
                                    "
                                    @focus="onPhoneNumberFocus($event)"
                                    @click="onPhoneNumberClick($event)"
                                    @keydown.backspace="onPhoneNumberBackspace($event)"
                                    @input="if (typeof loginPhoneSmsError !== 'undefined') { loginPhoneSmsError = null; }"
                                    @keydown.enter.prevent="if (typeof loginPhoneSmsLoading !== 'undefined' && typeof loginPhoneSmsSent !== 'undefined' && !loginPhoneSmsLoading && !loginPhoneSmsSent) sendLoginPhoneSms()"
                                    inputmode="numeric"
                                    dir="ltr"
                                    class="w-full text-sm md:text-lg py-3 md:py-4 px-4 md:px-6 rounded-lg border-2 transition-colors"
                                    :class="(typeof loginPhoneSmsError !== 'undefined' && loginPhoneSmsError) ? 'border-red-400 focus:border-red-500' : 'border-gray-300 focus:border-[#FF7500]'"
                                    placeholder="__ ___ __ __"
                                    required
                                >
                            </div>
                        </div>

                        <p x-show="typeof loginPhoneSmsError !== 'undefined' && loginPhoneSmsError" x-text="(typeof loginPhoneSmsError !== 'undefined' && loginPhoneSmsError) || ''" class="text-red-600 text-xs md:text-sm"></p>
                    </div>

                    {{-- Кнопка отправки кода --}}
                    <button
                        class="w-full py-3 md:py-4 px-4 md:px-6 rounded-lg bg-[#FF7500] text-white font-semibold text-sm md:text-lg hover:bg-[#e56700] transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        :disabled="typeof loginPhoneSmsLoading !== 'undefined' && loginPhoneSmsLoading"
                        @click="sendLoginPhoneSms"
                    >
                        <span x-show="typeof loginPhoneSmsLoading === 'undefined' || !loginPhoneSmsLoading">{{ st('auth.send_code', 'Отправить код') }}</span>
                        <span x-show="typeof loginPhoneSmsLoading !== 'undefined' && loginPhoneSmsLoading">{{ st('auth.sending', 'Отправка…') }}</span>
                    </button>
                </div>

                {{-- SMS код форма --}}
                <div x-show="loginPhoneSmsSent"
                     x-cloak
                     x-effect="
        if (loginPhoneSmsSent && (!authSuccess)) {
          $nextTick(() => {
            requestAnimationFrame(() => {
              const first = $el.querySelector('[data-otp-index=\'0\']');
              if (first) {
                first.focus({ preventScroll: true });
                try { first.select() } catch(e) {}
              }
            });
          });
        }
     "
                     class="mt-8 space-y-6">


                {{-- Блок успешной авторизации --}}
                    <div x-show="typeof authSuccess !== 'undefined' && authSuccess" x-cloak class="space-y-4 text-center">
                        <div class="mx-auto w-12 h-12 md:w-16 md:h-16 rounded-full bg-green-100 flex items-center justify-center mb-3 md:mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 md:w-10 md:h-10 text-green-600" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="M5 13l4 4L19 7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <h3 class="text-base md:text-xl font-semibold text-gray-900">{{ st('auth.success', 'Авторизация успешна!') }}</h3>
                        <p class="text-sm md:text-base text-gray-600">{{ st('auth.redirecting_wait', 'Ожидайте... пока идет переадресация') }}</p>
                    </div>

                    {{-- Поля для ввода кода (скрываем при успехе) --}}
                    <div x-show="typeof authSuccess === 'undefined' || !authSuccess" x-cloak>
                        <p class="text-sm md:text-lg lg:text-xl font-bold text-gray-700 mb-3 md:mb-4">
                            {{ st('auth.enter_last_4_digits', 'Введите последние 4 цифры входящего номера или код из SMS:') }}
                        </p>
                        <p class="text-xs md:text-base text-gray-600 mb-4 md:mb-6">
                            {{ st('auth.to_number','На ваш номер') }} <span class="font-medium" x-text="sms.phoneFormatted || sms.phonePretty"></span> {{ st('auth.code_sent_valid_3min', 'отправлен код подтверждения. Срок действия вашего кода 3 минуты.') }}
                        </p>
                        <div class="flex gap-[10px] justify-center">
                            <template x-for="(digit, i) in otp" :key="i">
                                <input
                                    :data-otp-index="i"
                                    type="text"
                                    inputmode="numeric"
                                    maxlength="1"
                                    x-model="otp[i]"
                                    x-ref="i === 0 ? 'otp1' : null"
                                    x-init="$nextTick(() => { if (i === 0) otpRefs[0] = $el })"

                                    @input="(e) => {
                const val = (e.target.value || '').replace(/\\D/g, '').slice(0, 1);
                e.target.value = val;
                otp[i] = val;

                // Сбрасываем ошибку при вводе
                if (otpError) otpError = null;

                if (val && i === 3) {
                    const code = otp.join('');
                    if (code.length === 4) $nextTick(() => verifySms());
                } else if (val && i < 3) {
                    $nextTick(() => {
                        const nextInput = e.target.parentElement.querySelector(`[data-otp-index='${i + 1}']`);
                        if (nextInput) nextInput.focus();
                    });
                }
            }"

                                    @keydown="(e) => {
                if (e.key === 'Backspace' && !otp[i] && i > 0) {
                    e.preventDefault();
                    const prevInput = e.target.parentElement.querySelector(`[data-otp-index='${i - 1}']`);
                    if (prevInput) prevInput.focus();
                } else if (e.key === 'Backspace' && otp[i]) {
                    otp[i] = '';
                    e.target.value = '';
                }
            }"

                                    @paste.prevent="(e) => {
                const pasted = (e.clipboardData?.getData('text') || '').replace(/\\D/g, '').slice(0, 4);
                for (let j = 0; j < pasted.length && (i + j) < 4; j++) {
                    otp[i + j] = pasted[j];
                }
                if (otpError) otpError = null;

                if (pasted.length === 4) {
                    $nextTick(() => verifySms());
                } else if (pasted.length > 0) {
                    const nextIdx = Math.min(i + pasted.length, 3);
                    $nextTick(() => {
                        const nextEl = e.target.parentElement.querySelector(`[data-otp-index='${nextIdx}']`);
                        if (nextEl) nextEl.focus();
                    });
                }
            }"

                                    :class="[
                i === 0 ? 'otp1' : '',
                otpError
                     ? 'border-[#DC2626] text-[#DC2626] shadow-[0_0_8px_rgba(255,6,6,0.5)]'
    : 'border-gray-300 text-gray-900 focus:border-[#FF7500]'
            ]"

                                    class="
                w-12 h-12 md:w-[70px] md:h-[70px]
                rounded-lg md:rounded-[10px]
                border
                bg-white
                text-center
                text-xl md:text-[26px] leading-6 md:leading-[32px] font-bold
                text-gray-900
                outline-none
                transition
            "
                                >
                            </template>
                        </div>

                        <p
                            x-show="otpError"
                            x-text="otpError || ''"
                            class="mt-2 text-red-600 text-xs md:text-sm text-center"
                        ></p>

                        <button
                            class="block w-full text-center text-xs md:text-sm text-gray-600 hover:text-gray-800"
                            :disabled="sms.resendIn>0"
                            @click="resendCode()">
                            <span x-show="sms.resendIn===0">{{ st('auth.send_again','Надіслати код ще раз') }}</span>
                            <span x-show="sms.resendIn>0">{{ st('auth.resend_in','Повторно через') }} <span x-text="sms.resendIn"></span> c</span>
                        </button>

                        <button
                            class="block w-full text-center text-xs md:text-sm text-[#FF7500] hover:underline mt-2"
                            @click="changePhone"
                        >
                            {{ st('auth.change_phone', 'Изменить номер телефона') }}
                        </button>

                        @include(front_view('components.auth.sms-code-help-card'))
                    </div>
                </div>
            </div>
            </div>

        </div>
    </div>
</div>
