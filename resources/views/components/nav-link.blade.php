@props(['href' => '#', 'active' => false, 'icon' => 'dot'])

<a href="{{ $href }}" aria-current="{{ $active ? 'page' : 'false' }}" @class(['nav-link', 'nav-link-active' => $active])>
    <x-icon :name="$icon" class="size-5 shrink-0" />
    <span class="min-w-0 leading-5">{{ $slot }}</span>
</a>
