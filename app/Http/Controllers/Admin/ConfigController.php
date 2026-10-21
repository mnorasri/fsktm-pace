<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CoursePlan;
use App\Models\CourseType;
use App\Models\Department;
use App\Models\MqfSc;
use App\Models\Program;
use App\Models\Sdg;
use App\Models\Setting;
use App\Models\Vbe;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ConfigController extends Controller
{
    /**
     * Registry of admin-managed configuration collections.
     *
     * @var array<string, array{label:string, model:class-string, rules:array<string,mixed>}>
     */
    protected array $types = [];

    public function __construct()
    {
        $this->types = [
            'departments' => [
                'label' => 'Departments',
                'model' => Department::class,
                'rules' => [
                    'code' => ['required', 'string', 'max:20'],
                    'name' => ['required', 'string', 'max:150'],
                    'description' => ['nullable', 'string'],
                ],
            ],
            'programs' => [
                'label' => 'Study Programmes',
                'model' => Program::class,
                'rules' => [
                    'department_id' => ['nullable', 'exists:departments,id'],
                    'code' => ['required', 'string', 'max:30'],
                    'name' => ['required', 'string', 'max:150'],
                    'level' => ['nullable', 'string', 'max:40'],
                    'duration_years' => ['required', 'numeric', 'min:1', 'max:8'],
                    'total_credits' => ['required', 'integer', 'min:1', 'max:300'],
                ],
            ],
            'course-plans' => [
                'label' => 'Course Plans',
                'model' => CoursePlan::class,
                'rules' => [
                    'program_id' => ['nullable', 'exists:programs,id'],
                    'code' => ['required', 'string', 'max:40'],
                    'name' => ['required', 'string', 'max:150'],
                    'version' => ['nullable', 'string', 'max:20'],
                    'total_credits' => ['nullable', 'integer', 'min:1', 'max:300'],
                    'description' => ['nullable', 'string'],
                ],
            ],
            'course-types' => [
                'label' => 'Course Types',
                'model' => CourseType::class,
                'rules' => [
                    'code' => ['required', 'string', 'max:20'],
                    'name' => ['required', 'string', 'max:100'],
                    'description' => ['nullable', 'string'],
                ],
            ],
            'mqf' => [
                'label' => 'MQF SC',
                'model' => MqfSc::class,
                'rules' => [
                    'code' => ['required', 'string', 'max:20'],
                    'name' => ['required', 'string', 'max:100'],
                    'description' => ['nullable', 'string'],
                ],
            ],
            'vbe' => [
                'label' => 'VBE',
                'model' => Vbe::class,
                'rules' => [
                    'code' => ['required', 'string', 'max:20'],
                    'name' => ['required', 'string', 'max:100'],
                    'description' => ['nullable', 'string'],
                ],
            ],
            'sdg' => [
                'label' => 'SDG',
                'model' => Sdg::class,
                'rules' => [
                    'code' => ['required', 'string', 'max:20'],
                    'name' => ['required', 'string', 'max:100'],
                    'description' => ['nullable', 'string'],
                ],
            ],
        ];
    }

    public function index(): View
    {
        return view('admin.configs', [
            'activeSession' => Setting::get('active_session', '2025/2026'),
            'activeSemester' => Setting::get('active_semester', '1'),
            'departments' => Department::orderBy('code')->get(),
            'programs' => Program::with('department')->orderBy('code')->get(),
            'plans' => CoursePlan::with('program')->orderBy('code')->get(),
            'courseTypes' => CourseType::orderBy('code')->get(),
            'mqfs' => MqfSc::orderBy('code')->get(),
            'vbes' => Vbe::orderBy('code')->get(),
            'sdgs' => Sdg::ordered()->get(),
        ]);
    }

    public function settings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'active_session' => ['required', 'string', 'max:20'],
            'active_semester' => ['required', 'string', 'max:10'],
        ]);

        Setting::put('active_session', $data['active_session']);
        Setting::put('active_semester', $data['active_semester']);

        return back()->with('status', 'Active academic term updated to '.$data['active_session'].' Semester '.$data['active_semester'].'.');
    }

    public function store(Request $request, string $type): RedirectResponse
    {
        $config = $this->resolve($type);
        $request->validate($this->uniqueRules($config['rules'], $type));

        $config['model']::create($this->payload($request, $config['rules']));

        return back()->with('status', $config['label'].' record created.');
    }

    public function update(Request $request, string $type, int $id): RedirectResponse
    {
        $config = $this->resolve($type);
        $record = $config['model']::findOrFail($id);

        $rules = $this->uniqueRules($config['rules'], $type, $record->getKey());

        // Codes stay unique across the collection.
        foreach (['code'] as $field) {
            if (isset($rules[$field])) {
                $rules[$field] = ['required', 'string', 'max:60', Rule::unique($record->getTable(), $field)->ignore($record->getKey())];
            }
        }

        $request->validate($rules);
        $record->update($this->payload($request, $config['rules']));

        return back()->with('status', $config['label'].' record updated.');
    }

    public function destroy(string $type, int $id): RedirectResponse
    {
        $config = $this->resolve($type);
        $config['model']::findOrFail($id)->delete();

        return back()->with('status', $config['label'].' record deleted.');
    }

    /**
     * @param  array<string, mixed>  $rules
     * @return array<string, mixed>
     */
    protected function uniqueRules(array $rules, string $type, ?int $ignore = null): array
    {
        $model = $this->types[$type]['model'];
        $table = (new $model)->getTable();

        if (isset($rules['code'])) {
            $unique = Rule::unique($table, 'code');
            if ($ignore) {
                $unique->ignore($ignore);
            }
            $rules['code'] = ['required', 'string', 'max:60', $unique];
        }

        return $rules;
    }

    /**
     * @param  array<string, mixed>  $rules
     * @return array<string, mixed>
     */
    protected function payload(Request $request, array $rules): array
    {
        return collect(array_keys($rules))
            ->mapWithKeys(fn ($key) => [$key => $request->input($key)])
            ->all();
    }

    /**
     * @return array{label:string, model:class-string, rules:array<string,mixed>}
     */
    protected function resolve(string $type): array
    {
        abort_unless(array_key_exists($type, $this->types), 404, 'Unknown configuration type.');

        return $this->types[$type];
    }
}
