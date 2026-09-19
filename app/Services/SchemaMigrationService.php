<?php

namespace App\Services;

use App\Models\DatabaseAuditLog;
use App\Models\DatabaseImport;
use App\Models\DatabaseImportColumn;
use App\Models\DatabaseImportTable;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SchemaMigrationService
{
    /**
     * Apply schema migrations for an import session.
     */
    public function applyMigrations(DatabaseImport $import, array $approvedColumnIds = []): array
    {
        $results = [
            'tables_created' => 0,
            'tables_updated' => 0,
            'columns_added' => 0,
            'columns_modified' => 0,
            'skipped_dangerous' => 0,
            'errors' => [],
        ];

        // Load tables with columns
        $tables = DatabaseImportTable::where('database_import_id', $import->id)->with('columns')->get();

        foreach ($tables as $table) {
            $tableName = $table->table_name;

            // 1. Create missing table
            if (!Schema::hasTable($tableName)) {
                try {
                    $this->createTable($table);
                    $results['tables_created']++;
                    $table->update(['status' => 'EXISTS', 'action' => 'create']);

                    DatabaseAuditLog::record(
                        action: 'CREATE TABLE',
                        tableName: $tableName,
                        newValue: "Created new table {$tableName} with {$table->columns->count()} columns",
                        metadata: ['import_id' => $import->id]
                    );
                } catch (\Throwable $e) {
                    $results['errors'][] = "Failed to create table {$tableName}: " . $e->getMessage();
                    Log::error("SchemaMigrationService createTable error: " . $e->getMessage());
                }
                continue;
            }

            // 2. Table exists, add or modify columns
            $tableUpdated = false;
            foreach ($table->columns as $column) {
                if ($column->action === 'add' && !Schema::hasColumn($tableName, $column->column_name)) {
                    try {
                        $this->addColumn($tableName, $column);
                        $results['columns_added']++;
                        $tableUpdated = true;

                        DatabaseAuditLog::record(
                            action: 'ADD COLUMN',
                            tableName: $tableName,
                            columnName: $column->column_name,
                            previousValue: 'Not Available',
                            newValue: $column->data_type,
                            metadata: ['import_id' => $import->id]
                        );
                    } catch (\Throwable $e) {
                        $results['errors'][] = "Failed to add column {$column->column_name} to {$tableName}: " . $e->getMessage();
                    }
                } elseif (in_array($column->id, $approvedColumnIds) || $column->is_approved) {
                    // Approved modification
                    try {
                        $this->modifyColumn($tableName, $column);
                        $results['columns_modified']++;
                        $tableUpdated = true;

                        DatabaseAuditLog::record(
                            action: 'MODIFY COLUMN',
                            tableName: $tableName,
                            columnName: $column->column_name,
                            previousValue: $column->existing_data_type,
                            newValue: $column->data_type,
                            metadata: ['import_id' => $import->id, 'approved' => true]
                        );
                    } catch (\Throwable $e) {
                        $results['errors'][] = "Failed to modify column {$column->column_name} on {$tableName}: " . $e->getMessage();
                    }
                } elseif ($column->requires_approval) {
                    $results['skipped_dangerous']++;
                }
            }

            if ($tableUpdated) {
                $results['tables_updated']++;
            }
        }

        return $results;
    }

    /**
     * Create table using parsed create statement or structured columns.
     */
    protected function createTable(DatabaseImportTable $table): void
    {
        $tableName = $table->table_name;

        // If raw create statement is available, clean and execute safely
        if (!empty($table->create_statement)) {
            $stmt = trim($table->create_statement);
            // Ensure IF NOT EXISTS
            if (!preg_match('/CREATE\s+TABLE\s+IF\s+NOT\s+EXISTS/i', $stmt)) {
                $stmt = preg_replace('/CREATE\s+TABLE/i', 'CREATE TABLE IF NOT EXISTS', $stmt, 1);
            }
            DB::statement($stmt);
            return;
        }

        // Fallback programmatic generation
        $sql = "CREATE TABLE IF NOT EXISTS `{$tableName}` (";
        $colDefs = [];
        $pk = $table->primary_key;

        foreach ($table->columns as $col) {
            $def = "`{$col->column_name}` {$col->data_type}";
            if (!$col->is_nullable) {
                $def .= " NOT NULL";
            } else {
                $def .= " NULL";
            }
            if ($col->is_auto_increment) {
                $def .= " AUTO_INCREMENT";
            }
            if ($col->default_value !== null) {
                $def .= " DEFAULT '{$col->default_value}'";
            }
            $colDefs[] = $def;
        }

        if (!empty($pk)) {
            $colDefs[] = "PRIMARY KEY (`{$pk}`)";
        }

        $sql .= implode(', ', $colDefs) . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        DB::statement($sql);
    }

    /**
     * Add column to existing table.
     */
    protected function addColumn(string $tableName, DatabaseImportColumn $column): void
    {
        $colName = $column->column_name;
        $type = $column->data_type;
        $nullability = $column->is_nullable ? "NULL" : "NOT NULL";
        $defaultClause = $column->default_value !== null ? "DEFAULT '{$column->default_value}'" : "";

        $sql = "ALTER TABLE `{$tableName}` ADD COLUMN `{$colName}` {$type} {$nullability} {$defaultClause}";
        DB::statement($sql);
    }

    /**
     * Modify column on existing table (Safe and approved only).
     */
    protected function modifyColumn(string $tableName, DatabaseImportColumn $column): void
    {
        $colName = $column->column_name;
        $type = $column->data_type;
        $nullability = $column->is_nullable ? "NULL" : "NOT NULL";
        $defaultClause = $column->default_value !== null ? "DEFAULT '{$column->default_value}'" : "";

        $sql = "ALTER TABLE `{$tableName}` MODIFY COLUMN `{$colName}` {$type} {$nullability} {$defaultClause}";
        DB::statement($sql);
    }
}
