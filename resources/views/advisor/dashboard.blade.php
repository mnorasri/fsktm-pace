@extends('layouts.app')

@section('title', 'My Supervisees')

@section('content')
    <x-page-header title="My Supervisees"
        subtitle="Click a supervisee to expand their pre-registration and study plan">
        <x-slot:actions>
            <x-badge tone="brand" dot>{{ $activeSession }} · Semester {{ $activeSemester }}</x-badge>
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-stat-card label="Total Advisees" :value="$summary['advisees']" icon="users" tone="brand" />
        <x-stat-card label="Pending Study Plans" :value="$summary['pending_plans']" icon="calendar" tone="amber" hint="Awaiting your review" />
        <x-stat-card label="Registrations to Approve" :value="$summary['pending_registrations']" icon="book" tone="sky" hint="Students awaiting your decision" />
    </div>

    <div class="mt-6">
        @if ($students->count() > 5)
            <div class="relative mb-3">
                <span class="pointer-events-none absolute inset-y-0 left-2.5 grid place-items-center text-slate-400">
                    <x-icon name="search" class="size-4" />
                </span>
                <input type="search" id="supervisee-search" placeholder="Search name or ID"
                    class="input h-9 pl-8 text-xs" autocomplete="off">
            </div>
        @endif

        <div id="supervisee-list" class="space-y-3">
            @forelse ($students as $row)
                @php($student = $row['student'])
                @php($isSelected = $selected && $selected['student']->id === $student->id)
                <details name="supervisee" data-student-id="{{ $student->id }}"
                    {{ $isSelected ? 'open' : '' }}
                    class="group card overflow-hidden">
                    <summary class="flex cursor-pointer list-none items-center gap-4 p-5 transition hover:bg-slate-50/70">
                        <span class="grid size-11 shrink-0 place-items-center rounded-full bg-gradient-to-br from-slate-700 to-slate-900 text-sm font-bold text-white">
                            {{ strtoupper(substr($student->name, 0, 1)) }}
                        </span>

                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-semibold text-slate-800">{{ $student->name }}</span>
                            <span class="block truncate text-xs text-slate-400">
                                {{ $student->student_id }}
                                @if ($student->program) · {{ $student->program->code }} @endif
                                @if ($student->plan) · {{ $student->plan->code }} @endif
                            </span>
                        </span>

                        <span class="hidden w-40 shrink-0 md:block">
                            <span class="flex items-center justify-between text-[11px] font-semibold text-slate-400">
                                <span>{{ $row['credits']['earned'] }} / {{ $row['credits']['required'] }} cr</span>
                                <span>{{ $row['credits']['percent'] }}%</span>
                            </span>
                            <span class="mt-1 block h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
                                <span class="block h-full rounded-full bg-brand-500" style="width: {{ $row['credits']['percent'] }}%"></span>
                            </span>
                        </span>

                        <span class="flex shrink-0 items-center gap-1.5">
                            @if ($row['submission']?->status === 'submitted')
                                <x-badge tone="amber" dot>Registration to approve</x-badge>
                            @elseif ($student->plan_status === 'submitted')
                                <x-badge tone="sky" dot>Plan awaiting review</x-badge>
                            @endif
                            <x-badge :tone="['approved'=>'emerald','submitted'=>'amber','rejected'=>'rose','draft'=>'slate'][$student->plan_status] ?? 'slate'" dot>
                                Plan: {{ ucfirst($student->plan_status) }}
                            </x-badge>
                            <x-icon name="chevron-down" class="size-5 text-slate-400 transition group-open:rotate-180" />
                        </span>
                    </summary>

                    <div class="border-t border-slate-100 bg-slate-50/40 p-5">
                        @if ($isSelected)
                            @include('advisor.partials.detail')
                        @else
                            <div class="panel-content" data-loaded="0"></div>
                        @endif
                    </div>
                </details>
            @empty
                <x-empty-state title="No supervisees yet" icon="users"
                    message="Students assigned to you as their academic advisor will appear here." />
            @endforelse
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const search = document.getElementById('supervisee-search');
            const items = document.querySelectorAll('#supervisee-list [data-student-id]');
            const empty = document.getElementById('supervisee-empty');

            if (search && items.length) {
                search.addEventListener('input', () => {
                    const term = search.value.trim().toLowerCase();
                    let visible = 0;

                    items.forEach((el) => {
                        const text = (el.textContent || '').toLowerCase();
                        const match = !term || text.includes(term);
                        el.classList.toggle('hidden', !match);
                        if (match) visible++;
                    });

                    if (empty) empty.classList.toggle('hidden', visible > 0);
                });
            }

            /* ------------------------------------------------------------
             * Accordion: only one supervisee panel open at a time, and
             * panels that were not rendered server-side are fetched on
             * first open so the page stays light.
             * ---------------------------------------------------------- */
            document.querySelectorAll('details[name="supervisee"]').forEach((details) => {
                details.addEventListener('toggle', () => {
                    if (!details.open) return;

                    // Close every other panel.
                    document.querySelectorAll('details[name="supervisee"]').forEach((other) => {
                        if (other !== details && other.open) other.open = false;
                    });

                    const panel = details.querySelector('.panel-content');
                    if (panel && panel.dataset.loaded !== '1') {
                        const id = details.dataset.studentId;
                        fetch(`/advisor/detail?student=${id}&tab=registrations`, {
                            headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        })
                            .then((response) => (response.ok ? response.text() : Promise.reject(new Error(`HTTP ${response.status}`))))
                            .then((html) => {
                                panel.innerHTML = html;
                                panel.dataset.loaded = '1';
                                window.bindDynamicBehaviours?.(panel);
                            })
                            .catch(() => {
                                panel.innerHTML = '<p class="px-2 py-4 text-xs text-slate-400">Could not load this supervisee.</p>';
                            });
                    }

                    if (details.dataset.studentId) {
                        history.replaceState(null, '', `/advisor?student=${details.dataset.studentId}`);
                    }
                });
            });
        });
    </script>
@endpush