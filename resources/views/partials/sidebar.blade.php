@php
    $user = auth()->user();
    $roleLabel = $user->roleLabel();
    $roleTone = match (true) {
        $user->isAdmin() => 'sky',
        $user->isHod() => 'amber',
        $user->isAdvisor() => 'emerald',
        default => 'brand',
    };
@endphp

<aside id="app-sidebar"
    class="fixed inset-y-0 left-0 z-40 flex w-72 -translate-x-full flex-col border-r border-slate-200/70 bg-white/95 backdrop-blur-xl transition-transform duration-300 lg:translate-x-0">

    {{-- Brand --}}
    <div class="flex items-center gap-3 border-b border-slate-100 px-5 py-5">
        <span class="grid size-11 place-items-center rounded-2xl bg-gradient-to-br from-brand-500 to-indigo-600 text-white shadow-lg shadow-brand-600/30">
            <x-icon name="academic-cap" class="size-6" />
        </span>
        <div class="min-w-0">
            <p class="truncate text-sm font-bold leading-tight text-slate-900">FSKTM PACE</p>
            <p class="truncate text-[11px] font-medium text-slate-400">Curriculum Load System</p>
        </div>
        <button type="button" data-sidebar-close class="ml-auto rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 lg:hidden">
            <x-icon name="x" class="size-5" />
        </button>
    </div>

    {{-- Term chip --}}
    <div class="px-4 pt-4">
        <div class="glass flex items-center justify-between rounded-2xl px-3.5 py-2.5 text-xs">
            <span class="font-semibold text-slate-500">Active Term</span>
            <span class="font-bold text-brand-700">{{ \App\Models\Setting::get('active_session', '2025/2026') }} · Sem {{ \App\Models\Setting::get('active_semester', '1') }}</span>
        </div>
    </div>

    <nav class="flex-1 space-y-1 overflow-y-auto px-4 py-4 scroll-thin">
        @if ($user->isAdmin())
            <p class="nav-group-label">Administration</p>
            <x-nav-link :href="route('admin.dashboard')" icon="home" :active="request()->routeIs('admin.dashboard')">Dashboard</x-nav-link>
            <x-nav-link :href="route('admin.configs.index')" icon="cog" :active="request()->routeIs('admin.configs.*')">Manage Configs</x-nav-link>
            <x-nav-link :href="route('admin.courses.index')" icon="book" :active="request()->routeIs('admin.courses.*')">Manage Courses</x-nav-link>

            <p class="nav-group-label">People</p>
            <x-nav-link :href="route('admin.lecturers.index')" icon="briefcase" :active="request()->routeIs('admin.lecturers.*')">Manage Lecturers</x-nav-link>
            <x-nav-link :href="route('admin.students.index')" icon="users" :active="request()->routeIs('admin.students.*')">Manage Students</x-nav-link>
            <x-nav-link :href="route('admin.hod.index')" icon="identification" :active="request()->routeIs('admin.hod.*')">Manage HoD</x-nav-link>
            <x-nav-link :href="route('admin.head-of-program.index')" icon="academic-cap" :active="request()->routeIs('admin.head-of-program.*')">Manage Head of Program</x-nav-link>
        @endif

        @if ($user->isAdvisor())
            <p class="nav-group-label">Advising</p>
            <x-nav-link :href="route('advisor.dashboard')" icon="users" :active="request()->routeIs('advisor.dashboard')">My Supervisees</x-nav-link>

            @if ($user->isHod())
                <p class="nav-group-label">Head of Department</p>
                <x-nav-link :href="route('advisor.offering.manage')" icon="clipboard" :active="request()->routeIs('advisor.offering.manage')">Manage Offering</x-nav-link>
                <x-nav-link :href="route('advisor.offering.grid')" icon="layers" :active="request()->routeIs('advisor.offering.grid')">Course Offering</x-nav-link>
                <x-nav-link :href="route('advisor.offering.workload')" icon="chart" :active="request()->routeIs('advisor.offering.workload')">Lecturer Workload</x-nav-link>
            @endif
        @endif

        @if ($user->isStudent())
            <p class="nav-group-label">My Studies</p>
            <x-nav-link :href="route('student.dashboard')" icon="home" :active="request()->routeIs('student.dashboard')">Dashboard</x-nav-link>
            <x-nav-link :href="route('student.study-plan')" icon="calendar" :active="request()->routeIs('student.study-plan')">Study Plan</x-nav-link>
            <x-nav-link :href="route('student.pre-registration')" icon="book" :active="request()->routeIs('student.pre-registration')">Pre-Registration</x-nav-link>
            <x-nav-link :href="route('student.slip')" icon="printer" :active="request()->routeIs('student.slip')">Registration Slip</x-nav-link>
        @endif

        <p class="nav-group-label">Account</p>
        <x-nav-link :href="route('profile')" icon="user" :active="request()->routeIs('profile*')">My Profile</x-nav-link>
    </nav>

    {{-- User card --}}
    <div class="border-t border-slate-100 p-4">
        <div class="flex items-center gap-3 rounded-2xl bg-slate-50 px-3.5 py-3">
            <span class="grid size-10 place-items-center rounded-full bg-gradient-to-br from-slate-700 to-slate-900 text-sm font-bold text-white">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </span>
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-semibold text-slate-800">{{ $user->name }}</p>
                <p class="truncate text-[11px] text-slate-400">{{ $user->email }}</p>
            </div>
        </div>
        <div class="mt-2 flex items-center justify-between gap-2">
            <x-badge :tone="$roleTone">{{ $roleLabel }}</x-badge>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-slate-500 transition hover:bg-rose-50 hover:text-rose-600">
                    <x-icon name="logout" class="size-4" />
                    Sign out
                </button>
            </form>
        </div>
    </div>
</aside>
