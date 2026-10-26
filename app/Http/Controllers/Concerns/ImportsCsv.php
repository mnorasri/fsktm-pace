<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Throwable;

trait ImportsCsv
{
    /**
     * Read an uploaded CSV into an array of rows keyed by both the header row
     * (lower-cased, trimmed) and the zero-based column position.
     *
     * @return array<int, array<string|int, string>>
     */
    protected function readCsv(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'rb');

        if ($handle === false) {
            return [];
        }

        $header = null;
        $rows = [];

        while (($line = fgetcsv($handle)) !== false) {
            if ($line === [null] || $line === []) {
                continue;
            }

            if ($header === null) {
                $header = array_map(
                    fn ($h) => str_replace(' ', '_', strtolower(trim((string) $h))),
                    $line
                );

                continue;
            }

            $row = [];
            foreach ($header as $index => $key) {
                $value = trim((string) ($line[$index] ?? ''));

                // Key by header name when present, and always retain the raw
                // positional value so callers can address fixed columns.
                if ($key !== '') {
                    $row[$key] = $value;
                }

                $row[$index] = $value;
            }

            if (array_filter($row, fn ($value) => $value !== '') !== []) {
                $rows[] = $row;
            }
        }

        fclose($handle);

        return $rows;
    }

    /**
     * Read a CSV cell by header name, falling back to its fixed column
     * position in the documented import structure.
     *
     * @param  array<string|int, string>  $row
     */
    protected function csvValue(array $row, string $key, int $position): ?string
    {
        $value = $row[$key] ?? $row[$position] ?? null;

        return blank($value) ? null : trim((string) $value);
    }

    /**
     * A short, readable reason a single import row was skipped. Foreign key and
     * unique constraint failures are translated into plain language; anything
     * else keeps its message (minus the SQL that Laravel appends).
     */
    protected function failureReason(Throwable $e): string
    {
        $message = trim($e->getMessage());

        if ($e instanceof QueryException) {
            if (str_contains($message, 'foreign key') || str_contains($message, '1452') || str_contains($message, '1451')) {
                return 'referenced record does not exist (foreign key constraint)';
            }

            if (str_contains($message, 'Duplicate entry') || str_contains($message, 'UNIQUE constraint failed') || str_contains($message, '1062')) {
                return 'duplicate value for a unique column (email, matric or staff ID)';
            }

            // Drop the "(Connection: …, SQL: …)" tail so the alert stays short.
            $message = trim((string) preg_split('/\s*\(Connection:/', $message)[0]);
        }

        if ($message === '') {
            $message = class_basename($e);
        }

        return Str::limit($message, 180);
    }

    /**
     * The flash payload the import summary alert renders: how many rows were
     * created, updated and skipped, plus the reasons (capped for readability).
     *
     * @param  array<int, string>  $failures
     * @return array{title: string, created: int, updated: int, failed: int, failures: array<int, string>, truncated: int}
     */
    protected function importReport(string $label, int $created, int $updated, array $failures): array
    {
        $failed = count($failures);
        $limit = 50;

        return [
            'title' => $failed === 0
                ? "{$label} import complete"
                : "{$label} import finished with skipped rows",
            'created' => $created,
            'updated' => $updated,
            'failed' => $failed,
            'failures' => array_slice($failures, 0, $limit),
            'truncated' => max(0, $failed - $limit),
        ];
    }
}
