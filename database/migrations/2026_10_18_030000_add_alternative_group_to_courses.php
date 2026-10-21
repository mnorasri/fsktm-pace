<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Courses sharing an alternative group are mutually exclusive: a study plan
     * carries exactly one of them (e.g. Pengajian Islam or Pengajian Moral).
     */
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->string('alternative_group', 60)->nullable()->after('sdg_id');
        });

        DB::table('courses')
            ->whereIn('code', ['UQI 10102', 'UQI 10202'])
            ->update(['alternative_group' => 'Pengajian']);
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('alternative_group');
        });
    }
};
