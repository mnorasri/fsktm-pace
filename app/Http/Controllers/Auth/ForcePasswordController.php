<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ForcePasswordController extends Controller
{
    /**
     * Ask the signed-in account to replace the default password.
     */
    public function edit(Request $request): View|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $user->must_change_password) {
            return redirect()->route($user->dashboardRoute());
        }

        return view('auth.force-password', ['user' => $user]);
    }

    /**
     * Store the new password and let the account continue to its dashboard.
     */
    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed', 'different:current_password'],
        ]);

        /** @var User $user */
        $user = $request->user();

        // The User model hashes the value on the way in.
        $user->update([
            'password' => $data['password'],
            'must_change_password' => false,
        ]);

        return redirect()->route($user->dashboardRoute())
            ->with('status', 'Your password has been changed.');
    }
}
