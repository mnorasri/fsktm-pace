<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Change your password · {{ config('app.name', 'FSKTM PACE System') }}</title>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="h-full bg-slate-100 font-sans text-slate-800 antialiased">
        <div class="flex min-h-full items-center justify-center p-6">
            <div class="w-full max-w-lg">
                <div class="mb-6 flex items-center gap-3">
                    <span class="grid size-11 place-items-center rounded-2xl bg-gradient-to-br from-brand-500 to-indigo-600 text-white shadow-lg shadow-brand-600/30">
                        <x-icon name="academic-cap" class="size-6" />
                    </span>
                    <div>
                        <p class="text-base font-bold leading-tight text-slate-900">FSKTM PACE System</p>
                        <p class="text-xs text-slate-400">Curriculum Load System</p>
                    </div>
                </div>

                <div class="card card-pad">
                    <div class="flex items-start gap-3">
                        <span class="grid size-10 shrink-0 place-items-center rounded-2xl bg-amber-50 text-amber-600">
                            <x-icon name="lock" class="size-5" />
                        </span>
                        <div>
                            <h1 class="text-xl font-bold tracking-tight text-slate-900">Choose a new password</h1>
                            <p class="mt-1 text-sm text-slate-500">
                                You signed in with the faculty default password. For your account's security,
                                set your own password before continuing.
                            </p>
                        </div>
                    </div>

                    @if (session('status'))
                        <div class="mt-5 flex items-start gap-2.5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                            <x-icon name="info" class="mt-0.5 size-4 shrink-0" />
                            <span>{{ session('status') }}</span>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="mt-5 flex items-start gap-2.5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                            <x-icon name="warning" class="mt-0.5 size-4 shrink-0" />
                            <span>{{ $errors->first() }}</span>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('password.change.update') }}" class="mt-6 space-y-4">
                        @csrf
                        @method('PUT')

                        <label class="block">
                            <span class="label">Current (default) password</span>
                            <input type="password" name="current_password" required autofocus
                                autocomplete="current-password" class="input" placeholder="••••••••">
                        </label>

                        <label class="block">
                            <span class="label">New password</span>
                            <input type="password" name="password" required autocomplete="new-password"
                                class="input" placeholder="At least 8 characters">
                        </label>

                        <label class="block">
                            <span class="label">Confirm new password</span>
                            <input type="password" name="password_confirmation" required autocomplete="new-password"
                                class="input" placeholder="Repeat the new password">
                        </label>

                        <button type="submit" class="btn-primary w-full">
                            <x-icon name="check" class="size-4" />
                            Save new password
                        </button>
                    </form>

                    <div class="mt-5 flex items-center justify-between gap-3 border-t border-slate-100 pt-4">
                        <p class="truncate text-xs text-slate-400">
                            Signed in as <span class="font-semibold text-slate-600">{{ $user->email }}</span>
                        </p>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn-ghost btn-sm">
                                <x-icon name="logout" class="size-3.5" /> Sign out
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
