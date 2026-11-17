<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::table('jobs')->exists()) {
            throw new RuntimeException('Drain the queue before changing user identifiers.');
        }

        $references = [
            'sessions' => ['user_id'],
            'users' => ['plan_reviewed_by'],
        ];
        $foreignKeys = [];

        $schema = DB::getDriverName() === 'sqlite' ? 'main' : DB::getDatabaseName();

        foreach (Schema::getTableListing($schema, false) as $table) {
            foreach (Schema::getForeignKeys($table) as $key) {
                if ($key['foreign_table'] !== 'users') {
                    continue;
                }

                if (count($key['columns']) !== 1 || ! in_array($key['foreign_columns'], [['id'], ['staff_id']], true)) {
                    throw new RuntimeException('Unexpected user foreign key: '.$key['name']);
                }

                $foreignKeys[$table][] = $key;

                if ($key['foreign_columns'] === ['id']) {
                    $references[$table][] = $key['columns'][0];
                }
            }
        }

        Schema::create('_user_key_map', function (Blueprint $table) {
            $table->string('old_id')->primary();
            $table->string('new_id')->unique();
            $table->string('staging_id')->unique();
        });

        try {
            foreach (DB::table('users')->orderBy('id')->get() as $user) {
                $id = (string) match ($user->role) {
                    'student' => $user->student_id,
                    'advisor' => $user->staff_id,
                    'admin' => $user->id,
                    default => throw new RuntimeException('Unknown user role: '.$user->role),
                };

                if (trim($id) === '' || str_starts_with($id, '__user_key_')) {
                    throw new RuntimeException('Missing or reserved identifier for user '.$user->id);
                }

                DB::table('_user_key_map')->insert([
                    'old_id' => (string) $user->id,
                    'new_id' => $id,
                    'staging_id' => '__user_key_'.$user->id,
                ]);
            }

            foreach ($references as $table => $columns) {
                if ($table === 'sessions') {
                    continue;
                }

                foreach (array_unique($columns) as $column) {
                    if (DB::table($table)->whereNotNull($column)
                        ->whereNotIn($column, DB::table('_user_key_map')->select('old_id'))->exists()) {
                        throw new RuntimeException("Unresolved user reference in {$table}.{$column}");
                    }
                }
            }
        } catch (Throwable $exception) {
            Schema::drop('_user_key_map');

            throw $exception;
        }

        foreach ($foreignKeys as $table => $keys) {
            Schema::table($table, function (Blueprint $blueprint) use ($keys) {
                foreach ($keys as $key) {
                    $blueprint->dropForeign(DB::getDriverName() === 'sqlite' ? $key['columns'] : $key['name']);
                }
            });
        }

        foreach ($references as $table => $columns) {
            $metadata = collect(Schema::getColumns($table))->keyBy('name');

            Schema::table($table, function (Blueprint $blueprint) use ($columns, $metadata) {
                foreach (array_unique($columns) as $column) {
                    $blueprint->string($column)->nullable($metadata[$column]['nullable'])->change();
                }
            });
        }

        if (DB::getDriverName() === 'sqlite') {
            Schema::table('users', function (Blueprint $table) {
                $table->string('id')->autoIncrement(false)->change();
            });
        } else {
            DB::statement(
                'ALTER TABLE users MODIFY id VARCHAR(255) NOT NULL, '
                .'DROP PRIMARY KEY, ADD PRIMARY KEY (id), '
                .'ADD UNIQUE KEY users_email_unique (email), DROP INDEX users_id_unique'
            );
        }

        $references['users'][] = 'id';

        DB::transaction(function () use ($references) {
            // Old authentication payloads contain numeric IDs. Keeping them could
            // sign someone into a different account after IDs have been reassigned.
            DB::table('sessions')->delete();
            DB::table('users')->update(['remember_token' => null]);

            foreach ([['old_id', 'staging_id'], ['staging_id', 'new_id']] as [$from, $to]) {
                foreach ($references as $table => $columns) {
                    foreach (array_unique($columns) as $column) {
                        $source = DB::connection()->getQueryGrammar()->wrap($table.'.'.$column);
                        DB::table($table)
                            ->whereIn($column, DB::table('_user_key_map')->select($from))
                            ->update([
                                $column => DB::raw("(SELECT {$to} FROM _user_key_map WHERE {$from} = {$source})"),
                            ]);
                    }
                }
            }
        });

        foreach ($foreignKeys as $table => $keys) {
            Schema::table($table, function (Blueprint $blueprint) use ($keys) {
                foreach ($keys as $key) {
                    $blueprint->foreign($key['columns'], $key['name'])
                        ->references('id')->on('users')
                        ->cascadeOnUpdate()->onDelete($key['on_delete']);
                }
            });
        }

        Schema::table('users', function (Blueprint $table) use ($foreignKeys) {
            $table->foreign('plan_reviewed_by')->references('id')->on('users')
                ->cascadeOnUpdate()->nullOnDelete();

            if (! collect($foreignKeys['users'] ?? [])->contains(fn ($key) => $key['columns'] === ['advisor_id'])) {
                $table->foreign('advisor_id')->references('id')->on('users')
                    ->cascadeOnUpdate()->nullOnDelete();
            }
        });

        Schema::drop('_user_key_map');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        throw new RuntimeException('User identifiers cannot be safely renumbered on rollback. Restore the pre-migration database backup instead.');
    }
};
