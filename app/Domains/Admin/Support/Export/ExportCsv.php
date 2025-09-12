<?php

namespace App\Domains\Admin\Support\Export;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportCsv
{
    /**
     * Stream a CSV download from the given query.
     *
     * @param  Builder  $query The Eloquent builder to stream from (will use cursor()).
     * @param  array<string,string>|array<int,string>  $columns Map of column => Header, or a list of column names.
     * @param  string  $filename The filename to download.
     * @param  string  $delimiter CSV delimiter, default comma.
     */
    public static function streamFromQuery(Builder $query, array $columns, string $filename = 'export.csv', string $delimiter = ','): StreamedResponse
    {
        // Normalize columns to [column => Header] form
        $normalized = [];
        foreach ($columns as $key => $value) {
            if (is_int($key)) {
                $normalized[$value] = Str::headline($value);
            } else {
                $normalized[$key] = $value;
            }
        }

        return response()->streamDownload(function () use ($query, $normalized, $delimiter) {
            $handle = fopen('php://output', 'w');

            // Output UTF-8 BOM for Excel compatibility
            fwrite($handle, "\xEF\xBB\xBF");

            // Header row
            fputcsv($handle, array_values($normalized), $delimiter);

            // Stream rows using cursor to avoid memory spikes
            foreach ($query->orderBy($query->getModel()->getKeyName())->cursor() as $model) {
                $row = [];
                foreach ($normalized as $column => $header) {
                    $value = data_get($model, $column);
                    // Convert booleans to 1/0
                    if (is_bool($value)) {
                        $value = $value ? 1 : 0;
                    }
                    // Convert arrays/objects to JSON for safety
                    if (is_array($value) || is_object($value)) {
                        $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    }
                    $row[] = $value;
                }
                fputcsv($handle, $row, $delimiter);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
