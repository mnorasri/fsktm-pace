@props(['href' => '#', 'active' => false, 'icon' => 'dot'])

<a href="{{ $href }}" @class(['nav-link', 'nav-link-active' => $active])>
    <x-icon :name="$icon" class="size-5 shrink-0" />
    <span class="truncate">{{ $slot }}</span>
</a>
