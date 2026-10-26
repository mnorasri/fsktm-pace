<?php

namespace App\Http\Controllers\Advisor;

use App\Http\Controllers\Controller;
use App\Models\PreRegistration;
use App\Models\RegistrationSubmission;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ApprovalController extends Controller
{
    public function approvePlan(Request $request, User $student): RedirectResponse
    {
        $this->authorizeAdvisee($student);

        $student->update([
            'plan_status' => 'approved',
            'plan_reviewed_by' => auth()->id(),
            'plan_review_note' => $request->input('note'),
        ]);

        return back()->with('status', $student->name."'s study plan was approved.");
    }

    public function rejectPlan(Request $request, User $student): RedirectResponse
    {
        $this->authorizeAdvisee($student);

        $student->update([
            'plan_status' => 'rejected',
            'plan_reviewed_by' => auth()->id(),
            'plan_review_note' => $request->input('note', 'Please review the flagged prerequisites and resubmit.'),
        ]);

        return back()->with('status', $student->name."'s study plan was returned for revision.");
    }

    public function approveRegistration(PreRegistration $registration): RedirectResponse
    {
        $this->authorizeAdvisee($registration->student);

        $registration->update([
            'status' => 'approved',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return back()->with('status', 'Pre-registration approved for '.$registration->student->name.'.');
    }

    public function rejectRegistration(PreRegistration $registration): RedirectResponse
    {
        $this->authorizeAdvisee($registration->student);

        $registration->update([
            'status' => 'rejected',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return back()->with('status', 'Pre-registration rejected for '.$registration->student->name.'.');
    }

    /**
     * Approve the student's whole pre-registration for the active term: the
     * batch is signed off and every course request in it is confirmed.
     */
    public function approveRegistrationBatch(Request $request, User $student): RedirectResponse
    {
        $this->authorizeAdvisee($student);

        [$session, $semester] = $this->activeTerm();
        $submission = RegistrationSubmission::forTerm($student, $session, $semester);

        if (! $submission || $submission->status !== RegistrationSubmission::STATUS_SUBMITTED) {
            return back()->withErrors([
                'registration' => 'There is no pre-registration awaiting your approval for '.$student->name.'.',
            ]);
        }

        $approved = $student->preRegistrations()
            ->where('session', $session)
            ->where('semester', $semester)
            ->where('status', 'pending')
            ->update([
                'status' => 'approved',
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ]);

        $submission->update([
            'status' => RegistrationSubmission::STATUS_APPROVED,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'note' => $request->input('note'),
        ]);

        return back()->with('status', $student->name."'s pre-registration was approved ({$approved} course(s) confirmed).");
    }

    /**
     * Return the batch to the student so they can adjust it and submit again.
     */
    public function returnRegistrationBatch(Request $request, User $student): RedirectResponse
    {
        $this->authorizeAdvisee($student);

        [$session, $semester] = $this->activeTerm();
        $submission = RegistrationSubmission::forTerm($student, $session, $semester);

        if (! $submission || $submission->status !== RegistrationSubmission::STATUS_SUBMITTED) {
            return back()->withErrors([
                'registration' => 'There is no pre-registration awaiting your approval for '.$student->name.'.',
            ]);
        }

        $submission->update([
            'status' => RegistrationSubmission::STATUS_RETURNED,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'note' => $request->input('note', 'Please review your course selection and submit again.'),
        ]);

        return back()->with('status', $student->name."'s pre-registration was returned for revision.");
    }

    private function authorizeAdvisee(User $student): void
    {
        $advisor = auth()->user();

        $allowed = $student->advisor_id === $advisor->staff_id
            || $advisor->isAdmin();

        abort_unless($allowed, 403);
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
