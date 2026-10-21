<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HodController extends Controller
{
    public function index(Request $request): View
    {
        $advisors = User::query()
            ->where('role', User::ROLE_ADVISOR)
            ->with('department')
            ->when($request->filled('department_id'), fn ($q) => $q->where('department_id', $request->integer('department_id')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where(fn ($inner) => $inner
                    ->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('staff_id', 'like', $term));
            })
            ->orderByDesc('is_hod')
            ->orderBy('name')
            ->get();

        return view('admin.hod', [
            'advisors' => $advisors,
            'departments' => Department::withCount([
                'users as hod_count' => fn ($q) => $q->where('is_hod', true),
            ])->orderBy('code')->get(),
        ]);
    }

    public function toggle(User $lecturer): RedirectResponse
    {
        abort_unless($lecturer->role === User::ROLE_ADVISOR, 404);

        $lecturer->update(['is_hod' => ! $lecturer->is_hod]);

        return back()->with('status', $lecturer->name.($lecturer->is_hod ? ' is now a Head of Department.' : ' is no longer a Head of Department.'));
    }
}
