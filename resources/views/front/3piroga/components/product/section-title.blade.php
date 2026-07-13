@props(['as' => 'h2', 'class' => '', 'underline' => true, 'size' => 'default'])
@php
    $sizeClasses = match ($size) {
        'catalog-subcategory' => 'text-[30px] leading-[30px]',
        default => 'text-[36px] leading-[36px]',
    };

    $sizeStyle = match ($size) {
        'catalog-subcategory' => 'font-size: 30px; line-height: 30px;',
        default => 'font-size: 36px; line-height: 36px;',
    };
@endphp
<{{ $as }} {{ $attributes->merge([
    'class' => 'inline-block font-intro '.$sizeClasses.' font-bold text-[#19191A] '.($underline ? 'border-b-2 border-[#FF7500] ' : '').$class,
    'style' => $sizeStyle,
]) }}>
{{ $slot }}
</{{ $as }}>
