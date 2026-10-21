<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\UploadedFile;

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
}
