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
        Schema::create('course_offering_requests', function (Blueprint $table) {
            $table->id();
            $table->string('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('session');
            $table->string('semester');
            $table->string('status')->default('pending_advisor')->index();
            $table->text('reason');
            $table->string('advisor_reviewed_by')->nullable();
            $table->foreign('advisor_reviewed_by')->references('id')->on('users')->cascadeOnUpdate()->nullOnDelete();
            $table->text('advisor_note')->nullable();
            $table->timestamp('advisor_reviewed_at')->nullable();
            $table->timestamp('forwarded_at')->nullable();
            $table->string('hod_reviewed_by')->nullable();
            $table->foreign('hod_reviewed_by')->references('id')->on('users')->cascadeOnUpdate()->nullOnDelete();
            $table->text('hod_note')->nullable();
            $table->timestamp('hod_reviewed_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'course_id', 'session', 'semester'], 'course_requests_student_course_term_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_offering_requests');
    }
};
