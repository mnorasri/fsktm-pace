<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ---------------------------------------------------------------
        // Global reference / configuration collections
        // (Firestore mirror: departments, programs, course_plans,
        //  course_types, mqf_sc, vbe)
        // ---------------------------------------------------------------
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('level')->nullable(); // Diploma / Degree / Master
            $table->decimal('duration_years', 3, 1)->default(4);
            $table->unsignedInteger('total_credits')->default(120);
            $table->timestamps();
        });

        Schema::create('course_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('version')->nullable();
            $table->unsignedInteger('total_credits')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('course_types', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('mqf_sc', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('vbe', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // ---------------------------------------------------------------
        // Unified identity: admins, advisors, HoDs and students all live
        // in `users`. Role is resolved dynamically from the profile
        // (Firestore mirror: students, advisors, hods, system_admin).
        // ---------------------------------------------------------------
        // SQLite cannot attach foreign keys through ALTER TABLE, so these
        // profile columns are plain indexed integers.
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('student')->index(); // admin|advisor|student
            $table->string('student_id')->nullable()->unique();
            $table->string('staff_id')->nullable()->unique();
            $table->unsignedBigInteger('department_id')->nullable()->index();
            $table->unsignedBigInteger('program_id')->nullable()->index();
            $table->unsignedBigInteger('plan_id')->nullable()->index();
            $table->unsignedBigInteger('advisor_id')->nullable()->index();
            $table->string('phone')->nullable();
            $table->boolean('is_hod')->default(false);
            $table->string('status')->default('active'); // active|inactive
            $table->string('plan_status')->default('draft'); // draft|submitted|approved|rejected
            $table->timestamp('plan_submitted_at')->nullable();
            $table->unsignedBigInteger('plan_reviewed_by')->nullable();
            $table->text('plan_review_note')->nullable();
        });

        // ---------------------------------------------------------------
        // Courses & curriculum mapping
        // ---------------------------------------------------------------
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->unsignedTinyInteger('credits')->default(3);
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('program_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('course_type_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('mqf_sc_id')->nullable()->constrained('mqf_sc')->nullOnDelete();
            $table->foreignId('vbe_id')->nullable()->constrained('vbe')->nullOnDelete();
            $table->text('prerequisites')->nullable(); // comma separated course codes
            $table->text('synopsis')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('plan_courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            // Placeholder keys such as REPLACE_ELECTIVE_1 drive the elective
            // substitution logic on the student's interactive study plan.
            $table->string('placeholder_key')->nullable();
            $table->unsignedTinyInteger('year');
            $table->unsignedTinyInteger('semester');
            $table->string('category')->default('required'); // required|elective|university
            $table->unsignedTinyInteger('credits')->nullable();
            $table->timestamps();
        });

        // ---------------------------------------------------------------
        // Global settings (active academic session / semester)
        // ---------------------------------------------------------------
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // ---------------------------------------------------------------
        // Course offerings per session / semester
        // ---------------------------------------------------------------
        Schema::create('offerings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('session');
            $table->string('semester');
            $table->string('section')->default('01');
            $table->foreignId('program_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('course_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('year')->nullable();
            $table->foreignId('lecturer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('coordinator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('advisee_group_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('capacity')->default(40);
            $table->boolean('is_offered')->default(true);
            $table->string('day')->nullable();
            $table->string('time')->nullable();
            $table->string('venue')->nullable();
            $table->timestamps();
        });

        // ---------------------------------------------------------------
        // Student interactive study plan (4 year grid)
        // ---------------------------------------------------------------
        Schema::create('study_plan_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->string('placeholder_key')->nullable();
            $table->unsignedTinyInteger('year');
            $table->unsignedTinyInteger('semester');
            $table->string('status')->default('PLANNED'); // PLANNED|COMPLETED|EXEMPTED
            $table->timestamps();
        });

        // ---------------------------------------------------------------
        // Course pre-registration requests
        // ---------------------------------------------------------------
        Schema::create('pre_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('offering_id')->constrained()->cascadeOnDelete();
            $table->string('session');
            $table->string('semester');
            $table->unsignedTinyInteger('credits')->default(3);
            $table->string('status')->default('pending'); // pending|approved|rejected
            $table->text('note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            // One registration request per student per offering (an offering
            // already belongs to a single session/semester).
            $table->unique(['user_id', 'offering_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pre_registrations');
        Schema::dropIfExists('study_plan_entries');
        Schema::dropIfExists('offerings');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('plan_courses');
        Schema::dropIfExists('courses');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'role', 'student_id', 'staff_id', 'department_id', 'program_id',
                'plan_id', 'advisor_id', 'phone', 'is_hod', 'status',
                'plan_status', 'plan_submitted_at', 'plan_reviewed_by', 'plan_review_note',
            ]);
        });

        Schema::dropIfExists('vbe');
        Schema::dropIfExists('mqf_sc');
        Schema::dropIfExists('course_types');
        Schema::dropIfExists('course_plans');
        Schema::dropIfExists('programs');
        Schema::dropIfExists('departments');
    }
};
