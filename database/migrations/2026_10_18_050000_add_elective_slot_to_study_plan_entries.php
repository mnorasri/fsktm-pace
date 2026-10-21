<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * An "Elective Option" row (course type CT07A — Elektif I … VI) is a
     * curriculum slot the student fills with one of the plan's "Elective
     * Course" (CT07) courses. The chosen course is stored in course_id, so
     * every existing reader of an entry keeps working, while elective_slot_id
     * remembers which slot it fills.
     */
    public function up(): void
    {
        Schema::table('study_plan_entries', function (Blueprint $table) {
            $table->foreignId('elective_slot_id')
                ->nullable()
                ->after('course_id')
                ->constrained('courses')
                ->nullOnDelete();
        });

        // The elective catalogue used to be seeded as if it were a term course
        // — every elective landed in Year 1 Semester 1. Those rows are now the
        // pool the Elective Option dropdown picks from, so drop them. Only the
        // student's own catalogue is touched: an elective from another
        // programme's plan that a student placed deliberately stays put.
        DB::table('study_plan_entries')
            ->whereNull('elective_slot_id')
            ->whereIn('course_id', function ($query) {
                $query->select('courses.id')
                    ->from('courses')
                    ->whereIn('courses.course_type_id', function ($inner) {
                        $inner->select('id')
                            ->from('course_types')
                            ->where('code', 'CT07');
                    })
                    ->whereColumn('courses.course_plan_id', 'study_plan_entries.course_plan_id');
            })
            ->delete();
    }

    public function down(): void
    {
        Schema::table('study_plan_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('elective_slot_id');
        });
    }
};
