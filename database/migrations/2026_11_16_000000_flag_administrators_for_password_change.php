<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Students and lecturers were flagged when the feature was introduced.
     * Administrators were not, so an administrator account that still signs in
     * with a shared default password is asked to choose its own now.
     *
     * bcrypt verification is deliberately expensive, so only the handful of
     * administrator rows are examined here; every other role is covered by the
     * earlier migrations and by the check performed at sign-in.
     */
    public function up(): void
    {
        $defaults = ['R@hsi4', 'R@hsi4#', 'P@ssw0rd'];

        DB::table('users')
            ->where('role', 'admin')
            ->where('must_change_password', false)
            ->orderBy('id')
            ->get()
            ->each(function (object $user) use ($defaults): void {
                foreach ($defaults as $default) {
                    if (Hash::check($default, $user->password)) {
                        DB::table('users')
                            ->where('id', $user->id)
                            ->update(['must_change_password' => true]);

                        break;
                    }
                }
            });
    }

    public function down(): void
    {
        // The flag is cleared as each account chooses its own password.
    }
};
