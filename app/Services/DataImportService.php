<?php

namespace App\Services;

use App\Models\DatabaseAuditLog;
use App\Models\DatabaseFieldMapping;
use App\Models\DatabaseImport;
use App\Models\DatabaseImportError;
use App\Models\DatabaseImportTable;
use DateTime;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class DataImportService
{
    protected SqlParserService $parserService;

    public function __construct(SqlParserService $parserService)
    {
        $this->parserService = $parserService;
    }

    /**
     * Execute full data import for an import session.
     */
    public function import(DatabaseImport $import): array
    {
        $import->update([
            'status' => 'IMPORTING',
            'started_at' => now(),
            'records_inserted' => 0,
            'records_updated' => 0,
            'records_skipped' => 0,
            'records_failed' => 0,
        ]);

        $mode = $import->import_mode ?? 'upsert';
        $filePath = $import->file_path;

        if (!file_exists($filePath)) {
            throw new Exception("Import SQL file not found at {$filePath}");
        }

        // Cache field mappings for fast lookup during streaming
        $mappings = DatabaseFieldMapping::where('database_import_id', $import->id)->get();
        $mappingMap = []; // [sourceTable => [sourceCol => targetCol]]
        $targetTableMap = []; // [sourceTable => targetTable]
        $transformMap = []; // [sourceTable => [sourceCol => rule]]

        foreach ($mappings as $m) {
            $mappingMap[$m->source_table][$m->source_column] = $m->target_column;
            $targetTableMap[$m->source_table] = $m->target_table;
            if (!empty($m->transformation_rule)) {
                $transformMap[$m->source_table][$m->source_column] = $m->transformation_rule;
            }
        }

        $counters = [
            'inserted' => 0,
            'updated' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];

        $globalRowIndex = 0;

        // Stream and process INSERT statements in batches of 200
        $this->parserService->streamInsertRows($filePath, function (string $sourceTable, array $rows) use (
            $import,
            $mode,
            $mappingMap,
            $targetTableMap,
            $transformMap,
            &$counters,
            &$globalRowIndex
        ) {
            $targetTable = $targetTableMap[$sourceTable] ?? (Schema::hasTable($sourceTable) ? $sourceTable : null);
            if (!$targetTable || !Schema::hasTable($targetTable)) {
                return;
            }

            $tableMappings = $mappingMap[$sourceTable] ?? [];
            $tableTransforms = $transformMap[$sourceTable] ?? [];
            $targetColumns = Schema::getColumnListing($targetTable);
            $pkColumn = $this->getPrimaryKeyColumn($targetTable);

            foreach ($rows as $rawRow) {
                $globalRowIndex++;
                try {
                    // Transform row fields according to mapping and normalization rules
                    $mappedRow = $this->transformAndNormalizeRow($rawRow, $tableMappings, $tableTransforms, $targetColumns);

                    if (empty($mappedRow)) {
                        continue;
                    }

                    // Process single record according to mode and duplicate rules
                    $result = $this->processRecord($targetTable, $mappedRow, $mode, $pkColumn);

                    if ($result === 'inserted') {
                        $counters['inserted']++;
                    } elseif ($result === 'updated') {
                        $counters['updated']++;
                    } elseif ($result === 'skipped') {
                        $counters['skipped']++;
                    }
                } catch (\Throwable $e) {
                    $counters['failed']++;

                    // Log to database_import_errors
                    DatabaseImportError::create([
                        'database_import_id' => $import->id,
                        'table_name' => $targetTable,
                        'row_number' => $globalRowIndex,
                        'column_name' => null,
                        'raw_data' => is_array($rawRow) ? array_slice($rawRow, 0, 10) : ['raw' => substr((string)$rawRow, 0, 200)],
                        'error_message' => $e->getMessage(),
                    ]);
                }
            }

            // Periodically persist progress counter in DB for live UI polling
            $import->update([
                'records_inserted' => $counters['inserted'],
                'records_updated' => $counters['updated'],
                'records_skipped' => $counters['skipped'],
                'records_failed' => $counters['failed'],
            ]);
        }, 200);

        // Finalize import status
        $finalStatus = ($counters['failed'] > 0 && ($counters['inserted'] > 0 || $counters['updated'] > 0))
            ? 'PARTIAL'
            : ($counters['failed'] > 0 && $counters['inserted'] === 0 && $counters['updated'] === 0 ? 'FAILED' : 'COMPLETED');

        $import->update([
            'status' => $finalStatus,
            'completed_at' => now(),
            'records_inserted' => $counters['inserted'],
            'records_updated' => $counters['updated'],
            'records_skipped' => $counters['skipped'],
            'records_failed' => $counters['failed'],
        ]);

        DatabaseAuditLog::record(
            action: 'DATA IMPORT',
            newValue: "Import completed ({$finalStatus}): {$counters['inserted']} inserted, {$counters['updated']} updated, {$counters['skipped']} skipped, {$counters['failed']} failed",
            metadata: ['import_id' => $import->id, 'mode' => $mode, 'counters' => $counters]
        );

        return $counters;
    }

    /**
     * Map, normalize, and sanitize individual record.
     */
    protected function transformAndNormalizeRow(
        array $rawRow,
        array $fieldMappings,
        array $transforms,
        array $targetColumns
    ): array {
        $result = [];

        foreach ($rawRow as $sourceCol => $val) {
            $targetCol = $fieldMappings[$sourceCol] ?? $sourceCol;

            // Only include if column exists in target table
            if (!in_array($targetCol, $targetColumns)) {
                continue;
            }

            $rule = $transforms[$sourceCol] ?? null;
            $normalizedVal = $this->normalizeValue($targetCol, $val, $rule);
            $result[$targetCol] = $normalizedVal;
        }

        // Auto timestamp if available and not present
        if (in_array('created_at', $targetColumns) && empty($result['created_at'])) {
            $result['created_at'] = now();
        }
        if (in_array('updated_at', $targetColumns) && empty($result['updated_at'])) {
            $result['updated_at'] = now();
        }

        return $result;
    }

    /**
     * Normalize individual value based on field type and rules.
     */
    public function normalizeValue(string $columnName, mixed $value, ?string $rule = null): mixed
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $value = trim($value);
            if ($value === '' || strtolower($value) === 'null') {
                return null;
            }
        }

        // Email normalization
        if ($rule === 'email_normalize' || str_contains($columnName, 'email')) {
            return strtolower(trim((string)$value));
        }

        // Phone normalization (Uniform standard, e.g. "+91 98765 43210" -> "9876543210")
        if ($rule === 'phone_normalize' || str_contains($columnName, 'phone') || str_contains($columnName, 'mobile') || str_contains($columnName, 'whatsapp')) {
            return $this->normalizePhoneNumber((string)$value);
        }

        // Date normalization
        if ($rule === 'date_format' || str_contains($columnName, 'date') || str_contains($columnName, 'at')) {
            return $this->normalizeDate((string)$value);
        }

        // Currency / Price cleaning
        if ($rule === 'currency_clean' || str_contains($columnName, 'price') || str_contains($columnName, 'rent') || str_contains($columnName, 'amount')) {
            $clean = preg_replace('/[^\d\.]/', '', (string)$value);
            return is_numeric($clean) ? (float)$clean : 0.00;
        }

        return $value;
    }

    /**
     * Standardize phone numbers (e.g. removes punctuation, strips country prefix if 10-digit standard).
     */
    public function normalizePhoneNumber(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        // If 12 digits starting with 91 (India), extract last 10 digits
        if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            return substr($digits, 2);
        }
        // If 11 digits starting with 0, strip leading 0
        if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            return substr($digits, 1);
        }
        return $digits;
    }

    /**
     * Standardize date formats to Y-m-d H:i:s or Y-m-d.
     */
    public function normalizeDate(string $dateStr): ?string
    {
        $dateStr = trim($dateStr);
        if (empty($dateStr) || str_starts_with($dateStr, '0000-00-00')) {
            return null;
        }

        try {
            $dt = new DateTime($dateStr);
            return $dt->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Process record insertion/updating according to selected mode.
     */
    protected function processRecord(string $tableName, array $data, string $mode, ?string $pkColumn): string
    {
        $existingRecord = $this->findExistingDuplicate($tableName, $data, $pkColumn);

        // 1. Skip Duplicates
        if ($mode === 'skip_duplicates') {
            if ($existingRecord) {
                return 'skipped';
            }
            DB::table($tableName)->insert($data);
            return 'inserted';
        }

        // 2. Insert Only
        if ($mode === 'insert_only') {
            if ($existingRecord) {
                return 'skipped';
            }
            DB::table($tableName)->insert($data);
            return 'inserted';
        }

        // 3. Update Existing
        if ($mode === 'update_existing') {
            if (!$existingRecord) {
                return 'skipped';
            }
            // Update existing record
            $matchKey = $pkColumn && isset($existingRecord->$pkColumn) ? $pkColumn : array_keys((array)$existingRecord)[0];
            DB::table($tableName)->where($matchKey, $existingRecord->$matchKey)->update($data);
            return 'updated';
        }

        // 4. Upsert (Default)
        if ($existingRecord) {
            $matchKey = $pkColumn && isset($existingRecord->$pkColumn) ? $pkColumn : array_keys((array)$existingRecord)[0];
            DB::table($tableName)->where($matchKey, $existingRecord->$matchKey)->update($data);
            return 'updated';
        }

        DB::table($tableName)->insert($data);
        return 'inserted';
    }

    /**
     * Check for duplicate record based on table duplicate rules.
     */
    protected function findExistingDuplicate(string $tableName, array $data, ?string $pkColumn): ?object
    {
        // 1. Check primary key first
        if ($pkColumn && !empty($data[$pkColumn])) {
            $found = DB::table($tableName)->where($pkColumn, $data[$pkColumn])->first();
            if ($found) {
                return $found;
            }
        }

        // 2. Customers / Contacts table duplicate rules: email OR phone
        if ($tableName === 'contacts' || str_contains($tableName, 'customer')) {
            if (!empty($data['email'])) {
                $found = DB::table($tableName)->where('email', $data['email'])->first();
                if ($found) return $found;
            }
            if (!empty($data['phone'])) {
                $normPhone = $this->normalizePhoneNumber((string)$data['phone']);
                $found = DB::table($tableName)->where(function ($q) use ($data, $normPhone) {
                    $q->where('phone', $data['phone'])
                      ->orWhere('phone', 'like', "%{$normPhone}%");
                })->first();
                if ($found) return $found;
            }
        }

        // 3. Leads table duplicate rules: phone OR email
        if ($tableName === 'crm_sales_leads' || str_contains($tableName, 'lead')) {
            if (!empty($data['email'])) {
                $found = DB::table($tableName)->where('email', $data['email'])->first();
                if ($found) return $found;
            }
            if (!empty($data['phone'])) {
                $found = DB::table($tableName)->where('phone', $data['phone'])->first();
                if ($found) return $found;
            }
        }

        // 4. Properties table duplicate rules: property_code or (owner_phone + title/address)
        if ($tableName === 'properties') {
            if (!empty($data['property_code'])) {
                $found = DB::table($tableName)->where('property_code', $data['property_code'])->first();
                if ($found) return $found;
            }
            if (!empty($data['owner_phone']) && !empty($data['title'])) {
                $found = DB::table($tableName)->where('owner_phone', $data['owner_phone'])->where('title', $data['title'])->first();
                if ($found) return $found;
            }
        }

        return null;
    }

    /**
     * Get primary key column name for a table.
     */
    protected function getPrimaryKeyColumn(string $tableName): ?string
    {
        try {
            $dbName = DB::getDatabaseName();
            $res = DB::selectOne("
                SELECT COLUMN_NAME
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = 'PRIMARY'
                LIMIT 1
            ", [$dbName, $tableName]);

            return $res?->COLUMN_NAME ?? (Schema::hasColumn($tableName, 'id') ? 'id' : null);
        } catch (\Throwable) {
            return Schema::hasColumn($tableName, 'id') ? 'id' : null;
        }
    }
}
