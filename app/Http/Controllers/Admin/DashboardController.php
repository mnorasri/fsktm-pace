<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CoursePlan;
use App\Models\Department;
use App\Models\Offering;
use App\Models\Program;
use App\Models\Setting;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'students' => User::where('role', User::ROLE_STUDENT)->count(),
            'lecturers' => User::where('role', User::ROLE_ADVISOR)->count(),
            'hods' => User::where('is_hod', true)->count(),
            'courses' => Course::count(),
            'programs' => Program::count(),
            'departments' => Department::count(),
            'plans' => CoursePlan::count(),
            'offerings' => Offering::where('session', Setting::get('active_session'))
                ->where('semester', Setting::get('active_semester'))
                ->where('is_offered', true)
                ->count(),
        ];

        $byDepartment = Department::withCount([
            'courses',
            'users as students_count' => fn ($q) => $q->where('role', User::ROLE_STUDENT),
            'users as lecturers_count' => fn ($q) => $q->where('role', User::ROLE_ADVISOR),
        ])->get();

        return view('admin.dashboard', compact('stats', 'byDepartment'));
    }
}
