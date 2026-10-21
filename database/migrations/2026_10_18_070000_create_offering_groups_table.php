<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One section + lecturer per PAK (advisor) group of an offering: the Head of
     * Department splits a course's registered students by their advisor and gives
     * each group its own section and lecturer.
     */
    public function up(): void
    {
        Schema::create('offering_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offering_id')->constrained()->cascadeOnDelete();
            $table->foreignId('advisor_id')->constrained('users')->cascadeOnDelete();
            $table->string('section')->nullable();
            $table->foreignId('lecturer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['offering_id', 'advisor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offering_groups');
    }
};
