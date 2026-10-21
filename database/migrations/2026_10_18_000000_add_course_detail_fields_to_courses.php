<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            // Added as plain nullable columns (no FK constraints) so the
            // change applies cleanly on SQLite.
            $table->decimal('course_load', 5, 1)->nullable();
            $table->unsignedTinyInteger('semester')->nullable();
            $table->unsignedBigInteger('course_plan_id')->nullable()->index();
            $table->string('sdg')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropIndex(['course_plan_id']);
            $table->dropColumn(['course_load', 'semester', 'course_plan_id', 'sdg']);
        });
    }
};
