<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The signed-in account's own profile — the one page every role shares.
 */
class ProfileController extends Controller
{
    /**
     * Show the user their own details, editable, next to their password form.
     */
    public function edit(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        return view('profile.edit', [
            'user' => $user->loadMissing(['program', 'plan', 'department', 'advisor']),
        ]);
    }

    /**
     * Save the details a user is trusted to keep up to date themselves.
     */
    public function update(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        // Addresses are case-insensitive, so store them in one shape.
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $user->update($data);

        return back()->with('status', 'Your profile has been updated.');
    }

    /**
     * Change the password, after confirming the current one.
     */
    public function password(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string', 'current_password'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        /** @var User $user */
        $user = $request->user();

        // The User model hashes the value on the way in.
        $user->update(['password' => $data['password']]);

        return back()->with('status', 'Your password has been changed.');
    }
}
