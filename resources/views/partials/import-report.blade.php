@if (session('import_report'))
    @php
        $report = session('import_report');
        $failed = $report['failed'];
    @endphp

    <div data-import-report
        class="mb-5 overflow-hidden rounded-2xl border {{ $failed > 0 ? 'border-amber-200 bg-amber-50' : 'border-emerald-200 bg-emerald-50' }}">
        <div class="flex items-start gap-3 p-4">
            <span
                class="grid size-9 shrink-0 place-items-center rounded-xl {{ $failed > 0 ? 'bg-amber-100 text-amber-600' : 'bg-emerald-100 text-emerald-600' }}">
                <x-icon :name="$failed > 0 ? 'warning' : 'check'" class="size-5" />
            </span>

            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold {{ $failed > 0 ? 'text-amber-900' : 'text-emerald-900' }}">
                    {{ $report['title'] }}
                </p>
                <p class="mt-1 text-xs {{ $failed > 0 ? 'text-amber-800' : 'text-emerald-800' }}">
                    <span class="font-semibold">{{ $report['created'] }}</span> created,
                    <span class="font-semibold">{{ $report['updated'] }}</span> updated,
                    <span class="font-semibold">{{ $failed }}</span> failed.
                </p>

                @if (! empty($report['failures']))
                    <ul
                        class="mt-3 max-h-56 space-y-1.5 overflow-y-auto rounded-xl border border-amber-100 bg-white/80 p-3 text-[11px] leading-relaxed text-amber-900">
                        @foreach ($report['failures'] as $failure)
                            <li class="font-mono break-words">{{ $failure }}</li>
                        @endforeach
                    </ul>

                    @if ($report['truncated'] > 0)
                        <p class="mt-2 text-[11px] font-medium text-amber-800">
                            …and {{ $report['truncated'] }} more failed row(s).
                        </p>
                    @endif
                @endif
            </div>

            <button type="button" data-import-report-close
                class="rounded-lg p-1 text-slate-400 transition hover:bg-white" aria-label="Dismiss import report">
                <x-icon name="x" class="size-4" />
            </button>
        </div>
    </div>
@endif
