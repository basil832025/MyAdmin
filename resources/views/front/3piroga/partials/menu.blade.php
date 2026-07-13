@php

    $activeIndex = 0; // временно
    $brand = '#FF7500';
@endphp

<nav class="sticky top-[68px] md:top-[64px] desk:top-[76px] z-40 bg-white shadow-sm mt-6" x-data="scrollTabs()" x-init="init">
    <div class="mx-auto w-full desk:w-[1343px] px-4 md:px-6">
    <div class="relative py-2 overflow-hidden">

        <!-- левая стрелка -->
        <button
            type="button"
            class="menu-arrow absolute left-0 top-1/2 -translate-y-1/2 mt-[3px] md:mt-0 z-20
             h-6 w-6 p-0 bg-white/80 backdrop-blur-sm rounded-none ring-0 shadow-none
             flex items-center justify-center"
            x-show="canScrollLeft" @click="scroll('left')" aria-label="Вліво">

            <svg width="11" height="21" viewBox="0 0 11 21" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M10.7271 1.687C10.8173 1.59153 10.8878 1.47923 10.9347 1.3565C10.9815 1.23377 11.0037 1.10302 10.9999 0.971721C10.9962 0.840419 10.9667 0.711134 10.913 0.591248C10.8593 0.471362 10.7826 0.363223 10.6871 0.273005C10.5916 0.182787 10.4793 0.112257 10.3566 0.0654411C10.2339 0.0186256 10.1031 -0.00355848 9.97181 0.000155861C9.84051 0.00387021 9.71123 0.03341 9.59134 0.0870888C9.47146 0.140767 9.36332 0.217534 9.2731 0.313005L0.773098 9.313C0.597562 9.49867 0.499756 9.74449 0.499756 10C0.499756 10.2555 0.597562 10.5013 0.773098 10.687L9.2731 19.688C9.36272 19.7856 9.47084 19.8643 9.59116 19.9198C9.71149 19.9752 9.84163 20.0062 9.97402 20.0109C10.1064 20.0156 10.2384 19.9939 10.3624 19.9472C10.4863 19.9004 10.5998 19.8295 10.6961 19.7386C10.7924 19.6476 10.8697 19.5384 10.9235 19.4173C10.9772 19.2963 11.0064 19.1657 11.0093 19.0333C11.0122 18.9008 10.9887 18.7691 10.9403 18.6458C10.8918 18.5225 10.8194 18.4101 10.7271 18.315L2.8751 10L10.7271 1.687Z" fill="#FF7500"/>
            </svg>
        </button>

        <!-- правая стрелка -->
        <button
            type="button"
            class="menu-arrow absolute right-0 top-1/2 -translate-y-1/2 mt-[3px] md:mt-0 z-20
             h-6 w-6 p-0 bg-white/80 backdrop-blur-sm rounded-none ring-0 shadow-none
             flex items-center justify-center"
            x-show="canScrollRight" @click="scroll('right')" aria-label="Вправо">
            <svg width="11" height="21" viewBox="0 0 11 21" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M0.273146 1.687C0.182927 1.59153 0.112398 1.47923 0.0655823 1.3565C0.0187664 1.23377 -0.00341797 1.10302 0.000296593 0.971721C0.00401115 0.840419 0.0335512 0.711134 0.0872297 0.591248C0.140908 0.471362 0.217674 0.363223 0.313146 0.273005C0.408617 0.182787 0.520924 0.112257 0.643652 0.0654411C0.76638 0.0186256 0.897128 -0.00355848 1.02843 0.000155861C1.15973 0.00387021 1.28902 0.03341 1.4089 0.0870888C1.52879 0.140767 1.63693 0.217534 1.72715 0.313005L10.2271 9.313C10.4027 9.49867 10.5005 9.74449 10.5005 10C10.5005 10.2555 10.4027 10.5013 10.2271 10.687L1.72715 19.688C1.63752 19.7856 1.52941 19.8643 1.40908 19.9198C1.28875 19.9752 1.15862 20.0062 1.02622 20.0109C0.893826 20.0156 0.761816 19.9939 0.637859 19.9472C0.513903 19.9004 0.400469 19.8295 0.304149 19.7386C0.207829 19.6476 0.13054 19.5384 0.0767736 19.4173C0.0230074 19.2963 -0.00616455 19.1657 -0.00904942 19.0333C-0.0119343 18.9008 0.0115271 18.7691 0.0599709 18.6458C0.108415 18.5225 0.180876 18.4101 0.273146 18.315L8.12515 10L0.273146 1.687Z" fill="#FF7500"/>
            </svg>


        </button>


        <!-- градиенты по краям -->
        <div x-show="canScrollLeft"  class="pointer-events-none absolute inset-y-0 left-0  w-8 bg-gradient-to-r from-white to-transparent"></div>
        <div x-show="canScrollRight" class="pointer-events-none absolute inset-y-0 right-0 w-8 bg-gradient-to-l from-white to-transparent"></div>

        <!-- скроллер -->
        <div x-ref="scroller"
             class="w-full overflow-x-auto no-scrollbar scroll-smooth">
            <ul class="flex items-center min-w-full w-max justify-center mt-[2px] md:mt-0 pl-10 pr-10 gap-4 md:gap-[70px] h-10">
                @foreach ($MainMenuItems as $i => $item)
                    @php
                        $active = ($i === $MenuactiveIndex);
                    @endphp
                    <li class="shrink-0 h-10">
                        <a href="{{ $item['url'] }}"
                           @class([
                        'inline-flex items-center h-10 leading-none text-[14px] font-bold border-b-2 transition',
                        'text-[#FF7500] border-[#FF7500]' => $active,
                        'text-[#C04103] border-transparent hover:text-[var(--brand)] hover:border-[var(--brand)]' => ! $active,
                        ])
                        style="--brand: {{ $MenuBrand }};"
                        @if($active) x-ref="activeTab" @endif
                        >
                        {{ $item['label'] }}
                        </a>
                    </li>
                @endforeach

            </ul>
        </div>
    </div>
    </div>
</nav>
