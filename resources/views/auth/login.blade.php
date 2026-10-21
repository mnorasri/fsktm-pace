<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Sign in · {{ config('app.name', 'FSKTM PACE System') }}</title>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="h-full bg-slate-100 font-sans text-slate-800 antialiased">
        <div class="grid min-h-full lg:grid-cols-2">
            {{-- Brand panel --}}
            <div class="relative hidden flex-col justify-between overflow-hidden bg-gradient-to-br from-brand-700 via-indigo-700 to-slate-900 p-12 text-white lg:flex">
                <div class="absolute -right-16 -top-16 size-72 rounded-full bg-white/10 blur-2xl"></div>
                <div class="absolute -bottom-24 -left-10 size-80 rounded-full bg-brand-400/20 blur-3xl"></div>

                <div class="relative space-y-6">
                    {{-- University mark --}}
                    <div class="inline-flex rounded-2xl bg-white px-5 py-4 shadow-xl shadow-slate-900/25">
                        <img src="{{ asset('images/uthm-logo.png') }}" alt="Universiti Tun Hussein Onn Malaysia"
                            class="h-16 w-auto">
                    </div>

                    <div class="flex items-center gap-3">
                        <span class="grid size-12 place-items-center rounded-2xl bg-white/15 backdrop-blur">
                            <x-icon name="academic-cap" class="size-7" />
                        </span>
                        <div>
                            <p class="text-lg font-bold leading-tight">FSKTM PACE System</p>
                            <p class="text-sm text-white/70">Pre-registration &amp; Academic Curriculum Load</p>
                        </div>
                    </div>
                </div>

                <div class="relative space-y-6">
                    <h1 class="max-w-md text-4xl font-bold leading-tight">
                        Plan, advise and approve — all in one academic workspace.
                    </h1>
                    <p class="max-w-md text-white/70">
                        A unified portal for students, academic advisors and heads of department to manage
                        curriculum plans, course offerings and pre-registration.
                    </p>

                    <div class="grid max-w-md grid-cols-1 gap-3 sm:grid-cols-2">
                        @foreach ([['Student', 'Plan &amp; register'], ['Advisor / HoD', 'Advise &amp; offer']] as [$label, $desc])
                            <div class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur">
                                <p class="text-sm font-semibold">{{ $label }}</p>
                                <p class="text-xs text-white/60">{!! $desc !!}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                <p class="relative text-xs text-white/50">
                    Faculty of Computer Science &amp; Information Technology
                </p>
            </div>

            {{-- Form panel --}}
            <div class="flex items-center justify-center p-6 sm:p-10">
                <div class="w-full max-w-md">
                    <div class="mb-6 lg:hidden">
                        <img src="{{ asset('images/uthm-logo.png') }}" alt="Universiti Tun Hussein Onn Malaysia"
                            class="h-12 w-auto">
                    </div>

                    <div class="mb-8 flex items-center gap-3 lg:hidden">
                        <span class="grid size-11 place-items-center rounded-2xl bg-gradient-to-br from-brand-500 to-indigo-600 text-white shadow-lg shadow-brand-600/30">
                            <x-icon name="academic-cap" class="size-6" />
                        </span>
                        <div>
                            <p class="text-base font-bold leading-tight text-slate-900">FSKTM PACE System</p>
                            <p class="text-xs text-slate-400">Curriculum Load System</p>
                        </div>
                    </div>

                    <div class="card card-pad">
                        <h2 class="text-2xl font-bold tracking-tight text-slate-900">Sign in</h2>
                        <p class="mt-1 text-sm text-slate-500">Use your FSKTM PACE account to continue.</p>

                        @if ($errors->any())
                            <div class="mt-5 flex items-start gap-2.5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                                <x-icon name="warning" class="mt-0.5 size-4 shrink-0" />
                                <span>{{ $errors->first() }}</span>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('login.store') }}" class="mt-6 space-y-4">
                            @csrf

                            <label class="block">
                                <span class="label">Email address</span>
                                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                                    autocomplete="username" placeholder="you@fsktm.test" class="input">
                                @error('email')
                                    <span class="mt-1 block text-xs font-medium text-rose-600">{{ $message }}</span>
                                @enderror
                            </label>

                            <label class="block">
                                <span class="label">Password</span>
                                <input id="password" type="password" name="password" required autocomplete="current-password"
                                    placeholder="••••••••" class="input">
                            </label>

                            <label class="flex items-center gap-2 text-sm text-slate-600">
                                <input type="checkbox" name="remember" class="checkbox">
                                Keep me signed in
                            </label>

                            <button type="submit" class="btn-primary w-full">
                                <x-icon name="logout" class="size-4" />
                                Sign in
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
