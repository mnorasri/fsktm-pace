<?php

namespace App\Http\Controllers\Advisor;

use App\Http\Controllers\Concerns\ValidatesCourse;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CoursePlan;
use App\Models\CourseType;
use App\Models\MqfSc;
use App\Models\Program;
use App\Models\Sdg;
use App\Models\Vbe;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The Head of Department's view of the course directory: the same list the
 * administrator manages, with add / edit / delete, without the bulk import.
 */
class HodCourseController extends Controller
{
    use ValidatesCourse;

    public function index(Request $request): View
    {
        $courses = Course::query()
            ->with(['program', 'coursePlan', 'courseType', 'mqfSc', 'vbe', 'sdg'])
            ->when($request->filled('program_id'), fn ($q) => $q->where('program_id', $request->integer('program_id')))
            ->when($request->filled('course_type_code'), fn ($q) => $q->where('course_type_code', $request->string('course_type_code')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where(fn ($inner) => $inner->where('course_code', 'like', $term)->orWhere('course_name_en', 'like', $term));
            })
            ->orderBy('course_code')
            ->paginate(15)
            ->withQueryString();

        return view('advisor.hod.courses', [
            'courses' => $courses,
            'programs' => Program::orderBy('code')->get(),
            'plans' => CoursePlan::orderBy('code')->get(),
            'courseTypes' => CourseType::orderBy('code')->get(),
            'mqfs' => MqfSc::orderBy('code')->get(),
            'vbes' => Vbe::orderBy('code')->get(),
            'sdgs' => Sdg::ordered()->get(),
            // The view points its forms at the HoD's own routes.
            'courseRoutes' => 'advisor.courses',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedCourse($request);

        // A course entered by a Head of Department belongs to that department.
        $data['department_id'] = auth()->user()->department_id;

        Course::create($data);

        return back()->with('status', 'Course created.');
    }

    public function update(Request $request, Course $course): RedirectResponse
    {
        $course->update($this->validatedCourse($request, $course));

        return back()->with('status', 'Course updated.');
    }

    public function destroy(Course $course): RedirectResponse
    {
        $course->delete();

        return back()->with('status', 'Course deleted.');
    }
}
