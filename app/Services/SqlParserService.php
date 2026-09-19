<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SqlParserService
{
    /**
     * Dangerous SQL patterns that must be blocked.
     */
    protected array $blacklistedPatterns = [
        '/\bDROP\s+DATABASE\b/i' => 'DROP DATABASE is strictly forbidden.',
        '/\bDROP\s+SCHEMA\b/i' => 'DROP SCHEMA is strictly forbidden.',
        '/\bTRUNCATE\s+(?:TABLE\s+)?/i' => 'TRUNCATE TABLE is forbidden.',
        '/\bCREATE\s+USER\b/i' => 'CREATE USER statement is blocked.',
        '/\bALTER\s+USER\b/i' => 'ALTER USER statement is blocked.',
        '/\bGRANT\b/i' => 'GRANT statement is blocked.',
        '/\bREVOKE\b/i' => 'REVOKE statement is blocked.',
        '/\bLOAD\s+DATA(?:\s+LOCAL)?\s+INFILE\b/i' => 'LOAD DATA INFILE is forbidden for security.',
        '/\bINTO\s+OUTFILE\b/i' => 'INTO OUTFILE statement is blocked.',
        '/\bSHUTDOWN\b/i' => 'SHUTDOWN statement is blocked.',
        '/\bSYSTEM\b/i' => 'SYSTEM calls are blocked.',
    ];

    /**
     * Scan file for dangerous/blacklisted SQL statements.
     */
    public function scanForMaliciousSql(string $filePath): array
    {
        $violations = [];
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new Exception("Unable to open SQL file for security scanning: {$filePath}");
        }

        $lineNumber = 0;
        while (($line = fgets($handle)) !== false) {
            $lineNumber++;
            // Strip comments
            $trimmed = trim($line);
            if (str_starts_with($trimmed, '--') || str_starts_with($trimmed, '#') || str_starts_with($trimmed, '/*')) {
                continue;
            }

            foreach ($this->blacklistedPatterns as $pattern => $message) {
                if (preg_match($pattern, $line)) {
                    $violations[] = [
                        'line' => $lineNumber,
                        'pattern' => $pattern,
                        'message' => $message,
                        'snippet' => substr($trimmed, 0, 120),
                    ];
                }
            }
        }
        fclose($handle);

        return $violations;
    }

    /**
     * Parse SQL file streamingly to extract tables, schema, relationships, and metadata.
     */
    public function parseFile(string $filePath): array
    {
        // 1. First run security scan
        $securityViolations = $this->scanForMaliciousSql($filePath);
        if (!empty($securityViolations)) {
            $firstViolation = $securityViolations[0];
            throw new Exception("Security Violation on line {$firstViolation['line']}: {$firstViolation['message']}");
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new Exception("Cannot open SQL file: {$filePath}");
        }

        $tables = [];
        $relationships = [];
        $recordCounts = [];
        $warnings = [];

        $currentStatement = '';
        $inMultilineComment = false;

        while (($line = fgets($handle)) !== false) {
            $trimmed = trim($line);

            // Handle multi-line comment /* ... */
            if ($inMultilineComment) {
                if (str_contains($trimmed, '*/')) {
                    $inMultilineComment = false;
                    $parts = explode('*/', $trimmed, 2);
                    $trimmed = trim($parts[1] ?? '');
                } else {
                    continue;
                }
            }

            if (str_starts_with($trimmed, '/*') && !str_contains($trimmed, '*/')) {
                $inMultilineComment = true;
                continue;
            }

            // Strip single-line comments and empty lines
            if (empty($trimmed) || str_starts_with($trimmed, '--') || str_starts_with($trimmed, '#')) {
                continue;
            }

            $currentStatement .= ' ' . $trimmed;

            // Check if statement ends with semicolon
            if (str_ends_with(rtrim($trimmed), ';')) {
                $stmt = trim($currentStatement);
                $currentStatement = '';

                // Process CREATE TABLE
                if (preg_match('/^CREATE\s+TABLE/i', $stmt)) {
                    $tableData = $this->parseCreateTableStatement($stmt);
                    if ($tableData) {
                        $tableName = $tableData['name'];
                        $tables[$tableName] = $tableData;
                        $recordCounts[$tableName] = $recordCounts[$tableName] ?? 0;

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
                // Process ALTER TABLE ADD COLUMN / ADD CONSTRAINT
                elseif (preg_match('/^ALTER\s+TABLE/i', $stmt)) {
                    $alterData = $this->parseAlterTableStatement($stmt);
                    if ($alterData && isset($tables[$alterData['table']])) {
                        $tableName = $alterData['table'];
                        if (!empty($alterData['columns'])) {
                            foreach ($alterData['columns'] as $newCol) {
                                $tables[$tableName]['columns'][] = $newCol;
                            }
                        }
                        if (!empty($alterData['foreign_keys'])) {
                            foreach ($alterData['foreign_keys'] as $fk) {
                                $tables[$tableName]['foreign_keys'][] = $fk;
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
                }
                // Fast record count estimate for INSERT INTO
                elseif (preg_match('/^INSERT\s+INTO\s+[`"\']?([a-zA-Z0-9_]+)[`"\']?/i', $stmt, $m)) {
                    $tbl = $m[1];
                    $rowCount = $this->estimateInsertRowCount($stmt);
                    $recordCounts[$tbl] = ($recordCounts[$tbl] ?? 0) + $rowCount;
                }
            }
        }
        fclose($handle);

        // Merge record counts into tables
        foreach ($tables as $name => &$table) {
            $table['record_count'] = $recordCounts[$name] ?? 0;
        }
        unset($table);

        $totalRecords = array_sum($recordCounts);

        return [
            'tables' => array_values($tables),
            'tables_keyed' => $tables,
            'relationships' => $relationships,
            'stats' => [
                'tables_count' => count($tables),
                'columns_count' => array_sum(array_map(fn($t) => count($t['columns']), $tables)),
                'records_count' => $totalRecords,
                'relationships_count' => count($relationships),
            ],
            'warnings' => $warnings,
        ];
    }

    /**
     * Parse single CREATE TABLE statement.
     */
    public function parseCreateTableStatement(string $stmt): ?array
    {
        if (!preg_match('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?(?:[`"\']?([a-zA-Z0-9_]+)[`"\']?\.)?[`"\']?([a-zA-Z0-9_]+)[`"\']?\s*\(/i', $stmt, $matches)) {
            return null;
        }

        $tableName = $matches[2] ?? $matches[1];
        $body = $this->extractParenthesizedBody($stmt);
        if (!$body) {
            return null;
        }

        $definitions = $this->splitDefinitions($body);
        $columns = [];
        $primaryKeys = [];
        $foreignKeys = [];
        $indexes = [];

        foreach ($definitions as $def) {
            $def = trim($def);
            if (empty($def)) continue;

            // PRIMARY KEY constraint
            if (preg_match('/^(?:CONSTRAINT\s+[`"\']?[a-zA-Z0-9_]+[`"\']?\s+)?PRIMARY\s+KEY\s*\(([^)]+)\)/i', $def, $pkMatch)) {
                $cols = array_map(fn($c) => trim(trim($c), '`"\' '), explode(',', $pkMatch[1]));
                $primaryKeys = array_merge($primaryKeys, $cols);
                continue;
            }

            // FOREIGN KEY constraint
            if (preg_match('/^(?:CONSTRAINT\s+[`"\']?([a-zA-Z0-9_]+)[`"\']?\s+)?FOREIGN\s+KEY\s*\(([^)]+)\)\s*REFERENCES\s+[`"\']?([a-zA-Z0-9_]+)[`"\']?\s*\(([^)]+)\)/i', $def, $fkMatch)) {
                $foreignKeys[] = [
                    'name' => $fkMatch[1] ?? null,
                    'column' => trim(trim($fkMatch[2]), '`"\' '),
                    'references_table' => trim($fkMatch[3], '`"\' '),
                    'references_column' => trim(trim($fkMatch[4]), '`"\' '),
                ];
                continue;
            }

            // UNIQUE / INDEX / KEY constraint
            if (preg_match('/^(?:UNIQUE\s+(?:KEY|INDEX)?|KEY|INDEX)\s+[`"\']?([a-zA-Z0-9_]+)?[`"\']?\s*\(([^)]+)\)/i', $def, $idxMatch)) {
                $isUnique = stripos($def, 'UNIQUE') !== false;
                $idxCols = array_map(fn($c) => trim(trim($c), '`"\' '), explode(',', $idxMatch[2]));
                $indexes[] = [
                    'name' => $idxMatch[1] ?? 'idx_' . implode('_', $idxCols),
                    'columns' => $idxCols,
                    'unique' => $isUnique,
                ];
                continue;
            }

            // Regular Column definition
            $colData = $this->parseColumnDefinition($def);
            if ($colData) {
                $columns[] = $colData;
                if ($colData['primary']) {
                    $primaryKeys[] = $colData['name'];
                }
            }
        }

        // Mark primary keys on columns
        $primaryKeys = array_unique($primaryKeys);
        foreach ($columns as &$col) {
            if (in_array($col['name'], $primaryKeys)) {
                $col['primary'] = true;
                $col['nullable'] = false;
            }
        }
        unset($col);

        return [
            'name' => $tableName,
            'columns' => $columns,
            'primary_key' => !empty($primaryKeys) ? implode(',', $primaryKeys) : null,
            'primary_keys' => $primaryKeys,
            'foreign_keys' => $foreignKeys,
            'indexes' => $indexes,
            'create_statement' => $stmt,
        ];
    }

    /**
     * Parse column definition string.
     */
    public function parseColumnDefinition(string $def): ?array
    {
        // Must start with column name
        if (!preg_match('/^[`"\']?([a-zA-Z0-9_]+)[`"\']?\s+([A-Za-z]+(?:\s*\([^)]+\))?(?:\s+UNSIGNED)?)/i', $def, $m)) {
            return null;
        }

        $name = $m[1];
        $rawType = trim($m[2]);
        $upperDef = strtoupper($def);

        // Nullable check
        $nullable = !str_contains($upperDef, 'NOT NULL');
        // Primary check
        $primary = str_contains($upperDef, 'PRIMARY KEY');
        // Auto increment
        $autoIncrement = str_contains($upperDef, 'AUTO_INCREMENT');

        // Default value
        $defaultValue = null;
        if (preg_match('/DEFAULT\s+([\'"].*?[\'"]|NULL|[0-9\.\-]+|[A-Za-z_]+(?:\(\))?)/i', $def, $defMatch)) {
            $defaultValue = trim($defMatch[1], "'\"");
            if (strtoupper($defaultValue) === 'NULL') {
                $defaultValue = null;
            }
        }

        // Clean standard type (e.g. VARCHAR(255), INT, TEXT, DECIMAL(10,2))
        $cleanType = strtoupper($rawType);

        return [
            'name' => $name,
            'type' => $cleanType,
            'raw_type' => $rawType,
            'nullable' => $nullable,
            'primary' => $primary,
            'auto_increment' => $autoIncrement,
            'default' => $defaultValue,
        ];
    }

    /**
     * Parse ALTER TABLE statement.
     */
    protected function parseAlterTableStatement(string $stmt): ?array
    {
        if (!preg_match('/ALTER\s+TABLE\s+[`"\']?([a-zA-Z0-9_]+)[`"\']?\s+(.+)/is', $stmt, $m)) {
            return null;
        }

        $tableName = $m[1];
        $actionPart = $m[2];
        $columns = [];
        $foreignKeys = [];

        if (preg_match('/ADD\s+COLUMN\s+(.+)/i', $actionPart, $addColMatch)) {
            $colData = $this->parseColumnDefinition(trim(rtrim($addColMatch[1], ';')));
            if ($colData) {
                $columns[] = $colData;
            }
        } elseif (preg_match('/FOREIGN\s+KEY\s*\(([^)]+)\)\s*REFERENCES\s+[`"\']?([a-zA-Z0-9_]+)[`"\']?\s*\(([^)]+)\)/i', $actionPart, $fkMatch)) {
            $foreignKeys[] = [
                'column' => trim(trim($fkMatch[1]), '`"\' '),
                'references_table' => trim($fkMatch[2], '`"\' '),
                'references_column' => trim(trim($fkMatch[3]), '`"\' '),
            ];
        }

        return [
            'table' => $tableName,
            'columns' => $columns,
            'foreign_keys' => $foreignKeys,
        ];
    }

    /**
     * Stream INSERT statements and invoke callback on batch of row associative arrays.
     */
    public function streamInsertRows(string $filePath, callable $callback, int $batchSize = 200): void
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new Exception("Unable to open SQL file: {$filePath}");
        }

        $currentStatement = '';
        $batches = []; // keyed by table

        while (($line = fgets($handle)) !== false) {
            $trimmed = trim($line);
            if (empty($trimmed) || str_starts_with($trimmed, '--') || str_starts_with($trimmed, '#') || str_starts_with($trimmed, '/*')) {
                continue;
            }

            $currentStatement .= ' ' . $trimmed;

            if (str_ends_with(rtrim($trimmed), ';')) {
                $stmt = trim($currentStatement);
                $currentStatement = '';

                if (preg_match('/^INSERT\s+INTO\s+[`"\']?([a-zA-Z0-9_]+)[`"\']?\s*(?:\(([^)]+)\))?\s*VALUES\s*(.+)/is', $stmt, $matches)) {
                    $tableName = $matches[1];
                    $columns = [];
                    if (!empty($matches[2])) {
                        $columns = array_map(fn($c) => trim(trim($c), '`"\' '), explode(',', $matches[2]));
                    }
                    $valuesStr = rtrim(trim($matches[3]), ';');

                    $rows = $this->parseValuesList($valuesStr, $columns);
                    if (!isset($batches[$tableName])) {
                        $batches[$tableName] = [];
                    }

                    foreach ($rows as $row) {
                        $batches[$tableName][] = $row;
                        if (count($batches[$tableName]) >= $batchSize) {
                            $callback($tableName, $batches[$tableName]);
                            $batches[$tableName] = [];
                        }
                    }
                }
            }
        }
        fclose($handle);

        // Flush remaining batches
        foreach ($batches as $tableName => $rows) {
            if (!empty($rows)) {
                $callback($tableName, $rows);
            }
        }
    }

    /**
     * Parse comma separated tuples of values: (val1, val2), (val3, val4)
     */
    public function parseValuesList(string $valuesStr, array $columns = []): array
    {
        $rows = [];
        $length = strlen($valuesStr);
        $inQuotes = false;
        $quoteChar = '';
        $inTuple = false;
        $currentValue = '';
        $currentRow = [];

        for ($i = 0; $i < $length; $i++) {
            $char = $valuesStr[$i];

            if ($inQuotes) {
                if ($char === '\\' && $i + 1 < $length) {
                    $currentValue .= $char . $valuesStr[$i + 1];
                    $i++;
                } elseif ($char === $quoteChar) {
                    $inQuotes = false;
                } else {
                    $currentValue .= $char;
                }
                continue;
            }

            if ($char === "'" || $char === '"') {
                $inQuotes = true;
                $quoteChar = $char;
                continue;
            }

            if ($char === '(' && !$inTuple) {
                $inTuple = true;
                $currentRow = [];
                $currentValue = '';
                continue;
            }

            if ($char === ')' && $inTuple) {
                $inTuple = false;
                $currentRow[] = $this->normalizeSqlValue($currentValue);
                $currentValue = '';

                // Map columns if available
                if (!empty($columns) && count($columns) === count($currentRow)) {
                    $rows[] = array_combine($columns, $currentRow);
                } else {
                    $rows[] = $currentRow;
                }
                continue;
            }

            if ($char === ',' && $inTuple) {
                $currentRow[] = $this->normalizeSqlValue($currentValue);
                $currentValue = '';
                continue;
            }

            if ($inTuple) {
                $currentValue .= $char;
            }
        }

        return $rows;
    }

    /**
     * Normalize literal SQL values.
     */
    protected function normalizeSqlValue(string $val): mixed
    {
        $trimmed = trim($val);
        if (strtoupper($trimmed) === 'NULL') {
            return null;
        }
        if (is_numeric($trimmed)) {
            return str_contains($trimmed, '.') ? (float) $trimmed : (int) $trimmed;
        }
        // Unescape standard SQL string
        return stripcslashes($trimmed);
    }

    /**
     * Fast estimate of row count in an INSERT INTO statement.
     */
    protected function estimateInsertRowCount(string $stmt): int
    {
        $valuesIndex = stripos($stmt, 'VALUES');
        if ($valuesIndex === false) return 1;
        $valuesPart = substr($stmt, $valuesIndex + 6);
        // Count top-level parentheses
        $count = substr_count($valuesPart, '),(') + 1;
        return max(1, $count);
    }

    /**
     * Extract parenthesized body of CREATE TABLE.
     */
    protected function extractParenthesizedBody(string $stmt): ?string
    {
        $firstParen = strpos($stmt, '(');
        if ($firstParen === false) return null;

        $lastParen = strrpos($stmt, ')');
        if ($lastParen === false || $lastParen <= $firstParen) return null;

        return substr($stmt, $firstParen + 1, $lastParen - $firstParen - 1);
    }

    /**
     * Split column and constraint definitions inside CREATE TABLE.
     */
    protected function splitDefinitions(string $body): array
    {
        $definitions = [];
        $current = '';
        $parenDepth = 0;
        $inQuotes = false;
        $quoteChar = '';
        $len = strlen($body);

        for ($i = 0; $i < $len; $i++) {
            $char = $body[$i];

            if ($inQuotes) {
                if ($char === '\\' && $i + 1 < $len) {
                    $current .= $char . $body[$i + 1];
                    $i++;
                } elseif ($char === $quoteChar) {
                    $inQuotes = false;
                    $current .= $char;
                } else {
                    $current .= $char;
                }
                continue;
            }

            if ($char === "'" || $char === '"' || $char === '`') {
                $inQuotes = true;
                $quoteChar = $char;
                $current .= $char;
                continue;
            }

            if ($char === '(') {
                $parenDepth++;
            } elseif ($char === ')') {
                $parenDepth--;
            }

            if ($char === ',' && $parenDepth === 0) {
                $definitions[] = trim($current);
                $current = '';
                continue;
            }

            $current .= $char;
        }

        if (!empty(trim($current))) {
            $definitions[] = trim($current);
        }

        return $definitions;
    }
}
