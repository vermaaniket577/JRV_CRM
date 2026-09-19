<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SchemaAnalyzerService
{
    /**
     * Compare parsed schema against active CRM database.
     */
    public function analyze(array $parsedTables): array
    {
        $comparison = [
            'tables' => [],
            'summary' => [
                'total_tables' => count($parsedTables),
                'new_tables' => 0,
                'existing_tables' => 0,
                'modified_tables' => 0,
                'identical_tables' => 0,
                'columns_to_add' => 0,
                'columns_to_modify' => 0,
                'requires_approval_count' => 0,
            ],
        ];

        foreach ($parsedTables as $table) {
            $tableName = $table['name'];
            $tableComparison = $this->analyzeTable($table);
            $comparison['tables'][] = $tableComparison;

            // Increment summary stats
            switch ($tableComparison['status']) {
                case 'NEW':
                    $comparison['summary']['new_tables']++;
                    break;
                case 'EXISTS':
                case 'MODIFIED':
                    $comparison['summary']['existing_tables']++;
                    if ($tableComparison['status'] === 'MODIFIED') {
                        $comparison['summary']['modified_tables']++;
                    }
                    break;
                case 'IDENTICAL':
                    $comparison['summary']['identical_tables']++;
                    break;
            }

            foreach ($tableComparison['columns'] as $col) {
                if ($col['action'] === 'add') {
                    $comparison['summary']['columns_to_add']++;
                } elseif ($col['action'] === 'modify' || $col['action'] === 'review') {
                    $comparison['summary']['columns_to_modify']++;
                }
                if ($col['requires_approval']) {
                    $comparison['summary']['requires_approval_count']++;
                }
            }
        }

        return $comparison;
    }

    /**
     * Analyze a single table against current database.
     */
    public function analyzeTable(array $table): array
    {
        $tableName = $table['name'];
        $tableExists = Schema::hasTable($tableName);

        if (!$tableExists) {
            $columns = [];
            foreach ($table['columns'] as $col) {
                $columns[] = [
                    'name' => $col['name'],
                    'type' => $col['type'],
                    'nullable' => $col['nullable'],
                    'default' => $col['default'],
                    'status' => 'NEW',
                    'action' => 'add',
                    'existing_type' => null,
                    'existing_nullable' => null,
                    'existing_default' => null,
                    'is_primary' => $col['primary'],
                    'is_auto_increment' => $col['auto_increment'],
                    'requires_approval' => false,
                    'diff_notes' => 'New column in new table',
                ];
            }

            return [
                'name' => $tableName,
                'status' => 'NEW',
                'action' => 'create',
                'records_count' => $table['record_count'] ?? 0,
                'columns_count' => count($columns),
                'columns' => $columns,
                'create_statement' => $table['create_statement'] ?? null,
                'primary_key' => $table['primary_key'] ?? null,
                'is_dangerous' => false,
                'requires_approval' => false,
            ];
        }

        // Table exists, compare existing columns
        $existingColumns = $this->getExistingTableColumns($tableName);
        $columnsComparison = [];
        $hasDifferences = false;
        $hasDangerousChanges = false;

        $existingColumnNames = array_keys($existingColumns);

        foreach ($table['columns'] as $col) {
            $colName = $col['name'];

            if (!isset($existingColumns[$colName])) {
                // New column to add to existing table
                $columnsComparison[] = [
                    'name' => $colName,
                    'type' => $col['type'],
                    'nullable' => $col['nullable'],
                    'default' => $col['default'],
                    'status' => 'NEW',
                    'action' => 'add',
                    'existing_type' => null,
                    'existing_nullable' => null,
                    'existing_default' => null,
                    'is_primary' => $col['primary'],
                    'is_auto_increment' => $col['auto_increment'],
                    'requires_approval' => false,
                    'diff_notes' => 'Column does not exist in CRM table. Can be safely added.',
                ];
                $hasDifferences = true;
            } else {
                // Column exists in both, compare definitions
                $existingCol = $existingColumns[$colName];
                $diffs = $this->compareColumnDefinitions($col, $existingCol);

                $requiresApproval = $diffs['is_dangerous'];
                if ($requiresApproval) {
                    $hasDangerousChanges = true;
                }

                if ($diffs['is_different']) {
                    $hasDifferences = true;
                    $action = $requiresApproval ? 'review' : 'modify';
                    $status = 'MODIFIED';
                } else {
                    $action = 'keep';
                    $status = 'IDENTICAL';
                }

                $columnsComparison[] = [
                    'name' => $colName,
                    'type' => $col['type'],
                    'nullable' => $col['nullable'],
                    'default' => $col['default'],
                    'status' => $status,
                    'action' => $action,
                    'existing_type' => $existingCol['type'],
                    'existing_nullable' => $existingCol['nullable'],
                    'existing_default' => $existingCol['default'],
                    'is_primary' => $col['primary'],
                    'is_auto_increment' => $col['auto_increment'],
                    'requires_approval' => $requiresApproval,
                    'diff_notes' => $diffs['notes'],
                ];
            }
        }

        $tableStatus = $hasDifferences ? 'MODIFIED' : 'IDENTICAL';

        return [
            'name' => $tableName,
            'status' => $tableStatus,
            'action' => $hasDifferences ? 'update' : 'keep',
            'records_count' => $table['record_count'] ?? 0,
            'columns_count' => count($columnsComparison),
            'columns' => $columnsComparison,
            'create_statement' => $table['create_statement'] ?? null,
            'primary_key' => $table['primary_key'] ?? null,
            'is_dangerous' => $hasDangerousChanges,
            'requires_approval' => $hasDangerousChanges,
        ];
    }

    /**
     * Get list of existing column metadata for a MySQL table.
     */
    protected function getExistingTableColumns(string $tableName): array
    {
        $columns = [];
        try {
            $databaseName = DB::getDatabaseName();
            $results = DB::select("
                SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, COLUMN_KEY, EXTRA
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
            ", [$databaseName, $tableName]);

            foreach ($results as $row) {
                $columns[$row->COLUMN_NAME] = [
                    'name' => $row->COLUMN_NAME,
                    'type' => strtoupper($row->COLUMN_TYPE),
                    'nullable' => $row->IS_NULLABLE === 'YES',
                    'default' => $row->COLUMN_DEFAULT,
                    'is_primary' => $row->COLUMN_KEY === 'PRI',
                    'is_auto_increment' => str_contains($row->EXTRA, 'auto_increment'),
                ];
            }
        } catch (\Throwable $e) {
            // Fallback to Schema column listing
            $colListing = Schema::getColumnListing($tableName);
            foreach ($colListing as $col) {
                $columns[$col] = [
                    'name' => $col,
                    'type' => 'VARCHAR(255)',
                    'nullable' => true,
                    'default' => null,
                    'is_primary' => $col === 'id',
                    'is_auto_increment' => $col === 'id',
                ];
            }
        }

        return $columns;
    }

    /**
     * Compare uploaded column against existing column.
     */
    protected function compareColumnDefinitions(array $uploaded, array $existing): array
    {
        $notes = [];
        $isDangerous = false;
        $isDifferent = false;

        $upType = strtoupper(preg_replace('/\s+/', '', $uploaded['type']));
        $exType = strtoupper(preg_replace('/\s+/', '', $existing['type']));

        // Compare types
        if ($upType !== $exType) {
            $isDifferent = true;
            $notes[] = "Type difference: Existing [{$existing['type']}] vs Uploaded [{$uploaded['type']}]";

            // Check if uploaded type narrows existing or changes fundamentally
            if ($this->isPotentiallyTruncatingTypeChange($existing['type'], $uploaded['type'])) {
                $isDangerous = true;
                $notes[] = "⚠️ Type narrowing or conversion may truncate data!";
            }
        }

        // Compare nullability
        if ($uploaded['nullable'] !== $existing['nullable']) {
            $isDifferent = true;
            $exNullStr = $existing['nullable'] ? 'NULL' : 'NOT NULL';
            $upNullStr = $uploaded['nullable'] ? 'NULL' : 'NOT NULL';
            $notes[] = "Nullability difference: Existing [{$exNullStr}] vs Uploaded [{$upNullStr}]";

            // If existing is NULL and uploaded is NOT NULL, changing might fail if existing rows have NULL
            if ($existing['nullable'] && !$uploaded['nullable']) {
                $isDangerous = true;
                $notes[] = "⚠️ Changing nullable to NOT NULL requires verification of existing NULL records.";
            }
        }

        // Compare primary key
        if ($uploaded['primary'] && !$existing['is_primary']) {
            $isDifferent = true;
            $isDangerous = true;
            $notes[] = "⚠️ Uploaded column marks PRIMARY KEY where existing does not.";
        }

        return [
            'is_different' => $isDifferent,
            'is_dangerous' => $isDangerous,
            'notes' => empty($notes) ? 'Identical definition' : implode(' | ', $notes),
        ];
    }

    /**
     * Detect if a type change might truncate data.
     */
    protected function isPotentiallyTruncatingTypeChange(string $existingType, string $uploadedType): bool
    {
        $ex = strtoupper($existingType);
        $up = strtoupper($uploadedType);

        if ((str_contains($ex, 'TEXT') || str_contains($ex, 'LONGTEXT')) && str_contains($up, 'VARCHAR')) {
            return true;
        }

        // Check VARCHAR length shrinkage: VARCHAR(255) -> VARCHAR(50)
        if (preg_match('/VARCHAR\((\d+)\)/', $ex, $m1) && preg_match('/VARCHAR\((\d+)\)/', $up, $m2)) {
            if ((int)$m2[1] < (int)$m1[1]) {
                return true;
            }
        }

        // Check BIGINT -> INT
        if (str_contains($ex, 'BIGINT') && !str_contains($up, 'BIGINT') && str_contains($up, 'INT')) {
            return true;
        }

        return false;
    }
}
