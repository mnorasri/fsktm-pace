<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per student per term: the pre-registration the student sent to
     * their advisor. Its absence means "not submitted yet"; the individual
     * course requests live in pre_registrations as before.
     */
    public function up(): void
    {
        Schema::create('registration_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('session', 20);
            $table->string('semester', 10);
            $table->string('status')->default('submitted'); // submitted|approved|returned
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'session', 'semester']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_submissions');
    }
};
