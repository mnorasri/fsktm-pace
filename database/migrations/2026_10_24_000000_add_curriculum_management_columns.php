<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Columns the curriculum management modules add to the existing tables:
     * the Malay course title and a study plan's number of semesters.
     */
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->string('name_my')->nullable()->after('name');
        });

        Schema::table('course_plans', function (Blueprint $table) {
            $table->unsignedTinyInteger('total_semester')->nullable()->after('version');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('name_my');
        });

        Schema::table('course_plans', function (Blueprint $table) {
            $table->dropColumn('total_semester');
        });
    }
};
