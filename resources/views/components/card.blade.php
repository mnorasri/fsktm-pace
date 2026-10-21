@props(['title' => null, 'subtitle' => null, 'pad' => true, 'tone' => null])

@php
    $toneClass = match ($tone) {
        'brand' => 'from-brand-500 to-indigo-600',
        'emerald' => 'from-emerald-500 to-teal-600',
        'amber' => 'from-amber-500 to-orange-600',
        'rose' => 'from-rose-500 to-pink-600',
        'sky' => 'from-sky-500 to-blue-600',
        default => null,
    };
@endphp

<div {{ $attributes->merge(['class' => 'card overflow-hidden']) }}>
    @if ($title || isset($actions))
        <div class="card-header">
            <div class="flex items-center gap-3">
                @if ($toneClass)
                    <span class="grid size-9 place-items-center rounded-2xl bg-gradient-to-br {{ $toneClass }} text-white shadow-sm">
                        {{ $icon ?? '' }}
                    </span>
                @endif
                <div>
                    <h3 class="text-base font-semibold text-slate-800">{{ $title }}</h3>
                    @if ($subtitle)
                        <p class="text-sm text-slate-500">{{ $subtitle }}</p>
                    @endif
                </div>
            </div>
            @isset($actions)
                <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
            @endisset
        </div>
    @endif
    <div class="{{ $pad ? 'card-pad' : '' }}">{{ $slot }}</div>
</div>
