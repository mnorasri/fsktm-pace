@props(['tone' => 'slate', 'dot' => false])

<span {{ $attributes->merge(['class' => 'badge-'.$tone]) }}>
    @if ($dot)
        <span class="size-1.5 rounded-full bg-current opacity-70"></span>
    @endif
    {{ $slot }}
</span>
