@props([
'variant' => 'header', // header | burger
])

@php
    $current = app()->getLocale();

    // Можно потом заменить на таблицу languages, но пока так
    $locales = [
        'uk' => 'UA',
        'ru' => 'RU',
        'en' => 'EN',
    ];

    $currentLabel = $locales[$current] ?? strtoupper($current);

    // В хидере обычно меню удобнее справа, в бургере — слева
    $menuSide = $variant === 'burger' ? 'left-0' : 'right-0';

    $btnClass = 'text-[14px] font-semibold leading-none';
@endphp

<div x-data="{ open:false }" class="relative select-none">
    <button
        type="button"
        class="flex items-center gap-2 {{ $btnClass }}"
        @click="open = !open"
        aria-label="Switch language"
    >
        <span>{{ $currentLabel }}</span>

        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd"
                  d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 10.94l3.71-3.71a.75.75 0 1 1 1.06 1.06l-4.24 4.25a.75.75 0 0 1-1.06 0L5.21 8.29a.75.75 0 0 1 .02-1.08z"
                  clip-rule="evenodd"/>
        </svg>
    </button>

    <div
        x-show="open"
        x-cloak
        @click.outside="open = false"
        class="absolute {{ $menuSide }} mt-2 w-20 overflow-hidden rounded-xl
               border border-black/10 bg-white shadow-lg"
    >
        @foreach($locales as $loc => $label)
            <a
                href="{{ route('lang.switch', $loc) }}"
                class="block px-3 py-2 text-sm hover:bg-black/5 {{ $current === $loc ? 'font-semibold' : '' }}"
            >
                {{ $label }}
            </a>
        @endforeach
    </div>
</div>
