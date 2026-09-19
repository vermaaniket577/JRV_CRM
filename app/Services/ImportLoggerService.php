<?php

namespace App\Services;

use App\Models\DatabaseImport;
use App\Models\DatabaseImportError;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportLoggerService
{
    /**
     * Get aggregated CRM import statistics for dashboard.
     */
    public function getDashboardSummary(): array
    {
        $totalImports = DatabaseImport::count();
        $completedImports = DatabaseImport::whereIn('status', ['COMPLETED', 'PARTIAL'])->count();

        $recordsInserted = (int) DatabaseImport::sum('records_inserted');
        $recordsUpdated = (int) DatabaseImport::sum('records_updated');
        $recordsSkipped = (int) DatabaseImport::sum('records_skipped');
        $recordsFailed = (int) DatabaseImport::sum('records_failed');
        $tablesImported = (int) DatabaseImport::sum('tables_detected');

        $latestImport = DatabaseImport::latest()->first();

        return [
            'tables_imported' => $tablesImported,
            'records_imported' => $recordsInserted + $recordsUpdated,
            'records_inserted' => $recordsInserted,
            'records_updated' => $recordsUpdated,
            'records_skipped' => $recordsSkipped,
            'records_failed' => $recordsFailed,
            'total_imports' => $totalImports,
            'completed_imports' => $completedImports,
            'last_sync_time' => $latestImport?->completed_at?->diffForHumans() ?? $latestImport?->created_at?->diffForHumans() ?? 'No imports yet',
            'latest_status' => $latestImport?->status ?? 'N/A',
        ];
    }

    /**
     * Export downloadable CSV of errors for an import session.
     */
    public function exportErrorsCsv(DatabaseImport $import): StreamedResponse
    {
        $fileName = "import_errors_{$import->id}_" . date('Ymd_His') . ".csv";

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($import) {
            $handle = fopen('php://output', 'w');

            // Header row
            fputcsv($handle, ['Row', 'Table', 'Column', 'Error Reason', 'Raw Data Snippet', 'Timestamp']);

            DatabaseImportError::where('database_import_id', $import->id)
                ->orderBy('id')
                ->chunk(200, function ($errors) use ($handle) {
                    foreach ($errors as $err) {
                        $rawSnippet = is_array($err->raw_data) ? json_encode($err->raw_data) : (string)$err->raw_data;
                        fputcsv($handle, [
                            $err->row_number ?? 'N/A',
                            $err->table_name,
                            $err->column_name ?? 'General',
                            $err->error_message,
                            substr($rawSnippet, 0, 200),
                            $err->created_at?->toDateTimeString(),
                        ]);
                    }
                });

            fclose($handle);
        }, 200, $headers);
    }
}
