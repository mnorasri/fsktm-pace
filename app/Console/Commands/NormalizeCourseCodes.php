<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

#[Signature('courses:normalize-codes {--dry-run : Preview whitespace removal and duplicate consolidation}')]
#[Description('Remove whitespace from course codes, preserving linked records and a private backup')]
class NormalizeCourseCodes extends Command
{
    private const REFERENCES = [
        'plan_courses' => ['course_id'],
        'study_plan_entries' => ['course_id', 'elective_slot_id'],
        'offerings' => ['course_id'],
        'course_offering_requests' => ['course_id'],
    ];

    public function handle(): int
    {
        try {
            return DB::transaction(function () {
                $courses = DB::table('courses')->orderBy('id')->lockForUpdate()->get();
                $groups = $courses->groupBy(fn ($course) => mb_strtolower($this->normalize($course->course_code)));
                $referenceCounts = [];
                foreach (self::REFERENCES as $table => $columns) {
                    foreach ($columns as $column) {
                        foreach (DB::table($table)->select($column)->selectRaw('COUNT(*) AS total')->whereNotNull($column)->groupBy($column)->get() as $count) {
                            $referenceCounts[$count->{$column}] = ($referenceCounts[$count->{$column}] ?? 0) + $count->total;
                        }
                    }
                }
                $mapping = [];
                $merges = [];
                $updates = [];

                foreach ($groups as $group) {
                    // Keep the record already used by the most linked rows, then the oldest.
                    $keeper = $group->sortByDesc(fn ($course) => $referenceCounts[$course->id] ?? 0)->first();
                    $code = $this->normalize($keeper->course_code);
                    if ($code === '') {
                        throw new RuntimeException('Course #'.$keeper->id.' has an empty normalized code.');
                    }
                    $attributes = ['course_code' => $code];
                    $prerequisites = [];
                    foreach ($group as $course) {
                        $mapping[$course->id] = $keeper->id;
                        foreach (explode(',', $course->pre_requisite ?? '') as $prerequisite) {
                            $prerequisite = $this->normalize($prerequisite);
                            if ($prerequisite !== '') {
                                $prerequisites[] = $prerequisite;
                            }
                        }
                        if ($course->id !== $keeper->id) {
                            $merges[$course->id] = $keeper->id;
                            $this->line('Merge #'.$course->id.' '.$course->course_code.' into #'.$keeper->id.' '.$code);
                            // Fill missing metadata, but do not overwrite the in-use record.
                            foreach ((array) $course as $field => $value) {
                                if (! in_array($field, ['id', 'course_code', 'pre_requisite', 'created_at', 'updated_at'], true)
                                    && ($keeper->{$field} === null || $keeper->{$field} === '')
                                    && $value !== null && $value !== '') {
                                    $attributes[$field] ??= $value;
                                }
                            }
                        }
                    }
                    $attributes['pre_requisite'] = $prerequisites === [] ? null : implode(', ', array_unique($prerequisites));
                    if ($group->count() > 1 || $keeper->course_code !== $code || $keeper->pre_requisite !== $attributes['pre_requisite']) {
                        $updates[$keeper->id] = $attributes;
                    }
                }

                $this->info(count($updates).' course record(s) to normalize; '.count($merges).' duplicate(s) to consolidate.');
                if ($this->option('dry-run') || $updates === []) {
                    return self::SUCCESS;
                }

                $backup = ['courses' => $courses, 'course_id_mapping' => $mapping, 'linked_records' => []];
                foreach (self::REFERENCES as $table => $columns) {
                    $backup['linked_records'][$table] = DB::table($table)
                        ->where(function ($query) use ($columns, $mapping) {
                            foreach ($columns as $column) {
                                $query->orWhereIn($column, array_keys($mapping));
                            }
                        })->lockForUpdate()->get();
                }
                $path = 'course-code-backups/'.now()->format('Ymd-His').'-'.Str::uuid().'.json';
                if (! Storage::disk('local')->put($path, json_encode($backup, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR), ['visibility' => 'private'])) {
                    throw new RuntimeException('Could not save the backup; no course data was changed.');
                }
                $this->info('Backup: '.Storage::disk('local')->path($path));

                foreach ($merges as $source => $target) {
                    foreach (self::REFERENCES as $table => $columns) {
                        foreach ($columns as $column) {
                            DB::table($table)->where($column, $source)->update([$column => $target]);
                        }
                    }
                    DB::table('courses')->where('id', $source)->delete();
                }
                foreach ($updates as $id => $attributes) {
                    DB::table('courses')->where('id', $id)->update($attributes);
                }
                $this->info('Course codes normalized. All linked rows were preserved.');

                return self::SUCCESS;
            });
        } catch (Throwable $exception) {
            $this->error('Normalization rolled back: '.$exception->getMessage());

            return self::FAILURE;
        }
    }

    private function normalize(string $code): string
    {
        return preg_replace('/[\s\p{Z}]+/u', '', $code);
    }
}
