<?php

namespace App\Domains\Admin\Support\Export;

use Filament\Actions;

trait HasCsvExport
{
    /**
     * Build a Filament header action that streams a CSV export of the resource's model.
     *
     * @param array<int,string>|array<string,string> $columns Column list or map column => Header.
     * @param string|null $filename Optional custom filename. Defaults to ModelName-YYYY-mm-dd_HH-ii.csv
     */
    protected function makeExportCsvAction(array $columns, ?string $filename = null): Actions\Action
    {
        return Actions\Action::make('exportCsv')
            ->label(__('Export CSV'))
            ->icon('heroicon-o-arrow-down-tray')
            ->color('gray')
            ->action(function () use ($columns, $filename) {
                // Resolve the Eloquent model class from the resource
                $resourceClass = $this->getResource();
                $modelClass = $resourceClass::getModel();

                $name = $filename ?? (class_basename($modelClass) . '-' . now()->format('Y-m-d_H-i') . '.csv');

                // Prefer the filtered table query (respects search, filters, sorting)
                $query = method_exists($this, 'getFilteredTableQuery')
                    ? $this->getFilteredTableQuery()
                    : $modelClass::query();

                return ExportCsv::streamFromQuery($query, $columns, $name);
            });
    }
}

