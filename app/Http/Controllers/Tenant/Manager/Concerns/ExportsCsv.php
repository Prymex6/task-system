<?php

namespace App\Http\Controllers\Tenant\Manager\Concerns;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams a report out as CSV.
 *
 * Streamed rather than built in memory, because a workspace with three years
 * of time entries produces a file nobody wants held in a PHP array. The BOM
 * is there so Excel opens the accented characters correctly instead of
 * showing mojibake, which is the first thing anyone reports otherwise.
 */
trait ExportsCsv
{
    /**
     * @param list<string> $headings
     * @param iterable<int, array<int, string|int|float|null>> $rows
     */
    protected function streamCsv(string $filename, array $headings, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headings, $rows) {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $headings, ';');

            foreach ($rows as $row) {
                fputcsv($handle, $row, ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
