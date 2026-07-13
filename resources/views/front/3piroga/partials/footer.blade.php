@php
    $footer_left_menu = \App\Support\Menus::bySlug('footer-left');
    $footer_center_menu = \App\Support\Menus::bySlug('footer-center');
    $footer_right_menu = \App\Support\Menus::bySlug('footer-right');


@endphp
<footer class="bg-white text-[#929292] xl:mt-[80px] mt-[40px]">
    <div class="max-w-screen-xl mx-auto px-4 md:px-6">
        {{-- линия-разделитель --}}
        <div class="border-t border-black/10"></div>

        {{-- сетка для md = 3 колонки, lg = 4 --}}
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-x-10 gap-y-8 py-12">

            {{-- Три пирога --}}
            <div>
                <h3 class="text-lg font-semibold text-black"> {{ st('all.try-pyroha','Три пироги') }}</h3>
                @if($footer_left_menu)

                <ul class="mt-4 space-y-2 text-[14px] text-[#929292] font-bold">
                    @foreach ($footer_left_menu as $it)

                    <li><a class="hover:text-black" href="{{ $it['href'] ?: '#' }}">{{$it['label']}}</a></li>
                    @endforeach

                </ul>
                @endif
            </div>

            {{-- Юридична інформація --}}
            <div>
                <h3 class="text-lg font-semibold text-black">{{ st('all.iurydychna-informatsiia','Юридична інформація') }}</h3>
                @if($footer_center_menu)
                <ul class="mt-4 space-y-2 text-[14px] text-[#929292] font-bold">
                    @foreach ($footer_center_menu as $it)

                        <li><a class="hover:text-black" href="{{ $it['href'] ?: '#' }}">{{$it['label']}}</a></li>
                    @endforeach
                        </ul>
                @endif
            </div>

            {{-- Доставка і ресторани --}}
            <div>
                <h3 class="text-lg font-semibold text-black">{{ st('all.dostavka-and-restorany','Доставка і ресторани') }}</h3>
                @if($footer_center_menu)
                <ul class="mt-4 space-y-2 text-[14px] text-[#929292] font-bold">
                    @foreach ($footer_right_menu as $it)

                        <li><a class="hover:text-black" href="{{ $it['href'] ?: '#' }}">{{$it['label']}}</a></li>
                    @endforeach
                     </ul>
                @endif
            </div>

            {{-- Контакти --}}
            <div class="md:col-span-3 lg:col-span-1">
                <h3 class="text-lg font-semibold text-black">{{ st('all.contacts','Контакти') }}</h3>

                @php
                    // phones from HeaderContacts composer
                    $phones = collect($headerPhones ?? []);

                    // same working-hours block as in the burger menu
                    $pickup = ['time' => 'з 09:00 до 20:00', 'title' => 'Приймаємо замовлення на самовивіз'];
                    $delivery = ['time' => 'з 09:00 до 21:00', 'title' => 'Доставляємо замовлення'];

                    if (!empty($headerSchedule)) {
                        foreach ($headerSchedule as $schedule) {
                            $slug = trim((string) ($schedule['slug'] ?? ''));

                            if ($slug === 'delivery') {
                                $delivery['time'] = (string) ($schedule['time'] ?? $delivery['time']);
                                $delivery['title'] = (string) ($schedule['title'] ?? $delivery['title']);
                            }

                            if ($slug === 'pickup') {
                                $pickup['time'] = (string) ($schedule['time'] ?? $pickup['time']);
                                $pickup['title'] = (string) ($schedule['title'] ?? $pickup['title']);
                            }
                        }
                    }

                    // email/address — берем из location, если есть (подстрой ключи под свою модель Location)
                    $email   = data_get($headerLocation, 'email')
                            ?? data_get($headerLocation, 'contact_email')
                            ?? config('site.email', 'info@3piroga.ua');

                    $address = data_get($headerLocation, 'address')
                            ?? data_get($headerLocation, 'address_text')
                            ?? '';
                @endphp

                <div class="mt-4 flex flex-col gap-6 text-[14px] font-bold">
                    {{-- телефоны --}}
                    <ul class="space-y-2 text-[#272828]">
                        @foreach ($phones as $p)
                            <li>
                                <a href="tel:{{ $p['tel'] }}" class="hover:text-black">
                                    {{ $p['display'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>

                    <div class="space-y-2">
                        <h4 class="text-[13px] font-semibold text-black">{{ __('location.sections.schedule') }}</h4>
                        <div>
                            <div class="text-[#9E9E9E] text-[13px]">{{ $pickup['title'] }}:</div>
                            <div class="font-semibold text-[14px] text-[#272828]">{{ $pickup['time'] }}</div>
                        </div>
                        <div>
                            <div class="text-[#9E9E9E] text-[13px]">{{ $delivery['title'] }}:</div>
                            <div class="font-semibold text-[14px] text-[#272828]">{{ $delivery['time'] }}</div>
                        </div>
                    </div>

                    {{-- email + address --}}
                    <ul class="space-y-2">
                        <li class="font-bold">
                            <a href="mailto:{{ $email }}" class="hover:text-black">{{ $email }}</a>
                        </li>

                        @if($address)
                            <li class="text-[#929292] font-normal">
                                {{ $address }}
                            </li>
                        @endif
                    </ul>
                </div>
            </div>


        </div>


            {{-- линия-разделитель --}}
            <div class="border-t border-black/10"></div>

            {{-- строка: логотип+лозунг слева | соцсети справа --}}
            <div class="py-8 flex flex-col gap-6 md:flex-row md:items-center justify-between">
                <div class=" flex items-start gap-4 desk:w-[498px] md:w-[419px]">
                    <img src="/images/logo-footer.png" alt="Три Пироги" class="w-14 h-14">
                    <p class="text-[#666666] text-[13px] leading-snug">
                        Мережа пекарень "ТРИ ПИРОГИ"  <span class="emoji">&#x2668;&#xFE0F;&#x2668;&#xFE0F;&#x2668;&#xFE0F;</span> - єдина у Києві, де печуть справжні осетинські пироги з пилу, з жару, в дров`яній печі
                    </p>
                </div>

                <ul class="flex items-center gap-8 md:gap-5 md:justify-end">
                    <li><a href="https://www.facebook.com/3piroga.ua" target="_blank" aria-label="Facebook" class="text-black hover:text-[#FF7500]">
                            <x-icons.facebook class="w-6 h-6"/>
                        </a></li>
                    <li><a href="https://www.instagram.com/3piroga_ua" target="_blank" aria-label="Instagram" class="text-black hover:text-[#FF7500]">
                            <x-icons.instagram class="w-6 h-6"/>
                        </a></li>
                    <li><a href="https://www.tiktok.com/@tripiroga?_r=1" aria-label="TikTok" target="_blank" class="text-black hover:text-[#FF7500]">
                            <x-icons.tiktok class="w-6 h-6"/>
                        </a></li>
                    <li><a href="https://t.me/OsetianBakery" target="_blank" aria-label="Telegram" class="text-black hover:text-[#FF7500]">
                            <x-icons.telegram class="w-6 h-6"/>
                        </a></li>
                    <li><a href="viber://chat?number=%2B380660784333" aria-label="Viber" class="text-black hover:text-[#FF7500]">
                            <x-icons.viber class="w-6 h-6" style="transform: scale(1.15); transform-origin: center;"/>
                        </a></li>
                    <li><a href="https://www.youtube.com/channel/UC37VV_ZFmkTacWeHKFsYWLQ" target="_blank" aria-label="YouTube" class="text-black hover:text-[#FF7500]">
                            <x-icons.youtube class="w-6 h-6"/>
                        </a></li>
                </ul>
            </div>

            {{-- нижняя строка копирайта --}}
            <div class="border-t border-black/10"></div>
            <div class="py-6 flex flex-col md:flex-row md:items-center md:justify-left gap-4">
                <p class="text-[14px] text-[#A9A9A9]">© ТРИ ПИРОГИ. Усі права захищені</p>
                <div class="flex items-center gap-6">
                    <img src="/images/payments/mastercard.png" alt="Mastercard" class="h-5">
                    <img src="/images/payments/visa.png" alt="VISA" class="h-5">
                </div>
            </div>

        </div>
    </footer>
