@extends('layouts.app')

@section('title', 'Manage Configs')

@php
    $deptOptions = $departments->map(fn ($d) => ['value' => $d->id, 'label' => $d->code.' — '.$d->name])->all();
    $programOptions = $programs->map(fn ($p) => ['value' => $p->id, 'label' => $p->code.' — '.$p->name])->all();

    $sections = [
        'departments' => [
            'title' => 'Departments',
            'subtitle' => 'Faculties and academic departments',
            'icon' => 'building',
            'items' => $departments,
            'fields' => [
                ['name' => 'code', 'label' => 'Code', 'type' => 'text', 'required' => true],
                ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true, 'full' => true],
                ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'full' => true],
            ],
        ],
        'programs' => [
            'title' => 'Study Programmes',
            'subtitle' => 'Degree and diploma programmes',
            'icon' => 'academic-cap',
            'items' => $programs,
            'fields' => [
                ['name' => 'department_id', 'label' => 'Department', 'type' => 'select', 'options' => $deptOptions],
                ['name' => 'code', 'label' => 'Code', 'type' => 'text', 'required' => true],
                ['name' => 'name', 'label' => 'Programme Name', 'type' => 'text', 'required' => true, 'full' => true],
                ['name' => 'level', 'label' => 'Level', 'type' => 'text'],
                ['name' => 'duration_years', 'label' => 'Duration (years)', 'type' => 'number', 'required' => true, 'step' => '0.5', 'min' => '1', 'max' => '8'],
                ['name' => 'total_credits', 'label' => 'Total Credits', 'type' => 'number', 'required' => true],
            ],
        ],
        'course-plans' => [
            'title' => 'Course Plans',
            'subtitle' => 'Curriculum plans per programme',
            'icon' => 'layers',
            'items' => $plans,
            'fields' => [
                ['name' => 'program_id', 'label' => 'Programme', 'type' => 'select', 'options' => $programOptions],
                ['name' => 'code', 'label' => 'Plan Code', 'type' => 'text', 'required' => true],
                ['name' => 'name', 'label' => 'Plan Name', 'type' => 'text', 'required' => true, 'full' => true],
                ['name' => 'version', 'label' => 'Version', 'type' => 'text'],
                ['name' => 'total_credits', 'label' => 'Total Credits', 'type' => 'number'],
                ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'full' => true],
            ],
        ],
        'course-types' => [
            'title' => 'Course Types',
            'subtitle' => 'Classification of courses',
            'icon' => 'book',
            'items' => $courseTypes,
            'fields' => [
                ['name' => 'code', 'label' => 'Code', 'type' => 'text', 'required' => true],
                ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
                ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'full' => true],
            ],
        ],
        'mqf' => [
            'title' => 'MQF SC',
            'subtitle' => 'MQF soft-skill / learning-outcome clusters',
            'icon' => 'sparkles',
            'items' => $mqfs,
            'fields' => [
                ['name' => 'code', 'label' => 'Code', 'type' => 'text', 'required' => true],
                ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
                ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'full' => true],
            ],
        ],
        'vbe' => [
            'title' => 'VBE',
            'subtitle' => 'Values-Based Education elements',
            'icon' => 'identification',
            'items' => $vbes,
            'fields' => [
                ['name' => 'code', 'label' => 'Code', 'type' => 'text', 'required' => true],
                ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
                ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'full' => true],
            ],
        ],
        'sdg' => [
            'title' => 'SDG',
            'subtitle' => 'UN Sustainable Development Goals (each course covers one)',
            'icon' => 'globe',
            'items' => $sdgs,
            'fields' => [
                ['name' => 'code', 'label' => 'Code', 'type' => 'text', 'required' => true],
                ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
                ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'full' => true],
            ],
        ],
    ];
@endphp

@section('content')
    <x-page-header title="Manage Configs" subtitle="Global academic configuration and reference collections" />

    {{-- Active academic term --}}
    <x-card pad title="Active Academic Term" subtitle="Sets the globally active session and semester across all portals" tone="brand" icon="calendar">
        <form method="POST" action="{{ route('admin.configs.settings') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            @csrf
            @method('PUT')
            <x-field name="active_session" label="Academic Session" :value="$activeSession" placeholder="2025/2026" required />
            <label class="block">
                <span class="label">Semester</span>
                <select name="active_semester" class="select">
                    @foreach (['1' => 'Semester 1', '2' => 'Semester 2', '3' => 'Special Semester'] as $value => $label)
                        <option value="{{ $value }}" @selected((string) $activeSemester === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <div class="flex items-end">
                <button class="btn-primary w-full"><x-icon name="check" class="size-4" /> Save active term</button>
            </div>
        </form>
    </x-card>

    <div class="mt-6 space-y-6">
        @foreach ($sections as $type => $section)
            <x-card pad :title="$section['title']" :subtitle="$section['subtitle']" :tone="'brand'" :icon="$section['icon']">
                <x-slot:actions>
                    <x-badge tone="slate">{{ $section['items']->count() }}</x-badge>
                    <button type="button" data-modal-open="add-{{ $type }}" class="btn-primary btn-sm">
                        <x-icon name="plus" class="size-3.5" /> Add
                    </button>
                </x-slot:actions>

                <div class="-mx-6 overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                @foreach ($section['fields'] as $field)
                                    @continue($field['type'] === 'textarea')
                                    <th>{{ $field['label'] }}</th>
                                @endforeach
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($section['items'] as $item)
                                <tr>
                                    @foreach ($section['fields'] as $field)
                                        @continue($field['type'] === 'textarea')
                                        <td>
                                            @if (($field['type'] ?? null) === 'select')
                                                @php($option = collect($field['options'] ?? [])->firstWhere('value', $item->{$field['name']}))
                                                {{ $option['label'] ?? '—' }}
                                            @else
                                                <span class="font-medium text-slate-700">{{ $item->{$field['name']} ?: '—' }}</span>
                                            @endif
                                        </td>
                                    @endforeach
                                    <td>
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button type="button" class="btn-ghost btn-sm"
                                                data-edit="edit-{{ $type }}"
                                                data-action="{{ route('admin.configs.update', [$type, $item->id]) }}"
                                                data-title="Edit {{ $section['title'] }}"
                                                @foreach ($section['fields'] as $field)
                                                    data-field-{{ $field['name'] }}="{{ $item->{$field['name']} }}"
                                                @endforeach>
                                                <x-icon name="cog" class="size-3.5" /> Edit
                                            </button>
                                            <form method="POST" action="{{ route('admin.configs.destroy', [$type, $item->id]) }}"
                                                data-confirm="Delete this record?">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn-danger btn-sm"><x-icon name="x" class="size-3.5" /></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="{{ count($section['fields']) + 1 }}" class="py-6 text-center text-sm text-slate-400">No records yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>

            {{-- Add modal --}}
            <x-modal id="add-{{ $type }}" title="Add {{ $section['title'] }}">
                <form method="POST" action="{{ route('admin.configs.store', $type) }}" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    @csrf
                    @include('admin.partials.config-fields', ['fields' => $section['fields']])
                    <div class="sm:col-span-2 flex items-center justify-end gap-2 pt-2">
                        <button type="button" data-modal-close class="btn-ghost">Cancel</button>
                        <button type="submit" class="btn-primary"><x-icon name="plus" class="size-4" /> Create</button>
                    </div>
                </form>
            </x-modal>

            {{-- Edit modal --}}
            <x-modal id="edit-{{ $type }}" title="Edit {{ $section['title'] }}">
                <form method="POST" action="">
                    @csrf
                    @method('PUT')
                    @include('admin.partials.config-fields', ['fields' => $section['fields']])
                    <div class="mt-5 flex items-center justify-end gap-2">
                        <button type="button" data-modal-close class="btn-ghost">Cancel</button>
                        <button type="submit" class="btn-primary"><x-icon name="check" class="size-4" /> Save changes</button>
                    </div>
                </form>
            </x-modal>
        @endforeach
    </div>
@endsection
