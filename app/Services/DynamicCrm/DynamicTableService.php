<?php

namespace App\Services\DynamicCrm;

use App\Models\CrmActivityLog;
use App\Models\CrmColumn;
use App\Models\CrmRelationship;
use App\Models\CrmTable;
use App\Models\Tenant;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Generic dynamic table operations — the heart of the metadata-driven CRM engine.
 * Handles listing, CRUD, search, filtering, sorting, pagination for ANY table.
 */
class DynamicTableService
{
    protected DynamicDatabaseManager $dbManager;

    public function __construct(DynamicDatabaseManager $dbManager)
    {
        $this->dbManager = $dbManager;
    }

    /**
     * Get the CrmTable metadata record, validated.
     */
    public function getTableMeta(Tenant $tenant, string $tableName): ?CrmTable
    {
        return CrmTable::where('tenant_id', $tenant->id)
            ->where('table_name', $tableName)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get all active tables for a tenant (for sidebar / menu).
     */
    public function getActiveTables(Tenant $tenant): \Illuminate\Database\Eloquent\Collection
    {
        return CrmTable::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('menu_order')
            ->get();
    }

    /**
     * Get paginated records for a table with search, filters, and sorting.
     */
    public function getRecords(
        Tenant $tenant,
        string $tableName,
        array $options = []
    ): LengthAwarePaginator {
        $table = $this->getTableMeta($tenant, $tableName);
        if (!$table) {
            return new LengthAwarePaginator([], 0, 15);
        }

        $this->dbManager->connectToTenantDb($tenant);
        $query = DB::connection('dynamic_crm')->table($tableName);

        // Search
        if (!empty($options['search'])) {
            $searchTerm = $options['search'];
            $searchableColumns = $table->searchableColumns()->pluck('column_name')->toArray();

            if (!empty($searchableColumns)) {
                $query->where(function ($q) use ($searchableColumns, $searchTerm) {
                    foreach ($searchableColumns as $col) {
                        $q->orWhere($col, 'LIKE', "%{$searchTerm}%");
                    }
                });
            }
        }

        // Column filters
        if (!empty($options['filters']) && is_array($options['filters'])) {
            foreach ($options['filters'] as $colName => $value) {
                if ($value === '' || $value === null) continue;
                // Validate column exists in metadata
                $colMeta = CrmColumn::where('table_id', $table->id)
                    ->where('column_name', $colName)
                    ->first();
                if (!$colMeta) continue;

                if (is_array($value)) {
                    $query->whereIn($colName, $value);
                } else {
                    $query->where($colName, $value);
                }
            }
        }

        // Date range filter
        if (!empty($options['date_from']) && !empty($options['date_column'])) {
            $query->where($options['date_column'], '>=', $options['date_from']);
        }
        if (!empty($options['date_to']) && !empty($options['date_column'])) {
            $query->where($options['date_column'], '<=', $options['date_to']);
        }

        // Sorting
        $sortColumn = $options['sort'] ?? $table->primary_key_column;
        $sortDirection = $options['direction'] ?? 'desc';

        // Validate sort column exists
        $validColumns = $table->columns()->pluck('column_name')->toArray();
        if (!in_array($sortColumn, $validColumns)) {
            $sortColumn = $table->primary_key_column;
        }
        $sortDirection = in_array(strtolower($sortDirection), ['asc', 'desc']) ? $sortDirection : 'desc';

        $query->orderBy($sortColumn, $sortDirection);

        // Pagination
        $perPage = min(max((int)($options['per_page'] ?? 15), 5), 100);
        $page = max((int)($options['page'] ?? 1), 1);

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get a single record by ID with resolved FK values.
     */
    public function getRecord(Tenant $tenant, string $tableName, int $id): ?array
    {
        $table = $this->getTableMeta($tenant, $tableName);
        if (!$table) return null;

        $this->dbManager->connectToTenantDb($tenant);
        $record = DB::connection('dynamic_crm')
            ->table($tableName)
            ->where($table->primary_key_column, $id)
            ->first();

        if (!$record) return null;

        $recordArray = (array) $record;

        // Resolve FK display values
        $fkColumns = CrmColumn::where('table_id', $table->id)
            ->where('is_foreign_key', true)
            ->get();

        foreach ($fkColumns as $fkCol) {
            $fkValue = $recordArray[$fkCol->column_name] ?? null;
            if ($fkValue && $fkCol->references_table) {
                $resolved = $this->resolveForeignKeyDisplay(
                    $tenant,
                    $fkCol->references_table,
                    $fkCol->references_column ?? 'id',
                    $fkValue
                );
                $recordArray["_fk_{$fkCol->column_name}_display"] = $resolved;
            }
        }

        // Get related records (hasMany relationships)
        $incoming = CrmRelationship::where('tenant_id', $tenant->id)
            ->where('target_table', $tableName)
            ->get();

        $relatedData = [];
        foreach ($incoming as $rel) {
            try {
                $relatedRecords = DB::connection('dynamic_crm')
                    ->table($rel->source_table)
                    ->where($rel->source_column, $id)
                    ->limit(50)
                    ->get()
                    ->map(fn($r) => (array) $r)
                    ->toArray();

                $relTable = CrmTable::where('tenant_id', $tenant->id)
                    ->where('table_name', $rel->source_table)
                    ->first();

                $relatedData[] = [
                    'table_name' => $rel->source_table,
                    'display_name' => $relTable?->display_name ?? Str::title(str_replace('_', ' ', $rel->source_table)),
                    'column' => $rel->source_column,
                    'records' => $relatedRecords,
                    'count' => count($relatedRecords),
                ];
            } catch (\Throwable $e) {
                Log::debug("Could not load related records from {$rel->source_table}: " . $e->getMessage());
            }
        }

        $recordArray['_related'] = $relatedData;

        return $recordArray;
    }

    /**
     * Create a new record in a dynamic table.
     */
    public function createRecord(Tenant $tenant, string $tableName, array $data, ?int $userId = null): int
    {
        $table = $this->getTableMeta($tenant, $tableName);
        if (!$table) throw new \Exception("Table '{$tableName}' not found.");

        // Filter to only valid columns
        $validColumns = $table->editableColumns()->pluck('column_name')->toArray();
        $insertData = array_intersect_key($data, array_flip($validColumns));

        // Add timestamps if columns exist
        $allCols = $table->columns()->pluck('column_name')->toArray();
        if (in_array('created_at', $allCols)) {
            $insertData['created_at'] = now();
        }
        if (in_array('updated_at', $allCols)) {
            $insertData['updated_at'] = now();
        }

        $this->dbManager->connectToTenantDb($tenant);
        $id = DB::connection('dynamic_crm')->table($tableName)->insertGetId($insertData);

        // Update record count
        $this->updateTableRecordCount($tenant, $tableName);

        // Log
        CrmActivityLog::log($tenant->id, 'created', $tableName, $id, $insertData, $userId);

        return $id;
    }

    /**
     * Update a record in a dynamic table.
     */
    public function updateRecord(Tenant $tenant, string $tableName, int $id, array $data, ?int $userId = null): bool
    {
        $table = $this->getTableMeta($tenant, $tableName);
        if (!$table) throw new \Exception("Table '{$tableName}' not found.");

        // Filter to only valid editable columns
        $validColumns = $table->editableColumns()->pluck('column_name')->toArray();
        $updateData = array_intersect_key($data, array_flip($validColumns));

        // Add updated_at timestamp
        $allCols = $table->columns()->pluck('column_name')->toArray();
        if (in_array('updated_at', $allCols)) {
            $updateData['updated_at'] = now();
        }

        $this->dbManager->connectToTenantDb($tenant);

        // Get old record for audit log
        $oldRecord = DB::connection('dynamic_crm')
            ->table($tableName)
            ->where($table->primary_key_column, $id)
            ->first();

        $affected = DB::connection('dynamic_crm')
            ->table($tableName)
            ->where($table->primary_key_column, $id)
            ->update($updateData);

        // Log
        CrmActivityLog::log($tenant->id, 'updated', $tableName, $id, [
            'old' => $oldRecord ? (array) $oldRecord : [],
            'new' => $updateData,
        ], $userId);

        return $affected > 0;
    }

    /**
     * Delete a record from a dynamic table.
     */
    public function deleteRecord(Tenant $tenant, string $tableName, int $id, ?int $userId = null): bool
    {
        $table = $this->getTableMeta($tenant, $tableName);
        if (!$table) throw new \Exception("Table '{$tableName}' not found.");

        $this->dbManager->connectToTenantDb($tenant);

        // Get record for audit log before deleting
        $record = DB::connection('dynamic_crm')
            ->table($tableName)
            ->where($table->primary_key_column, $id)
            ->first();

        $deleted = DB::connection('dynamic_crm')
            ->table($tableName)
            ->where($table->primary_key_column, $id)
            ->delete();

        // Update record count
        $this->updateTableRecordCount($tenant, $tableName);

        // Log
        CrmActivityLog::log($tenant->id, 'deleted', $tableName, $id, $record ? (array) $record : [], $userId);

        return $deleted > 0;
    }

    /**
     * Generate Laravel validation rules from column metadata.
     */
    public function generateValidationRules(Tenant $tenant, string $tableName, bool $isUpdate = false): array
    {
        $table = $this->getTableMeta($tenant, $tableName);
        if (!$table) return [];

        $rules = [];
        $columns = $table->editableColumns()->get();

        foreach ($columns as $col) {
            $colRules = [];

            if ($col->is_required && !$isUpdate) {
                $colRules[] = 'required';
            } else {
                $colRules[] = 'nullable';
            }

            // Type-based rules
            switch ($col->data_type) {
                case 'int':
                case 'bigint':
                case 'smallint':
                case 'mediumint':
                case 'tinyint':
                    $colRules[] = 'integer';
                    break;
                case 'decimal':
                case 'float':
                case 'double':
                    $colRules[] = 'numeric';
                    break;
                case 'date':
                    $colRules[] = 'date';
                    break;
                case 'datetime':
                case 'timestamp':
                    $colRules[] = 'date';
                    break;
                case 'varchar':
                case 'char':
                    $colRules[] = 'string';
                    $colRules[] = 'max:255';
                    break;
                case 'text':
                case 'mediumtext':
                case 'longtext':
                    $colRules[] = 'string';
                    $colRules[] = 'max:65535';
                    break;
                case 'enum':
                    if (!empty($col->enum_values)) {
                        $colRules[] = 'in:' . implode(',', $col->enum_values);
                    }
                    break;
            }

            // Name-based rules
            $lower = strtolower($col->column_name);
            if (str_contains($lower, 'email')) {
                $colRules[] = 'email';
            }
            if (str_contains($lower, 'url') || str_contains($lower, 'website')) {
                $colRules[] = 'url';
            }

            $rules[$col->column_name] = $colRules;
        }

        return $rules;
    }

    /**
     * Resolve a foreign key value to its display name.
     */
    public function resolveForeignKeyDisplay(Tenant $tenant, string $targetTable, string $targetColumn, $value): ?string
    {
        $targetMeta = CrmTable::where('tenant_id', $tenant->id)
            ->where('table_name', $targetTable)
            ->first();

        if (!$targetMeta) return (string) $value;

        $displayCol = $targetMeta->display_column;
        if (!$displayCol) return (string) $value;

        try {
            $this->dbManager->connectToTenantDb($tenant);
            $record = DB::connection('dynamic_crm')
                ->table($targetTable)
                ->where($targetColumn, $value)
                ->first();

            if ($record) {
                return $record->{$displayCol} ?? (string) $value;
            }
        } catch (\Throwable $e) {
            Log::debug("FK resolve failed: {$targetTable}.{$targetColumn}={$value} — " . $e->getMessage());
        }

        return (string) $value;
    }

    /**
     * Resolve FK display values for paginated records (bulk).
     */
    public function resolveRecordsForeignKeys(Tenant $tenant, string $tableName, $records): array
    {
        $table = $this->getTableMeta($tenant, $tableName);
        if (!$table) return $records instanceof \Traversable ? iterator_to_array($records) : (array) $records;

        $fkColumns = CrmColumn::where('table_id', $table->id)
            ->where('is_foreign_key', true)
            ->whereNotNull('references_table')
            ->get();

        if ($fkColumns->isEmpty()) {
            return collect($records)->map(fn($r) => (array) $r)->toArray();
        }

        // Pre-fetch all FK values in bulk
        $fkLookups = [];
        foreach ($fkColumns as $fkCol) {
            $values = collect($records)->pluck($fkCol->column_name)->filter()->unique()->values()->toArray();
            if (empty($values)) continue;

            $targetMeta = CrmTable::where('tenant_id', $tenant->id)
                ->where('table_name', $fkCol->references_table)
                ->first();

            if (!$targetMeta || !$targetMeta->display_column) continue;

            try {
                $this->dbManager->connectToTenantDb($tenant);
                $refRecords = DB::connection('dynamic_crm')
                    ->table($fkCol->references_table)
                    ->whereIn($fkCol->references_column ?? 'id', $values)
                    ->get();

                $lookup = [];
                foreach ($refRecords as $r) {
                    $key = $r->{$fkCol->references_column ?? 'id'};
                    $lookup[$key] = $r->{$targetMeta->display_column} ?? (string) $key;
                }

                $fkLookups[$fkCol->column_name] = $lookup;
            } catch (\Throwable $e) {
                Log::debug("Bulk FK resolve failed for {$fkCol->column_name}: " . $e->getMessage());
            }
        }

        // Map records with resolved values
        return collect($records)->map(function ($record) use ($fkLookups) {
            $row = (array) $record;
            foreach ($fkLookups as $colName => $lookup) {
                $val = $row[$colName] ?? null;
                if ($val !== null && isset($lookup[$val])) {
                    $row["_fk_{$colName}_display"] = $lookup[$val];
                }
            }
            return $row;
        })->toArray();
    }

    /**
     * Get FK options for a form select field (related record dropdown).
     */
    public function getForeignKeyOptions(Tenant $tenant, string $referencesTable, string $referencesColumn = 'id', int $limit = 200): array
    {
        $targetMeta = CrmTable::where('tenant_id', $tenant->id)
            ->where('table_name', $referencesTable)
            ->first();

        if (!$targetMeta) return [];

        $displayCol = $targetMeta->display_column ?? $referencesColumn;

        try {
            $this->dbManager->connectToTenantDb($tenant);
            $records = DB::connection('dynamic_crm')
                ->table($referencesTable)
                ->select([$referencesColumn, $displayCol])
                ->orderBy($displayCol)
                ->limit($limit)
                ->get();

            return $records->map(fn($r) => [
                'value' => $r->{$referencesColumn},
                'label' => $r->{$displayCol} ?? $r->{$referencesColumn},
            ])->toArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Update the cached record count for a table.
     */
    protected function updateTableRecordCount(Tenant $tenant, string $tableName): void
    {
        try {
            $this->dbManager->connectToTenantDb($tenant);
            $count = DB::connection('dynamic_crm')->table($tableName)->count();

            CrmTable::where('tenant_id', $tenant->id)
                ->where('table_name', $tableName)
                ->update(['record_count' => $count]);
        } catch (\Throwable $e) {
            // Non-critical
        }
    }
}
