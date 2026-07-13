@auth
    <div
        x-data="{
    open:false,
    hover:false,
    justOpened:false,
    canHover: window.matchMedia('(hover:hover)').matches
  }"
        x-init="
    const mq = window.matchMedia('(hover:hover)');
    const upd = () => canHover = mq.matches;
    upd();
    mq.addEventListener ? mq.addEventListener('change', upd) : mq.addListener(upd);

    window.addEventListener('popstate', () => { open=false; hover=false; justOpened=false; });
    document.addEventListener('visibilitychange', () => { if (!document.hidden && open) { open=false; hover=false; justOpened=false; } });
  "
        @keydown.escape.window="open=false; justOpened=false"
        class="relative shrink-0 h-5 flex items-center"
    >
        {{-- ТРИГЕР --}}
        <button
            type="button"
            @click.stop="
      justOpened = true;
      open = !open;
      $nextTick(() => setTimeout(() => justOpened = false, 200));
    "
            @mouseenter="if (canHover) { hover=true; open=true }"
            @mouseleave="if (canHover) { hover=false; setTimeout(()=>{ if(!hover) open=false }, 120) }"
            class="inline-flex h-5 min-h-5 max-h-5 items-center gap-2 p-0 border-0 bg-transparent text-sm leading-none font-medium text-[#19191A] hover:text-orange-600 shrink-0"
            style="padding:0;border:0;background:transparent;line-height:1;appearance:none;-webkit-appearance:none;"
            aria-haspopup="menu"
            :aria-expanded="open"
        >
            <svg class="w-5 h-5 shrink-0 flex-none" width="20" height="20" viewBox="0 0 19 21" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M13 12.6409C11.8789 12.6409 11.3398 13.2659 9.5 13.2659C7.66016 13.2659 7.125 12.6409 6 12.6409C3.10156 12.6409 0.75 14.9925 0.75 17.8909V18.8909C0.75 19.9261 1.58984 20.7659 2.625 20.7659H16.375C17.4102 20.7659 18.25 19.9261 18.25 18.8909V17.8909C18.25 14.9925 15.8984 12.6409 13 12.6409ZM16.375 18.8909H2.625V17.8909C2.625 16.0316 4.14062 14.5159 6 14.5159C6.57031 14.5159 7.49609 15.1409 9.5 15.1409C11.5195 15.1409 12.4258 14.5159 13 14.5159C14.8594 14.5159 16.375 16.0316 16.375 17.8909V18.8909ZM9.5 12.0159C12.6055 12.0159 15.125 9.4964 15.125 6.39093C15.125 3.28546 12.6055 0.76593 9.5 0.76593C6.39453 0.76593 3.875 3.28546 3.875 6.39093C3.875 9.4964 6.39453 12.0159 9.5 12.0159ZM9.5 2.64093C11.5664 2.64093 13.25 4.32452 13.25 6.39093C13.25 8.45734 11.5664 10.1409 9.5 10.1409C7.43359 10.1409 5.75 8.45734 5.75 6.39093C5.75 4.32452 7.43359 2.64093 9.5 2.64093Z" fill="currentColor" stroke="currentColor" stroke-width="0.0390625"/>
            </svg>
            {{-- Текст на планшете скрываем, показываем с lg --}}
            <span class="hidden lg:flex items-center leading-none whitespace-nowrap">
                @php
                    $user = auth()->user();
                    $displayName = trim($user->name ?? '');
                    if (empty($displayName)) {
                        // Если имени нет, показываем номер телефона в формате +380505585...
                        $phone = $user->phone ?? '';
                        if ($phone && strlen($phone) >= 12) {
                            // Форматируем: +380505585... (первые 9 цифр + ... + последние 3)
                            $displayName = '+' . substr($phone, 0, 9) . '...';
                        } elseif ($phone) {
                            $displayName = '+' . $phone;
                        } else {
                            $displayName = 'Мій профіль';
                        }
                    }
                @endphp
                {{ $displayName }}
            </span>
            <svg class="hidden lg:block w-4 h-4 opacity-60" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path d="M5.3 7.3a1 1 0 011.4 0L10 10.6l3.3-3.3a1 1 0 111.4 1.4l-4 4a1 1 0 01-1.4 0l-4-4a1 1 0 010-1.4z"/>
            </svg>
        </button>

        {{-- ФОН для клика поза меню на мобільних --}}
        <div
            x-show="open"
            x-cloak
            x-transition:enter="transition ease-out duration-100"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-40 lg:hidden bg-black/20"
            @click="if (!justOpened) { open = false; justOpened = false; }"
        ></div>

        {{-- ДРОПДАУН --}}
        <div
            x-show="open"
            x-cloak
            @click.stop
            @mouseenter="if (canHover) hover=true"
            @mouseleave="if (canHover) { hover=false; setTimeout(()=>{ if(!hover) open=false }, 120) }"
            class="profile-dropdown-desktop
    z-50 pointer-events-auto rounded-lg bg-white shadow-xl ring-1 ring-black/10 overflow-hidden

    fixed left-4 right-4
    top-[72px]

    lg:absolute lg:top-full lg:left-auto lg:right-0 lg:mt-0 lg:w-72 lg:max-w-[288px]
  "
            role="menu"
            aria-label="Меню профілю"
        >
            @include(front_view('pages.menu.profile-menu'))
        </div>

        <style>
            @media (min-width: 1024px) {
                .profile-dropdown-desktop {
                    top: calc(100% + 13px) !important;
                    margin-top: 0 !important;
                }
            }
        </style>

    </div>
@else
    @php
        $locale = app()->getLocale();
        $authUrl = in_array($locale, ['ru', 'en'], true)
            ? route('localized.auth.show', ['locale' => $locale])
            : route('auth.show');
    @endphp
    <a
        href="{{ $authUrl }}"
        class="inline-flex items-center gap-2 text-sm leading-none font-medium text-[#19191A] hover:text-orange-600 shrink-0"
    >

    <svg class="w-5 h-5 shrink-0 flex-none" width="20" height="20" viewBox="0 0 19 21" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <path d="M13 12.6409C11.8789 12.6409 11.3398 13.2659 9.5 13.2659C7.66016 13.2659 7.125 12.6409 6 12.6409C3.10156 12.6409 0.75 14.9925 0.75 17.8909V18.8909C0.75 19.9261 1.58984 20.7659 2.625 20.7659H16.375C17.4102 20.7659 18.25 19.9261 18.25 18.8909V17.8909C18.25 14.9925 15.8984 12.6409 13 12.6409ZM16.375 18.8909H2.625V17.8909C2.625 16.0316 4.14062 14.5159 6 14.5159C6.57031 14.5159 7.49609 15.1409 9.5 15.1409C11.5195 15.1409 12.4258 14.5159 13 14.5159C14.8594 14.5159 16.375 16.0316 16.375 17.8909V18.8909ZM9.5 12.0159C12.6055 12.0159 15.125 9.4964 15.125 6.39093C15.125 3.28546 12.6055 0.76593 9.5 0.76593C6.39453 0.76593 3.875 3.28546 3.875 6.39093C3.875 9.4964 6.39453 12.0159 9.5 12.0159ZM9.5 2.64093C11.5664 2.64093 13.25 4.32452 13.25 6.39093C13.25 8.45734 11.5664 10.1409 9.5 10.1409C7.43359 10.1409 5.75 8.45734 5.75 6.39093C5.75 4.32452 7.43359 2.64093 9.5 2.64093Z" fill="currentColor" stroke="currentColor" stroke-width="0.0390625"/>
    </svg>
        {{-- Текст на планшете скрываем, показываем с lg --}}
        <span class="hidden lg:flex items-center leading-none whitespace-nowrap">
            {{ st('header.login','Увійти') }}
        </span>
    </a>
@endauth
