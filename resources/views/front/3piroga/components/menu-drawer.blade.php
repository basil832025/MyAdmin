@props([
'event' => 'open-mobile-menu',        // какое событие открывает
'side'  => 'left',                    // 'left' | 'right'
'width' => 'w-[304px] sm:w-[360px] lg:w-[420px] max-w-[90vw]',
'panelClass' => '',                   // доп. классы для панели
'overlayClass' => '',                 // доп. классы для затемнения
])
@php
                     if (! isset($headerLocation, $headerPhones, $headerPhonePrimary, $headerSchedule)) {
                         $header = app(\App\Services\HeaderContacts::class)->buildBySlug(config('site.header_location_slug', '3pie'));

                         $headerPhones = $headerPhones ?? ($header['phones'] ?? collect());
                         $headerPhonePrimary = $headerPhonePrimary ?? ($header['primary'] ?? null);
                         $headerLocation = $headerLocation ?? ($header['location'] ?? null);
                         $headerSchedule = $headerSchedule ?? ($header['schedule'] ?? collect());
                     }

                     $MainMenuItems = $MainMenuItems ?? [];

                     // phones from HeaderContacts composer
                     $phones = collect($headerPhones ?? []);
                     $phoneCols = $phones->chunk(2); // по 2 номера в колонку

                     // email/address — берем из location, если есть (подстрой ключи под свою модель Location)
                     $email   = data_get($headerLocation, 'email')
                             ?? data_get($headerLocation, 'contact_email')
                             ?? config('site.email', 'info@3piroga.ua');

                      $address = data_get($headerLocation, 'address')
                              ?? data_get($headerLocation, 'address_text')
                              ?? '';

                      $lat = data_get($headerLocation, 'lat');
                      $lng = data_get($headerLocation, 'lng');
                      $googleMapLink = (string) (data_get($headerLocation, 'google_map_link') ?? '');

                      $hasCoords = is_numeric($lat) && is_numeric($lng);

                      // Fallback link: destination only
                      if ($hasCoords) {
                          $destination = $lat . ',' . $lng;
                          $mapsHref = 'https://www.google.com/maps/dir/?api=1&destination=' . urlencode($destination);
                      } elseif ($googleMapLink !== '') {
                          $mapsHref = $googleMapLink;
                      } else {
                          $mapsHref = 'https://www.google.com/maps/search/?api=1&query=' . urlencode((string) $address);
                      }



       $burgerCatalogItems = collect($MainMenuItems)->map(function ($it) {
    return [
        'key'        => $it['slug'] ?? $it['id'],
        'label'      => $it['label'],
        'href'       => $it['url'],
        'activeWhen' => ltrim($it['url'], '/') . '*',
    ];
})->values()->all();




  $accountMenu = \App\Support\Menus::bySlug('profile-menu');
  $aboutMenu = \App\Support\Menus::bySlug('menu-left-pages');
@endphp
<div
    x-data="{ open: false }"
    x-on:{{ $event }}.window="open = true"
    x-init="$watch('open', v => {
        document.documentElement.classList.toggle('no-scroll', v)
        document.body.classList.toggle('no-scroll', v)
     })"
    x-on:keydown.window.escape="open = false"

    x-cloak
>
    <!-- затемнение -->
    <div
        x-show="open"
        x-transition.opacity
        @click="open=false"
        class="fixed inset-0 z-50 bg-black/40 {{ $overlayClass }}"
        aria-hidden="true"
    ></div>

@php
    $from = $side === 'right' ? 'translate-x-full' : '-translate-x-full';
    $pos  = $side === 'right' ? 'right-0' : 'left-0';
@endphp


    <!-- панель слева -->
    <aside
        x-show="open"
        x-transition:enter="transform transition ease-in-out duration-300"
        x-transition:enter-start="-translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transform transition ease-in-out duration-300"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="-translate-x-full"
        :id="$id('drawer')"
        class="fixed left-0 top-0 z-[60] h-full md:w-[414px] w-[355px] bg-white shadow-2xl h-full overflow-y-auto custom-scroll"
        role="dialog"
        aria-modal="true"
    >
        <!-- ВНУТРЕННИЙ СКРОЛЛЕР  class="h-full overflow-y-auto pr-3 custom-scroll" -->
        <div >
            <!-- Шапка меню -->
            <div class="flex items-center justify-between px-8 py-6">
                <div class="flex items-center gap-3">
                    <img src="{{ asset('images/logo.svg') }}" alt="Три пироги">

                    {{-- Language switch (как в хидере) --}}
                    <div class="ml-2">
                        <x-ui.lang-switch variant="burger" />
                    </div>
                </div>

                <button class="p-2 rounded-lg hover:bg-black/5" @click="open=false" aria-label="Закрыть меню">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M6 6l12 12M18 6l-12 12"/>
                    </svg>
                </button>
            </div>

        <!-- Контент (скролл внутри) -->
        <div class="h-[calc(100%-56px)] overflow-y-auto px-8">
            <!-- разделы -->
            <x-ui.menu-list  :items="$accountMenu" :is_account_menu="true"  />
            <x-ui.menu-list  :items="$aboutMenu" :remember="true" />


               <div class="mt-6">
                   <x-ui.menu-list
                       title="{{ st('menu.title', 'Меню') }}"
                       :items="$burgerCatalogItems"
                       :remember="true"
                   />

                   @guest('web')
            <!-- Кнопка входа -->
            <div class="px-3 py-5">
                <a href="{{ route('auth.show') }}"
                   class="block text-center rounded-[4px] bg-[#FF7500] text-white font-semibold py-3">
                    {{ st('auth.login','Увійти') }}
                </a>
            </div>
                   @endguest

               <!-- Контакты -->
            <div class="px-3 pb-6 text-[16px] text-[#272828]">
                <h4 class="font-semibold mb-2">{{ st('all.contacts','Контакти') }}</h4>
                <div class="space-y-1">

                    {{-- телефоны --}}
                    @foreach ($phoneCols as $col)
                            @foreach ($col as $p)
                                    <a href="tel:{{ $p['tel'] }}" class="hover:text-black block">
                                        {{ $p['display'] }}
                                    </a>
                            @endforeach
                    @endforeach

                    <a href="mailto:{{ $email }}" class="hover:text-black block">{{ $email }}</a>

                      <div class="mt-2 text-xs text-[#929292]">
                          @if($address)
                                 <a
                                     href="{{ $mapsHref }}"
                                     class="hover:text-[#272828] underline underline-offset-2"
                                     target="_blank"
                                     rel="noopener noreferrer"
                                     data-maps-link="1"
                                     data-maps-destination="{{ $hasCoords ? ($lat . ',' . $lng) : '' }}"
                                     data-maps-destination-address="{{ e((string) $address) }}"
                                 >
                                     {{ $address }}
                                 </a>
                          @endif
                      </div>

                      @php
                          // Same schedule rendering as on /nashi-restorany
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
                      @endphp

                      <div class="mt-4">
                          <h4 class="font-semibold mb-2 text-[16px] text-[#272828]">{{ __('location.sections.schedule') }}</h4>
                          <div class="space-y-2">
                              <div>
                                  <div class="text-[#9E9E9E] text-[13px]">{{ $pickup['title'] }}:</div>
                                  <div class="font-semibold text-[14px] text-[#272828]">{{ $pickup['time'] }}</div>
                              </div>
                              <div>
                                  <div class="text-[#9E9E9E] text-[13px]">{{ $delivery['title'] }}:</div>
                                  <div class="font-semibold text-[14px] text-[#272828]">{{ $delivery['time'] }}</div>
                              </div>
                          </div>
                      </div>
                  </div>

                 <!-- соцсети -->
                 <div class="mt-4">
                    <span class="text-[#929292] text-[13px]">{{ st('all.my-v-sotsialnykh-merezhakh','Ми в соціальних мережах') }}</span>
                <ul class="flex items-center mt-2 gap-8 md:gap-8 md:justify-left">
                    <li><a href="https://www.facebook.com/3piroga.ua" target="_blank" aria-label="Facebook" class="text-black hover:text-[#FF7500]">
                            <x-icons.facebook class="w-8 h-8"/>
                        </a></li>
                    <li><a href="https://www.instagram.com/3piroga_ua" target="_blank" aria-label="Instagram"  class="text-black hover:text-[#FF7500]">
                            <x-icons.instagram class="w-8 h-8"/>
                        </a></li>
                    <li><a href="https://www.tiktok.com/@tripiroga?_r=1" aria-label="TikTok" target="_blank" class="text-black hover:text-[#FF7500]">
                            <x-icons.tiktok class="w-8 h-8"/>
                        </a></li>
                    <li><a href="https://t.me/OsetianBakery" target="_blank" aria-label="Telegram" class="text-black hover:text-[#FF7500]">
                            <x-icons.telegram class="w-8 h-8"/>
                        </a></li>
                    <li><a href="viber://chat?number=%2B380660784333" aria-label="Viber" class="text-black hover:text-[#FF7500]">
                            <x-icons.viber class="w-8 h-8"/>
                        </a></li>
                    <li><a href="https://www.youtube.com/channel/UC37VV_ZFmkTacWeHKFsYWLQ" target="_blank" aria-label="YouTube" class="text-black hover:text-[#FF7500]">
                            <x-icons.youtube class="w-8 h-8"/>
                        </a></li>
                </ul>
            </div>
            </div>
        </div>
        </div>
    </aside>
</div>
