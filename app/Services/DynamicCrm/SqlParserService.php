<?php

namespace App\Services\DynamicCrm;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

/**
 * Comprehensive SQL parser that extracts full schema metadata from SQL dump files.
 * Detects tables, columns, data types, keys, relationships, indexes, and data rows.
 */
class SqlParserService
{
    /**
     * Dangerous SQL patterns that must be blocked from uploaded files.
     */
    protected array $dangerousPatterns = [
        '/\bDROP\s+DATABASE\b/i',
        '/\bDROP\s+SCHEMA\b/i',
        '/\bTRUNCATE\s+TABLE\b/i',
        '/\bCREATE\s+USER\b/i',
        '/\bGRANT\b/i',
        '/\bREVOKE\b/i',
        '/\bALTER\s+USER\b/i',
        '/\bDELETE\s+FROM\s+mysql\b/i',
        '/\bINTO\s+OUTFILE\b/i',
        '/\bLOAD_FILE\b/i',
        '/\bSHUTDOWN\b/i',
        '/\bSUPER\b/i',
    ];

    /**
     * Parse a full SQL dump and return structured schema + data.
     *
     * @return array{
     *   tables: array<string, array{
     *     columns: array,
     *     primary_key: string|null,
     *     indexes: array,
     *     foreign_keys: array,
     *     auto_increment: string|null,
     *   }>,
     *   relationships: array,
     *   data: array<string, array>,
     *   warnings: array,
     *   stats: array
     * }
     */
    public function parse(string $sql): array
    {
        $tables = [];
        $relationships = [];
        $data = [];
        $warnings = [];

        // 1. Security check
        foreach ($this->dangerousPatterns as $pattern) {
            if (preg_match($pattern, $sql)) {
                $warnings[] = "Blocked dangerous SQL pattern: " . trim(preg_replace('/\/.*\/[a-z]*/', '', $pattern));
            }
        }

        // 2. Remove comments
        $cleanSql = $this->removeComments($sql);

        // 3. Split into statements
        $statements = $this->splitStatements($cleanSql);

        // 4. Process each statement
        foreach ($statements as $stmt) {
            $trimmed = trim($stmt);
            if (empty($trimmed)) continue;

            // Skip dangerous statements
            $isDangerous = false;
            foreach ($this->dangerousPatterns as $pattern) {
                if (preg_match($pattern, $trimmed)) {
                    $isDangerous = true;
                    break;
                }
            }
            if ($isDangerous) continue;

            // Skip USE/CREATE DATABASE
            if (preg_match('/^\s*(?:USE|CREATE\s+DATABASE|DROP\s+TABLE)\b/i', $trimmed)) {
                continue;
            }

            // Parse CREATE TABLE
            if (preg_match('/^\s*CREATE\s+TABLE/i', $trimmed)) {
                $tableData = $this->parseCreateTable($trimmed);
                if ($tableData) {
                    $tableName = $tableData['name'];
                    $tables[$tableName] = $tableData;

                    // Collect foreign keys as relationships
                    foreach ($tableData['foreign_keys'] as $fk) {
                        $relationships[] = [
                            'source_table' => $tableName,
                            'source_column' => $fk['column'],
                            'target_table' => $fk['references_table'],
                            'target_column' => $fk['references_column'],
                            'type' => 'belongsTo',
                        ];
                    }
                }
            }

            // Parse ALTER TABLE for additional FKs
            if (preg_match('/^\s*ALTER\s+TABLE/i', $trimmed)) {
                $alterData = $this->parseAlterTable($trimmed);
                if ($alterData) {
                    $tableName = $alterData['table'];
                    foreach ($alterData['foreign_keys'] as $fk) {
                        // Merge FK into existing table metadata
                        if (isset($tables[$tableName])) {
                            $tables[$tableName]['foreign_keys'][] = $fk;
                            // Mark column as FK
                            foreach ($tables[$tableName]['columns'] as &$col) {
                                if ($col['name'] === $fk['column']) {
                                    $col['is_foreign_key'] = true;
                                    $col['references_table'] = $fk['references_table'];
                                    $col['references_column'] = $fk['references_column'];
                                }
                            }
                            unset($col);
                        }

                        $relationships[] = [
                            'source_table' => $tableName,
                            'source_column' => $fk['column'],
                            'target_table' => $fk['references_table'],
                            'target_column' => $fk['references_column'],
                            'type' => 'belongsTo',
                        ];
                    }
                }
            }

            // Parse INSERT INTO
            if (preg_match('/^\s*INSERT\s+INTO/i', $trimmed)) {
                $insertData = $this->parseInsertInto($trimmed);
                if ($insertData) {
                    $tableName = $insertData['table'];
                    if (!isset($data[$tableName])) {
                        $data[$tableName] = [];
                    }
                    $data[$tableName] = array_merge($data[$tableName], $insertData['rows']);
                }
            }
        }

        // 5. Auto-detect FK relationships from naming conventions (_id suffix)
        $additionalRels = $this->detectImplicitRelationships($tables);
        $relationships = array_merge($relationships, $additionalRels);

        // Remove duplicate relationships
        $relationships = $this->deduplicateRelationships($relationships);

        // 6. Stats
        $totalRecords = 0;
        foreach ($data as $rows) {
            $totalRecords += count($rows);
        }

        return [
            'tables' => $tables,
            'relationships' => $relationships,
            'data' => $data,
            'warnings' => $warnings,
            'stats' => [
                'tables_count' => count($tables),
                'relationships_count' => count($relationships),
                'total_records' => $totalRecords,
            ],
        ];
    }

    /**
     * Parse a CREATE TABLE statement into structured metadata.
     */
    protected function parseCreateTable(string $stmt): ?array
    {
        // Extract table name
        if (!preg_match('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?[`"\']?([a-zA-Z0-9_]+)[`"\']?\s*\(/i', $stmt, $nameMatch)) {
            return null;
        }

        $tableName = $nameMatch[1];
        $columns = [];
        $primaryKey = null;
        $indexes = [];
        $foreignKeys = [];
        $autoIncrement = null;

        // Extract the body between the outer parentheses
        $body = $this->extractParenthesizedBody($stmt);
        if (!$body) return null;

        // Split body into column/constraint definitions
        $definitions = $this->splitColumnDefinitions($body);

        $colOrder = 0;
        foreach ($definitions as $def) {
            $def = trim($def);
            if (empty($def)) continue;

            // PRIMARY KEY constraint
            if (preg_match('/^\s*PRIMARY\s+KEY\s*\(\s*[`"]?([a-zA-Z0-9_]+)[`"]?/i', $def, $pkMatch)) {
                $primaryKey = $pkMatch[1];
                continue;
            }

            // UNIQUE constraint
            if (preg_match('/^\s*UNIQUE\s+(?:KEY|INDEX)?\s*(?:[`"]?[a-zA-Z0-9_]*[`"]?)?\s*\(/i', $def)) {
                if (preg_match('/\(\s*[`"]?([a-zA-Z0-9_]+)[`"]?/i', $def, $uqMatch)) {
                    $indexes[] = ['type' => 'unique', 'column' => $uqMatch[1]];
                }
                continue;
            }

            // INDEX/KEY constraint
            if (preg_match('/^\s*(?:INDEX|KEY)\s+/i', $def)) {
                if (preg_match('/\(\s*[`"]?([a-zA-Z0-9_]+)[`"]?/i', $def, $idxMatch)) {
                    $indexes[] = ['type' => 'index', 'column' => $idxMatch[1]];
                }
                continue;
            }

            // FOREIGN KEY constraint
            if (preg_match('/FOREIGN\s+KEY\s*\(\s*[`"]?([a-zA-Z0-9_]+)[`"]?\s*\)\s*REFERENCES\s+[`"]?([a-zA-Z0-9_]+)[`"]?\s*\(\s*[`"]?([a-zA-Z0-9_]+)[`"]?\s*\)/i', $def, $fkMatch)) {
                $foreignKeys[] = [
                    'column' => $fkMatch[1],
                    'references_table' => $fkMatch[2],
                    'references_column' => $fkMatch[3],
                ];
                continue;
            }

            // CONSTRAINT ... FOREIGN KEY
            if (preg_match('/^\s*CONSTRAINT\s+/i', $def)) {
                if (preg_match('/FOREIGN\s+KEY\s*\(\s*[`"]?([a-zA-Z0-9_]+)[`"]?\s*\)\s*REFERENCES\s+[`"]?([a-zA-Z0-9_]+)[`"]?\s*\(\s*[`"]?([a-zA-Z0-9_]+)[`"]?\s*\)/i', $def, $fkMatch)) {
                    $foreignKeys[] = [
                        'column' => $fkMatch[1],
                        'references_table' => $fkMatch[2],
                        'references_column' => $fkMatch[3],
                    ];
                }
                continue;
            }

            // Column definition
            if (preg_match('/^\s*[`"]?([a-zA-Z0-9_]+)[`"]?\s+([a-zA-Z]+)(?:\(([^)]*)\))?(.*)$/i', $def, $colMatch)) {
                $colName = $colMatch[1];
                $colType = strtolower($colMatch[2]);
                $typeParams = $colMatch[3] ?? null;
                $remainder = $colMatch[4] ?? '';

                // Skip if this looks like a keyword, not a column
                if (in_array(strtoupper($colName), ['PRIMARY', 'KEY', 'INDEX', 'UNIQUE', 'CONSTRAINT', 'FOREIGN', 'CHECK'])) {
                    continue;
                }

                $isPrimary = (bool) preg_match('/PRIMARY\s+KEY/i', $remainder);
                $isAutoIncrement = (bool) preg_match('/AUTO_INCREMENT/i', $remainder);
                $isNullable = !preg_match('/NOT\s+NULL/i', $remainder);
                $isUnsigned = (bool) preg_match('/UNSIGNED/i', $remainder);

                // Default value
                $defaultValue = null;
                if (preg_match("/DEFAULT\s+'([^']*)'/i", $remainder, $dvMatch)) {
                    $defaultValue = $dvMatch[1];
                } elseif (preg_match('/DEFAULT\s+(\S+)/i', $remainder, $dvMatch)) {
                    $val = $dvMatch[1];
                    if (!in_array(strtoupper($val), ['NULL', 'NOT', 'ON', 'AUTO_INCREMENT', 'PRIMARY', 'KEY', 'UNIQUE', 'COMMENT'])) {
                        $defaultValue = $val;
                    }
                }

                // Enum values
                $enumValues = null;
                if (in_array($colType, ['enum', 'set']) && $typeParams) {
                    preg_match_all("/'([^']*)'/", $typeParams, $enumMatches);
                    $enumValues = $enumMatches[1] ?? [];
                }

                if ($isPrimary) {
                    $primaryKey = $colName;
                }
                if ($isAutoIncrement) {
                    $autoIncrement = $colName;
                }

                // Detect inline FK
                $isForeignKey = false;
                $referencesTable = null;
                $referencesColumn = null;
                if (preg_match('/REFERENCES\s+[`"]?([a-zA-Z0-9_]+)[`"]?\s*\(\s*[`"]?([a-zA-Z0-9_]+)[`"]?\s*\)/i', $remainder, $refMatch)) {
                    $isForeignKey = true;
                    $referencesTable = $refMatch[1];
                    $referencesColumn = $refMatch[2];
                    $foreignKeys[] = [
                        'column' => $colName,
                        'references_table' => $referencesTable,
                        'references_column' => $referencesColumn,
                    ];
                }

                $columns[] = [
                    'name' => $colName,
                    'data_type' => $colType,
                    'type_params' => $typeParams,
                    'is_primary' => $isPrimary,
                    'is_auto_increment' => $isAutoIncrement,
                    'is_nullable' => $isNullable,
                    'is_unsigned' => $isUnsigned,
                    'is_foreign_key' => $isForeignKey,
                    'references_table' => $referencesTable,
                    'references_column' => $referencesColumn,
                    'default_value' => $defaultValue,
                    'enum_values' => $enumValues,
                    'display_order' => $colOrder++,
                ];
            }
        }

        // Mark FK columns from separate FK constraints
        foreach ($foreignKeys as $fk) {
            foreach ($columns as &$col) {
                if ($col['name'] === $fk['column'] && !$col['is_foreign_key']) {
                    $col['is_foreign_key'] = true;
                    $col['references_table'] = $fk['references_table'];
                    $col['references_column'] = $fk['references_column'];
                }
            }
            unset($col);
        }

        return [
            'name' => $tableName,
            'columns' => $columns,
            'primary_key' => $primaryKey ?? ($autoIncrement ?: 'id'),
            'indexes' => $indexes,
            'foreign_keys' => $foreignKeys,
            'auto_increment' => $autoIncrement,
        ];
    }

    /**
     * Parse ALTER TABLE for additional FK constraints.
     */
    protected function parseAlterTable(string $stmt): ?array
    {
        if (!preg_match('/ALTER\s+TABLE\s+[`"]?([a-zA-Z0-9_]+)[`"]?/i', $stmt, $match)) {
            return null;
        }

        $tableName = $match[1];
        $foreignKeys = [];

        // Find FOREIGN KEY constraints
        preg_match_all('/ADD\s+(?:CONSTRAINT\s+[`"]?\w+[`"]?\s+)?FOREIGN\s+KEY\s*\(\s*[`"]?([a-zA-Z0-9_]+)[`"]?\s*\)\s*REFERENCES\s+[`"]?([a-zA-Z0-9_]+)[`"]?\s*\(\s*[`"]?([a-zA-Z0-9_]+)[`"]?\s*\)/i', $stmt, $fkMatches, PREG_SET_ORDER);

        foreach ($fkMatches as $fk) {
            $foreignKeys[] = [
                'column' => $fk[1],
                'references_table' => $fk[2],
                'references_column' => $fk[3],
            ];
        }

        return [
            'table' => $tableName,
            'foreign_keys' => $foreignKeys,
        ];
    }

    /**
     * Parse INSERT INTO statement and extract rows.
     */
    protected function parseInsertInto(string $stmt): ?array
    {
        if (!preg_match('/INSERT\s+INTO\s+[`"]?([a-zA-Z0-9_]+)[`"]?\s*(?:\(([^)]*)\))?\s*VALUES\s*(.*)/is', $stmt, $match)) {
            return null;
        }

        $tableName = $match[1];
        $columnsList = [];
        if (!empty(trim($match[2] ?? ''))) {
            $columnsList = array_map(
                fn($c) => trim(str_replace(['`', '"', "'"], '', $c)),
                explode(',', $match[2])
            );
        }

        $valuesBlock = trim($match[3]);
        $rows = [];

        // Extract value tuples
        preg_match_all('/\(([^)]*(?:\([^)]*\)[^)]*)*)\)/s', $valuesBlock, $tupleMatches);

        foreach ($tupleMatches[1] as $tuple) {
            $values = $this->parseCsvRow($tuple);
            $row = [];

            if (!empty($columnsList)) {
                foreach ($columnsList as $idx => $colName) {
                    $row[$colName] = $values[$idx] ?? null;
                }
            } else {
                foreach ($values as $idx => $val) {
                    $row["col_{$idx}"] = $val;
                }
            }

            if (!empty($row)) {
                $rows[] = $row;
            }
        }

        return [
            'table' => $tableName,
            'columns' => $columnsList,
            'rows' => $rows,
        ];
    }

    /**
     * Detect implicit relationships from column naming conventions (e.g., customer_id → customers).
     */
    protected function detectImplicitRelationships(array $tables): array
    {
        $allTableNames = array_keys($tables);
        $relationships = [];

        foreach ($tables as $tableName => $tableData) {
            foreach ($tableData['columns'] as $col) {
                if ($col['is_foreign_key']) continue; // Already detected explicitly

                $colName = $col['name'];
                if (!str_ends_with($colName, '_id')) continue;

                $potentialRef = substr($colName, 0, -3); // Remove '_id'
                $pluralRef = Str::plural($potentialRef);
                $singularRef = Str::singular($potentialRef);

                $targetTable = null;
                if (in_array($pluralRef, $allTableNames)) {
                    $targetTable = $pluralRef;
                } elseif (in_array($singularRef, $allTableNames)) {
                    $targetTable = $singularRef;
                } elseif (in_array($potentialRef, $allTableNames)) {
                    $targetTable = $potentialRef;
                }

                if ($targetTable && $targetTable !== $tableName) {
                    $relationships[] = [
                        'source_table' => $tableName,
                        'source_column' => $colName,
                        'target_table' => $targetTable,
                        'target_column' => $tables[$targetTable]['primary_key'] ?? 'id',
                        'type' => 'belongsTo',
                    ];
                }
            }
        }

        return $relationships;
    }

    /**
     * Remove SQL comments (single-line and multi-line).
     */
    public function removeComments(string $sql): string
    {
        // Remove multi-line comments
        $sql = preg_replace('/\/\*[\s\S]*?\*\//', '', $sql);

        // Remove single-line comments but preserve strings
        $lines = explode("\n", $sql);
        $cleanLines = [];
        foreach ($lines as $line) {
            $trimmed = ltrim($line);
            if (str_starts_with($trimmed, '--') || str_starts_with($trimmed, '#')) {
                continue;
            }
            $cleanLines[] = $line;
        }

        return implode("\n", $cleanLines);
    }

    /**
     * Split SQL into individual statements, respecting quotes and backticks.
     */
    public function splitStatements(string $sql): array
    {
        $statements = [];
        $current = '';
        $inSingleQuote = false;
        $inDoubleQuote = false;
        $inBacktick = false;
        $len = strlen($sql);

        for ($i = 0; $i < $len; $i++) {
            $char = $sql[$i];
            $prevChar = $i > 0 ? $sql[$i - 1] : '';

            if ($char === "'" && $prevChar !== '\\' && !$inDoubleQuote && !$inBacktick) {
                $inSingleQuote = !$inSingleQuote;
            } elseif ($char === '"' && $prevChar !== '\\' && !$inSingleQuote && !$inBacktick) {
                $inDoubleQuote = !$inDoubleQuote;
            } elseif ($char === '`' && !$inSingleQuote && !$inDoubleQuote) {
                $inBacktick = !$inBacktick;
            }

            if ($char === ';' && !$inSingleQuote && !$inDoubleQuote && !$inBacktick) {
                $stmt = trim($current);
                if (!empty($stmt)) {
                    $statements[] = $stmt;
                }
                $current = '';
                continue;
            }

            $current .= $char;
        }

        $final = trim($current);
        if (!empty($final)) {
            $statements[] = $final;
        }

        return $statements;
    }

    /**
     * Extract the parenthesized body from CREATE TABLE (...).
     */
    protected function extractParenthesizedBody(string $stmt): ?string
    {
        $depth = 0;
        $start = null;
        $end = null;
        $len = strlen($stmt);

        for ($i = 0; $i < $len; $i++) {
            if ($stmt[$i] === '(') {
                if ($depth === 0) {
                    $start = $i + 1;
                }
                $depth++;
            } elseif ($stmt[$i] === ')') {
                $depth--;
                if ($depth === 0) {
                    $end = $i;
                    break;
                }
            }
        }

        if ($start !== null && $end !== null) {
            return substr($stmt, $start, $end - $start);
        }

        return null;
    }

    /**
     * Split column definitions in CREATE TABLE body, respecting nested parentheses.
     */
    protected function splitColumnDefinitions(string $body): array
    {
        $parts = [];
        $current = '';
        $depth = 0;
        $inQuote = false;
        $quoteChar = '';

        for ($i = 0; $i < strlen($body); $i++) {
            $char = $body[$i];
            $prevChar = $i > 0 ? $body[$i - 1] : '';

            if (!$inQuote && ($char === "'" || $char === '"')) {
                $inQuote = true;
                $quoteChar = $char;
            } elseif ($inQuote && $char === $quoteChar && $prevChar !== '\\') {
                $inQuote = false;
            }

            if (!$inQuote) {
                if ($char === '(') $depth++;
                if ($char === ')') $depth--;
            }

            if ($char === ',' && $depth === 0 && !$inQuote) {
                $parts[] = trim($current);
                $current = '';
                continue;
            }

            $current .= $char;
        }

        if (trim($current)) {
            $parts[] = trim($current);
        }

        return $parts;
    }

    /**
     * Parse a CSV-style values tuple from INSERT INTO.
     */
    protected function parseCsvRow(string $tuple): array
    {
        $values = [];
        $current = '';
        $inQuote = false;
        $quoteChar = '';
        $len = strlen($tuple);

        for ($i = 0; $i < $len; $i++) {
            $char = $tuple[$i];
            $prevChar = $i > 0 ? $tuple[$i - 1] : '';

            if (!$inQuote && ($char === "'" || $char === '"')) {
                $inQuote = true;
                $quoteChar = $char;
                continue;
            }

            if ($inQuote && $char === $quoteChar && $prevChar !== '\\') {
                $inQuote = false;
                continue;
            }

            if ($char === ',' && !$inQuote) {
                $val = trim($current);
                $values[] = $this->cleanSqlValue($val);
                $current = '';
                continue;
            }

            $current .= $char;
        }

        $val = trim($current);
        if ($val !== '') {
            $values[] = $this->cleanSqlValue($val);
        }

        return $values;
    }

    /**
     * Clean a raw SQL value (remove quotes, handle NULL).
     */
    protected function cleanSqlValue(string $val): mixed
    {
        $val = trim($val);
        if (strtoupper($val) === 'NULL') return null;
        if (str_starts_with($val, "'") && str_ends_with($val, "'")) {
            return stripslashes(substr($val, 1, -1));
        }
        if (str_starts_with($val, '"') && str_ends_with($val, '"')) {
            return stripslashes(substr($val, 1, -1));
        }
        if (is_numeric($val)) {
            return str_contains($val, '.') ? (float) $val : (int) $val;
        }
        return $val;
    }

    /**
     * Remove duplicate relationships.
     */
    protected function deduplicateRelationships(array $relationships): array
    {
        $seen = [];
        $unique = [];

        foreach ($relationships as $rel) {
            $key = $rel['source_table'] . '.' . $rel['source_column'] . '->' . $rel['target_table'] . '.' . $rel['target_column'];
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $unique[] = $rel;
            }
        }

        return $unique;
    }

    /**
     * Validate that the SQL content is safe to process.
     */
    public function validate(string $sql): array
    {
        $errors = [];

        if (empty(trim($sql))) {
            $errors[] = 'SQL file is empty.';
        }

        if (strlen($sql) > 200 * 1024 * 1024) { // 200MB limit
            $errors[] = 'SQL file exceeds maximum allowed size (200MB).';
        }

        // Check for CREATE TABLE presence
        if (!preg_match('/CREATE\s+TABLE/i', $sql)) {
            // Also accept INSERT-only dumps
            if (!preg_match('/INSERT\s+INTO/i', $sql)) {
                $errors[] = 'No CREATE TABLE or INSERT INTO statements found in the SQL file.';
            }
        }

        // Check for dangerous statements
        foreach ($this->dangerousPatterns as $pattern) {
            if (preg_match($pattern, $sql)) {
                $errors[] = 'SQL file contains potentially dangerous statements that are not allowed.';
                break;
            }
        }

        return $errors;
    }
}
