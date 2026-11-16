<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', 'Dashboard') · {{ config('app.name', 'FSKTM PACE System') }}</title>

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="h-full bg-slate-100 font-sans text-slate-800 antialiased">
        @php($pageTitle = trim(strip_tags($__env->yieldContent('title'))) ?: 'Dashboard')
        <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[60] focus:rounded-xl focus:bg-white focus:px-4 focus:py-3 focus:text-brand-700">Skip to content</a>
        <div class="flex min-h-full">
            @include('partials.sidebar')

            <div id="sidebar-backdrop" class="fixed inset-0 z-30 hidden bg-slate-900/40 backdrop-blur-sm lg:hidden"></div>

            <div class="flex min-w-0 flex-1 flex-col lg:pl-72">
                @include('partials.topbar')

                <main id="main-content" tabindex="-1" class="flex-1 px-4 py-6 sm:px-6 lg:px-8">
                    @yield('content')
                </main>

                <footer class="border-t border-slate-200/70 px-4 py-5 text-center text-xs text-slate-400 sm:px-6 lg:px-8">
                    FSKTM PACE System · Pre-registration &amp; Academic Curriculum Load
                    <span class="mx-1">·</span>
                    <span class="font-mono">{{ now()->format('d M Y') }}</span>
                </footer>
            </div>
        </div>

        @include('partials.flash')

        @stack('modals')
        @stack('scripts')
    </body>
</html>
