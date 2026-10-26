@extends('layouts.app')

@section('title', 'My Supervisees')

@section('content')
    <x-page-header title="My Supervisees"
        subtitle="Pick a supervisee to review their pre-registration and study plan">
        <x-slot:actions>
            <x-badge tone="brand" dot>{{ $activeSession }} · Semester {{ $activeSemester }}</x-badge>
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-stat-card label="Total Advisees" :value="$summary['advisees']" icon="users" tone="brand" />
        <x-stat-card label="Pending Study Plans" :value="$summary['pending_plans']" icon="calendar" tone="amber" hint="Awaiting your review" />
        <x-stat-card label="Registrations to Approve" :value="$summary['pending_registrations']" icon="book" tone="sky" hint="Students awaiting your decision" />
    </div>

    <div class="mt-6 grid grid-cols-1 items-start gap-4 xl:grid-cols-[19rem_minmax(0,1fr)]">

        {{-- ---------------------------------------------------------------
           | Supervisee list (master)
           --------------------------------------------------------------- --}}
        <aside class="card overflow-hidden xl:sticky xl:top-6">
            <div class="border-b border-slate-100 px-4 py-3">
                <div class="flex items-center justify-between gap-2">
                    <p class="text-xs font-bold uppercase tracking-widest text-slate-400">Supervisees</p>
                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-500">
                        {{ $students->count() }}
                    </span>
                </div>

                @if ($students->count() > 5)
                    <div class="relative mt-2.5">
                        <span class="pointer-events-none absolute inset-y-0 left-2.5 grid place-items-center text-slate-400">
                            <x-icon name="search" class="size-4" />
                        </span>
                        <input type="search" id="supervisee-search" placeholder="Search name or ID"
                            class="input h-9 pl-8 text-xs" autocomplete="off">
                    </div>
                @endif
            </div>

            <ul id="supervisee-list" class="max-h-[30rem] divide-y divide-slate-100 overflow-y-auto scroll-thin">
                @foreach ($students as $row)
                    @php($item = $row['student'])
                    @php($isActive = $selected && $selected['student']->id === $item->id)
                    <li data-supervisee-item data-search="{{ strtolower($item->name.' '.$item->student_id) }}">
                        <a href="{{ route('advisor.dashboard', ['student' => $item->id]) }}#detail"
                            data-student-id="{{ $item->id }}"
                            data-swap-detail="{{ route('advisor.dashboard.detail', ['student' => $item->id, 'tab' => $tab]) }}"
                            class="flex items-center gap-3 px-4 py-2.5 transition {{ $isActive ? 'bg-brand-50' : 'hover:bg-slate-50' }}">
                            <span class="grid size-9 shrink-0 place-items-center rounded-full text-xs font-bold text-white
                                    {{ $isActive ? 'bg-gradient-to-br from-brand-500 to-brand-700' : 'bg-gradient-to-br from-slate-600 to-slate-800' }}">
                                {{ strtoupper(substr($item->name, 0, 1)) }}
                            </span>

                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-semibold {{ $isActive ? 'text-brand-700' : 'text-slate-700' }}">
                                    {{ $item->name }}
                                </span>
                                <span class="block truncate text-[11px] text-slate-400">{{ $item->student_id }}</span>
                            </span>

                            <span class="flex shrink-0 items-center gap-1.5">
                                @if ($row['submission']?->status === 'submitted')
                                    <span class="size-2 rounded-full bg-amber-500" title="Registration awaiting approval"></span>
                                @elseif ($item->plan_status === 'submitted')
                                    <span class="size-2 rounded-full bg-sky-500" title="Study plan awaiting approval"></span>
                                @endif
                                <x-icon name="chevron-right"
                                    class="size-4 {{ $isActive ? 'text-brand-500' : 'text-slate-300' }}" />
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>

            <p id="supervisee-empty" class="hidden px-4 py-6 text-center text-xs text-slate-400">
                No supervisee matches your search.
            </p>

            @if ($students->isEmpty())
                <div class="p-4">
                    <x-empty-state title="No supervisees yet" icon="users"
                        message="Students assigned to you as their academic advisor will appear here." />
                </div>
            @endif
        </aside>

        {{-- ---------------------------------------------------------------
           | Selected supervisee (detail) — swapped in place, no page reload
           --------------------------------------------------------------- --}}
        <section id="detail" class="min-w-0">
            @if ($selected)
                @include('advisor.partials.detail')
            @else
                <x-empty-state title="Select a supervisee" icon="users"
                    message="Choose a student from the list to review their pre-registration." />
            @endif
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const search = document.getElementById('supervisee-search');
            const items = document.querySelectorAll('#supervisee-list [data-supervisee-item]');
            const empty = document.getElementById('supervisee-empty');
            const detail = document.getElementById('detail');

            if (search && items.length) {
                search.addEventListener('input', () => {
                    const term = search.value.trim().toLowerCase();
                    let visible = 0;

                    items.forEach((item) => {
                        const match = !term || (item.dataset.search || '').includes(term);
                        item.classList.toggle('hidden', !match);
                        if (match) visible++;
                    });

                    if (empty) empty.classList.toggle('hidden', visible > 0);
                });
            }

            if (!detail) return;

            const setActive = (studentId) => {
                document.querySelectorAll('#supervisee-list [data-supervisee-item]').forEach((li) => {
                    const link = li.querySelector('a');
                    if (!link) return;
                    const active = link.dataset.studentId === studentId;

                    link.classList.toggle('bg-brand-50', active);
                    link.classList.toggle('hover:bg-slate-50', !active);

                    const avatar = link.querySelector('span.grid');
                    if (avatar) {
                        avatar.classList.toggle('from-brand-500', active);
                        avatar.classList.toggle('to-brand-700', active);
                        avatar.classList.toggle('from-slate-600', !active);
                        avatar.classList.toggle('to-slate-800', !active);
                    }

                    const name = link.querySelector('span.min-w-0 span');
                    if (name) {
                        name.classList.toggle('text-brand-700', active);
                        name.classList.toggle('text-slate-700', !active);
                    }

                    const chevron = link.querySelector('svg');
                    if (chevron) {
                        chevron.classList.toggle('text-brand-500', active);
                        chevron.classList.toggle('text-slate-300', !active);
                    }
                });
            };

            const loadDetail = (url, pushUrl = null) => {
                fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then((response) => {
                        if (!response.ok) throw new Error(`HTTP ${response.status}`);
                        return response.text();
                    })
                    .then((html) => {
                        detail.innerHTML = html;
                        window.bindDynamicBehaviours?.(detail);
                        const match = url.match(/[?&]student=(\d+)/);
                        setActive(match ? match[1] : null);
                        if (pushUrl) history.pushState(null, '', pushUrl);
                    })
                    .catch(() => {
                        window.location.href = pushUrl ?? url;
                    });
            };

            document.addEventListener('click', (event) => {
                const link = event.target.closest?.('[data-swap-detail], [data-swap-tab]');
                if (!link) return;
                event.preventDefault();
                loadDetail(link.dataset.swapDetail ?? link.dataset.swapTab, link.getAttribute('href'));
            });
        });
    </script>
@endpush