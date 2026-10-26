<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The users table is keyed by email. The numeric id stays as a unique,
     * auto-incrementing alternate key so every existing relation, route and
     * lookup (which all use users.id) keeps working untouched.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            // SQLite cannot change a primary key in place; the test schema
            // keeps the numeric key since the application never keys on email.
            return;
        }

        // One statement, so InnoDB never sees a moment without a unique index
        // on id (the columns of other tables reference it).
        DB::statement(
            'ALTER TABLE users '
            .'DROP INDEX users_email_unique, '
            .'DROP PRIMARY KEY, '
            .'ADD PRIMARY KEY (email), '
            .'ADD UNIQUE KEY users_id_unique (id)'
        );
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement(
            'ALTER TABLE users '
            .'DROP PRIMARY KEY, '
            .'ADD PRIMARY KEY (id), '
            .'DROP INDEX users_id_unique, '
            .'ADD UNIQUE KEY users_email_unique (email)'
        );
    }
};
