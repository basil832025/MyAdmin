    @extends(front_view('layouts.app'))
    @section('title', st('auth.title', 'Авторизация'))

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/inputmask@5/dist/inputmask.min.js"></script>
    @endpush

    @section('content')
    <div class="bg-gray-50 flex items-start md:items-center justify-center pt-4 pb-4 md:py-8 px-4">
        <div class="w-full max-w-[1160px] bg-white rounded-[24px] shadow-2xl overflow-hidden mt-0 md:mt-0">
            {{-- Обертка: мобилка колонка, десктоп РАЗВОРОТ ряда --}}
            <div class="w-full flex flex-col md:flex-row-reverse md:min-h-[760px]">

                {{-- Картинка справа (desktop) отключена по запросу --}}

                {{-- Форма: мобилка снизу, десктоп слева --}}
                <div
                    class="flex-1 flex flex-col justify-start md:justify-between
           p-4 md:p-6 lg:p-8 xl:p-10 overflow-visible
           pt-4 pb-[calc(50vh+env(safe-area-inset-bottom))] md:pt-8 md:pb-8"
                    x-data="authPhoneSmsPage()"
                >
                    {{-- Форма входа --}}
                    <div class="flex-1 flex flex-col justify-start md:justify-center max-w-[380px] pt-0 md:pt-4">
                        {{-- Первый шаг: Ввод телефона --}}
                        <div x-show="!loginPhoneSmsSent" x-cloak>
                            <p class="text-sm md:text-lg lg:text-xl text-gray-700 mb-6 md:mb-8 leading-relaxed">
                                {{ st('auth.enter_phone_for_sms', 'Введите номер телефона, на этот номер поступит звонок или SMS:') }}
                            </p>

                            {{-- Поле телефона (код страны + номер) --}}
                            <div class="space-y-4 mb-6 md:mb-8">
                                <div class="flex gap-2 md:gap-3">
                                    {{-- Код страны с флагом (только Украина) --}}
                                    <div class="flex items-center gap-1 md:gap-2 px-2 md:px-4 py-3 md:py-4 text-sm md:text-lg border-2 rounded-lg min-w-[100px] md:min-w-[140px]"
                                         :class="loginPhoneSmsError ? 'border-red-400' : 'border-gray-300'"
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
                                            @input="
                                                let digits = ($event.target.value || '').replace(/\D/g, '');
                                                digits = digits.replace(/^0+/, '').slice(0, 9);
                                                loginPhoneSmsData.phoneNumber = digits;
                                                if (loginPhoneSmsError) loginPhoneSmsError = null;
                                            "
                                            @keydown.enter.prevent="if (!loginPhoneSmsLoading && !loginPhoneSmsSent) sendLoginPhoneSms()"
                                            inputmode="numeric"
                                            dir="ltr"
                                            class="w-full text-base md:text-lg py-3 md:py-4 px-4 md:px-6 rounded-lg border-2 transition-colors"
                                            :class="loginPhoneSmsError ? 'border-red-400 focus:border-red-500' : 'border-gray-300 focus:border-[#FF7500]'"
                                            placeholder="__ ___ __ __"
                                            required
                                        >
                                    </div>
                                </div>

                                <p x-show="loginPhoneSmsError" x-text="loginPhoneSmsError || ''" class="text-red-600 text-xs md:text-sm"></p>
                            </div>

                            {{-- Кнопка отправки кода --}}
                            <button
                                class="w-full py-3 md:py-4 px-4 md:px-6 rounded-lg bg-[#FF7500] text-white font-semibold text-sm md:text-lg hover:bg-[#e56700] transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                :disabled="loginPhoneSmsLoading"
                                @click="sendLoginPhoneSms"
                            >
                                <span x-show="!loginPhoneSmsLoading">{{ st('auth.send_code', 'Отправить код') }}</span>
                                <span x-show="loginPhoneSmsLoading">{{ st('auth.sending', 'Отправка…') }}</span>
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
                            <div x-show="authSuccess" x-cloak class="space-y-4 text-center">
                                <div class="mx-auto w-12 h-12 md:w-16 md:h-16 rounded-full bg-green-100 flex items-center justify-center mb-3 md:mb-4">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 md:w-10 md:h-10 text-green-600" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path d="M5 13l4 4L19 7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                                <h3 class="text-base md:text-xl font-semibold text-gray-900">{{ st('auth.success', 'Авторизация успешна!') }}</h3>
                                <p class="text-sm md:text-base text-gray-600">{{ st('auth.redirecting_wait', 'Ожидайте... пока идет переадресация') }}</p>
                            </div>

                            {{-- Поля для ввода кода (скрываем при успехе) --}}
                            <div x-show="!authSuccess" x-cloak>
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
                                                const val = (e.target.value || '').replace(/\D/g, '').slice(0, 1);
                                                e.target.value = val;
                                                otp[i] = val;

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
                                                const pasted = (e.clipboardData?.getData('text') || '').replace(/\D/g, '').slice(0, 4);
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
    @endsection

    @push('scripts')
    <script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('authPhoneSmsPage', () => {
            // Импортируем логику из auth-modal.js
            const authModal = Alpine.store('authModal');

            return {
                // Инициализация данных
                loginPhoneSmsData: {
                    countryCode: '380',
                    countryFlag: '',
                    phoneNumber: '',
                    phone: ''
                },
                loginPhoneSmsSent: false,
                loginPhoneSmsLoading: false,
                loginPhoneSmsError: null,
                otp: ['', '', '', ''],
                otpError: null,
                authSuccess: false,
                sms: {
                    phoneFormatted: '',
                    phonePretty: '',
                    resendIn: 0
                },
                otpRefs: [],

                init() {
                    // Инициализация
                },

                onPhoneNumberFocus(e) {
                    const val = e.target.value.replace(/\D/g, '');
                    if (val.length === 0) {
                        e.target.value = '';
                    }
                },

                onPhoneNumberClick(e) {
                    const val = e.target.value.replace(/\D/g, '');
                    if (val.length === 0) {
                        e.target.value = '';
                    }
                },

                onPhoneNumberBackspace(e) {
                    if (e.target.value.replace(/\D/g, '').length === 0) {
                        e.target.value = '';
                    }
                },

                async sendLoginPhoneSms() {
                    let phoneNumber = this.loginPhoneSmsData.phoneNumber.replace(/\D/g, '');
                    phoneNumber = phoneNumber.replace(/^0+/, '');

                    if (phoneNumber.length !== 9) {
                        this.loginPhoneSmsError = 'Введіть правильний номер телефону';
                        return;
                    }

                    this.loginPhoneSmsData.phoneNumber = phoneNumber;

                    const phone = '380' + phoneNumber;
                    this.loginPhoneSmsLoading = true;
                    this.loginPhoneSmsError = null;

                    try {
                        const response = await fetch('{{ route('auth.phone-sms.send-code') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ phone })
                        });

                        const data = await response.json();

                        if (!response.ok || !data.ok) {
                            this.loginPhoneSmsError = data.message || 'Помилка відправки коду';
                            this.loginPhoneSmsLoading = false;
                            return;
                        }

                        this.loginPhoneSmsSent = true;
                        this.loginPhoneSmsData.phone = phone;
                        this.sms.phoneFormatted = data.phone_formatted || phone;
                        this.sms.phonePretty = data.phone_pretty || phone;
                        this.loginPhoneSmsLoading = false;

                        // Запускаем таймер для повторной отправки
                        this.sms.resendIn = 60;
                        const timer = setInterval(() => {
                            this.sms.resendIn--;
                            if (this.sms.resendIn <= 0) {
                                clearInterval(timer);
                            }
                        }, 1000);
                    } catch (error) {
                        console.error('Error sending SMS:', error);
                        this.loginPhoneSmsError = 'Помилка відправки коду. Спробуйте ще раз.';
                        this.loginPhoneSmsLoading = false;
                    }
                },

                async verifySms() {
                    const code = this.otp.join('');
                    if (code.length !== 4) {
                        this.otpError = 'Невірний код. Перевірте цифри та спробуйте ще раз.';
                        return;
                    }

                    this.otpError = null;

                    try {
                        const response = await fetch('{{ route('auth.phone-sms.verify') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                phone: this.loginPhoneSmsData.phone,
                                code: code
                            })
                        });

                        const data = await response.json();

                        if (!response.ok || !data.ok) {
                            this.otpError = data.message || 'Невірний код. Перевірте цифри та спробуйте ще раз.';
                            return;
                        }

                        this.authSuccess = true;

                        // Редирект после успешной авторизации
                        if (data.redirect) {
                            setTimeout(() => {
                                window.location.href = data.redirect;
                            }, 1000);
                        } else {
                            setTimeout(() => {
                                window.location.href = '{{ route('profile.index') }}';
                            }, 1000);
                        }
                    } catch (error) {
                        console.error('Error verifying SMS:', error);
                        this.otpError = 'Невірний код. Перевірте цифри та спробуйте ще раз.';
                    }
                },

                async resendCode() {
                    if (this.sms.resendIn > 0) return;
                    await this.sendLoginPhoneSms();
                },

                changePhone() {
                    this.loginPhoneSmsSent = false;
                    this.otp = ['', '', '', ''];
                    this.otpError = null;
                    this.authSuccess = false;
                    this.sms.resendIn = 0;
                }
            };
        });
    });
    </script>
    @endpush
