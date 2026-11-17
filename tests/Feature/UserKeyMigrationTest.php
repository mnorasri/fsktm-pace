<?php

use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    $this->originalConnection = DB::getDefaultConnection();
    config(['database.connections.user_key_migration_test' => [
        'driver' => 'sqlite', 'database' => ':memory:', 'foreign_key_constraints' => true,
    ]]);
    DB::setDefaultConnection('user_key_migration_test');

    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('role');
        $table->string('staff_id')->nullable()->unique();
        $table->string('student_id')->nullable()->unique();
        $table->string('email')->unique();
        $table->string('remember_token')->nullable();
        $table->unsignedBigInteger('plan_reviewed_by')->nullable();
        $table->string('advisor_id')->nullable();
        $table->foreign('advisor_id')->references('staff_id')->on('users')->nullOnDelete();
    });
    Schema::create('jobs', fn (Blueprint $table) => $table->id());
    Schema::create('sessions', function (Blueprint $table) {
        $table->string('id')->primary();
        $table->unsignedBigInteger('user_id')->nullable();
    });
    foreach ([
        'offerings' => ['lecturer_id', 'coordinator_id', 'advisee_group_id'],
        'offering_groups' => ['advisor_id', 'lecturer_id'],
        'offering_sections' => ['lecturer_id'],
        'pre_registrations' => ['user_id', 'reviewed_by'],
        'registration_submissions' => ['user_id', 'reviewed_by'],
        'study_plan_entries' => ['user_id'],
        'programs' => ['head_of_program_id'],
    ] as $name => $columns) {
        Schema::create($name, function (Blueprint $table) use ($columns) {
            $table->id();
            foreach ($columns as $column) {
                $table->foreignId($column)->nullable()->constrained('users')->nullOnDelete();
            }
        });
    }
});

afterEach(function () {
    DB::purge('user_key_migration_test');
    DB::setDefaultConnection($this->originalConnection);
});

test('the user key migration remaps all references without losing swapped numeric identifiers', function () {
    DB::table('users')->insert([
        ['id' => 1, 'role' => 'admin', 'email' => 'admin@test', 'staff_id' => null, 'student_id' => null, 'remember_token' => 'old'],
        ['id' => 2, 'role' => 'advisor', 'email' => 'staff@test', 'staff_id' => '3', 'student_id' => null, 'remember_token' => 'old'],
        ['id' => 3, 'role' => 'student', 'email' => 'student@test', 'staff_id' => null, 'student_id' => '2', 'remember_token' => 'old'],
    ]);
    DB::table('users')->where('id', 3)->update(['advisor_id' => '3', 'plan_reviewed_by' => 2]);
    DB::table('sessions')->insert(['id' => 'old-session', 'user_id' => 3]);
    $expected = [
        'offerings' => ['lecturer_id' => '3', 'coordinator_id' => '3', 'advisee_group_id' => '3'],
        'offering_groups' => ['advisor_id' => '3', 'lecturer_id' => '3'],
        'offering_sections' => ['lecturer_id' => '3'],
        'pre_registrations' => ['user_id' => '2', 'reviewed_by' => '3'],
        'registration_submissions' => ['user_id' => '2', 'reviewed_by' => '3'],
        'study_plan_entries' => ['user_id' => '2'],
        'programs' => ['head_of_program_id' => '3'],
    ];
    foreach ($expected as $table => $values) {
        DB::table($table)->insert(array_map(fn ($id) => $id === '3' ? 2 : 3, $values));
    }

    $migration = require database_path('migrations/2026_11_17_211047_use_staff_and_student_identifiers_as_user_keys.php');
    $migration->up();

    $this->assertDatabaseHas('users', ['id' => '1', 'role' => 'admin']);
    $this->assertDatabaseHas('users', ['id' => '3', 'role' => 'advisor']);
    $this->assertDatabaseHas('users', [
        'id' => '2', 'role' => 'student', 'advisor_id' => '3', 'plan_reviewed_by' => '3', 'remember_token' => null,
    ]);
    foreach ($expected as $table => $values) {
        $this->assertDatabaseHas($table, $values);
        $this->assertDatabaseCount($table, 1);
    }
    $this->assertDatabaseCount('sessions', 0);
    expect(DB::select('PRAGMA foreign_key_check'))->toBe([]);
    expect(Schema::hasTable('_user_key_map'))->toBeFalse();

    DB::table('users')->where('id', '3')->update(['id' => 'renamed', 'staff_id' => 'renamed']);
    $this->assertDatabaseHas('offerings', ['lecturer_id' => 'renamed']);
    $this->assertDatabaseHas('users', ['id' => '2', 'advisor_id' => 'renamed', 'plan_reviewed_by' => 'renamed']);
});

test('the migration refuses missing or colliding user identifiers before changing the schema', function (?string $staffId, string $exception) {
    DB::table('users')->insert([
        ['id' => 1, 'role' => 'admin', 'email' => 'admin@test', 'staff_id' => null],
        ['id' => 2, 'role' => 'advisor', 'email' => 'staff@test', 'staff_id' => $staffId],
    ]);
    $migration = require database_path('migrations/2026_11_17_211047_use_staff_and_student_identifiers_as_user_keys.php');

    expect(fn () => $migration->up())->toThrow($exception);

    $this->assertDatabaseHas('users', ['id' => 2, 'email' => 'staff@test']);
    expect(collect(Schema::getColumns('users'))->firstWhere('name', 'id')['type_name'])->toBe('integer');
    expect(Schema::hasTable('_user_key_map'))->toBeFalse();
})->with([
    'missing' => [null, RuntimeException::class],
    'admin collision' => ['1', QueryException::class],
]);

test('the migration does not inspect or change user references in another database', function () {
    DB::statement("ATTACH DATABASE ':memory:' AS other");
    DB::statement('CREATE TABLE other.users (id INTEGER PRIMARY KEY)');
    DB::statement('CREATE TABLE other.assignments (id INTEGER PRIMARY KEY, user_id INTEGER REFERENCES users(id))');
    DB::statement('INSERT INTO other.users (id) VALUES (999)');
    DB::statement('INSERT INTO other.assignments (id, user_id) VALUES (1, 999)');

    $migration = require database_path('migrations/2026_11_17_211047_use_staff_and_student_identifiers_as_user_keys.php');
    $migration->up();

    expect(DB::table('other.assignments')->value('user_id'))->toBe(999);
    expect(collect(Schema::getColumns('other.assignments'))->firstWhere('name', 'user_id')['type_name'])->toBe('integer');
    expect(Schema::getForeignKeys('other.assignments'))->toHaveCount(1);
});
