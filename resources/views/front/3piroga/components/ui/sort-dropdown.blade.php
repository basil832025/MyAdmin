@props([
// подписи
'labels' => [
'popular'       => st('catalog.sort.popular',        'Популярні'),
'new'           => st('catalog.sort.new',            'Новинки'),
'price_asc'     => st('catalog.sort.price_asc',      'Ціна: зростання'),
'price_desc'    => st('catalog.sort.price_desc',     'Ціна: спадання'),
'discount_asc'  => st('catalog.sort.discount_asc',   'Знижка: зростання'),
'discount_desc' => st('catalog.sort.discount_desc',  'Знижка: спадання'),
],
// имя параметра в query string
'param' => 'sort',
// текущее значение (если не передали — возьмём из query)
'value' => null,
// ширина по макету
'width' => 'w-[267px]',
])

@php
    $current = $value ?? request($param, null);
    // Если сортировка не выбрана, показываем "Сортувати"
    $defaultLabel = st('catalog.sort.default', 'Сортувати');
    // URL для сброса сортировки (удаляем параметр)
    $queryParams = request()->query();
    unset($queryParams[$param]);
    $resetUrl = request()->url() . (!empty($queryParams) ? '?' . http_build_query($queryParams) : '');
@endphp

<div {{ $attributes->merge(['class' => "relative $width"]) }} 
     x-data="{ open:false }"
     x-init="
     // гарантируем, что меню закрыто при инициализации
     open = false;
     // закрываем меню при навигации (popstate - назад/вперед)
     window.addEventListener('popstate', () => { open = false; });
     // закрываем меню при загрузке страницы (только один раз)
     $nextTick(() => { 
         if (open) open = false; 
     });
     // закрываем меню при видимости страницы
     document.addEventListener('visibilitychange', () => { 
         if (!document.hidden && open) { open = false; } 
     });
     ">
    <!-- Кнопка -->
    <button type="button" @click="open = !open"
            class="w-full h-10 rounded-[12px] border border-[#E5E7EB] bg-white
                 px-3 flex items-center justify-between">
        <span class="font-bold text-[16px] leading-none text-[#19191A]">
            {{ $current && isset($labels[$current]) ? $labels[$current] : $defaultLabel }}
        </span>
        <svg class="w-5 h-5 text-[#19191A] transition-transform"
             :class="open ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </button>

    <!-- Меню -->
    <div x-show="open" 
         x-cloak
         x-transition.origin.top.left @click.outside="open=false"
         class="absolute z-20 mt-2 w-full bg-white rounded-2xl border border-[#E5E7EB] shadow-lg p-2">
        {{-- Первый пункт "Сортувати" (сброс сортировки) --}}
        <a href="{{ $resetUrl }}"
           @click="open=false"
           class="block px-3 py-2 rounded-[10px] text-[15px] text-[#19191A]
            hover:bg-neutral-100 {{ !$current ? 'bg-[#FFE6B8] font-semibold' : '' }}">
            {{ $defaultLabel }}
        </a>
        @foreach($labels as $key => $label)
            <a href="{{ request()->fullUrlWithQuery([$param => $key]) }}"
               @click="open=false"
               class="block px-3 py-2 rounded-[10px] text-[15px] text-[#19191A]
                hover:bg-neutral-100 {{ $current === $key ? 'bg-[#FFE6B8] font-semibold' : '' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>
</div>
