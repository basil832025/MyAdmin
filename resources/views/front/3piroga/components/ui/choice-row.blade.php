@props(['value','left'=>'','right'=>''])

@php
    $valueStr = (string)$value;
@endphp

<button type="button"
        x-on:click="(() => { const parent = $el.closest('[x-data]'); if (parent && window.Alpine) { const data = window.Alpine.$data(parent); if (data && 'selected' in data) { data.selected = '{{ $valueStr }}'; } } })()"
        class="desk:w-[354px] md:w-[336px] w-[331px] flex items-center justify-between rounded-lg border px-3 py-2 transition-colors "
        x-bind:class="(() => { try { const parent = $el.closest('[x-data]'); if (parent && window.Alpine) { const data = window.Alpine.$data(parent); if (data && 'selected' in data && String(data.selected || '') === '{{ $valueStr }}') { return 'bg-[#FF7500] border-transparent text-white'; } } } catch(e) {} return 'bg-white border-[#E5E7EB] text-[#666666] hover:border-[#FF7500]/50'; })()">

    <span class="inline-flex items-center gap-2">
        {!! $left ?? '' !!}
    </span>

    <span class="inline-flex items-end gap-2">
        {!! $right ?? '' !!}
    </span>
</button>
