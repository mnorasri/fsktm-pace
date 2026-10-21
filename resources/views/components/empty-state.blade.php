@props(['title' => 'Nothing here yet', 'message' => null, 'icon' => 'sparkles'])

<div class="flex flex-col items-center justify-center gap-3 rounded-2xl border border-dashed border-slate-200 bg-slate-50/50 px-6 py-12 text-center">
    <span class="grid size-12 place-items-center rounded-2xl bg-white text-slate-400 shadow-sm">
        <x-icon :name="$icon" class="size-6" />
    </span>
    <p class="text-sm font-semibold text-slate-700">{{ $title }}</p>
    @if ($message)
        <p class="max-w-sm text-sm text-slate-500">{{ $message }}</p>
    @endif
    @isset($slot)
        <div class="mt-1">{{ $slot }}</div>
    @endisset
</div>
