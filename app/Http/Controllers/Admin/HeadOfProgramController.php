<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Program;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HeadOfProgramController extends Controller
{
    public function index(Request $request): View
    {
        // Step 1 — the selected department drives everything that follows.
        $selectedDepartment = $request->filled('department_id')
            ? Department::find($request->integer('department_id'))
            : null;

        // Step 2 — programmes belong to the chosen department.
        $programs = $selectedDepartment
            ? Program::where('department_id', $selectedDepartment->id)
                ->with('headOfProgram')
                ->orderBy('code')
                ->get()
            : collect();

        // Guard: ignore a programme that is not part of the chosen department.
        $selectedProgram = $request->filled('program_id')
            ? $programs->firstWhere('id', $request->integer('program_id'))
            : null;

        // Step 3 — lecturers are listed by department.
        $lecturers = $selectedDepartment
            ? User::query()
                ->where('role', User::ROLE_ADVISOR)
                ->where('department_id', $selectedDepartment->id)
                ->with(['department', 'headedPrograms'])
                ->orderBy('name')
                ->get()
            : collect();

        return view('admin.head-of-program', [
            'departments' => Department::orderBy('code')->get(),
            'selectedDepartment' => $selectedDepartment,
            'programs' => $programs,
            'selectedProgram' => $selectedProgram,
            'lecturers' => $lecturers,
        ]);
    }

    public function assign(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'program_id' => ['required', 'exists:programs,id'],
            'lecturer_id' => ['required', 'exists:users,id'],
        ]);

        $program = Program::findOrFail($data['program_id']);
        $lecturer = User::findOrFail($data['lecturer_id']);

        abort_unless($lecturer->role === User::ROLE_ADVISOR, 422);
        abort_unless(
            $lecturer->department_id !== null && $lecturer->department_id === $program->department_id,
            422
        );

        // One Head of Program per programme, drawn from its own department.
        $program->update(['head_of_program_id' => $lecturer->id]);

        return redirect()
            ->route('admin.head-of-program.index', [
                'department_id' => $program->department_id,
                'program_id' => $program->id,
            ])
            ->with('status', $lecturer->name.' is now the Head of Program for '.$program->code.'.');
    }

    public function revoke(Program $program): RedirectResponse
    {
        $code = $program->code;
        $program->update(['head_of_program_id' => null]);

        return back()->with('status', 'Head of Program removed from '.$code.'.');
    }
}
