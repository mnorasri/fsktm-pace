<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A student's advisor is referenced by the advisor's staff ID
     * (e.g. "norasri") instead of the internal user ID.
     */
    public function up(): void
    {
        // Resolve the current numeric links before the column type changes.
        $staffIds = DB::table('users')->whereNotNull('staff_id')->pluck('staff_id', 'id');
        $links = DB::table('users')->whereNotNull('advisor_id')->pluck('advisor_id', 'id');

        Schema::table('users', function (Blueprint $table) {
            $table->string('advisor_id')->nullable()->change();
        });

        foreach ($links as $studentId => $advisorId) {
            DB::table('users')->where('id', $studentId)->update([
                'advisor_id' => $staffIds[(int) $advisorId] ?? null,
            ]);
        }
    }

    public function down(): void
    {
        $ids = DB::table('users')->whereNotNull('staff_id')->pluck('id', 'staff_id');
        $links = DB::table('users')->whereNotNull('advisor_id')->pluck('advisor_id', 'id');

        // Clear the staff IDs before the column becomes numeric again.
        DB::table('users')->whereNotNull('advisor_id')->update(['advisor_id' => null]);

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('advisor_id')->nullable()->change();
        });

        foreach ($links as $studentId => $staffId) {
            DB::table('users')->where('id', $studentId)->update([
                'advisor_id' => $ids[$staffId] ?? null,
            ]);
        }
    }
};
