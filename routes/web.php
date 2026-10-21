<?php

use App\Http\Controllers\Admin\ConfigController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\HeadOfProgramController;
use App\Http\Controllers\Admin\HodController;
use App\Http\Controllers\Admin\LecturerController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Advisor\ApprovalController;
use App\Http\Controllers\Advisor\DashboardController as AdvisorDashboardController;
use App\Http\Controllers\Advisor\HodOfferingController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Student\PreRegistrationController;
use App\Http\Controllers\Student\SlipController;
use App\Http\Controllers\Student\StudyPlanController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return Auth::check()
        ? redirect()->route(Auth::user()->dashboardRoute())
        : redirect()->route('login');
})->name('home');

// -------------------------------------------------------------------
// Authentication (unified login for all three roles)
// -------------------------------------------------------------------
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

// -------------------------------------------------------------------
// Profile (every signed-in account, whatever its role)
// -------------------------------------------------------------------
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'password'])->name('profile.password');
});

// -------------------------------------------------------------------
// Admin portal
// -------------------------------------------------------------------
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::get('/configs', [ConfigController::class, 'index'])->name('configs.index');
    Route::put('/configs/settings', [ConfigController::class, 'settings'])->name('configs.settings');
    Route::post('/configs/{type}', [ConfigController::class, 'store'])->name('configs.store');
    Route::put('/configs/{type}/{id}', [ConfigController::class, 'update'])->name('configs.update');
    Route::delete('/configs/{type}/{id}', [ConfigController::class, 'destroy'])->name('configs.destroy');

    Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
    Route::post('/courses', [CourseController::class, 'store'])->name('courses.store');
    Route::post('/courses/import', [CourseController::class, 'import'])->name('courses.import');
    Route::put('/courses/{course}', [CourseController::class, 'update'])->name('courses.update');
    Route::delete('/courses/{course}', [CourseController::class, 'destroy'])->name('courses.destroy');

    Route::get('/lecturers', [LecturerController::class, 'index'])->name('lecturers.index');
    Route::post('/lecturers', [LecturerController::class, 'store'])->name('lecturers.store');
    Route::post('/lecturers/import', [LecturerController::class, 'import'])->name('lecturers.import');
    Route::put('/lecturers/{lecturer}', [LecturerController::class, 'update'])->name('lecturers.update');
    Route::delete('/lecturers/{lecturer}', [LecturerController::class, 'destroy'])->name('lecturers.destroy');
    Route::post('/lecturers/{lecturer}/reset-password', [LecturerController::class, 'resetPassword'])->name('lecturers.reset-password');

    Route::get('/students', [StudentController::class, 'index'])->name('students.index');
    Route::post('/students', [StudentController::class, 'store'])->name('students.store');
    Route::post('/students/import', [StudentController::class, 'import'])->name('students.import');
    Route::put('/students/{student}', [StudentController::class, 'update'])->name('students.update');
    Route::delete('/students/{student}', [StudentController::class, 'destroy'])->name('students.destroy');
    Route::post('/students/{student}/reset-password', [StudentController::class, 'resetPassword'])->name('students.reset-password');

    Route::get('/hod', [HodController::class, 'index'])->name('hod.index');
    Route::post('/hod/{lecturer}/toggle', [HodController::class, 'toggle'])->name('hod.toggle');

    Route::get('/head-of-program', [HeadOfProgramController::class, 'index'])->name('head-of-program.index');
    Route::post('/head-of-program', [HeadOfProgramController::class, 'assign'])->name('head-of-program.assign');
    Route::delete('/head-of-program/{program}', [HeadOfProgramController::class, 'revoke'])->name('head-of-program.revoke');
});

// -------------------------------------------------------------------
// Advisor / PAK portal
// -------------------------------------------------------------------
Route::middleware(['auth', 'role:advisor'])->prefix('advisor')->name('advisor.')->group(function () {
    Route::get('/', [AdvisorDashboardController::class, 'index'])->name('dashboard');

    Route::post('/plans/{student}/approve', [ApprovalController::class, 'approvePlan'])->name('plans.approve');
    Route::post('/plans/{student}/reject', [ApprovalController::class, 'rejectPlan'])->name('plans.reject');
    Route::post('/registrations/{registration}/approve', [ApprovalController::class, 'approveRegistration'])->name('registrations.approve');
    Route::post('/registrations/{registration}/reject', [ApprovalController::class, 'rejectRegistration'])->name('registrations.reject');

    // The whole pre-registration for the active term, approved as one batch.
    Route::post('/registrations/{student}/approve-term', [ApprovalController::class, 'approveRegistrationBatch'])->name('registrations.batch.approve');
    Route::post('/registrations/{student}/return-term', [ApprovalController::class, 'returnRegistrationBatch'])->name('registrations.batch.return');

    // Head of Department toolset
    Route::middleware('hod')->group(function () {
        Route::get('/offering/manage', [HodOfferingController::class, 'manage'])->name('offering.manage');
        Route::get('/offering/grid', [HodOfferingController::class, 'grid'])->name('offering.grid');
        Route::get('/offering/workload', [HodOfferingController::class, 'workload'])->name('offering.workload');
        Route::get('/offering/export', [HodOfferingController::class, 'export'])->name('offering.export');
        Route::post('/offering/toggle', [HodOfferingController::class, 'toggle'])->name('offering.toggle');
        Route::put('/offering/{offering}', [HodOfferingController::class, 'assign'])->name('offering.assign');

        // One section + lecturer per PAK (advisor) group of an offering.
        Route::put('/offering/{offering}/group', [HodOfferingController::class, 'assignGroup'])->name('offering.group');
    });
});

// -------------------------------------------------------------------
// Student portal
// -------------------------------------------------------------------
Route::middleware(['auth', 'role:student'])->prefix('student')->name('student.')->group(function () {
    Route::get('/', [StudentDashboardController::class, 'index'])->name('dashboard');

    Route::get('/study-plan', [StudyPlanController::class, 'index'])->name('study-plan');
    Route::post('/study-plan/entries', [StudyPlanController::class, 'addCourse'])->name('study-plan.add');
    Route::put('/study-plan/entries/{entry}/status', [StudyPlanController::class, 'updateStatus'])->name('study-plan.status');
    Route::put('/study-plan/entries/{entry}/term', [StudyPlanController::class, 'moveTerm'])->name('study-plan.term');
    Route::put('/study-plan/entries/{entry}/replace', [StudyPlanController::class, 'replaceElective'])->name('study-plan.replace');
    Route::put('/study-plan/entries/{entry}/elective', [StudyPlanController::class, 'chooseElective'])->name('study-plan.elective');
    Route::delete('/study-plan/entries/{entry}', [StudyPlanController::class, 'remove'])->name('study-plan.remove');
    Route::post('/study-plan/submit', [StudyPlanController::class, 'submit'])->name('study-plan.submit');
    Route::put('/study-plan/alternative', [StudyPlanController::class, 'chooseAlternative'])->name('study-plan.alternative');

    Route::get('/pre-registration', [PreRegistrationController::class, 'index'])->name('pre-registration');
    Route::post('/pre-registration/submit', [PreRegistrationController::class, 'submit'])->name('pre-registration.submit');
    Route::post('/pre-registration/{offering}', [PreRegistrationController::class, 'store'])->name('pre-registration.store');
    Route::delete('/pre-registration/{registration}', [PreRegistrationController::class, 'destroy'])->name('pre-registration.destroy');

    Route::get('/slip', [SlipController::class, 'show'])->name('slip');
});

// -------------------------------------------------------------------
// Addresses that only save a form
// -------------------------------------------------------------------
// A browser reload, a bookmark, or a shared link may open a form's action with
// GET. Those addresses only answer POST, PUT or DELETE, so Laravel would reply
// with a "405 Method Not Allowed" error page. Show a real page instead. The
// catch-all answers every verb so that an unknown address keeps its 404.
Route::any('{fallbackPlaceholder}', function (Request $request) {
    if (! in_array($request->method(), ['GET', 'HEAD'], true)) {
        abort(404);
    }

    $savesForm = collect(Route::getRoutes()->getRoutes())
        ->contains(fn ($route) => ! $route->isFallback && $route->matches($request, false));

    if (! $savesForm) {
        abort(404);
    }

    return redirect()
        ->route(Auth::check() ? Auth::user()->dashboardRoute() : 'login')
        ->with('notice', 'That address saves a form instead of opening a page.');
})->where('fallbackPlaceholder', '.*')->fallback();
