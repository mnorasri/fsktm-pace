@props(['id', 'title' => null, 'subtitle' => null, 'size' => 'md'])

@php
    $widths = [
        'sm' => 'max-w-md',
        'md' => 'max-w-xl',
        'lg' => 'max-w-3xl',
        'xl' => 'max-w-5xl',
    ];
@endphp

<div id="{{ $id }}" data-modal class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 p-4 backdrop-blur-sm">
    <div class="max-h-[90vh] w-full {{ $widths[$size] }} overflow-y-auto rounded-3xl bg-white shadow-2xl scroll-thin">
        <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
            <div>
                <h3 data-modal-title class="text-lg font-semibold text-slate-800">{{ $title }}</h3>
                @if ($subtitle)
                    <p class="text-sm text-slate-500">{{ $subtitle }}</p>
                @endif
            </div>
            <button type="button" data-modal-close class="rounded-xl p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600">
                <x-icon name="x" class="size-5" />
            </button>
        </div>

        <div class="px-6 py-5">{{ $slot }}</div>

        @isset($footer)
            <div class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-100 bg-slate-50/70 px-6 py-4">
                {{ $footer }}
            </div>
        @endisset
    </div>
</div>
