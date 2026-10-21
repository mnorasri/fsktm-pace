@php
    $toasts = [];

    if (session('status')) {
        $toasts[] = ['tone' => 'emerald', 'icon' => 'check', 'text' => session('status')];
    }
    if (session('notice')) {
        $toasts[] = ['tone' => 'sky', 'icon' => 'info', 'text' => session('notice')];
    }
    if (session('reset_password')) {
        $toasts[] = [
            'tone' => 'sky',
            'icon' => 'lock',
            'text' => 'Password for '.session('reset_password')['name'].' reset to: '.session('reset_password')['password'],
        ];
    }
    if ($errors->any()) {
        foreach ($errors->all() as $error) {
            $toasts[] = ['tone' => 'rose', 'icon' => 'warning', 'text' => $error];
        }
    }
@endphp

<div class="pointer-events-none fixed right-4 top-4 z-[60] flex w-full max-w-sm flex-col gap-2 print:hidden">
    @foreach ($toasts as $toast)
        @php
            $tone = match ($toast['tone']) {
                'rose' => 'border-rose-200 text-rose-700',
                'sky' => 'border-sky-200 text-sky-700',
                default => 'border-emerald-200 text-emerald-700',
            };
            $chip = match ($toast['tone']) {
                'rose' => 'bg-rose-100 text-rose-600',
                'sky' => 'bg-sky-100 text-sky-600',
                default => 'bg-emerald-100 text-emerald-600',
            };
        @endphp
        <div data-toast class="toast {{ $tone }} animate-rise">
            <span class="grid size-8 shrink-0 place-items-center rounded-xl {{ $chip }}">
                <x-icon :name="$toast['icon']" class="size-4" />
            </span>
            <p class="flex-1 pt-0.5 text-sm font-medium leading-snug">{{ $toast['text'] }}</p>
            <button type="button" data-toast-close class="rounded-lg p-1 text-slate-400 transition hover:bg-slate-100">
                <x-icon name="x" class="size-4" />
            </button>
        </div>
    @endforeach
</div>
