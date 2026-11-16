<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Lecturer accounts created before the forced-password-change feature also
     * started with the faculty default password, so ask them to choose their
     * own on their next sign-in.
     */
    public function up(): void
    {
        DB::table('users')
            ->where('role', 'advisor')
            ->update(['must_change_password' => true]);
    }

    public function down(): void
    {
        // Nothing to undo: the flag is cleared as each account signs in.
    }
};
