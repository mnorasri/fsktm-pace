<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Setting;
use App\Models\StudyPlanEntry;
use App\Models\User;
use App\Services\CurriculumService;
use App\Services\StudyPlanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudyPlanController extends Controller
{
    public function __construct(
        private readonly CurriculumService $curriculum,
        private readonly StudyPlanService $plans,
    ) {}

    public function index(): View
    {
        /** @var User $student */
        $student = auth()->user();

        $entries = $this->plans->entriesFor($student);
        $grid = $this->plans->grid($student, $entries);

        $warnings = [];
        $pendingElectives = 0;

        foreach ($entries as $entry) {
            if ($entry->course) {
                $result = $this->curriculum->checkPrerequisites($student, $entry->course, $entry, false);
                if (! empty($result['late'])) {
                    $warnings[$entry->id] = $result['late'];
                }

                if ($entry->isElectiveOption() && ! $entry->hasElectiveChoice()) {
                    $pendingElectives++;
                }
            } elseif ($entry->placeholder_key) {
                $pendingElectives++;
            }
        }

        $electiveCourses = $this->plans->electiveCoursesFor($student);

        // Elective course id → the slot it was chosen for, e.g. "Elektif III".
        $electiveChoices = $entries
            ->filter(fn (StudyPlanEntry $entry) => $entry->hasElectiveChoice())
            ->mapWithKeys(fn (StudyPlanEntry $entry) => [
                (int) $entry->course_id => $entry->electiveSlotCourse()?->name ?? 'Elective Option',
            ]);

        return view('student.study-plan', [
            'student' => $student,
            'plan' => $student->plan,
            'years' => $grid['years'],
            'semesters' => $grid['semesters'],
            'semesterLabels' => StudyPlanService::SEMESTER_LABELS,
            'yearRange' => range(1, $grid['maxYear']),
            'movableSemesters' => $this->plans->movableSemesters($entries),
            'warnings' => $warnings,
            'pendingElectives' => $pendingElectives,
            'credits' => $student->creditSummary(),
            'alternativeGroups' => $this->plans->alternativeGroupsFor($student),
            'electiveCourses' => $electiveCourses,
            'electiveChoices' => $electiveChoices,
            'replaceableCourses' => Course::where('is_active', true)
                ->with('courseType')
                ->orderBy('code')
                ->get(),
            'activeSession' => Setting::get('active_session'),
            'activeSemester' => Setting::get('active_semester'),
        ]);
    }

    public function updateStatus(Request $request, StudyPlanEntry $entry): RedirectResponse
    {
        $this->authorizeEntry($entry);

        $data = $request->validate([
            'status' => ['required', 'in:PLANNED,COMPLETED,EXEMPTED'],
        ]);

        $entry->update(['status' => $data['status']]);

        return back()->with('status', $this->plans->entryLabel($entry).' marked as '.$data['status'].'.');
    }

    /**
     * Move a course to another term. Odd semesters stay odd and even stay even
     * — industrial training is the exception and may be placed in either.
     */
    public function moveTerm(Request $request, StudyPlanEntry $entry): RedirectResponse
    {
        $this->authorizeEntry($entry);

        $entry->loadMissing(['course.courseType', 'electiveSlot']);

        // The curriculum places each Elective Option slot in a fixed semester —
        // the student changes what fills it, never when it sits.
        if ($entry->isElectiveOption()) {
            return back()->withErrors([
                'term' => 'An elective option slot stays in the semester your curriculum places it in — choose a different elective instead.',
            ]);
        }

        $data = $request->validate([
            'term' => ['required', 'string', 'regex:/^\d{1,2}:\d{1,2}$/'],
        ]);

        [$year, $semester] = array_map('intval', explode(':', $data['term']));

        if ($year < 1 || $year > 8 || ! in_array($semester, $this->plans->semestersFor($entry), true)) {
            return back()->withErrors(['term' => 'That term is not available for this course.']);
        }

        if ($entry->status !== 'PLANNED') {
            return back()->withErrors(['term' => 'Only planned courses can be moved to another term.']);
        }

        if ((int) $entry->year === $year && (int) $entry->semester === $semester) {
            return back();
        }

        $entry->update(['year' => $year, 'semester' => $semester]);

        return back()->with('status', $this->plans->entryLabel($entry).' moved to Year '.$year.' '.$this->plans->semesterLabel($semester).'.');
    }

    /**
     * Pick which course of an either/or group (e.g. Pengajian) the plan carries.
     */
    public function chooseAlternative(Request $request): RedirectResponse
    {
        /** @var User $student */
        $student = auth()->user();

        $data = $request->validate([
            'group' => ['required', 'string', 'max:60'],
            'course_id' => ['required', 'exists:courses,id'],
        ]);

        $course = Course::findOrFail($data['course_id']);

        if ($course->alternative_group !== $data['group']) {
            return back()->withErrors(['course_id' => $course->code.' is not part of the '.$data['group'].' choice.']);
        }

        if ($student->plan_id && (int) $course->course_plan_id !== (int) $student->plan_id) {
            return back()->withErrors(['course_id' => $course->code.' is not part of your study plan.']);
        }

        $groupEntries = $student->studyPlanEntries()
            ->whereHas('course', fn ($query) => $query->where('alternative_group', $data['group']))
            ->get();

        $existing = $groupEntries->firstWhere('course_id', $course->id);
        $current = $existing ?? $groupEntries->first();

        // Stay in the term the choice already occupies, otherwise fall back to
        // the course's own curriculum semester.
        $entry = StudyPlanEntry::updateOrCreate(
            ['user_id' => $student->id, 'course_id' => $course->id],
            [
                'course_plan_id' => $student->plan_id,
                'placeholder_key' => null,
                'year' => $current?->year ?? $this->plans->curriculumYear($course),
                'semester' => $current?->semester ?? $this->plans->curriculumSemester($course),
                'status' => $existing?->status ?? 'PLANNED',
            ],
        );

        $this->plans->dropOtherAlternatives($entry);

        return back()->with('status', $course->code.' selected for '.$data['group'].'.');
    }

    public function replaceElective(Request $request, StudyPlanEntry $entry): RedirectResponse
    {
        $this->authorizeEntry($entry);

        $data = $request->validate([
            'course_id' => ['required', 'exists:courses,id'],
        ]);

        $entry->update(['course_id' => $data['course_id']]);
        $entry->load('course');

        $this->plans->dropOtherAlternatives($entry);

        return back()->with('status', 'Elective replaced with '.$entry->course->code.'.');
    }

    /**
     * Fill an "Elective Option" slot (course type CT07A) with one of the
     * plan's "Elective Course" (CT07) courses. The slot itself is remembered in
     * elective_slot_id, so the row keeps its label and can be changed or
     * cleared.
     */
    public function chooseElective(Request $request, StudyPlanEntry $entry): RedirectResponse
    {
        $this->authorizeEntry($entry);

        $entry->loadMissing(['course.courseType', 'electiveSlot']);

        if (! $entry->isElectiveOption()) {
            return back()->withErrors(['course_id' => 'That study plan row is not an elective option slot.']);
        }

        /** @var User $student */
        $student = auth()->user();

        $data = $request->validate([
            'course_id' => ['nullable', 'exists:courses,id'],
        ]);

        $slot = $entry->electiveSlotCourse();
        $slotId = $entry->elective_slot_id ?: $entry->course_id;
        $label = $slot?->name ?? 'this elective slot';

        // "Not chosen yet" restores the empty slot.
        if (blank($data['course_id'] ?? null)) {
            $entry->update(['course_id' => $slotId, 'elective_slot_id' => null]);

            return back()->with('status', $label.' cleared — pick a course when you are ready.');
        }

        $course = $this->plans->electiveCoursesFor($student)->firstWhere('id', (int) $data['course_id']);

        if (! $course) {
            return back()->withErrors([
                'course_id' => 'That course is not offered as an elective course for your programme.',
            ]);
        }

        // An elective fills a single slot: taking the same course twice would
        // double-count its credits.
        $takenBy = $student->studyPlanEntries()
            ->where('course_id', $course->id)
            ->whereKeyNot($entry->getKey())
            ->with('electiveSlot')
            ->first();

        if ($takenBy) {
            return back()->withErrors([
                'course_id' => $course->code.' is already chosen for '.($takenBy->electiveSlot?->name ?? 'another elective slot').'.',
            ]);
        }

        $entry->update(['course_id' => $course->id, 'elective_slot_id' => $slotId]);

        return back()->with('status', $course->code.' chosen for '.$label.'.');
    }

    public function addCourse(Request $request): RedirectResponse
    {
        /** @var User $student */
        $student = auth()->user();

        $data = $request->validate([
            'course_id' => ['required', 'exists:courses,id'],
            'year' => ['required', 'integer', 'min:1', 'max:8'],
            'semester' => ['required', 'integer', 'min:1', 'max:3'],
        ]);

        $exists = StudyPlanEntry::where('user_id', $student->id)
            ->where('course_id', $data['course_id'])
            ->exists();

        if ($exists) {
            return back()->withErrors(['course_id' => 'That course is already on your study plan.']);
        }

        $entry = StudyPlanEntry::create($data + [
            'user_id' => $student->id,
            'course_plan_id' => $student->plan_id,
            'status' => 'PLANNED',
        ]);

        // Adding a course from an either/or group replaces the other option.
        $entry->load('course');
        $this->plans->dropOtherAlternatives($entry);

        return back()->with('status', $this->plans->entryLabel($entry).' added to your study plan.');
    }

    public function remove(StudyPlanEntry $entry): RedirectResponse
    {
        $this->authorizeEntry($entry);

        $entry->loadMissing(['course.courseType', 'electiveSlot']);

        // An Elective Option slot is part of the curriculum: the student swaps
        // the course inside it rather than deleting the slot.
        if ($entry->isElectiveOption()) {
            return back()->withErrors([
                'entry' => ($entry->electiveSlotCourse()?->name ?? 'The elective').' is part of your curriculum — choose a different elective course instead of removing it.',
            ]);
        }

        // A group the student must choose from cannot be emptied — they switch
        // to the other option instead.
        if ($entry->course?->alternative_group) {
            return back()->withErrors([
                'entry' => $entry->course->alternative_group.' is required — switch to the other option instead of removing it.',
            ]);
        }

        $entry->delete();

        return back()->with('status', 'Course removed from your study plan.');
    }

    public function submit(): RedirectResponse
    {
        /** @var User $student */
        $student = auth()->user();

        $student->update([
            'plan_status' => 'submitted',
            'plan_submitted_at' => now(),
            'plan_review_note' => null,
        ]);

        return back()->with('status', 'Your study plan has been submitted to your advisor for approval.');
    }

    private function authorizeEntry(StudyPlanEntry $entry): void
    {
        abort_unless($entry->user_id === auth()->id(), 403);
    }
}
