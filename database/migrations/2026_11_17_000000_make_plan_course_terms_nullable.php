<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Elective rows are not tied to a year and semester: the Head of Department
 * adds the course to the plan now and a term is chosen later (or by the
 * student, from the elective catalogue). Allow those rows to carry no term.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plan_courses', function (Blueprint $table) {
            $table->unsignedTinyInteger('year')->nullable()->change();
            $table->unsignedTinyInteger('semester')->nullable()->change();
        });
    }

    public function down(): void
    {
        // A term-less row cannot go back to a required column; park any that
        // remain in Year 1 Semester 1 before restoring the constraint.
        DB::table('plan_courses')->whereNull('year')->update(['year' => 1]);
        DB::table('plan_courses')->whereNull('semester')->update(['semester' => 1]);

        Schema::table('plan_courses', function (Blueprint $table) {
            $table->unsignedTinyInteger('year')->nullable(false)->change();
            $table->unsignedTinyInteger('semester')->nullable(false)->change();
        });
    }
};
