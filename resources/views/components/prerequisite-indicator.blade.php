@props(['prerequisites' => []])

@if (count($prerequisites) > 0)
    @php($unmet = collect($prerequisites)->contains(fn ($prerequisite) => ! $prerequisite['met']))
    <div {{ $attributes->class(['mt-2 rounded-xl border p-2.5', 'border-amber-200 bg-amber-50' => $unmet, 'border-emerald-200 bg-emerald-50' => ! $unmet]) }}>
        <p class="flex items-center gap-1.5 text-xs font-bold {{ $unmet ? 'text-amber-800' : 'text-emerald-800' }}">
            <x-icon :name="$unmet ? 'warning' : 'check'" class="size-3.5 shrink-0" />
            {{ $unmet ? 'Prerequisite warning — not yet fulfilled' : 'Prerequisites fulfilled' }}
        </p>
        <ul class="mt-1.5 flex flex-wrap gap-1.5">
            @foreach ($prerequisites as $prerequisite)
                <li>
                    <x-badge :tone="$prerequisite['met'] ? 'emerald' : 'amber'">
                        {{ $prerequisite['code'] }} · {{ str($prerequisite['status'])->replace('_', ' ')->lower()->ucfirst() }}
                    </x-badge>
                </li>
            @endforeach
        </ul>
        @if ($unmet)
            <p class="mt-1.5 text-[11px] text-amber-800">Registration is allowed. Student and advisor should review these prerequisite statuses.</p>
        @endif
    </div>
@endif
