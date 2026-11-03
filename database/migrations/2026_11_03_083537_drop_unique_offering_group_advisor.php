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
        Schema::table('offering_groups', function (Blueprint $table) {
            // Keep an index for the FK columns before dropping the unique one.
            $table->index(['offering_id', 'advisor_id']);
        });

        Schema::table('offering_groups', function (Blueprint $table) {
            $table->dropUnique(['offering_id', 'advisor_id']);
        });
    }

    public function down(): void
    {
        Schema::table('offering_groups', function (Blueprint $table) {
            $table->unique(['offering_id', 'advisor_id']);
        });

        Schema::table('offering_groups', function (Blueprint $table) {
            $table->dropIndex(['offering_id', 'advisor_id']);
        });
    }
};
