<?php

namespace App\Http\Controllers\Advisor;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CoursePlan;
use App\Models\Department;
use App\Models\Offering;
use App\Models\OfferingGroup;
use App\Models\PreRegistration;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HodOfferingController extends Controller
{
    /**
     * What coordinating a course adds to the coordinator's load.
     */
    private const COORDINATOR_LOAD = 1.0;

    /**
     * Manage Offering — registration statistics grouped by course-code
     * prefix plus inline section / lecturer / coordinator assignment.
     */
    public function manage(Request $request): View
    {
        [$session, $semester] = $this->activeTerm();

        $courseGroup = $request->string('group')->trim()->value() ?: null;

        $offerings = Offering::query()
            ->with([
                'course.department', 'lecturer', 'coordinator', 'adviseeGroup', 'program',
                // The per-PAK-group sections of this offering, if any.
                'groups.advisor', 'groups.lecturer',
            ])
            ->withCount([
                'preRegistrations',
                'preRegistrations as approved_count' => fn ($q) => $q->where('status', 'approved'),
            ])
            ->where('session', $session)
            ->where('semester', $semester)
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where(fn ($inner) => $inner
                    ->whereHas('course', fn ($c) => $c->where('course_code', 'like', $term)->orWhere('course_name_en', 'like', $term)));
            })
            // Course groups are the course-code prefixes, e.g. "BIC".
            ->when($courseGroup, fn ($q) => $q->whereHas('course', fn ($c) => $c->where('course_code', 'like', $courseGroup.'%')))
            ->get();

        $groups = $offerings
            ->groupBy(fn (Offering $offering) => $this->courseGroup($offering->course->code))
            ->sortKeys();

        // Every course group offered this term, so the filter can list them
        // even while one of them is selected.
        $groupOptions = Course::query()
            ->whereIn('id', Offering::query()
                ->where('session', $session)
                ->where('semester', $semester)
                ->select('course_id'))
            ->pluck('course_code')
            ->map(fn (?string $code) => $this->courseGroup($code))
            ->unique()
            ->sort()
            ->values();

        // Students already registered, counted per PAK group — the academic
        // advisor each student belongs to.
        $registered = PreRegistration::query()
            ->whereIn('offering_id', $offerings->pluck('id'))
            ->with('student.advisor')
            ->get()
            ->groupBy('offering_id')
            ->map(fn ($rows) => $rows
                ->groupBy(fn (PreRegistration $registration) => $registration->student?->advisor?->id ?? 0)
                ->map(fn ($group) => [
                    'advisor' => $group->first()->student?->advisor,
                    'students' => $group->count(),
                ])
                ->sortByDesc('students')
                ->values());

        // Per offering: the registration breakdown next to the section and
        // lecturer already given to that group.
        $groupPanels = $offerings->mapWithKeys(function (Offering $offering) use ($registered) {
            $byAdvisor = $registered->get($offering->id, collect())->keyBy(fn ($row) => $row['advisor']?->id ?? 0);
            $assigned = $offering->groups->keyBy('advisor_id');

            $rows = $byAdvisor->keys()
                ->map(fn ($advisorId) => [
                    'advisor' => $byAdvisor[$advisorId]['advisor'],
                    'students' => $byAdvisor[$advisorId]['students'],
                    'assignment' => $assigned->get($advisorId),
                ])
                ->merge($assigned
                    ->reject(fn (OfferingGroup $group) => $byAdvisor->has($group->advisor_id))
                    ->map(fn (OfferingGroup $group) => [
                        'advisor' => $group->advisor,
                        'students' => 0,
                        'assignment' => $group,
                    ]))
                ->sortByDesc('students')
                ->values();

            return [$offering->id => $rows];
        });

        // A coordinator is chosen from the lecturers already assigned to the
        // course — its own lecturer plus every PAK group's lecturer.
        $assignedLecturers = $offerings->mapWithKeys(fn (Offering $offering) => [
            $offering->id => $offering->groups
                ->pluck('lecturer')
                ->push($offering->lecturer)
                ->filter()
                ->unique('id')
                ->sortBy('name')
                ->values(),
        ]);

        // Page total: every advisor and the students they have registered.
        $totalsByAdvisor = $registered->flatten(1)
            ->groupBy(fn ($row) => $row['advisor']?->id ?? 0)
            ->map(fn ($rows) => [
                'advisor' => $rows->first()['advisor'],
                'students' => $rows->sum('students'),
            ])
            ->sortByDesc('students')
            ->values();

        return view('advisor.hod.manage', [
            'groups' => $groups,
            'groupOptions' => $groupOptions,
            'courseGroup' => $courseGroup,
            'offerings' => $offerings,
            'advisors' => User::where('role', User::ROLE_ADVISOR)->orderBy('name')->get(),
            'groupPanels' => $groupPanels,
            'assignedLecturers' => $assignedLecturers,
            'totalsByAdvisor' => $totalsByAdvisor,
            // Sections 01–10 — the address a PAK group can be given.
            'sectionOptions' => collect(range(1, 10))
                ->map(fn (int $number) => str_pad((string) $number, 2, '0', STR_PAD_LEFT)),
            'activeSession' => $session,
            'activeSemester' => $semester,
        ]);
    }

    /**
     * Give one PAK (advisor) group of an offering its own section and lecturer.
     */
    public function assignGroup(Request $request, Offering $offering): RedirectResponse
    {
        $data = $request->validate([
            'advisor_id' => ['required', 'exists:users,id'],
            'section' => ['nullable', 'string', 'max:10'],
            'lecturer_id' => ['nullable', 'exists:users,id'],
        ]);

        $group = OfferingGroup::updateOrCreate(
            ['offering_id' => $offering->id, 'advisor_id' => $data['advisor_id']],
            ['section' => $data['section'] ?: null, 'lecturer_id' => $data['lecturer_id'] ?: null],
        );

        $section = $group->section ? 'Section '.$group->section : 'no section yet';

        return back()->with('status', ($group->advisor?->name ?? 'PAK group').' · '.$section.' saved for '.$offering->course->code.'.');
    }

    /**
     * Course Offering Toggle Grid — the courses of each programme plan, split
     * into an odd-semester column and an even-semester column.
     */
    public function grid(Request $request): View
    {
        [$session, $semester] = $this->activeTerm();

        $selectedPlan = $request->integer('plan_id') ?: null;

        $plans = CoursePlan::query()
            ->with([
                'program',
                'courses' => fn ($q) => $q->with('courseType')->orderBy('semester')->orderBy('course_code'),
                'planCourses.course.courseType',
            ])
            ->when($selectedPlan, fn ($q) => $q->whereKey($selectedPlan))
            ->orderBy('code')
            ->get();

        // A plan's courses come from two sources: those attached to it through
        // their course_plan_id, and those placed on it in the study plan grid
        // (plan_courses). Union them so every course related to the plan shows
        // up here, even when it was only added through the study plan. Placement
        // (year + semester) is taken from plan_courses, not from the course's
        // own curriculum semester.
        foreach ($plans as $plan) {
            $map = $plan->planCourses->keyBy('course_id');

            $attached = $plan->courses;
            $fromPlan = $plan->planCourses
                ->map(fn ($row) => $row->course)
                ->filter()
                ->unique('id')
                ->reject(fn ($course) => $attached->contains('id', $course->id));

            $merged = $attached
                ->concat($fromPlan)
                ->sortBy('course_code')
                ->values();

            foreach ($merged as $course) {
                $row = $map->get($course->id);
                $course->plan_year = $row?->year;
                $course->plan_semester = $row?->semester;
            }

            $plan->setRelation('courses', $merged);
        }

        $existing = Offering::where('session', $session)
            ->where('semester', $semester)
            ->get()
            ->keyBy('course_id');

        return view('advisor.hod.grid', [
            'plans' => $plans,
            'planOptions' => CoursePlan::orderBy('code')->get(['id', 'code', 'name']),
            'selectedPlan' => $selectedPlan,
            'existing' => $existing,
            'activeSession' => $session,
            'activeSemester' => $semester,
        ]);
    }

    public function toggle(Request $request): RedirectResponse
    {
        [$session, $semester] = $this->activeTerm();

        $validated = $request->validate([
            'course_id' => ['required', 'exists:courses,id'],
            'course_plan_id' => ['nullable', 'exists:course_plans,id'],
            'year' => ['nullable', 'integer', 'min:1', 'max:8'],
        ]);

        $offering = Offering::where('session', $session)
            ->where('semester', $semester)
            ->where('course_id', $validated['course_id'])
            ->first();

        if ($offering) {
            $offering->delete();

            return back()->with('status', 'Course removed from the offering grid for '.$session.' Semester '.$semester.'.');
        }

        Offering::create([
            'course_id' => $validated['course_id'],
            'session' => $session,
            'semester' => $semester,
            'section' => '01',
            'course_plan_id' => $validated['course_plan_id'] ?? null,
            'year' => $validated['year'] ?? null,
            'capacity' => 40,
            'is_offered' => true,
        ]);

        return back()->with('status', 'Course added to the offering grid for '.$session.' Semester '.$semester.'.');
    }

    /**
     * Lecturer Load Dashboard — the teaching load each lecturer carries in the
     * active term, taken from the assignments made in Manage Offering: the
     * course as a whole, each PAK group's own section, and coordinating a
     * course, which counts one extra load.
     */
    public function workload(Request $request): View
    {
        [$session, $semester] = $this->activeTerm();

        $selectedDepartment = $request->integer('department_id') ?: null;

        $departments = Department::with([
            'users' => fn ($q) => $q->where('role', User::ROLE_ADVISOR)->orderBy('name'),
        ])
            ->when($selectedDepartment, fn ($q) => $q->whereKey($selectedDepartment))
            ->orderBy('code')
            ->get();

        $assignments = $this->termAssignments($session, $semester)->groupBy('lecturer_id');

        $load = $departments->map(function ($department) use ($assignments) {
            return [
                'department' => $department,
                'lecturers' => $department->users->map(function (User $lecturer) use ($assignments) {
                    $mine = $assignments->get($lecturer->id, collect());
                    $teaching = $mine->reject->coordinating->values();

                    return [
                        'lecturer' => $lecturer,
                        'sections' => $teaching->count(),
                        'courses' => $teaching->pluck('course_id')->unique()->count(),
                        'load' => $mine->sum('load'),
                        'assignments' => $teaching,
                        'coordinations' => $mine->filter->coordinating->values(),
                        'advisees' => $lecturer->advisees()->count(),
                    ];
                }),
            ];
        });

        return view('advisor.hod.workload', [
            'departments' => $load,
            'departmentOptions' => Department::orderBy('code')->get(['id', 'code', 'name']),
            'selectedDepartment' => $selectedDepartment,
            'totalSections' => $load->sum(fn ($d) => $d['lecturers']->sum('sections')),
            'totalLoad' => $load->sum(fn ($d) => $d['lecturers']->sum('load')),
            'coordinatorLoad' => self::COORDINATOR_LOAD,
            'activeSession' => $session,
            'activeSemester' => $semester,
        ]);
    }

    /**
     * Every lecturer attached to a course in the term, one row per assignment:
     * a whole course, a PAK group's own section, or coordinating the course.
     *
     * @return Collection<int, array{lecturer_id: int, course_id: int, code: string, section: string|null, pak: string|null, coordinating: bool, load: float}>
     */
    private function termAssignments(string $session, string $semester): Collection
    {
        $offerings = Offering::with(['course', 'groups.advisor'])
            ->where('session', $session)
            ->where('semester', $semester)
            ->get();

        $rows = collect();

        foreach ($offerings as $offering) {
            $course = $offering->course;

            // What one section of this course is worth to its lecturer.
            $units = (float) ($course->course_load ?? $course->credits ?? 0);

            if ($offering->lecturer_id) {
                $rows->push([
                    'lecturer_id' => $offering->lecturer_id,
                    'course_id' => $course->id,
                    'code' => $course->code,
                    'section' => $offering->section,
                    'pak' => null,
                    'coordinating' => false,
                    'load' => $units,
                ]);
            }

            foreach ($offering->groups as $group) {
                if ($group->lecturer_id) {
                    $rows->push([
                        'lecturer_id' => $group->lecturer_id,
                        'course_id' => $course->id,
                        'code' => $course->code,
                        'section' => $group->section,
                        'pak' => $group->advisor?->name,
                        'coordinating' => false,
                        'load' => $units,
                    ]);
                }
            }

            if ($offering->coordinator_id) {
                $rows->push([
                    'lecturer_id' => $offering->coordinator_id,
                    'course_id' => $course->id,
                    'code' => $course->code,
                    'section' => $offering->section,
                    'pak' => null,
                    'coordinating' => true,
                    'load' => self::COORDINATOR_LOAD,
                ]);
            }
        }

        return $rows;
    }

    public function assign(Request $request, Offering $offering): RedirectResponse
    {
        $data = $request->validate([
            'section' => ['nullable', 'string', 'max:10'],
            'lecturer_id' => ['nullable', 'exists:users,id'],
            'coordinator_id' => ['nullable', 'exists:users,id'],
            'advisee_group_id' => ['nullable', 'exists:users,id'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:500'],
            'day' => ['nullable', 'string', 'max:20'],
            'time' => ['nullable', 'string', 'max:40'],
            'venue' => ['nullable', 'string', 'max:40'],
        ]);

        // An empty select means "clear it", not an empty string in an id column.
        $offering->update(array_map(fn ($value) => $value === '' ? null : $value, $data));

        return back()->with('status', 'Offering for '.$offering->course->code.' updated.');
    }

    public function export(): StreamedResponse
    {
        [$session, $semester] = $this->activeTerm();

        $offerings = Offering::with(['course', 'lecturer', 'coordinator', 'adviseeGroup'])
            ->where('session', $session)
            ->where('semester', $semester)
            ->get()
            ->sortBy(fn ($o) => $o->course->code);

        $filename = 'offering-report-'.str_replace('/', '-', $session).'-sem'.$semester.'.csv';

        return Response::streamDownload(function () use ($offerings) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Course Code', 'Course Name', 'Section', 'Credits', 'Lecturer', 'Coordinator', 'PAK Group', 'Registered', 'Capacity', 'Day', 'Time', 'Venue']);

            foreach ($offerings as $offering) {
                fputcsv($out, [
                    $offering->course->code,
                    $offering->course->name,
                    $offering->section,
                    $offering->course->credits,
                    $offering->lecturer?->name,
                    $offering->coordinator?->name,
                    $offering->adviseeGroup?->name,
                    $offering->preRegistrations()->count(),
                    $offering->capacity,
                    $offering->day,
                    $offering->time,
                    $offering->venue,
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * The course group a code belongs to — its prefix, e.g. "BIC" or "UQ*".
     */
    private function courseGroup(?string $code): string
    {
        return strtoupper(trim(substr((string) $code, 0, 4)));
    }

    /**
     * @return array{0:string,1:string}
     */
    private function activeTerm(): array
    {
        return [
            Setting::get('active_session', '2025/2026'),
            Setting::get('active_semester', '1'),
        ];
    }
}
