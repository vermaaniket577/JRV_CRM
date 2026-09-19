<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DatabaseAuditLog;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DatabaseTableController extends Controller
{
    /**
     * List all database tables in the CRM.
     */
    public function index(Request $request): Response
    {
        $dbName = DB::getDatabaseName();
        $rawTables = DB::select("
            SELECT TABLE_NAME, TABLE_ROWS, CREATE_TIME, UPDATE_TIME, (DATA_LENGTH + INDEX_LENGTH) as size_bytes
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = ?
            ORDER BY TABLE_NAME ASC
        ", [$dbName]);

        $tables = [];
        $search = strtolower($request->query('search', ''));

        foreach ($rawTables as $row) {
            $tableName = $row->TABLE_NAME;

            // Optional search filter
            if (!empty($search) && !str_contains(strtolower($tableName), $search)) {
                continue;
            }

            // Accurate count
            $realCount = DB::table($tableName)->count();

            $tables[] = [
                'table_name' => $tableName,
                'record_count' => $realCount,
                'size_formatted' => $this->formatBytes($row->size_bytes ?? 0),
                'create_time' => $row->CREATE_TIME,
                'update_time' => $row->UPDATE_TIME,
                'is_crm_core' => in_array($tableName, ['users', 'contacts', 'crm_sales_leads', 'properties', 'companies', 'tenants']),
            ];
        }

        return Inertia::render('Admin/Database/Tables', [
            'tables' => $tables,
            'filters' => ['search' => $search],
        ]);
    }

    /**
     * Generic dynamic CRM data viewer for any table.
     */
    public function show(Request $request, string $table): Response
    {
        if (!Schema::hasTable($table)) {
            abort(404, "Database table `{$table}` does not exist.");
        }

        $columns = $this->getTableColumnsInfo($table);
        $columnNames = array_column($columns, 'name');

        $query = DB::table($table);

        // 1. Search across all text/varchar columns
        $search = $request->query('search', '');
        if (!empty($search)) {
            $query->where(function ($q) use ($columns, $search) {
                foreach ($columns as $col) {
                    if (str_contains($col['type'], 'VARCHAR') || str_contains($col['type'], 'TEXT') || str_contains($col['type'], 'CHAR')) {
                        $q->orWhere($col['name'], 'like', "%{$search}%");
                    }
                }
            });
        }

        // 2. Date filtering
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');
        $dateCol = in_array('created_at', $columnNames) ? 'created_at' : (in_array('date', $columnNames) ? 'date' : null);

        if ($dateCol && !empty($dateFrom)) {
            $query->whereDate($dateCol, '>=', $dateFrom);
        }
        if ($dateCol && !empty($dateTo)) {
            $query->whereDate($dateCol, '<=', $dateTo);
        }

        // 3. Sorting
        $sortColumn = $request->query('sort', in_array('id', $columnNames) ? 'id' : $columnNames[0]);
        $sortDirection = strtolower($request->query('direction', 'desc')) === 'asc' ? 'asc' : 'desc';

        if (in_array($sortColumn, $columnNames)) {
            $query->orderBy($sortColumn, $sortDirection);
        }

        // 4. Pagination
        $perPage = min(100, max(10, (int) $request->query('per_page', 20)));
        $records = $query->paginate($perPage)->withQueryString();

        $primaryKey = $this->getPrimaryKeyColumn($table);

        return Inertia::render('Admin/Database/TableView', [
            'tableName' => $table,
            'primaryKey' => $primaryKey,
            'columns' => $columns,
            'records' => $records,
            'filters' => [
                'search' => $search,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'sort' => $sortColumn,
                'direction' => $sortDirection,
                'per_page' => $perPage,
            ],
            'totalRecords' => DB::table($table)->count(),
        ]);
    }

    /**
     * Store new record dynamically.
     */
    public function storeRecord(Request $request, string $table): JsonResponse
    {
        if (!Schema::hasTable($table)) {
            abort(404, "Table not found");
        }

        $columns = Schema::getColumnListing($table);
        $data = $request->only($columns);

        if (in_array('created_at', $columns) && empty($data['created_at'])) {
            $data['created_at'] = now();
        }
        if (in_array('updated_at', $columns) && empty($data['updated_at'])) {
            $data['updated_at'] = now();
        }

        $id = DB::table($table)->insertGetId($data);

        DatabaseAuditLog::record(
            action: 'RECORD CREATED',
            tableName: $table,
            newValue: "Created new record #{$id} in {$table}",
            metadata: ['data' => $data]
        );

        return response()->json([
            'success' => true,
            'message' => 'Record created successfully.',
            'id' => $id,
        ]);
    }

    /**
     * Update existing record dynamically.
     */
    public function updateRecord(Request $request, string $table, $id): JsonResponse
    {
        if (!Schema::hasTable($table)) {
            abort(404, "Table not found");
        }

        $primaryKey = $this->getPrimaryKeyColumn($table) ?? 'id';
        $columns = Schema::getColumnListing($table);
        $data = $request->only($columns);
        unset($data[$primaryKey]); // Do not modify primary key

        if (in_array('updated_at', $columns)) {
            $data['updated_at'] = now();
        }

        $oldRecord = DB::table($table)->where($primaryKey, $id)->first();
        DB::table($table)->where($primaryKey, $id)->update($data);

        DatabaseAuditLog::record(
            action: 'RECORD UPDATED',
            tableName: $table,
            previousValue: json_encode($oldRecord),
            newValue: json_encode($data),
            metadata: ['id' => $id]
        );

        return response()->json([
            'success' => true,
            'message' => 'Record updated successfully.',
        ]);
    }

    /**
     * Delete record dynamically.
     */
    public function destroyRecord(Request $request, string $table, $id): JsonResponse
    {
        if (!Schema::hasTable($table)) {
            abort(404, "Table not found");
        }

        $primaryKey = $this->getPrimaryKeyColumn($table) ?? 'id';
        $oldRecord = DB::table($table)->where($primaryKey, $id)->first();

        DB::table($table)->where($primaryKey, $id)->delete();

        DatabaseAuditLog::record(
            action: 'RECORD DELETED',
            tableName: $table,
            previousValue: json_encode($oldRecord),
            newValue: null,
            metadata: ['id' => $id]
        );

        return response()->json([
            'success' => true,
            'message' => 'Record deleted successfully.',
        ]);
    }

    /**
     * Export dynamic table data to CSV.
     */
    public function exportCsv(Request $request, string $table): StreamedResponse
    {
        if (!Schema::hasTable($table)) {
            abort(404, "Table not found");
        }

        $fileName = "{$table}_export_" . date('Ymd_His') . ".csv";
        $columns = Schema::getColumnListing($table);

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($table, $columns) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $columns);

            DB::table($table)->orderBy(DB::raw('1'))->chunk(200, function ($rows) use ($handle, $columns) {
                foreach ($rows as $row) {
                    $line = [];
                    foreach ($columns as $col) {
                        $val = $row->$col ?? '';
                        $line[] = is_array($val) ? json_encode($val) : (string)$val;
                    }
                    fputcsv($handle, $line);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Export dynamic table data to Excel formatted CSV.
     */
    public function exportExcel(Request $request, string $table): StreamedResponse
    {
        return $this->exportCsv($request, $table);
    }

    /**
     * Fetch detailed column information.
     */
    protected function getTableColumnsInfo(string $tableName): array
    {
        $dbName = DB::getDatabaseName();
        $results = DB::select("
            SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_KEY, EXTRA
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
            ORDER BY ORDINAL_POSITION ASC
        ", [$dbName, $tableName]);

        $columns = [];
        foreach ($results as $r) {
            $columns[] = [
                'name' => $r->COLUMN_NAME,
                'type' => strtoupper($r->COLUMN_TYPE),
                'nullable' => $r->IS_NULLABLE === 'YES',
                'is_primary' => $r->COLUMN_KEY === 'PRI',
                'is_auto_increment' => str_contains($r->EXTRA, 'auto_increment'),
            ];
        }

        return $columns;
    }

    protected function getPrimaryKeyColumn(string $tableName): ?string
    {
        $dbName = DB::getDatabaseName();
        $res = DB::selectOne("
            SELECT COLUMN_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = 'PRIMARY'
            LIMIT 1
        ", [$dbName, $tableName]);

        return $res?->COLUMN_NAME ?? (Schema::hasColumn($tableName, 'id') ? 'id' : null);
    }

    protected function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB'];
        $power = floor(log($bytes, 1024));
        return round($bytes / pow(1024, $power), 2) . ' ' . $units[$power];
    }
}
