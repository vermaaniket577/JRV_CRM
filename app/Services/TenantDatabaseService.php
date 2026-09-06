<?php

namespace App\Services;

use App\Models\Industry;
use App\Models\Tenant;
use App\Models\TenantCrmRecord;
use App\Models\TenantCustomColumn;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TenantDatabaseService
{
    /**
     * Provision a dedicated MySQL database for the tenant and configure custom columns.
     */
    public function createTenantDatabase(Tenant $tenant, array $selectedColumns = []): bool
    {
        try {
            // 1. Generate sanitized database name from subdomain
            $cleanSub = preg_replace('/[^a-zA-Z0-9_]/', '_', strtolower($tenant->subdomain ?: ($tenant->slug ?: 'crm')));
            $cleanSub = substr($cleanSub, 0, 20);
            $dbName = "crm_tenant_{$tenant->id}_{$cleanSub}";

            // 2. Create the dedicated database in MySQL
            DB::statement("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

            // 3. Register dynamic connection
            $this->registerDynamicConnection($dbName);

            // 4. If no columns passed, load industry presets
            if (empty($selectedColumns)) {
                $selectedColumns = $this->getDefaultColumnsForTenant($tenant);
            }

            // 5. Create dynamic CRM records and core isolated tables in tenant's dedicated database
            $this->createTenantRecordsTable($dbName, $selectedColumns);

            // 6. Save column definitions in tenant_custom_columns (main database)
            $this->syncTenantCustomColumns($tenant, $selectedColumns);

            // 7. Update tenant record
            $tenant->update([
                'database_name' => $dbName,
                'database_host' => config('database.connections.mysql.host', '127.0.0.1'),
                'database_port' => config('database.connections.mysql.port', '3306'),
                'database_username' => config('database.connections.mysql.username', 'root'),
                'database_status' => 'active',
                'database_created_at' => now(),
                'selected_columns_meta' => $selectedColumns,
            ]);

            // 8. Seed initial sample records matching the selected columns
            $this->seedSampleRecords($tenant, $selectedColumns, $dbName);

            return true;
        } catch (\Throwable $e) {
            Log::error("Failed to create tenant database for tenant #{$tenant->id}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            // Fallback: Still save columns and mark status
            $this->syncTenantCustomColumns($tenant, $selectedColumns);
            $tenant->update([
                'database_name' => "crm_tenant_{$tenant->id}",
                'database_status' => 'active_local',
                'database_created_at' => now(),
                'selected_columns_meta' => $selectedColumns,
            ]);

            $this->seedSampleRecords($tenant, $selectedColumns);

            return false;
        }
    }

    /**
     * Register a dynamic database connection in Laravel config.
     */
    public function registerDynamicConnection(string $databaseName): void
    {
        config([
            'database.connections.tenant_dynamic' => [
                'driver' => 'mysql',
                'host' => config('database.connections.mysql.host', '127.0.0.1'),
                'port' => config('database.connections.mysql.port', '3306'),
                'database' => $databaseName,
                'username' => config('database.connections.mysql.username', 'root'),
                'password' => config('database.connections.mysql.password', ''),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => false,
            ],
        ]);

        DB::purge('tenant_dynamic');
    }

    /**
     * Create the dynamic CRM table inside tenant's dedicated database.
     */
    private function createTenantRecordsTable(string $dbName, array $columns): void
    {
        $columnSqlParts = [
            "`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY",
            "`tenant_id` BIGINT UNSIGNED NOT NULL",
            "`title` VARCHAR(255) NULL",
            "`status` VARCHAR(50) DEFAULT 'New'",
            "`value` DECIMAL(15, 2) DEFAULT 0.00",
        ];

        foreach ($columns as $col) {
            $key = preg_replace('/[^a-zA-Z0-9_]/', '_', strtolower($col['key'] ?? ''));
            if (!$key || in_array($key, ['id', 'tenant_id', 'title', 'status', 'value', 'created_at', 'updated_at', 'custom_data'])) {
                continue;
            }

            $type = $col['type'] ?? 'text';
            $sqlType = match ($type) {
                'number', 'integer' => "INT NULL",
                'currency', 'decimal' => "DECIMAL(15, 2) DEFAULT 0.00",
                'date' => "DATE NULL",
                'datetime' => "DATETIME NULL",
                'boolean' => "TINYINT(1) DEFAULT 0",
                'textarea' => "TEXT NULL",
                default => "VARCHAR(255) NULL",
            };

            $columnSqlParts[] = "`{$key}` {$sqlType}";
        }

        $columnSqlParts[] = "`custom_data` JSON NULL";
        $columnSqlParts[] = "`created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP";
        $columnSqlParts[] = "`updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";

        $columnsSql = implode(",\n    ", $columnSqlParts);

        $sql = "CREATE TABLE IF NOT EXISTS `{$dbName}`.`crm_leads` (
    {$columnsSql}
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        DB::statement($sql);

        // Also create a schema info table in tenant database
        $metaSql = "CREATE TABLE IF NOT EXISTS `{$dbName}`.`crm_column_schemas` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `column_key` VARCHAR(100) NOT NULL UNIQUE,
    `column_label` VARCHAR(255) NOT NULL,
    `column_type` VARCHAR(50) NOT NULL,
    `options` JSON NULL,
    `is_required` TINYINT(1) DEFAULT 0,
    `display_order` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        DB::statement($metaSql);

        // Create isolated Contacts table in dedicated DB
        $contactsSql = "CREATE TABLE IF NOT EXISTS `{$dbName}`.`contacts` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` BIGINT UNSIGNED NOT NULL,
    `first_name` VARCHAR(255) NOT NULL,
    `last_name` VARCHAR(255) NULL,
    `email` VARCHAR(255) NULL,
    `phone` VARCHAR(50) NULL,
    `company_name` VARCHAR(255) NULL,
    `job_title` VARCHAR(255) NULL,
    `source` VARCHAR(100) NULL,
    `custom_fields` JSON NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        DB::statement($contactsSql);

        // Create isolated Deals table in dedicated DB
        $dealsSql = "CREATE TABLE IF NOT EXISTS `{$dbName}`.`deals` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` BIGINT UNSIGNED NOT NULL,
    `contact_id` BIGINT UNSIGNED NULL,
    `title` VARCHAR(255) NOT NULL,
    `value` DECIMAL(15, 2) DEFAULT 0.00,
    `currency` VARCHAR(10) DEFAULT 'INR',
    `stage` VARCHAR(100) DEFAULT 'New',
    `closed_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        DB::statement($dealsSql);

        // Create isolated Payment Plans & Invoices tables in dedicated DB
        $plansSql = "CREATE TABLE IF NOT EXISTS `{$dbName}`.`payment_plans` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` BIGINT UNSIGNED NOT NULL,
    `contact_id` BIGINT UNSIGNED NULL,
    `invoice_number` VARCHAR(100) NOT NULL UNIQUE,
    `customer_name` VARCHAR(255) NOT NULL,
    `customer_email` VARCHAR(255) NOT NULL,
    `customer_phone` VARCHAR(50) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `total_amount` DECIMAL(15, 2) DEFAULT 0.00,
    `currency` VARCHAR(10) DEFAULT 'INR',
    `plan_type` VARCHAR(50) DEFAULT 'one_time',
    `status` VARCHAR(50) DEFAULT 'pending',
    `payment_token` VARCHAR(64) NOT NULL UNIQUE,
    `due_date` DATE NULL,
    `notes` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        DB::statement($plansSql);

        $instSql = "CREATE TABLE IF NOT EXISTS `{$dbName}`.`payment_plan_installments` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `payment_plan_id` BIGINT UNSIGNED NOT NULL,
    `installment_number` INT DEFAULT 1,
    `title` VARCHAR(255) NULL,
    `amount` DECIMAL(15, 2) DEFAULT 0.00,
    `due_date` DATE NULL,
    `status` VARCHAR(50) DEFAULT 'pending',
    `paid_at` TIMESTAMP NULL,
    `payment_method` VARCHAR(50) NULL,
    `transaction_ref` VARCHAR(255) NULL,
    `utr_number` VARCHAR(255) NULL,
    `proof_url` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        DB::statement($instSql);

        // Insert column metadata into tenant database
        foreach ($columns as $index => $col) {
            $k = preg_replace('/[^a-zA-Z0-9_]/', '_', strtolower($col['key'] ?? ''));
            $label = $col['label'] ?? ucfirst(str_replace('_', ' ', $k));
            $type = $col['type'] ?? 'text';
            $optJson = !empty($col['options']) ? json_encode($col['options']) : null;
            $req = !empty($col['is_required']) ? 1 : 0;
            $order = $col['display_order'] ?? ($index + 1);

            DB::statement(
                "INSERT INTO `{$dbName}`.`crm_column_schemas` (`column_key`, `column_label`, `column_type`, `options`, `is_required`, `display_order`) 
                 VALUES (?, ?, ?, ?, ?, ?) 
                 ON DUPLICATE KEY UPDATE `column_label` = VALUES(`column_label`), `column_type` = VALUES(`column_type`), `options` = VALUES(`options`)",
                [$k, $label, $type, $optJson, $req, $order]
            );
        }
    }

    /**
     * Synchronize column definitions to tenant_custom_columns in main DB.
     */
    public function syncTenantCustomColumns(Tenant $tenant, array $columns): void
    {
        TenantCustomColumn::where('tenant_id', $tenant->id)->delete();

        foreach ($columns as $index => $col) {
            $key = preg_replace('/[^a-zA-Z0-9_]/', '_', strtolower($col['key'] ?? ''));
            if (!$key) continue;

            TenantCustomColumn::create([
                'tenant_id' => $tenant->id,
                'table_name' => 'crm_records',
                'column_key' => $key,
                'column_label' => $col['label'] ?? ucfirst(str_replace('_', ' ', $key)),
                'column_type' => $col['type'] ?? 'text',
                'options' => $col['options'] ?? null,
                'is_required' => !empty($col['is_required']),
                'is_default' => !empty($col['is_default']),
                'default_value' => $col['default_value'] ?? null,
                'display_order' => $col['display_order'] ?? ($index + 1),
                'is_visible' => $col['is_visible'] ?? true,
            ]);
        }
    }

    /**
     * Add a dynamic column to tenant database at runtime.
     */
    public function addColumnToTenantDatabase(Tenant $tenant, array $col): bool
    {
        $key = preg_replace('/[^a-zA-Z0-9_]/', '_', strtolower($col['key'] ?? ''));
        if (!$key) return false;

        $type = $col['type'] ?? 'text';
        $label = $col['label'] ?? ucfirst(str_replace('_', ' ', $key));

        // 1. Save in main DB
        TenantCustomColumn::updateOrCreate(
            ['tenant_id' => $tenant->id, 'column_key' => $key],
            [
                'table_name' => 'crm_records',
                'column_label' => $label,
                'column_type' => $type,
                'options' => $col['options'] ?? null,
                'is_required' => !empty($col['is_required']),
                'display_order' => TenantCustomColumn::where('tenant_id', $tenant->id)->count() + 1,
                'is_visible' => true,
            ]
        );

        // 2. Add column to tenant's dedicated database if exists
        if ($tenant->database_name) {
            try {
                $sqlType = match ($type) {
                    'number', 'integer' => "INT NULL",
                    'currency', 'decimal' => "DECIMAL(15, 2) DEFAULT 0.00",
                    'date' => "DATE NULL",
                    'datetime' => "DATETIME NULL",
                    'boolean' => "TINYINT(1) DEFAULT 0",
                    'textarea' => "TEXT NULL",
                    default => "VARCHAR(255) NULL",
                };

                DB::statement("ALTER TABLE `{$tenant->database_name}`.`crm_leads` ADD COLUMN IF NOT EXISTS `{$key}` {$sqlType}");

                $optJson = !empty($col['options']) ? json_encode($col['options']) : null;
                $req = !empty($col['is_required']) ? 1 : 0;
                DB::statement(
                    "INSERT INTO `{$tenant->database_name}`.`crm_column_schemas` (`column_key`, `column_label`, `column_type`, `options`, `is_required`, `display_order`) 
                     VALUES (?, ?, ?, ?, ?, 99) 
                     ON DUPLICATE KEY UPDATE `column_label` = VALUES(`column_label`)",
                    [$key, $label, $type, $optJson, $req]
                );
            } catch (\Throwable $e) {
                Log::warning("Could not alter tenant database: " . $e->getMessage());
            }
        }

        return true;
    }

    /**
     * Get default/preset column options for an industry or general CRM.
     */
    public function getPresetColumnsForIndustry(?Industry $industry = null): array
    {
        // Core standard columns
        $standardColumns = [
            ['key' => 'contact_name', 'label' => 'Client / Contact Name', 'type' => 'text', 'is_required' => true, 'is_default' => true, 'is_visible' => true, 'category' => 'core'],
            ['key' => 'email', 'label' => 'Email Address', 'type' => 'email', 'is_required' => false, 'is_default' => true, 'is_visible' => true, 'category' => 'core'],
            ['key' => 'phone', 'label' => 'Phone / WhatsApp', 'type' => 'text', 'is_required' => true, 'is_default' => true, 'is_visible' => true, 'category' => 'core'],
            ['key' => 'company', 'label' => 'Company / Organization', 'type' => 'text', 'is_required' => false, 'is_default' => true, 'is_visible' => true, 'category' => 'core'],
            ['key' => 'deal_stage', 'label' => 'Pipeline Stage', 'type' => 'dropdown', 'options' => ['New Lead', 'Contacted', 'Qualified', 'Proposal Sent', 'Won', 'Lost'], 'is_required' => true, 'is_default' => true, 'is_visible' => true, 'category' => 'core'],
            ['key' => 'deal_value', 'label' => 'Estimated Value (₹)', 'type' => 'currency', 'is_required' => false, 'is_default' => true, 'is_visible' => true, 'category' => 'core'],
            ['key' => 'lead_source', 'label' => 'Lead Source', 'type' => 'dropdown', 'options' => ['Website', 'Google Ads', 'WhatsApp', 'Referral', 'Cold Call', 'Social Media', 'Walk-in'], 'is_required' => false, 'is_default' => true, 'is_visible' => true, 'category' => 'core'],
            ['key' => 'assigned_agent', 'label' => 'Assigned Representative', 'type' => 'text', 'is_required' => false, 'is_default' => true, 'is_visible' => true, 'category' => 'core'],
            ['key' => 'city', 'label' => 'City / Location', 'type' => 'text', 'is_required' => false, 'is_default' => true, 'is_visible' => true, 'category' => 'core'],
            ['key' => 'follow_up_date', 'label' => 'Next Follow-up Date', 'type' => 'date', 'is_required' => false, 'is_default' => true, 'is_visible' => true, 'category' => 'core'],
        ];

        $industrySlug = $industry ? $industry->slug : 'general';

        $industrySpecific = match ($industrySlug) {
            'real-estate' => [
                ['key' => 'property_type', 'label' => 'Property Type', 'type' => 'dropdown', 'options' => ['Apartment', 'Villa', 'Commercial Office', 'Plot / Land', 'Penthouse', 'Warehouse'], 'is_required' => true, 'is_default' => true, 'is_visible' => true, 'category' => 'industry'],
                ['key' => 'preferred_bhk', 'label' => 'BHK Requirement', 'type' => 'dropdown', 'options' => ['1 BHK', '2 BHK', '3 BHK', '4 BHK', '5+ BHK', 'Studio', 'N/A'], 'is_required' => false, 'is_default' => true, 'is_visible' => true, 'category' => 'industry'],
                ['key' => 'budget_range', 'label' => 'Budget Range', 'type' => 'dropdown', 'options' => ['Under ₹25 Lakhs', '₹25L - ₹50L', '₹50L - ₹1 Crore', '₹1Cr - ₹2.5Cr', '₹2.5Cr - ₹5Cr', '₹5Cr+'], 'is_required' => false, 'is_default' => true, 'is_visible' => true, 'category' => 'industry'],
                ['key' => 'possession_timeline', 'label' => 'Possession Timeline', 'type' => 'dropdown', 'options' => ['Ready to Move', 'Within 3 Months', 'Within 6 Months', 'Under Construction (1-2 Yrs)', 'Investment'], 'is_required' => false, 'is_default' => true, 'is_visible' => true, 'category' => 'industry'],
                ['key' => 'preferred_locality', 'label' => 'Target Neighborhood/Area', 'type' => 'text', 'is_required' => false, 'is_default' => true, 'is_visible' => true, 'category' => 'industry'],
            ],
            'healthcare' => [
                ['key' => 'patient_age', 'label' => 'Patient Age', 'type' => 'number', 'is_required' => false, 'is_default' => true, 'is_visible' => true, 'category' => 'industry'],
                ['key' => 'gender', 'label' => 'Gender', 'type' => 'dropdown', 'options' => ['Male', 'Female', 'Other'], 'is_required' => false, 'is_default' => true, 'is_visible' => true, 'category' => 'industry'],
                ['key' => 'blood_group', 'label' => 'Blood Group', 'type' => 'dropdown', 'options' => ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'], 'is_required' => false, 'is_default' => true, 'is_visible' => true, 'category' => 'industry'],
                ['key' => 'department', 'label' => 'Department / Specialty', 'type' => 'dropdown', 'options' => ['Cardiology', 'Orthopedics', 'Dental', 'General Medicine', 'Neurology', 'Pediatrics', 'Dermatology'], 'is_required' => true, 'is_default' => true, 'is_visible' => true, 'category' => 'industry'],
                ['key' => 'consulting_doctor', 'label' => 'Consulting Doctor', 'type' => 'text', 'is_required' => false, 'is_default' => true, 'is_visible' => true, 'category' => 'industry'],
                ['key' => 'symptoms_diagnosis', 'label' => 'Symptoms / Primary Complaint', 'type' => 'textarea', 'is_required' => false, 'is_default' => true, 'is_visible' => true, 'category' => 'industry'],
            ],
            'education' => [
                ['key' => 'course_interested', 'label' => 'Course / Program', 'type' => 'dropdown', 'options' => ['B.Tech / Engineering', 'MBA / Management', 'BBA / BCA', 'Medical / MBBS', 'Data Science & AI', 'Digital Marketing', 'Study Abroad Program'], 'is_required' => true, 'is_default' => true, 'is_visible' => true, 'category' => 'industry'],
                ['key' => 'highest_qualification', 'label' => 'Highest Qualification', 'type' => 'dropdown', 'options' => ['10th Standard', '12th Standard', 'Bachelor Graduate', 'Post Graduate', 'Diploma'], 'is_required' => false, 'is_default' => true, 'is_visible' => true, 'category' => 'industry'],
                ['key' => 'academic_score', 'label' => 'Score / Percentage / CGPA', 'type' => 'text', 'is_required' => false, 'is_default' => true, 'is_visible' => true, 'category' => 'industry'],
                ['key' => 'target_batch_year', 'label' => 'Target Intake Year', 'type' => 'dropdown', 'options' => ['2026 Batch', '2027 Batch', 'Immediate Intake'], 'is_required' => false, 'is_default' => true, 'is_visible' => true, 'category' => 'industry'],
                ['key' => 'counselor_name', 'label' => 'Assigned Counselor', 'type' => 'text', 'is_required' => false, 'is_default' => true, 'is_visible' => true, 'category' => 'industry'],
            ],
            'matrimonial' => [
                ['key' => 'candidate_age', 'label' => 'Candidate Age', 'type' => 'number', 'is_required' => true, 'is_default' => true, 'is_visible' => true, 'category' => 'industry'],
                ['key' => 'gender', 'label' => 'Gender', 'type' => 'dropdown', 'options' => ['Bride (Female)', 'Groom (Male)'], 'is_required' => true, 'is_default' => true, 'is_visible' => true, 'category' => 'industry'],
                ['key' => 'caste_community', 'label' => 'Caste / Community', 'type' => 'text', 'is_required' => false, 'is_default' => true, 'is_visible' => true, 'category' => 'industry'],
                ['key' => 'gotra', 'label' => 'Gotra / Sub-caste', 'type' => 'text', 'is_required' => false, 'is_default' => true, 'is_visible' => true, 'category' => 'industry'],
                ['key' => 'marital_status', 'label' => 'Marital Status', 'type' => 'dropdown', 'options' => ['Never Married', 'Divorced', 'Widowed', 'Awaiting Divorce'], 'is_required' => true, 'is_default' => true, 'is_visible' => true, 'category' => 'industry'],
                ['key' => 'annual_income', 'label' => 'Annual Package / Income', 'type' => 'dropdown', 'options' => ['₹5L - ₹10L', '₹10L - ₹25L', '₹25L - ₹50L', '₹50L - ₹1 Crore', '₹1Cr+'], 'is_required' => false, 'is_default' => true, 'is_visible' => true, 'category' => 'industry'],
                ['key' => 'profession', 'label' => 'Profession / Occupation', 'type' => 'text', 'is_required' => false, 'is_default' => true, 'is_visible' => true, 'category' => 'industry'],
            ],
            'financial-services', 'finance' => [
                ['key' => 'loan_investment_type', 'label' => 'Product Type', 'type' => 'dropdown', 'options' => ['Home Loan', 'Business Loan', 'Personal Loan', 'Mutual Funds / SIP', 'Insurance Policy', 'Wealth Advisory'], 'is_required' => true, 'is_default' => true, 'is_visible' => true, 'category' => 'industry'],
                ['key' => 'requested_amount', 'label' => 'Requested Amount / Capital', 'type' => 'currency', 'is_required' => false, 'is_default' => true, 'is_visible' => true, 'category' => 'industry'],
                ['key' => 'cibil_credit_score', 'label' => 'CIBIL / Credit Score', 'type' => 'number', 'is_required' => false, 'is_default' => true, 'is_visible' => true, 'category' => 'industry'],
                ['key' => 'employment_type', 'label' => 'Employment Type', 'type' => 'dropdown', 'options' => ['Salaried', 'Self-Employed Professional', 'Business Owner', 'Freelancer'], 'is_required' => false, 'is_default' => true, 'is_visible' => true, 'category' => 'industry'],
            ],
            'research-publication', 'journal-publication', 'publication' => [
                ['key' => 'paper_title', 'label' => 'Manuscript / Paper Title', 'type' => 'text', 'is_required' => true, 'is_default' => true, 'is_visible' => true, 'category' => 'industry'],
                ['key' => 'manuscript_id', 'label' => 'Paper ID / Tracking No.', 'type' => 'text', 'is_required' => false, 'is_default' => true, 'is_visible' => true, 'category' => 'industry'],
                ['key' => 'target_journal', 'label' => 'Target Journal', 'type' => 'dropdown', 'options' => ['Intl Journal of Recent Research', 'Scopus Index Engineering Journal', 'UGC CARE Approved Journal', 'Medical & Pharma Research Journal', 'Other'], 'is_required' => true, 'is_default' => true, 'is_visible' => true, 'category' => 'industry'],
                ['key' => 'author_affiliation', 'label' => 'University / Institution', 'type' => 'text', 'is_required' => false, 'is_default' => true, 'is_visible' => true, 'category' => 'industry'],
                ['key' => 'research_domain', 'label' => 'Research Track / Domain', 'type' => 'dropdown', 'options' => ['Computer Science & AI', 'Electrical & Electronics', 'Mechanical Engineering', 'Medical & Health Sciences', 'Management & Commerce', 'Humanities & Social Sciences'], 'is_required' => false, 'is_default' => true, 'is_visible' => true, 'category' => 'industry'],
                ['key' => 'similarity_percentage', 'label' => 'Plagiarism Similarity (%)', 'type' => 'number', 'is_required' => false, 'is_default' => true, 'is_visible' => true, 'category' => 'industry'],
                ['key' => 'assigned_reviewer', 'label' => 'Assigned Reviewer', 'type' => 'text', 'is_required' => false, 'is_default' => true, 'is_visible' => true, 'category' => 'industry'],
                ['key' => 'review_decision', 'label' => 'Review Decision', 'type' => 'dropdown', 'options' => ['Under Review', 'Accepted', 'Minor Revision', 'Major Revision', 'Rejected'], 'is_required' => false, 'is_default' => true, 'is_visible' => true, 'category' => 'industry'],
                ['key' => 'apc_amount', 'label' => 'APC / Publication Fee (₹)', 'type' => 'currency', 'is_required' => false, 'is_default' => true, 'is_visible' => true, 'category' => 'industry'],
                ['key' => 'doi_number', 'label' => 'DOI / ISSN Number', 'type' => 'text', 'is_required' => false, 'is_default' => true, 'is_visible' => true, 'category' => 'industry'],
                ['key' => 'volume_issue', 'label' => 'Volume & Issue', 'type' => 'text', 'is_required' => false, 'is_default' => true, 'is_visible' => true, 'category' => 'industry'],
            ],
            default => [
                ['key' => 'requirement_details', 'label' => 'Product / Service Needed', 'type' => 'text', 'is_required' => false, 'is_default' => true, 'is_visible' => true, 'category' => 'industry'],
                ['key' => 'priority', 'label' => 'Lead Priority', 'type' => 'dropdown', 'options' => ['High / Hot 🔥', 'Medium / Warm ⚡', 'Low / Cold ❄️'], 'is_required' => false, 'is_default' => true, 'is_visible' => true, 'category' => 'industry'],
            ],
        };

        return array_merge($standardColumns, $industrySpecific);
    }

    /**
     * Get default columns for a given tenant.
     */
    public function getDefaultColumnsForTenant(Tenant $tenant): array
    {
        return $this->getPresetColumnsForIndustry($tenant->industry);
    }

    /**
     * Seed contextual initial records with user's selected columns.
     */
    private function seedSampleRecords(Tenant $tenant, array $columns, ?string $dbName = null): void
    {
        $sampleRows = [
            [
                'contact_name' => 'Rajesh Sharma',
                'email' => 'rajesh.sharma@example.com',
                'phone' => '+91 98765 43210',
                'company' => 'Apex Infra Ventures',
                'deal_stage' => 'Qualified',
                'deal_value' => 85000.00,
                'lead_source' => 'Website',
                'assigned_agent' => 'Sarah Connor',
                'city' => 'Mumbai',
                'follow_up_date' => now()->addDays(2)->format('Y-m-d'),
                'property_type' => '3 BHK Luxury Apartment',
                'preferred_bhk' => '3 BHK',
                'budget_range' => '₹1Cr - ₹2.5Cr',
                'course_interested' => 'MBA / Management',
                'patient_age' => 38,
                'department' => 'Cardiology',
                'caste_community' => 'Jain / Shwetambar',
                'marital_status' => 'Never Married',
                'priority' => 'High / Hot 🔥',
            ],
            [
                'contact_name' => 'Priya Nair',
                'email' => 'priya.nair@innovatetech.io',
                'phone' => '+91 98123 45678',
                'company' => 'Innovate Tech Labs',
                'deal_stage' => 'Proposal Sent',
                'deal_value' => 145000.00,
                'lead_source' => 'WhatsApp',
                'assigned_agent' => 'Sarah Connor',
                'city' => 'Bengaluru',
                'follow_up_date' => now()->addDays(4)->format('Y-m-d'),
                'property_type' => 'Commercial Office',
                'preferred_bhk' => 'N/A',
                'budget_range' => '₹2.5Cr - ₹5Cr',
                'course_interested' => 'Data Science & AI',
                'patient_age' => 29,
                'department' => 'General Medicine',
                'caste_community' => 'Hindu / Nair',
                'marital_status' => 'Never Married',
                'priority' => 'High / Hot 🔥',
            ],
            [
                'contact_name' => 'Amitabh Verma',
                'email' => 'amitabh.verma@globalcorp.in',
                'phone' => '+91 99887 76655',
                'company' => 'Global Logistics & Supply',
                'deal_stage' => 'New Lead',
                'deal_value' => 45000.00,
                'lead_source' => 'Referral',
                'assigned_agent' => 'John Doe',
                'city' => 'Delhi NCR',
                'follow_up_date' => now()->addDays(1)->format('Y-m-d'),
                'property_type' => 'Villa',
                'preferred_bhk' => '4 BHK',
                'budget_range' => '₹50L - ₹1 Crore',
                'course_interested' => 'B.Tech / Engineering',
                'patient_age' => 45,
                'department' => 'Orthopedics',
                'caste_community' => 'Verma / Kayastha',
                'marital_status' => 'Never Married',
                'priority' => 'Medium / Warm ⚡',
            ],
        ];

        foreach ($sampleRows as $row) {
            $customData = [];
            foreach ($columns as $c) {
                $k = $c['key'] ?? '';
                if (isset($row[$k])) {
                    $customData[$k] = $row[$k];
                }
            }

            // Save in main DB tenant_crm_records
            TenantCrmRecord::create([
                'tenant_id' => $tenant->id,
                'title' => $row['contact_name'] . ' - ' . ($row['company'] ?? 'Enquiry'),
                'contact_name' => $row['contact_name'],
                'email' => $row['email'],
                'phone' => $row['phone'],
                'company' => $row['company'],
                'status' => $row['deal_stage'] ?? 'New',
                'value' => $row['deal_value'] ?? 0.00,
                'custom_data' => $customData,
            ]);

            // Save directly into tenant dedicated database table if created
            if ($dbName) {
                try {
                    $insertCols = ['tenant_id', 'title', 'status', 'value', 'custom_data'];
                    $insertVals = [
                        $tenant->id,
                        $row['contact_name'] . ' - ' . ($row['company'] ?? 'Enquiry'),
                        $row['deal_stage'] ?? 'New',
                        $row['deal_value'] ?? 0.00,
                        json_encode($customData),
                    ];

                    foreach ($columns as $c) {
                        $k = preg_replace('/[^a-zA-Z0-9_]/', '_', strtolower($c['key'] ?? ''));
                        if (in_array($k, ['id', 'tenant_id', 'title', 'status', 'value', 'created_at', 'updated_at', 'custom_data'])) {
                            continue;
                        }
                        if (isset($row[$k])) {
                            $insertCols[] = "`{$k}`";
                            $insertVals[] = $row[$k];
                        }
                    }

                    $colList = implode(', ', array_map(fn($col) => str_starts_with($col, '`') ? $col : "`{$col}`", $insertCols));
                    $placeholders = implode(', ', array_fill(0, count($insertVals), '?'));

                    DB::statement("INSERT INTO `{$dbName}`.`crm_leads` ({$colList}) VALUES ({$placeholders})", $insertVals);
                } catch (\Throwable $e) {
                    // Ignore sample row insertion failure in dedicated DB
                }
            }
        }
    }

    /**
     * Remove a column directly from the tenant's dedicated database table.
     */
    public function removeColumnFromTenantDatabase(Tenant $tenant, string $columnKey): bool
    {
        $key = preg_replace('/[^a-zA-Z0-9_]/', '_', strtolower($columnKey));
        if (!$key || in_array($key, ['id', 'tenant_id', 'title', 'status', 'value', 'created_at', 'updated_at', 'custom_data'])) {
            return false;
        }

        if ($tenant->database_name) {
            try {
                DB::statement("ALTER TABLE `{$tenant->database_name}`.`crm_leads` DROP COLUMN IF EXISTS `{$key}`");
                DB::statement("DELETE FROM `{$tenant->database_name}`.`crm_column_schemas` WHERE `column_key` = ?", [$key]);
            } catch (\Throwable $e) {
                Log::warning("Could not drop column from tenant database: " . $e->getMessage());
            }
        }

        return true;
    }

    /**
     * Sync navigation customizations directly into tenant's dedicated database.
     */
    public function syncTenantNavigation(Tenant $tenant, array $items): void
    {
        if (!$tenant->database_name) return;

        try {
            $sql = "CREATE TABLE IF NOT EXISTS `{$tenant->database_name}`.`crm_navigation_items` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `module_key` VARCHAR(100) NOT NULL UNIQUE,
                `label` VARCHAR(255) NOT NULL,
                `icon` VARCHAR(100) NOT NULL,
                `route` VARCHAR(255) NOT NULL,
                `is_enabled` TINYINT(1) DEFAULT 1,
                `display_order` INT DEFAULT 0,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

            DB::statement($sql);

            foreach ($items as $index => $item) {
                $key = $item['key'] ?? ('item_' . ($index + 1));
                $label = $item['label'] ?? ucfirst($key);
                $icon = $item['icon'] ?? 'LinkIcon';
                $route = $item['route'] ?? '/';
                $enabled = !empty($item['is_enabled']) ? 1 : 0;
                $order = $item['display_order'] ?? ($index + 1);

                DB::statement(
                    "INSERT INTO `{$tenant->database_name}`.`crm_navigation_items` (`module_key`, `label`, `icon`, `route`, `is_enabled`, `display_order`) 
                     VALUES (?, ?, ?, ?, ?, ?) 
                     ON DUPLICATE KEY UPDATE `label` = VALUES(`label`), `icon` = VALUES(`icon`), `route` = VALUES(`route`), `is_enabled` = VALUES(`is_enabled`), `display_order` = VALUES(`display_order`)",
                    [$key, $label, $icon, $route, $enabled, $order]
                );
            }
        } catch (\Throwable $e) {
            Log::warning("Could not sync navigation to tenant database: " . $e->getMessage());
        }
    }

    /**
     * Sync business branding & general settings directly into tenant's dedicated database.
     */
    public function syncTenantSettings(Tenant $tenant, array $settings): void
    {
        if (!$tenant->database_name) return;

        try {
            $sql = "CREATE TABLE IF NOT EXISTS `{$tenant->database_name}`.`crm_settings` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `setting_key` VARCHAR(100) NOT NULL UNIQUE,
                `setting_value` TEXT NULL,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

            DB::statement($sql);

            foreach ($settings as $k => $v) {
                $val = is_array($v) ? json_encode($v) : (string) $v;
                DB::statement(
                    "INSERT INTO `{$tenant->database_name}`.`crm_settings` (`setting_key`, `setting_value`) 
                     VALUES (?, ?) 
                     ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`)",
                    [$k, $val]
                );
            }
        } catch (\Throwable $e) {
            Log::warning("Could not sync settings to tenant database: " . $e->getMessage());
        }
    }
}
