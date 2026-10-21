@extends('layouts.app')

@section('title', 'My Profile')

@php
    $roleTone = match (true) {
        $user->isAdmin() => 'sky',
        $user->isHod() => 'amber',
        $user->isAdvisor() => 'emerald',
        default => 'brand',
    };

    // Everything the account carries but the user cannot edit here.
    $account = collect([
        ['Role', $user->roleLabel()],
        $user->isStudent()
            ? ['Student ID', $user->student_id ?: '—']
            : ['Staff ID', $user->staff_id ?: '—'],
        $user->program ? ['Programme', $user->program->code.' · '.$user->program->name] : null,
        $user->plan ? ['Study plan', $user->plan->code.' · '.$user->plan->name] : null,
        $user->department ? ['Department', $user->department->code.' · '.$user->department->name] : null,
        $user->isStudent() ? ['Advisor', $user->advisor?->name ?? 'Not assigned'] : null,
        ['Account status', $user->status === 'active' ? 'Active' : 'Deactivated'],
        ['Member since', $user->created_at?->format('d M Y') ?? '—'],
    ])->filter()->values();
@endphp

@section('content')
    <x-page-header title="My Profile"
        subtitle="View your account details and keep your name, contact information and password up to date.">
        <x-slot:actions>
            <x-badge :tone="$roleTone" dot>{{ $user->roleLabel() }}</x-badge>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-card pad title="Personal information"
                subtitle="Your name and contact details, as they appear across the system.">
                <form method="POST" action="{{ route('profile.update') }}" class="grid gap-4 sm:grid-cols-2">
                    @csrf
                    @method('PUT')

                    <div class="sm:col-span-2">
                        <label class="block">
                            <span class="label">Full name</span>
                            <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="input">
                            @error('name')
                                <span class="mt-1 block text-xs font-medium text-rose-600">{{ $message }}</span>
                            @enderror
                        </label>
                    </div>

                    <x-field name="email" label="Email address" type="email" :value="$user->email" required
                        placeholder="you@fsktm.test" />

                    <x-field name="phone" label="Phone number" :value="$user->phone" placeholder="e.g. 012-3456789" />

                    <div class="sm:col-span-2 flex items-center justify-between gap-3 border-t border-slate-100 pt-4">
                        <p class="text-xs text-slate-400">
                            Your email address is also the one you sign in with.
                        </p>
                        <button type="submit" class="btn-primary">
                            <x-icon name="check" class="size-4" />
                            Save changes
                        </button>
                    </div>
                </form>
            </x-card>

            <x-card pad title="Change password"
                subtitle="Enter your current password, then choose a new one of at least 6 characters.">
                <form method="POST" action="{{ route('profile.password') }}" class="grid gap-4 sm:grid-cols-2">
                    @csrf
                    @method('PUT')

                    <div class="sm:col-span-2">
                        <label class="block">
                            <span class="label">Current password</span>
                            <input type="password" name="current_password" required autocomplete="current-password"
                                placeholder="••••••••" class="input">
                            @error('current_password')
                                <span class="mt-1 block text-xs font-medium text-rose-600">{{ $message }}</span>
                            @enderror
                        </label>
                    </div>

                    <label class="block">
                        <span class="label">New password</span>
                        <input type="password" name="password" required autocomplete="new-password"
                            placeholder="••••••••" class="input">
                        @error('password')
                            <span class="mt-1 block text-xs font-medium text-rose-600">{{ $message }}</span>
                        @enderror
                    </label>

                    <label class="block">
                        <span class="label">Confirm new password</span>
                        <input type="password" name="password_confirmation" required autocomplete="new-password"
                            placeholder="••••••••" class="input">
                    </label>

                    <div class="sm:col-span-2 flex items-center justify-between gap-3 border-t border-slate-100 pt-4">
                        <p class="flex items-center gap-1.5 text-xs text-slate-400">
                            <x-icon name="info" class="size-3.5" />
                            You stay signed in after changing your password.
                        </p>
                        <button type="submit" class="btn-primary">
                            <x-icon name="lock" class="size-4" />
                            Update password
                        </button>
                    </div>
                </form>
            </x-card>
        </div>

        <div class="space-y-6">
            <x-card pad title="Account" subtitle="Set by the faculty administrator.">
                <dl class="divide-y divide-slate-100">
                    @foreach ($account as [$label, $value])
                        <div class="flex items-start justify-between gap-4 py-2.5">
                            <dt class="text-[11px] font-bold uppercase tracking-wider text-slate-400">{{ $label }}</dt>
                            <dd class="max-w-[60%] text-right text-sm font-medium text-slate-700">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            </x-card>
        </div>
    </div>
@endsection
