<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * A student's advisor_id is a foreign key to the advisor's staff ID.
     * Deleting an advisor clears their students' link; changing a staff ID
     * carries the students along.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            // SQLite cannot add a foreign key to an existing table; the test
            // schema relies on the validation in the controllers instead.
            return;
        }

        DB::statement(
            'ALTER TABLE users ADD CONSTRAINT users_advisor_id_foreign '
            .'FOREIGN KEY (advisor_id) REFERENCES users (staff_id) '
            .'ON DELETE SET NULL ON UPDATE CASCADE'
        );
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE users DROP FOREIGN KEY users_advisor_id_foreign');
    }
};
