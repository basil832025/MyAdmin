@php
    use App\Models\SiteText;
    use App\Models\Setting;

    // Текущий язык
    $locale = app()->getLocale();

    // Забираем все записи со слагом, начинающимся на "auth."
    $authTexts = SiteText::query()
        ->where('slug', 'like', 'auth.%')
        ->get(['slug', 'value']);

    // Формируем словарь: ['auth.login' => 'Увійти', ...]
    $authI18n = $authTexts
        ->mapWithKeys(fn($row) => [
            $row->slug => $row->getTranslation('value', $locale) ?: $row->slug,
        ])
        ->toArray();

    // Получаем настройку метода авторизации
    $setting = Setting::first();
    $cartAuthMethod = $setting?->cart_auth_method ?? 'phone_password_sms';
@endphp
<div x-data>
<template x-teleport="body">

    <div
        id="authModal"
        x-data="authModal({ i18n: @js($authI18n), authMethod: @js($cartAuthMethod) })"
        x-show="open"
        x-init="open = false; /* console.log('AuthModal init:', { authMethod, open }) */"
        x-transition.opacity
        x-cloak

        data-login="{{ route('auth.login') }}"
        data-send-code="{{ route('auth.register.send-code') }}"
        data-verify="{{ route('auth.register.verify') }}"
        data-pwd-send-code="{{ route('auth.password.sendCode') }}"
        data-pwd-verify="{{ route('auth.password.verify') }}"
        data-phone-sms-send-code="{{ route('auth.phone-sms.send-code') }}"
        data-phone-sms-verify="{{ route('auth.phone-sms.verify') }}"
    @open-auth-modal.window="
    const payload = $event.detail || {};

    // СНАЧАЛА подставляем данные, ПОТОМ переключаем вкладку
    // подставить имя в регистрацию
    if (payload.name) {
        registerData.name = payload.name;
    }

    // подставить email в регистрацию
    if (payload.email) {
        registerData.email = payload.email;
    }

    // подставить телефон в логин и регистрацию
    if (payload.phone) {
        loginData.phone    = payload.phone;
        registerData.phone = payload.phone;
    }

    // подставить пароль в обе формы, если передан
    if (payload.password) {
        loginData.password = payload.password;
        registerData.password = payload.password;
        registerData.password_confirmation = payload.password;
    }

    // открыть модалку
    open = true;
    // console.log('OPEN', {
    //     authMethod,
    //     normalized: (authMethod ?? '').toString().trim(),
    // });
    $nextTick(() => {
        const el = document.getElementById('login-phone-sms-new');
        // console.log('NEW WINDOW INPUT exists?', !!el, el?.offsetParent);
    });
    loginLoading = false;

    // Отладка в консоль
    // console.log('AuthModal Opening:', {
    //     authMethod: authMethod,
    //     open: open,
    //     condition: (authMethod === 'phone_sms' && open),
    //     loginPhoneSmsData: typeof loginPhoneSmsData !== 'undefined' ? loginPhoneSmsData : 'undefined'
    // });

    // вкладка: login / register (после подстановки данных)
    if (payload.tab) {
        switchTab(payload.tab);
    } else {
        switchTab('login');
    }

    // сфокусироваться на поле логина (как и было раньше)
    $nextTick(() => {
      setTimeout(() => {
        if (typeof focusLoginAfterOpen === 'function') {
          focusLoginAfterOpen();
        }
      }, 150);
    });
"

        class="fixed inset-0 z-[99999] overflow-y-auto"
>
    <div class="fixed inset-0 bg-black/40 backdrop-blur-[1px] z-[9998]" @click="open=false" x-show="open"></div>

    {{-- Вариант 1: Старое модальное окно (телефон + пароль) --}}
        @include(front_view('components.auth.modal-legacy'))


    {{-- Вариант 2: Новое модальное окно только для phone_sms (Figma дизайн) --}}
        @include(front_view('components.auth.modal-phone-sms'))
</div>

</template>
</div>
