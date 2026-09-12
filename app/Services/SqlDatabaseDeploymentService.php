<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\ImportLog;
use App\Models\Industry;
use App\Models\Member;
use App\Models\NavigationItem;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\TenantCrmRecord;
use App\Models\TenantCustomColumn;
use App\Models\TenantSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SqlDatabaseDeploymentService
{
    protected TenantDatabaseService $tenantDbService;
    protected IndustryConfigurationService $industryConfigService;

    public function __construct(TenantDatabaseService $tenantDbService, IndustryConfigurationService $industryConfigService)
    {
        $this->tenantDbService = $tenantDbService;
        $this->industryConfigService = $industryConfigService;
    }

    /**
     * Read an uploaded SQL database backup file and deploy it into the user CRM.
     */
    public function deploySqlFile(Tenant $tenant, string $filePath, string $originalFileName, ?int $userId = null): array
    {
        @set_time_limit(300);
        @ini_set('memory_limit', '512M');

        $sql = file_get_contents($filePath);
        if (empty(trim($sql))) {
            throw new \Exception('The uploaded SQL file is empty.');
        }

        return $this->deploySqlContent($tenant, $sql, $originalFileName, $userId);
    }

    /**
     * Read and deploy raw SQL content into the tenant's CRM.
     */
    public function deploySqlContent(Tenant $tenant, string $sql, string $originalFileName, ?int $userId = null): array
    {
        $mainDbName = config('database.connections.mysql.database', 'jrv_crm');

        // 1. Ensure tenant has dedicated database name
        if (empty($tenant->database_name)) {
            $cleanSub = preg_replace('/[^a-zA-Z0-9_]/', '_', strtolower($tenant->subdomain ?: ($tenant->slug ?: 'crm')));
            $cleanSub = substr($cleanSub, 0, 20);
            $dbName = "crm_tenant_{$tenant->id}_{$cleanSub}";
            $tenant->update([
                'database_name' => $dbName,
                'database_status' => 'active',
                'database_created_at' => now(),
            ]);
        } else {
            $dbName = $tenant->database_name;
        }

        // Ensure the dedicated database exists in MySQL
        try {
            DB::statement("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
        } catch (\Throwable $e) {
            Log::warning("Could not create database `{$dbName}`, falling back to main DB: " . $e->getMessage());
            $dbName = $mainDbName;
        }

        // 2. Parse and split SQL statements
        $statements = $this->splitSqlStatements($sql);
        $executedTables = [];
        $executedCount = 0;
        $failedCount = 0;

        // Switch connection to target database for execution
        $switchedDb = false;
        if ($dbName !== $mainDbName) {
            try {
                DB::statement("USE `{$dbName}`;");
                $switchedDb = true;
            } catch (\Throwable $e) {
                $switchedDb = false;
            }
        }

        try {
            DB::statement("SET FOREIGN_KEY_CHECKS = 0;");
        } catch (\Throwable $e) {}

        try {
            foreach ($statements as $rawStmt) {
                $stmt = $this->sanitizeStatement($rawStmt);
                if (!$stmt) {
                    continue;
                }

                // Detect table creation
                if (preg_match('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?[`"]?([a-zA-Z0-9_]+)[`"]?/i', $stmt, $m)) {
                    $executedTables[] = $m[1];
                } elseif (preg_match('/INSERT\s+INTO\s+[`"]?([a-zA-Z0-9_]+)[`"]?/i', $stmt, $m)) {
                    if (!in_array($m[1], $executedTables)) {
                        $executedTables[] = $m[1];
                    }
                }

                try {
                    DB::unprepared($stmt);
                    $executedCount++;
                } catch (\Throwable $e) {
                    $failedCount++;
                    Log::debug("SQL deploy warning on statement: " . substr($stmt, 0, 100) . " - " . $e->getMessage());
                }
            }
        } finally {
            try {
                DB::statement("SET FOREIGN_KEY_CHECKS = 1;");
            } catch (\Throwable $e) {}

            if ($switchedDb) {
                try {
                    DB::statement("USE `{$mainDbName}`;");
                } catch (\Throwable $e) {}
            }
        }

        // 3. Fallback table discovery from SQL content if not yet identified
        if (empty($executedTables)) {
            preg_match_all('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?[`"]?([a-zA-Z0-9_]+)[`"]?/i', $sql, $tblMatches);
            if (!empty($tblMatches[1])) {
                $executedTables = array_unique($tblMatches[1]);
            }
            if (empty($executedTables)) {
                preg_match_all('/INSERT\s+INTO\s+[`"]?([a-zA-Z0-9_]+)[`"]?/i', $sql, $insMatches);
                if (!empty($insMatches[1])) {
                    $executedTables = array_unique($insMatches[1]);
                }
            }
        }

        $baseFileName = pathinfo($originalFileName, PATHINFO_FILENAME);
        $primaryTable = !empty($executedTables)
            ? reset($executedTables)
            : (Str::snake(preg_replace('/[^a-zA-Z0-9_]/', '_', $baseFileName)) ?: 'crm_leads');

        // 4. Extract schema columns & data rows for CRM deployment
        $columnsData = [];
        $importedRows = [];

        // Check information schema of target DB for the table
        $columnsFromDb = [];
        try {
            $columnsFromDb = DB::select(
                "SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? ORDER BY ORDINAL_POSITION",
                [$dbName, $primaryTable]
            );
        } catch (\Throwable $e) {}

        if (!empty($columnsFromDb)) {
            foreach ($columnsFromDb as $col) {
                if (in_array(strtolower($col->COLUMN_NAME), ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                    continue;
                }
                $columnsData[] = [
                    'key' => $col->COLUMN_NAME,
                    'label' => Str::title(str_replace('_', ' ', $col->COLUMN_NAME)),
                    'type' => $this->detectColumnType($col->COLUMN_NAME, $col->DATA_TYPE),
                ];
            }

            // Fetch actual deployed rows from MySQL table
            try {
                $queryTable = $dbName !== $mainDbName ? "`{$dbName}`.`{$primaryTable}`" : "`{$primaryTable}`";
                $importedRows = collect(DB::select("SELECT * FROM {$queryTable} LIMIT 500"))
                    ->map(fn($r) => (array)$r)
                    ->toArray();
            } catch (\Throwable $e) {}
        }

        // If table could not be queried directly from information schema, parse directly from SQL syntax
        if (empty($columnsData) || empty($importedRows)) {
            $parsed = $this->parseTableAndRowsFromSql($sql, $primaryTable);
            if (empty($columnsData)) {
                $columnsData = $parsed['columns'];
            }
            if (empty($importedRows)) {
                $importedRows = $parsed['rows'];
            }
        }

        // 5. Intelligently detect sector & adapt the user's CRM
        $detectedIndustry = $this->detectAndConvertCrm(
            $tenant,
            $primaryTable,
            $columnsData,
            $importedRows,
            $originalFileName
        );
        $tenant->refresh();

        // 6. Register Custom Columns in User CRM metadata
        $importedColsCount = 0;
        $existingKeys = TenantCustomColumn::where('tenant_id', $tenant->id)->pluck('column_key')->toArray();
        $maxOrder = TenantCustomColumn::where('tenant_id', $tenant->id)->max('display_order') ?? 0;

        foreach ($columnsData as $c) {
            if (!in_array($c['key'], $existingKeys)) {
                TenantCustomColumn::create([
                    'tenant_id' => $tenant->id,
                    'table_name' => $primaryTable,
                    'column_key' => $c['key'],
                    'column_label' => $c['label'],
                    'column_type' => $c['type'],
                    'is_required' => false,
                    'is_default' => false,
                    'is_visible' => true,
                    'display_order' => ++$maxOrder,
                ]);
                $importedColsCount++;
            }
        }

        // 7. Deploy records into TenantCrmRecord & Sector Entities
        $importedRowsCount = 0;
        if (!empty($importedRows)) {
            foreach ($importedRows as $row) {
                $rowArr = (array)$row;

                $cName = $rowArr['name'] ?? $rowArr['contact_name'] ?? $rowArr['title'] ?? $rowArr['full_name'] ?? $rowArr['first_name'] ?? null;
                if (!$cName) {
                    foreach ($rowArr as $k => $v) {
                        if (is_string($v) && strlen($v) > 1 && !in_array($k, ['id', 'email', 'phone', 'status'])) {
                            $cName = $v;
                            break;
                        }
                    }
                }

                $recordValue = 0;
                foreach ($rowArr as $k => $v) {
                    $lk = strtolower($k);
                    if (str_contains($lk, 'price') || str_contains($lk, 'rent') || str_contains($lk, 'amount') || str_contains($lk, 'salary') || str_contains($lk, 'fee') || str_contains($lk, 'value') || str_contains($lk, 'cost') || str_contains($lk, 'budget')) {
                        if (is_numeric($v) && (float)$v > 0) {
                            $recordValue = (float)$v;
                            break;
                        }
                    }
                }

                TenantCrmRecord::create([
                    'tenant_id' => $tenant->id,
                    'title' => $cName ?: 'Imported Record',
                    'contact_name' => $cName ?: 'Imported Record',
                    'email' => $rowArr['email'] ?? $rowArr['mail'] ?? $rowArr['client_email'] ?? null,
                    'phone' => $rowArr['phone'] ?? $rowArr['mobile'] ?? $rowArr['contact'] ?? null,
                    'company' => $rowArr['company'] ?? $rowArr['org'] ?? $rowArr['organization'] ?? null,
                    'status' => $rowArr['status'] ?? 'New Lead',
                    'value' => $recordValue,
                    'custom_data' => $rowArr,
                    'created_by' => $userId,
                ]);

                $this->syncSectorEntity($tenant, $rowArr, $primaryTable, $detectedIndustry);
                $importedRowsCount++;
            }
        }

        // 8. Log the completed deployment
        ImportLog::create([
            'tenant_id' => $tenant->id,
            'import_source' => 'SQL Database Upload',
            'entity_type' => 'crm_database_table',
            'file_name' => $originalFileName,
            'total_rows' => $importedRowsCount,
            'imported_rows' => $importedRowsCount,
            'failed_rows' => 0,
            'status' => 'Completed',
            'summary' => "Deployed SQL database into CRM: Table '{$primaryTable}' created in database `{$dbName}` with {$importedColsCount} custom columns and {$importedRowsCount} records.",
        ]);

        return [
            'success' => true,
            'database_name' => $dbName,
            'primary_table' => $primaryTable,
            'tables' => $executedTables,
            'tables_created' => $executedTables,
            'columns_registered' => $importedColsCount,
            'rows_deployed' => $importedRowsCount,
            'detected_industry' => $detectedIndustry,
            'message' => "Successfully read SQL database, deployed table '{$primaryTable}' into database `{$dbName}`, and synchronized {$importedRowsCount} records into CRM!",
        ];
    }

    /**
     * Split full SQL dump into distinct executable statements, respecting quotes and backticks.
     */
    public function splitSqlStatements(string $sql): array
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

            // Handle single quote
            if ($char === "'" && $prevChar !== '\\' && !$inDoubleQuote && !$inBacktick) {
                $inSingleQuote = !$inSingleQuote;
            }
            // Handle double quote
            elseif ($char === '"' && $prevChar !== '\\' && !$inSingleQuote && !$inBacktick) {
                $inDoubleQuote = !$inDoubleQuote;
            }
            // Handle backtick
            elseif ($char === '`' && !$inSingleQuote && !$inDoubleQuote) {
                $inBacktick = !$inBacktick;
            }

            // End of SQL statement
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
     * Clean and sanitize SQL statements, stripping comments and neutralizing foreign USE/CREATE DATABASE commands.
     */
    public function sanitizeStatement(string $statement): ?string
    {
        $lines = explode("\n", $statement);
        $cleanedLines = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (str_starts_with($trimmed, '--') || str_starts_with($trimmed, '#')) {
                continue;
            }
            $cleanedLines[] = $line;
        }

        $stmt = trim(implode("\n", $cleanedLines));

        // Strip C-style comments /* ... */
        $stmt = preg_replace('/\/\*[\s\S]*?\*\//', '', $stmt);
        $stmt = trim($stmt);

        if (empty($stmt)) {
            return null;
        }

        // Neutralize foreign database operations so tenant CRM database is protected
        if (preg_match('/^(?:CREATE\s+DATABASE|USE\s+|DROP\s+DATABASE)/i', $stmt)) {
            return null;
        }

        return $stmt;
    }

    /**
     * Map SQL database data types to CRM custom column types.
     */
    public function detectColumnType(string $columnName, string $dataType = 'text'): string
    {
        $colLower = strtolower($columnName);
        $typeLower = strtolower($dataType);

        if (str_contains($colLower, 'email') || str_contains($colLower, 'mail')) {
            return 'email';
        }
        if (str_contains($colLower, 'phone') || str_contains($colLower, 'mobile') || str_contains($colLower, 'contact')) {
            return 'phone';
        }
        if (str_contains($colLower, 'price') || str_contains($colLower, 'amount') || str_contains($colLower, 'value') || str_contains($colLower, 'salary') || str_contains($colLower, 'fee') || str_contains($colLower, 'rent') || str_contains($colLower, 'deposit')) {
            return 'currency';
        }
        if (in_array($typeLower, ['date', 'datetime', 'timestamp']) || str_contains($colLower, 'date') || str_contains($colLower, 'dob') || str_contains($colLower, 'year')) {
            return 'date';
        }
        if (in_array($typeLower, ['int', 'tinyint', 'smallint', 'mediumint', 'bigint', 'decimal', 'float', 'double']) || str_contains($colLower, 'count') || str_contains($colLower, 'bhk') || str_contains($colLower, 'bedroom') || str_contains($colLower, 'area')) {
            return 'number';
        }
        if (in_array($typeLower, ['text', 'mediumtext', 'longtext']) || str_contains($colLower, 'notes') || str_contains($colLower, 'description') || str_contains($colLower, 'address')) {
            return 'textarea';
        }

        return 'text';
    }

    /**
     * Parse table schema and data rows directly from SQL text when database query is unavailable.
     */
    public function parseTableAndRowsFromSql(string $sql, string $tableName): array
    {
        $columns = [];
        $rows = [];

        // Parse CREATE TABLE block
        if (preg_match('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?[`"]?' . preg_quote($tableName, '/') . '[`"]?\s*\(([\s\S]*?)\)(?:ENGINE|\s*;)/i', $sql, $tblMatch)) {
            $colDefs = explode(',', $tblMatch[1]);
            foreach ($colDefs as $def) {
                $def = trim($def);
                if (preg_match('/^[`"]?([a-zA-Z0-9_]+)[`"]?\s+([a-zA-Z]+)/i', $def, $cm)) {
                    $name = $cm[1];
                    $type = strtolower($cm[2]);
                    if (in_array(strtolower($name), ['primary', 'key', 'index', 'constraint', 'unique', 'foreign'])) {
                        continue;
                    }
                    if (in_array(strtolower($name), ['id', 'created_at', 'updated_at'])) {
                        continue;
                    }

                    $columns[] = [
                        'key' => $name,
                        'label' => Str::title(str_replace('_', ' ', $name)),
                        'type' => $this->detectColumnType($name, $type),
                    ];
                }
            }
        }

        // Parse INSERT INTO rows
        if (preg_match_all('/INSERT\s+INTO\s+[`"]?' . preg_quote($tableName, '/') . '[`"]?\s*(?:\(([\s\S]*?)\))?\s*VALUES\s*([\s\S]*?);/i', $sql, $insMatches, PREG_SET_ORDER)) {
            foreach ($insMatches as $ins) {
                $colsList = !empty($ins[1])
                    ? array_map(fn($c) => trim(str_replace(['`', '"', "'"], '', $c)), explode(',', $ins[1]))
                    : array_column($columns, 'key');

                $valuesBlock = trim($ins[2]);
                preg_match_all('/\(([\s\S]*?)\)/', $valuesBlock, $tupleMatches);

                foreach ($tupleMatches[1] as $tuple) {
                    $vals = str_getcsv($tuple, ',', "'");
                    $row = [];
                    foreach ($colsList as $idx => $colName) {
                        $row[$colName] = isset($vals[$idx]) ? trim($vals[$idx]) : null;
                    }
                    if (!empty($row)) {
                        $rows[] = $row;
                    }
                }
            }
        }

        return ['columns' => $columns, 'rows' => $rows];
    }

    /**
     * Detect industry sector and convert tenant CRM configuration.
     */
    public function detectAndConvertCrm(Tenant $tenant, string $tableName, array $columnsData, array $importedRows, string $fileName): ?Industry
    {
        $indicators = [
            'real-estate' => [
                'table' => ['propert', 'flat', 'apartment', 'listing', 'rent', 'bhk', 'villa', 'housing', 'plot', 'estate', 'realt'],
                'columns' => ['bhk', 'bedroom', 'bathroom', 'carpet_area', 'sqft', 'furnishing', 'locality', 'property_type', 'property_code', 'listing_type', 'rent', 'deposit', 'possession', 'floor', 'amenities', 'owner_name', 'owner_phone', 'builtup'],
            ],
            'education' => [
                'table' => ['course', 'student', 'admission', 'college', 'school', 'university', 'academic', 'curriculum', 'enrollment', 'tuition'],
                'columns' => ['course', 'student', 'admission', 'enrollment', 'degree', 'semester', 'grade', 'syllabus', 'counselor', 'fees', 'tuition', 'passing_year', 'qualification', 'marks', 'percentage', 'board'],
            ],
            'healthcare' => [
                'table' => ['patient', 'doctor', 'appointment', 'treatment', 'clinic', 'hospital', 'medical', 'prescription', 'diagnos'],
                'columns' => ['patient', 'doctor', 'treatment', 'appointment', 'diagnosis', 'prescription', 'blood_group', 'symptoms', 'disease', 'dosage', 'specialization', 'ward', 'bed', 'physician', 'clinic'],
            ],
            'matrimonial' => [
                'table' => ['biodata', 'matrimonial', 'match', 'bride', 'groom', 'profiles', 'community'],
                'columns' => ['gotra', 'sub_caste', 'caste', 'rashi', 'nakshatra', 'kundali', 'manglik', 'marital_status', 'biodata', 'mother_gotra', 'family_type', 'complexion', 'horoscope', 'partner_preference'],
            ],
            'recruitment' => [
                'table' => ['job', 'candidate', 'resume', 'applicant', 'vacancy', 'placement', 'interview', 'hiring', 'recruitment'],
                'columns' => ['job_title', 'candidate_name', 'resume', 'current_ctc', 'expected_ctc', 'notice_period', 'skills', 'experience_years', 'interview_round', 'recruiter', 'hiring_manager', 'applicant_id'],
            ],
            'legal' => [
                'table' => ['case', 'hearing', 'court', 'lawyer', 'advocate', 'petition', 'litigation', 'legal'],
                'columns' => ['case_number', 'court_name', 'hearing_date', 'judge', 'advocate', 'petitioner', 'respondent', 'matter_type', 'legal_fee', 'bench'],
            ],
            'research-publication' => [
                'table' => ['manuscript', 'journal', 'paper', 'article', 'publication', 'submission'],
                'columns' => ['manuscript_title', 'doi', 'journal_name', 'volume', 'issue', 'scopus', 'apc_fee', 'peer_review', 'abstract', 'keywords', 'author_email'],
            ],
            'automobile' => [
                'table' => ['vehicle', 'car', 'automobile', 'fleet', 'bike', 'motor', 'dealership'],
                'columns' => ['vin', 'odometer', 'make', 'model', 'fuel_type', 'chassis', 'registration_no', 'transmission', 'engine_capacity', 'mileage'],
            ],
            'ecommerce' => [
                'table' => ['product', 'order', 'sku', 'inventory', 'item', 'catalog', 'merchandise'],
                'columns' => ['sku', 'product_name', 'unit_price', 'stock_quantity', 'barcode', 'reorder_level', 'supplier', 'weight_kg'],
            ],
            'insurance' => [
                'table' => ['policy', 'claim', 'insurance', 'underwriting', 'insured'],
                'columns' => ['policy_no', 'sum_insured', 'premium', 'claim_amount', 'policy_type', 'nominee', 'insured_name', 'tenure'],
            ],
            'banking-finance' => [
                'table' => ['loan', 'account', 'banking', 'finance', 'borrower'],
                'columns' => ['loan_amount', 'interest_rate', 'account_no', 'cibil_score', 'collateral', 'emi', 'borrower_name'],
            ],
        ];

        $scores = array_fill_keys(array_keys($indicators), 0);

        $cleanTableName = strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', ' ', $tableName)));
        $cleanFileName = strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', ' ', $fileName)));
        $columnKeys = array_map('strtolower', array_column($columnsData, 'key'));

        foreach ($indicators as $sector => $data) {
            // Check Table Name
            foreach ($data['table'] as $kw) {
                if (str_contains($cleanTableName, $kw)) {
                    $scores[$sector] += 8;
                }
                if (str_contains($cleanFileName, $kw)) {
                    $scores[$sector] += 5;
                }
            }

            // Check Columns
            foreach ($columnKeys as $col) {
                foreach ($data['columns'] as $kw) {
                    if (str_contains($col, $kw)) {
                        $scores[$sector] += 3;
                        break;
                    }
                }
            }

            // Check Sample Data
            $sampleValues = [];
            foreach (array_slice($importedRows, 0, 10) as $row) {
                foreach ((array)$row as $val) {
                    if (is_string($val) && strlen($val) > 2 && strlen($val) < 50) {
                        $sampleValues[] = strtolower($val);
                    }
                }
            }

            foreach ($sampleValues as $val) {
                foreach ($data['columns'] as $kw) {
                    if (str_contains($val, $kw)) {
                        $scores[$sector] += 1;
                        break;
                    }
                }
            }
        }

        arsort($scores);
        $bestSlug = array_key_first($scores);
        $highestScore = $scores[$bestSlug];

        $industry = null;
        if ($highestScore >= 3) {
            $industry = Industry::where('slug', $bestSlug)->first();
        }

        if ($industry) {
            $this->industryConfigService->switchIndustry($tenant, $industry);

            TenantSetting::setByKey('industry_name', $industry->name, $tenant->id);
            TenantSetting::setByKey('industry_slug', $industry->slug, $tenant->id);
            TenantSetting::setByKey('industry_color', $industry->color, $tenant->id);
            TenantSetting::setByKey('active_crm_table', $tableName, $tenant->id);
            $tenant->refresh();
        }

        // Add prominent Navigation Item in User CRM
        $cleanLabel = Str::title(str_replace(['_', '-'], ' ', $tableName));
        if (in_array(strtolower($cleanLabel), ['crm leads', 'imported record', 'imported records', 'data', 'sheet1', 'table'])) {
            $cleanLabel = $industry ? ($industry->name . ' Data') : 'CRM Data';
        }

        NavigationItem::updateOrCreate(
            ['tenant_id' => $tenant->id, 'key' => 'tenant_crm_database'],
            [
                'label' => $cleanLabel,
                'icon' => 'CircleStackIcon',
                'route' => '/tenant/crm-records',
                'display_order' => 2,
                'is_enabled' => true,
            ]
        );

        return $industry;
    }

    /**
     * Cross-sync imported rows into sector entities.
     */
    public function syncSectorEntity(Tenant $tenant, array $rowArr, ?string $tableName = null, ?Industry $detectedIndustry = null): void
    {
        $industrySlug = $detectedIndustry?->slug ?? $tenant->industry?->slug;

        $cName = $rowArr['name'] ?? $rowArr['contact_name'] ?? $rowArr['title'] ?? $rowArr['full_name'] ?? $rowArr['first_name'] ?? null;
        $email = $rowArr['email'] ?? $rowArr['mail'] ?? $rowArr['client_email'] ?? null;
        $phone = $rowArr['phone'] ?? $rowArr['mobile'] ?? $rowArr['contact'] ?? $rowArr['contact_phone'] ?? null;

        // 1. Real Estate Properties
        $isProperty = ($industrySlug === 'real-estate')
            || in_array(strtolower((string)$tableName), ['properties', 'property', 'real_estate', 'listings', 'flats', 'units', 'apartments'])
            || isset($rowArr['property_type'])
            || isset($rowArr['bedrooms'])
            || isset($rowArr['bhk'])
            || isset($rowArr['carpet_area_sqft'])
            || (isset($rowArr['locality']) && isset($rowArr['price']));

        if ($isProperty) {
            $propTitle = $rowArr['title'] ?? $rowArr['property_name'] ?? $rowArr['name'] ?? $rowArr['unit_name'] ?? 'Real Estate Property';
            $pType = $rowArr['property_type'] ?? $rowArr['type'] ?? 'Apartment';
            $existingProp = Property::where('tenant_id', $tenant->id)->where('title', $propTitle)->first();
            $pCode = $rowArr['property_code'] 
                ?? ($existingProp?->property_code 
                    ?: ('PROP-' . strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $pType) ?: 'PRP', 0, 3)) . '-' . str_pad((Property::max('id') ?? 0) + 1, 4, '0', STR_PAD_LEFT)));
            $bhk = $rowArr['bedrooms'] ?? $rowArr['bhk'] ?? 2;

            $propPrice = 0;
            foreach ($rowArr as $k => $v) {
                $lk = strtolower($k);
                if (str_contains($lk, 'price') || str_contains($lk, 'rent') || str_contains($lk, 'amount')) {
                    if (is_numeric($v) && (float)$v > 0) {
                        $propPrice = (float)$v;
                        break;
                    }
                }
            }
            if ($propPrice <= 0) {
                $propPrice = 25000;
            }

            Property::updateOrCreate(
                ['tenant_id' => $tenant->id, 'title' => $propTitle],
                [
                    'property_code' => $pCode,
                    'listing_type' => $rowArr['listing_type'] ?? $rowArr['purpose'] ?? 'For Rent',
                    'property_type' => $pType,
                    'price' => $propPrice,
                    'security_deposit' => (float)($rowArr['security_deposit'] ?? $rowArr['deposit'] ?? 50000),
                    'bedrooms' => is_numeric($bhk) ? (int)$bhk : 2,
                    'bathrooms' => (int)($rowArr['bathrooms'] ?? 2),
                    'carpet_area_sqft' => (int)($rowArr['carpet_area_sqft'] ?? $rowArr['area'] ?? 850),
                    'furnishing_status' => $rowArr['furnishing_status'] ?? $rowArr['furnishing'] ?? 'Semi-Furnished',
                    'locality' => $rowArr['locality'] ?? $rowArr['location'] ?? $rowArr['address'] ?? 'Prime Area',
                    'city' => $rowArr['city'] ?? 'Mumbai',
                    'state' => $rowArr['state'] ?? 'Maharashtra',
                    'owner_name' => $rowArr['owner_name'] ?? $rowArr['owner'] ?? 'Property Owner',
                    'owner_phone' => $rowArr['owner_phone'] ?? $phone ?? '+91 9800000000',
                    'owner_email' => $rowArr['owner_email'] ?? $email ?? null,
                    'status' => $rowArr['status'] ?? 'Available',
                ]
            );
        }

        // 2. Matrimonial Members
        $isMember = ($industrySlug === 'matrimonial')
            || in_array(strtolower((string)$tableName), ['members', 'biodata', 'matrimonial', 'profiles'])
            || isset($rowArr['gotra'])
            || isset($rowArr['caste'])
            || isset($rowArr['rashi']);

        if ($isMember) {
            $mCode = 'MBR-' . str_pad(Member::where('tenant_id', $tenant->id)->count() + 1, 4, '0', STR_PAD_LEFT);
            $fullName = $cName ?: 'Community Member';
            $nameParts = explode(' ', trim($fullName), 2);

            Member::updateOrCreate(
                ['tenant_id' => $tenant->id, 'phone' => $phone ?: ('+91 98' . rand(10000000, 99999999))],
                [
                    'member_code' => $rowArr['member_code'] ?? $mCode,
                    'first_name' => $nameParts[0],
                    'last_name' => $nameParts[1] ?? '',
                    'gender' => $rowArr['gender'] ?? 'Male',
                    'age' => (int)($rowArr['age'] ?? 26),
                    'marital_status' => $rowArr['marital_status'] ?? 'Never Married',
                    'religion' => $rowArr['religion'] ?? 'Hindu',
                    'caste' => $rowArr['caste'] ?? 'General',
                    'sub_caste' => $rowArr['sub_caste'] ?? null,
                    'gotra' => $rowArr['gotra'] ?? null,
                    'education_level' => $rowArr['education'] ?? $rowArr['degree'] ?? 'Graduate',
                    'occupation_type' => $rowArr['occupation'] ?? $rowArr['profession'] ?? 'Private Sector',
                    'annual_income' => (float)($rowArr['annual_income'] ?? $rowArr['income'] ?? 600000),
                    'city' => $rowArr['city'] ?? 'Mumbai',
                    'state' => $rowArr['state'] ?? 'Maharashtra',
                    'email' => $email,
                    'verification_status' => 'Verified',
                ]
            );
        }

        // 3. Contacts & Universal Leads
        if ($cName || !empty($email) || !empty($phone)) {
            $parts = preg_split('/\s+/', trim($cName ?: 'Contact'), 2);
            $cEmail = $email ?: ('contact_' . Str::random(6) . '@example.com');

            $jobTitle = $rowArr['designation'] ?? $rowArr['job_title'] ?? null;
            if (!$jobTitle && $industrySlug === 'education') {
                $jobTitle = $rowArr['course'] ?? $rowArr['course_name'] ?? 'Student';
            } elseif (!$jobTitle && $industrySlug === 'healthcare') {
                $jobTitle = $rowArr['doctor'] ?? $rowArr['treatment'] ?? 'Patient';
            }

            Contact::updateOrCreate(
                ['tenant_id' => $tenant->id, 'email' => $cEmail],
                [
                    'first_name' => $parts[0] ?? 'Contact',
                    'last_name' => $parts[1] ?? '',
                    'phone' => $phone ?? '',
                    'job_title' => $jobTitle,
                    'status' => $rowArr['status'] ?? 'Active',
                ]
            );

            if (Schema::hasTable('crm_sales_leads')) {
                try {
                    DB::table('crm_sales_leads')->updateOrInsert(
                        ['email' => $cEmail],
                        [
                            'tenant_id' => $tenant->id,
                            'customer_name' => $cName ?: 'Client Lead',
                            'company_name' => $rowArr['company'] ?? $rowArr['org'] ?? null,
                            'phone' => $phone ?: '',
                            'industry' => $industrySlug ?: 'general',
                            'deal_stage' => $rowArr['deal_stage'] ?? $rowArr['stage'] ?? $rowArr['status'] ?? 'New Lead',
                            'estimated_mrr' => (float)($rowArr['value'] ?? $rowArr['deal_value'] ?? $rowArr['price'] ?? $rowArr['rent'] ?? $rowArr['amount'] ?? 0),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                } catch (\Throwable $e) {
                    // Non-critical background lead sync
                }
            }
        }
    }

    /**
     * Read an uploaded spreadsheet (CSV, XLSX, XLS, JSON) and deploy it into the user CRM.
     */
    public function deploySpreadsheetFile(Tenant $tenant, $file, string $originalFileName, ?int $userId = null): array
    {
        @set_time_limit(300);
        @ini_set('memory_limit', '512M');

        $importService = app(\App\Services\UniversalImportService::class);
        $importedRows = $importService->parseFile($file);

        if (empty($importedRows)) {
            throw new \Exception('No rows found in the uploaded file.');
        }

        $baseFileName = pathinfo($originalFileName, PATHINFO_FILENAME);
        $tableName = Str::snake(preg_replace('/[^a-zA-Z0-9_]/', '_', $baseFileName)) ?: 'crm_leads';

        $firstRow = (array)$importedRows[0];
        $columnsData = [];

        foreach (array_keys($firstRow) as $colKey) {
            if (in_array(strtolower($colKey), ['id', 'created_at', 'updated_at'])) continue;

            $dataType = 'text';
            $lower = strtolower($colKey);
            if (str_contains($lower, 'email')) {
                $dataType = 'email';
            } elseif (str_contains($lower, 'phone') || str_contains($lower, 'mobile') || str_contains($lower, 'contact')) {
                $dataType = 'phone';
            } elseif (str_contains($lower, 'price') || str_contains($lower, 'rent') || str_contains($lower, 'amount') || str_contains($lower, 'salary') || str_contains($lower, 'fee') || str_contains($lower, 'deposit')) {
                $dataType = 'currency';
            } elseif (str_contains($lower, 'date') || str_contains($lower, 'dob') || str_contains($lower, 'year')) {
                $dataType = 'date';
            } elseif (str_contains($lower, 'age') || str_contains($lower, 'count') || str_contains($lower, 'bhk') || str_contains($lower, 'bedroom') || str_contains($lower, 'area')) {
                $dataType = 'number';
            }

            $columnsData[] = [
                'key' => $colKey,
                'label' => Str::title(str_replace('_', ' ', $colKey)),
                'type' => $dataType,
            ];
        }

        // Intelligently detect sector and automatically convert tenant CRM
        $detectedIndustry = $this->detectAndConvertCrm($tenant, $tableName, $columnsData, $importedRows, $originalFileName);
        $tenant->refresh();

        // Register columns in TenantCustomColumn
        $existingKeys = TenantCustomColumn::where('tenant_id', $tenant->id)->pluck('column_key')->toArray();
        $maxOrder = TenantCustomColumn::where('tenant_id', $tenant->id)->max('display_order') ?? 0;
        $importedColsCount = 0;

        foreach ($columnsData as $c) {
            if (!in_array($c['key'], $existingKeys)) {
                TenantCustomColumn::create([
                    'tenant_id' => $tenant->id,
                    'table_name' => $tableName,
                    'column_key' => $c['key'],
                    'column_label' => $c['label'],
                    'column_type' => $c['type'],
                    'is_required' => false,
                    'is_default' => false,
                    'is_visible' => true,
                    'display_order' => ++$maxOrder,
                ]);
                $importedColsCount++;
            }
        }

        // Insert into TenantCrmRecord and sync to sector tables
        $importedRowsCount = 0;
        foreach ($importedRows as $row) {
            $rowArr = (array)$row;
            $cName = $rowArr['name'] ?? $rowArr['contact_name'] ?? $rowArr['title'] ?? $rowArr['full_name'] ?? $rowArr['first_name'] ?? null;
            if (!$cName) {
                foreach ($rowArr as $k => $v) {
                    if (is_string($v) && strlen($v) > 1 && !in_array($k, ['id', 'email', 'phone', 'status'])) {
                        $cName = $v;
                        break;
                    }
                }
            }

            TenantCrmRecord::create([
                'tenant_id' => $tenant->id,
                'title' => $cName ?: 'Imported Record',
                'contact_name' => $cName ?: 'Imported Record',
                'email' => $rowArr['email'] ?? $rowArr['mail'] ?? null,
                'phone' => $rowArr['phone'] ?? $rowArr['mobile'] ?? $rowArr['contact'] ?? null,
                'company' => $rowArr['company'] ?? $rowArr['org'] ?? $rowArr['organization'] ?? null,
                'status' => $rowArr['status'] ?? 'New Lead',
                'value' => (float)($rowArr['value'] ?? $rowArr['price'] ?? $rowArr['rent'] ?? $rowArr['amount'] ?? 0),
                'custom_data' => $rowArr,
                'created_by' => $userId,
            ]);

            $this->syncSectorEntity($tenant, $rowArr, $tableName, $detectedIndustry);
            $importedRowsCount++;
        }

        $dbName = $tenant->database_name ?: "crm_tenant_{$tenant->id}";

        ImportLog::create([
            'tenant_id' => $tenant->id,
            'import_source' => 'Spreadsheet Database Upload',
            'entity_type' => 'crm_database_table',
            'file_name' => $originalFileName,
            'total_rows' => $importedRowsCount,
            'imported_rows' => $importedRowsCount,
            'failed_rows' => 0,
            'status' => 'Completed',
            'summary' => "Created database table '{$tableName}' with {$importedColsCount} schema columns and loaded {$importedRowsCount} records into CRM.",
        ]);

        return [
            'success' => true,
            'database_name' => $dbName,
            'primary_table' => $tableName,
            'tables_created' => [$tableName],
            'columns_registered' => $importedColsCount,
            'rows_deployed' => $importedRowsCount,
            'detected_industry' => $detectedIndustry,
        ];
    }
}
