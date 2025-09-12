<?php

use App\Domains\Admin\Support\Export\ExportCsv;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\StreamedResponse;

if (! function_exists('set_testing_locale')) {
    function set_testing_locale(string $locale = 'en'): void
    {
        app()->setLocale($locale);
        config(['app.locale' => $locale]);
    }
}

if (! function_exists('assert_csv_header')) {
    /**
     * Assert the first CSV row equals the expected header.
     *
     * @param array<int, array<int, string|null>> $rows
     * @param array<int, string> $expected
     */
    function assert_csv_header(array $rows, array $expected): void
    {
        $header = $rows[0] ?? [];
        expect($header)->toEqual($expected);
    }
}

if (! function_exists('assert_export_button')) {
    function assert_export_button(BaseTestCase $t, string $url, string $label = 'Export CSV'): void
    {
        $response = $t->get($url);
        $response->assertOk();
        $response->assertSee($label);
    }
}

if (! function_exists('csv_stream_from_list_page')) {
    /**
     * @param class-string $listPageClass Filament List page class (e.g., ListTriggerWarnings::class)
     * @param array<int,string>|array<string,string> $columns Columns passed to ExportCsv
     */
    function csv_stream_from_list_page(string $listPageClass, array $columns, string $filename = 'test.csv'): StreamedResponse
    {
        $component = Livewire::test($listPageClass);
        $page = $component->instance();
        $query = method_exists($page, 'getFilteredTableQuery') ? $page->getFilteredTableQuery() : $page::getModel()::query();

        return ExportCsv::streamFromQuery($query, $columns, $filename);
    }
}

if (! function_exists('capture_streamed_csv')) {
    function capture_streamed_csv(StreamedResponse $stream): string
    {
        ob_start();
        $stream->sendContent();
        $csv = ob_get_clean();

        // Normalize and strip BOM
        $csv = str_replace(["\r\n", "\r"], "\n", $csv);
        return preg_replace('/^\xEF\xBB\xBF/', '', $csv) ?? '';
    }
}

if (! function_exists('parse_csv_lines')) {
    /**
     * @return array<int, array<int, string|null>>
     */
    function parse_csv_lines(string $csv): array
    {
        $lines = array_values(array_filter(explode("\n", trim($csv)), fn ($l) => $l !== ''));
        $rows = [];
        foreach ($lines as $line) {
            $f = fopen('php://memory', 'r+');
            fwrite($f, $line); rewind($f);
            $row = fgetcsv($f) ?: [];
            fclose($f);
            $rows[] = $row;
        }
        return $rows;
    }
}
