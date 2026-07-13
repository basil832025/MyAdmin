@props(['src', 'class' => 'h-5 w-5'])
@unless(empty($src))
    <span
        {{ $attributes->merge([
            'class' => "inline-block $class [background-color:currentColor]
                        [mask:var(--i)_no-repeat_center/contain]
                        [-webkit-mask:var(--i)_no-repeat_center/contain]",
        ]) }}
        style="--i: url('{{ $src }}')"
    ></span>
@endunless
