<?php

namespace App\Services\DynamicCrm;

use App\Models\CrmActivityLog;
use App\Models\CrmColumn;
use App\Models\CrmDatabase;
use App\Models\CrmRelationship;
use App\Models\CrmTable;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Secure SQL import service. Executes parsed SQL into a dedicated tenant database,
 * populates metadata tables, and provides progress reporting.
 */
class SqlImportService
{
    protected SqlParserService $parser;
    protected DynamicDatabaseManager $dbManager;

    public function __construct(SqlParserService $parser, DynamicDatabaseManager $dbManager)
    {
        $this->parser = $parser;
        $this->dbManager = $dbManager;
    }

    /**
     * Full import pipeline: parse SQL → create tenant DB → execute → register metadata.
     */
    public function import(Tenant $tenant, string $sql, string $originalFileName, ?int $userId = null): array
    {
        // 1. Validate
        $errors = $this->parser->validate($sql);
        if (!empty($errors)) {
            throw new \Exception('SQL validation failed: ' . implode('; ', $errors));
        }

        // 2. Parse
        $parsed = $this->parser->parse($sql);

        if (empty($parsed['tables']) && empty($parsed['data'])) {
            throw new \Exception('No tables or data found in the SQL file.');
        }

        // 3. Get/create dedicated tenant database
        $dbName = $this->dbManager->ensureTenantDatabase($tenant);

        // 4. Register CrmDatabase record
        $crmDb = CrmDatabase::updateOrCreate(
            ['tenant_id' => $tenant->id, 'database_name' => $dbName],
            [
                'name' => pathinfo($originalFileName, PATHINFO_FILENAME),
                'original_file' => $originalFileName,
                'file_size_bytes' => strlen($sql),
                'status' => 'importing',
                'tables_count' => $parsed['stats']['tables_count'],
                'relationships_count' => $parsed['stats']['relationships_count'],
                'total_records' => $parsed['stats']['total_records'],
            ]
        );

        // 5. Execute SQL statements in the tenant database
        $executionResult = $this->executeSql($tenant, $sql, $dbName);

        // 6. Register metadata from parsed schema
        $this->registerMetadata($tenant, $crmDb, $parsed, $dbName);

        // 7. Update record counts from actual DB
        $this->updateRecordCounts($tenant, $crmDb, $dbName);

        // 8. Mark import complete
        $crmDb->update([
            'status' => 'active',
            'import_summary' => [
                'statements_executed' => $executionResult['executed'],
                'statements_failed' => $executionResult['failed'],
                'tables_created' => array_keys($parsed['tables']),
                'records_imported' => $parsed['stats']['total_records'],
                'relationships_detected' => count($parsed['relationships']),
                'warnings' => $parsed['warnings'],
                'imported_at' => now()->toIso8601String(),
            ],
        ]);

        // 9. Log activity
        CrmActivityLog::log($tenant->id, 'database_uploaded', null, $crmDb->id, [
            'file' => $originalFileName,
            'tables' => count($parsed['tables']),
            'records' => $parsed['stats']['total_records'],
        ], $userId);

        return [
            'success' => true,
            'database' => $crmDb,
            'database_name' => $dbName,
            'tables_created' => array_keys($parsed['tables']),
            'tables_count' => count($parsed['tables']),
            'relationships_count' => count($parsed['relationships']),
            'total_records' => $parsed['stats']['total_records'],
            'statements_executed' => $executionResult['executed'],
            'statements_failed' => $executionResult['failed'],
            'warnings' => $parsed['warnings'],
        ];
    }

    /**
     * Execute sanitized SQL in the tenant's dedicated database.
     */
    protected function executeSql(Tenant $tenant, string $sql, string $dbName): array
    {
        $mainDb = config('database.connections.mysql.database', 'jrv_crm');
        $executed = 0;
        $failed = 0;
        $switchedDb = false;

        // Switch to tenant database
        if ($dbName !== $mainDb) {
            try {
                DB::statement("USE `{$dbName}`");
                $switchedDb = true;
            } catch (\Throwable $e) {
                Log::warning("Could not switch to database {$dbName}: " . $e->getMessage());
            }
        }

        try {
            DB::statement("SET FOREIGN_KEY_CHECKS = 0");

            $statements = $this->parser->splitStatements($this->parser->removeComments($sql));

            foreach ($statements as $stmt) {
                $stmt = trim($stmt);
                if (empty($stmt)) continue;

                // Skip dangerous / meta statements
                if (preg_match('/^\s*(?:USE|CREATE\s+DATABASE|DROP\s+DATABASE|DROP\s+SCHEMA|TRUNCATE|CREATE\s+USER|GRANT|REVOKE|ALTER\s+USER)\b/i', $stmt)) {
                    continue;
                }

                // Allow: CREATE TABLE, ALTER TABLE, INSERT INTO, CREATE INDEX, DROP TABLE (with caution)
                if (!preg_match('/^\s*(?:CREATE\s+TABLE|ALTER\s+TABLE|INSERT\s+INTO|CREATE\s+(?:UNIQUE\s+)?INDEX|SET|LOCK|UNLOCK)\b/i', $stmt)) {
                    // Skip unrecognized statements for safety
                    continue;
                }

                try {
                    DB::unprepared($stmt);
                    $executed++;
                } catch (\Throwable $e) {
                    $failed++;
                    Log::debug("SQL import warning: " . substr($stmt, 0, 100) . " — " . $e->getMessage());
                }
            }
        } finally {
            try { DB::statement("SET FOREIGN_KEY_CHECKS = 1"); } catch (\Throwable $e) {}
            if ($switchedDb) {
                try { DB::statement("USE `{$mainDb}`"); } catch (\Throwable $e) {}
            }
        }

        return ['executed' => $executed, 'failed' => $failed];
    }

    /**
     * Register parsed table/column/relationship metadata into CRM metadata tables.
     */
    protected function registerMetadata(Tenant $tenant, CrmDatabase $crmDb, array $parsed, string $dbName): void
    {
        $tableOrder = 0;

        foreach ($parsed['tables'] as $tableName => $tableData) {
            // Detect display column for this table
            $displayColumn = $this->detectDisplayColumn($tableData['columns']);

            $crmTable = CrmTable::updateOrCreate(
                ['database_id' => $crmDb->id, 'table_name' => $tableName],
                [
                    'tenant_id' => $tenant->id,
                    'display_name' => Str::title(str_replace('_', ' ', $tableName)),
                    'icon' => $this->guessTableIcon($tableName),
                    'is_active' => true,
                    'is_visible_in_menu' => true,
                    'menu_order' => $tableOrder++,
                    'primary_key_column' => $tableData['primary_key'] ?? 'id',
                    'display_column' => $displayColumn,
                ]
            );

            // Register columns
            $colOrder = 0;
            foreach ($tableData['columns'] as $col) {
                $formType = CrmColumn::mapDataTypeToFormType($col['data_type'], $col['name']);

                // Determine searchability
                $isSearchable = in_array($col['data_type'], ['varchar', 'char', 'text', 'mediumtext', 'longtext', 'enum'])
                    && !$col['is_primary']
                    && !$col['is_foreign_key'];

                // Determine visibility (hide internal columns by default)
                $isVisible = !in_array(strtolower($col['name']), ['created_at', 'updated_at', 'deleted_at', 'remember_token', 'password']);

                // Auto-increment PKs are not editable
                $isEditable = !$col['is_auto_increment'] && !$col['is_primary'];

                CrmColumn::updateOrCreate(
                    ['table_id' => $crmTable->id, 'column_name' => $col['name']],
                    [
                        'tenant_id' => $tenant->id,
                        'display_name' => Str::title(str_replace('_', ' ', $col['name'])),
                        'data_type' => $col['data_type'],
                        'form_type' => $formType,
                        'is_primary' => $col['is_primary'],
                        'is_auto_increment' => $col['is_auto_increment'],
                        'is_nullable' => $col['is_nullable'],
                        'is_searchable' => $isSearchable,
                        'is_visible' => $isVisible,
                        'is_editable' => $isEditable,
                        'is_required' => !$col['is_nullable'] && !$col['is_auto_increment'],
                        'is_foreign_key' => $col['is_foreign_key'],
                        'references_table' => $col['references_table'],
                        'references_column' => $col['references_column'],
                        'display_column' => null, // Will be resolved later via relationships
                        'default_value' => $col['default_value'],
                        'enum_values' => $col['enum_values'],
                        'display_order' => $colOrder++,
                    ]
                );
            }
        }

        // Register relationships
        foreach ($parsed['relationships'] as $rel) {
            CrmRelationship::updateOrCreate(
                [
                    'database_id' => $crmDb->id,
                    'source_table' => $rel['source_table'],
                    'source_column' => $rel['source_column'],
                ],
                [
                    'tenant_id' => $tenant->id,
                    'target_table' => $rel['target_table'],
                    'target_column' => $rel['target_column'],
                    'relationship_type' => $rel['type'],
                    'display_label' => Str::title(str_replace('_', ' ', $rel['target_table'])),
                ]
            );

            // Update FK column's display_column based on target table
            $targetCrmTable = CrmTable::where('database_id', $crmDb->id)
                ->where('table_name', $rel['target_table'])
                ->first();

            if ($targetCrmTable && $targetCrmTable->display_column) {
                CrmColumn::where('tenant_id', $tenant->id)
                    ->whereHas('table', fn($q) => $q->where('table_name', $rel['source_table'])->where('database_id', $crmDb->id))
                    ->where('column_name', $rel['source_column'])
                    ->update(['display_column' => $targetCrmTable->display_column]);
            }
        }
    }

    /**
     * Update actual record counts from the tenant database.
     */
    protected function updateRecordCounts(Tenant $tenant, CrmDatabase $crmDb, string $dbName): void
    {
        $mainDb = config('database.connections.mysql.database', 'jrv_crm');
        $tables = CrmTable::where('database_id', $crmDb->id)->get();
        $totalRecords = 0;

        foreach ($tables as $table) {
            try {
                $qualifiedTable = ($dbName !== $mainDb)
                    ? "`{$dbName}`.`{$table->table_name}`"
                    : "`{$table->table_name}`";

                $count = DB::selectOne("SELECT COUNT(*) as cnt FROM {$qualifiedTable}");
                $recordCount = $count->cnt ?? 0;
                $table->update(['record_count' => $recordCount]);
                $totalRecords += $recordCount;
            } catch (\Throwable $e) {
                Log::debug("Could not count records for {$table->table_name}: " . $e->getMessage());
            }
        }

        $crmDb->update(['total_records' => $totalRecords]);
    }

    /**
     * Detect the best "display column" for a table (used when referenced by FK).
     */
    protected function detectDisplayColumn(array $columns): ?string
    {
        $candidates = ['name', 'title', 'label', 'display_name', 'full_name', 'username',
                        'email', 'first_name', 'company_name', 'product_name', 'order_number',
                        'code', 'description', 'subject'];

        $colNames = array_column($columns, 'name');

        // Exact match first
        foreach ($candidates as $candidate) {
            if (in_array($candidate, $colNames)) {
                return $candidate;
            }
        }

        // Partial match
        foreach ($colNames as $colName) {
            $lower = strtolower($colName);
            if (str_contains($lower, 'name') || str_contains($lower, 'title') || str_contains($lower, 'label')) {
                return $colName;
            }
        }

        // Fallback: first varchar/text column that isn't a key
        foreach ($columns as $col) {
            if (in_array($col['data_type'], ['varchar', 'char', 'text']) && !$col['is_primary'] && !$col['is_foreign_key']) {
                return $col['name'];
            }
        }

        return null;
    }

    /**
     * Guess an appropriate icon for a table based on its name.
     */
    protected function guessTableIcon(string $tableName): string
    {
        $lower = strtolower($tableName);

        $iconMap = [
            'user' => 'UserGroupIcon', 'customer' => 'UserGroupIcon', 'client' => 'UserGroupIcon',
            'employee' => 'UserGroupIcon', 'staff' => 'UserGroupIcon', 'member' => 'UserGroupIcon',
            'order' => 'ShoppingCartIcon', 'sale' => 'ShoppingCartIcon', 'purchase' => 'ShoppingCartIcon',
            'product' => 'CubeIcon', 'item' => 'CubeIcon', 'inventory' => 'CubeIcon',
            'invoice' => 'DocumentTextIcon', 'payment' => 'CreditCardIcon', 'transaction' => 'CreditCardIcon',
            'task' => 'ClipboardDocumentListIcon', 'project' => 'FolderIcon',
            'email' => 'EnvelopeIcon', 'message' => 'ChatBubbleLeftIcon',
            'report' => 'ChartBarIcon', 'setting' => 'CogIcon', 'config' => 'CogIcon',
            'categor' => 'TagIcon', 'tag' => 'TagIcon',
            'address' => 'MapPinIcon', 'location' => 'MapPinIcon',
            'propert' => 'HomeIcon', 'house' => 'HomeIcon', 'apartment' => 'HomeIcon',
            'doctor' => 'HeartIcon', 'patient' => 'HeartIcon',
            'course' => 'AcademicCapIcon', 'student' => 'AcademicCapIcon',
        ];

        foreach ($iconMap as $keyword => $icon) {
            if (str_contains($lower, $keyword)) {
                return $icon;
            }
        }

        return 'TableCellsIcon';
    }

    /**
     * Remove SQL comments — delegates to parser's method made public.
     */
    private function removeComments(string $sql): string
    {
        // Inline removal for the execute path
        $sql = preg_replace('/\/\*[\s\S]*?\*\//', '', $sql);
        $lines = explode("\n", $sql);
        $clean = [];
        foreach ($lines as $line) {
            $t = ltrim($line);
            if (str_starts_with($t, '--') || str_starts_with($t, '#')) continue;
            $clean[] = $line;
        }
        return implode("\n", $clean);
    }
}
