<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Align the courses table with the curriculum structure:
 *
 *   course_code, course_name_en, course_name_my, credit_hours,
 *   course_type_code (FK), course_load, pre_requisite, sc_code,
 *   vbe_ept_code (FK), sdg_code (FK)
 *
 * The reference columns become the codes themselves (the configured tables
 * already enforce unique codes). The application keeps the numeric internal id
 * as the primary key, exactly as it does for programmes, study plans and users.
 *
 * SQLite cannot drop a column that takes part in a foreign key, so on SQLite
 * the legacy *_id columns are left in place (unused); MySQL/MariaDB drops them
 * and adds the code-based foreign keys.
 */
return new class extends Migration
{
    /**
     * The reference columns that move from a numeric id to the code itself:
     * [old id column, new code column, referenced table].
     *
     * @var array<int, array{0: string, 1: string, 2: string}>
     */
    protected array $references = [
        ['course_type_id', 'course_type_code', 'course_types'],
        ['mqf_sc_id', 'sc_code', 'mqf_sc'],
        ['vbe_id', 'vbe_ept_code', 'vbe'],
        ['sdg_id', 'sdg_code', 'sdgs'],
    ];

    public function up(): void
    {
        // 1. Add the code-based reference columns.
        Schema::table('courses', function (Blueprint $table) {
            $table->string('course_type_code')->nullable()->after('course_load');
            $table->string('sc_code')->nullable()->after('course_type_code');
            $table->string('vbe_ept_code')->nullable()->after('sc_code');
            $table->string('sdg_code')->nullable()->after('vbe_ept_code');
        });

        // 2. Copy each existing numeric reference to its code.
        $this->copyIdsToCodes();

        // 3. Rename the text columns to the requested structure.
        Schema::table('courses', function (Blueprint $table) {
            $table->renameColumn('code', 'course_code');
            $table->renameColumn('name', 'course_name_en');
            $table->renameColumn('name_my', 'course_name_my');
            $table->renameColumn('credits', 'credit_hours');
            $table->renameColumn('prerequisites', 'pre_requisite');
        });

        // 4. On MySQL/MariaDB drop the legacy id columns and enforce the new
        //    code references. SQLite keeps them (see class docblock).
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('courses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('course_type_id');
            $table->dropConstrainedForeignId('mqf_sc_id');
            $table->dropConstrainedForeignId('vbe_id');
        });

        // sdg_id was added as a plain indexed column, without a foreign key.
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('sdg_id');
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->foreign('course_type_code')->references('code')->on('course_types')->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('sc_code')->references('code')->on('mqf_sc')->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('vbe_ept_code')->references('code')->on('vbe')->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('sdg_code')->references('code')->on('sdgs')->nullOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('courses', function (Blueprint $table) {
                $table->dropForeign(['course_type_code']);
                $table->dropForeign(['sc_code']);
                $table->dropForeign(['vbe_ept_code']);
                $table->dropForeign(['sdg_code']);
            });
        }

        Schema::table('courses', function (Blueprint $table) {
            $table->renameColumn('course_code', 'code');
            $table->renameColumn('course_name_en', 'name');
            $table->renameColumn('course_name_my', 'name_my');
            $table->renameColumn('credit_hours', 'credits');
            $table->renameColumn('pre_requisite', 'prerequisites');
        });

        if (DB::getDriverName() === 'sqlite') {
            Schema::table('courses', function (Blueprint $table) {
                $table->dropColumn('course_type_code');
                $table->dropColumn('sc_code');
                $table->dropColumn('vbe_ept_code');
                $table->dropColumn('sdg_code');
            });

            return;
        }

        Schema::table('courses', function (Blueprint $table) {
            $table->foreignId('course_type_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('mqf_sc_id')->nullable()->constrained('mqf_sc')->nullOnDelete();
            $table->foreignId('vbe_id')->nullable()->constrained('vbe')->nullOnDelete();
            $table->unsignedBigInteger('sdg_id')->nullable()->index();
        });

        $this->copyCodesToIds();

        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('course_type_code');
            $table->dropColumn('sc_code');
            $table->dropColumn('vbe_ept_code');
            $table->dropColumn('sdg_code');
        });
    }

    /**
     * Fill each new code column from the referenced table, one row at a time so
     * the migration runs the same way on SQLite and MariaDB.
     */
    protected function copyIdsToCodes(): void
    {
        $lookups = [];

        foreach ($this->references as [$idColumn, $codeColumn, $table]) {
            $lookups[$idColumn] = [
                'code' => $codeColumn,
                'map' => DB::table($table)->pluck('code', 'id'),
            ];
        }

        DB::table('courses')
            ->select(array_merge(['id'], array_keys($lookups)))
            ->orderBy('id')
            ->each(function ($course) use ($lookups) {
                $update = [];

                foreach ($lookups as $idColumn => $lookup) {
                    $id = $course->{$idColumn} ?? null;
                    $update[$lookup['code']] = $id === null
                        ? null
                        : ($lookup['map'][$id] ?? null);
                }

                DB::table('courses')->where('id', $course->id)->update($update);
            });
    }

    /**
     * Reverse of copyIdsToCodes(), used by down() on MySQL/MariaDB.
     */
    protected function copyCodesToIds(): void
    {
        $lookups = [];

        foreach ($this->references as [$idColumn, $codeColumn, $table]) {
            $lookups[$codeColumn] = [
                'id' => $idColumn,
                'map' => DB::table($table)->pluck('id', 'code'),
            ];
        }

        DB::table('courses')
            ->select(array_merge(['id'], array_keys($lookups)))
            ->orderBy('id')
            ->each(function ($course) use ($lookups) {
                $update = [];

                foreach ($lookups as $codeColumn => $lookup) {
                    $code = $course->{$codeColumn} ?? null;
                    $update[$lookup['id']] = $code === null
                        ? null
                        : ($lookup['map'][$code] ?? null);
                }

                DB::table('courses')->where('id', $course->id)->update($update);
            });
    }
};
