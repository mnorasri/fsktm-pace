@php
    $user = auth()->user();
@endphp

<header class="sticky top-0 z-20 border-b border-slate-200/70 bg-white/80 backdrop-blur-xl">
    <div class="flex items-center gap-3 px-4 py-3.5 sm:px-6 lg:px-8">
        <button type="button" data-sidebar-open class="rounded-xl border border-slate-200 p-2 text-slate-500 transition hover:bg-slate-50 lg:hidden">
            <x-icon name="menu" class="size-5" />
        </button>

        <div class="min-w-0">
            <p class="truncate text-sm font-semibold text-slate-800">
                {{ $pageTitle ?? 'Dashboard' }}
            </p>
            <p class="truncate text-[11px] text-slate-400">
                {{ $user->isStudent() ? ($user->student_id ? 'Student ID '.$user->student_id : 'Student') : $user->roleLabel() }}
            </p>
        </div>

        <div class="ml-auto flex items-center gap-2">
            <span class="hidden items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 sm:inline-flex">
                <span class="size-2 animate-pulse rounded-full bg-emerald-500"></span>
                {{ \App\Models\Setting::get('active_session', '2025/2026') }} · Sem {{ \App\Models\Setting::get('active_semester', '1') }}
            </span>

            <div class="relative" data-menu>
                <button type="button" data-menu-toggle class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-2.5 py-1.5 transition hover:bg-slate-50">
                    <span class="grid size-7 place-items-center rounded-full bg-gradient-to-br from-brand-500 to-indigo-600 text-[11px] font-bold text-white">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </span>
                    <x-icon name="chevron-down" class="size-4 text-slate-400" />
                </button>
                <div data-menu-panel class="absolute right-0 z-30 mt-2 hidden w-60 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl">
                    <div class="border-b border-slate-100 px-4 py-3">
                        <p class="text-sm font-semibold text-slate-800">{{ $user->name }}</p>
                        <p class="text-xs text-slate-400">{{ $user->roleLabel() }}</p>
                    </div>
                    <a href="{{ route('profile') }}"
                        class="flex w-full items-center gap-2.5 border-b border-slate-100 px-4 py-3 text-sm font-medium text-slate-600 transition hover:bg-slate-50 hover:text-brand-700">
                        <x-icon name="user" class="size-4" />
                        My Profile
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex w-full items-center gap-2.5 px-4 py-3 text-sm font-medium text-slate-600 transition hover:bg-rose-50 hover:text-rose-600">
                            <x-icon name="logout" class="size-4" />
                            Sign out
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>
