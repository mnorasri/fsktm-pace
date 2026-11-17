<?php

namespace App\Http\Controllers\Concerns;

use App\Models\CoursePlan;
use App\Support\StudyPlanRows;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The CSV download of one study plan. The rows themselves come from
 * StudyPlanRows, so the grid and the export always agree.
 */
trait BuildsStudyPlanCsv
{
    /**
     * Every row of a plan, ready for the year/semester grid.
     *
     * @return Collection<int, array<string, mixed>>
     */
    protected function rowsFor(CoursePlan $plan): Collection
    {
        return app(StudyPlanRows::class)->for($plan);
    }

    /**
     * Download one plan's curriculum as CSV: the same rows as the grid, in
     * term order. Courses without a term come last with empty term cells.
     */
    protected function studyPlanCsv(CoursePlan $plan): StreamedResponse
    {
        $rows = $this->rowsFor($plan)
            ->sortBy(fn (array $row) => sprintf(
                '%02d%02d%s',
                $row['year'] ?? 99,
                $row['semester'] ?? 9,
                $row['code'],
            ))
            ->values();

        $filename = 'study-plan-'.(Str::slug($plan->code) ?: 'plan').'.csv';

        return Response::streamDownload(function () use ($plan, $rows) {
            $out = fopen('php://output', 'w');

            fputcsv($out, [
                'study_plan_code', 'course_code', 'course_name',
                'year', 'semester', 'category', 'credits',
            ], ',', '"', '\\');

            foreach ($rows as $row) {
                fputcsv($out, [
                    $plan->code,
                    $row['code'],
                    $row['name'],
                    $row['year'],
                    $row['semester'],
                    $row['category'],
                    $row['credits'],
                ], ',', '"', '\\');
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
