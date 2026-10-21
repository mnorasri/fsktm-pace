@props(['label' => '', 'value' => '', 'hint' => null, 'tone' => 'brand', 'icon' => 'sparkles'])

@php
    $tones = [
        'brand' => ['bg-brand-50 text-brand-600', 'text-brand-700'],
        'emerald' => ['bg-emerald-50 text-emerald-600', 'text-emerald-700'],
        'amber' => ['bg-amber-50 text-amber-600', 'text-amber-700'],
        'rose' => ['bg-rose-50 text-rose-600', 'text-rose-700'],
        'sky' => ['bg-sky-50 text-sky-600', 'text-sky-700'],
        'slate' => ['bg-slate-100 text-slate-600', 'text-slate-700'],
    ];
    [$chip, $accent] = $tones[$tone] ?? $tones['brand'];
@endphp

<div class="card card-pad animate-rise">
    <div class="flex items-start justify-between gap-3">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $label }}</p>
            <p class="mt-1.5 text-3xl font-bold tracking-tight text-slate-800">{{ $value }}</p>
        </div>
        <span class="grid size-11 place-items-center rounded-2xl {{ $chip }}">
            <x-icon :name="$icon" class="size-5" />
        </span>
    </div>
    @if ($hint)
        <p class="mt-3 text-xs font-medium {{ $accent }}">{{ $hint }}</p>
    @endif
</div>
