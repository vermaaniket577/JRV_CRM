<?php

namespace App\Services;

use App\Models\DatabaseFieldMapping;
use App\Models\DatabaseImport;
use App\Models\DatabaseImportTable;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class FieldMappingService
{
    /**
     * Dictionary of common CRM synonyms / aliases.
     */
    protected array $synonymGroups = [
        'phone' => ['phone', 'mobile', 'mobile_number', 'contact_number', 'phone_number', 'contact_no', 'whatsapp', 'tel', 'cell'],
        'email' => ['email', 'email_address', 'e_mail', 'mail', 'user_email', 'contact_email', 'owner_email'],
        'name' => ['name', 'full_name', 'customer_name', 'client_name', 'contact_name', 'owner_name', 'first_name', 'display_name'],
        'title' => ['title', 'property_title', 'headline', 'subject', 'job_title', 'listing_title'],
        'description' => ['description', 'details', 'property_description', 'notes', 'remarks', 'bio', 'summary', 'content'],
        'price' => ['price', 'amount', 'rent', 'rent_amount', 'rental_price', 'cost', 'fee', 'estimated_mrr', 'total_amount', 'budget'],
        'address' => ['address', 'location', 'street', 'property_address', 'full_address'],
        'city' => ['city', 'town', 'district', 'city_name'],
        'locality' => ['locality', 'area', 'neighborhood', 'suburb', 'sector'],
        'status' => ['status', 'stage', 'deal_stage', 'state', 'condition'],
        'created_at' => ['created_at', 'creation_date', 'registered_at', 'date_added', 'join_date', 'timestamp'],
    ];

    /**
     * Common CRM target tables to suggest mapping into.
     */
    protected array $standardCrmTables = [
        'contacts' => [
            'label' => 'Contacts / Customers',
            'columns' => ['first_name', 'last_name', 'email', 'phone', 'job_title', 'source', 'status', 'created_at'],
        ],
        'crm_sales_leads' => [
            'label' => 'Sales Leads',
            'columns' => ['customer_name', 'company_name', 'email', 'phone', 'industry', 'deal_stage', 'estimated_mrr', 'created_at'],
        ],
        'properties' => [
            'label' => 'Real Estate Properties',
            'columns' => ['title', 'property_code', 'listing_type', 'property_type', 'price', 'security_deposit', 'bedrooms', 'bathrooms', 'carpet_area_sqft', 'city', 'state', 'locality', 'address', 'owner_name', 'owner_phone', 'owner_email', 'status', 'description', 'created_at'],
        ],
        'companies' => [
            'label' => 'Companies',
            'columns' => ['name', 'domain', 'industry', 'phone', 'address', 'city', 'country'],
        ],
    ];

    /**
     * Generate intelligent field mappings for an import session.
     */
    public function generateMappings(DatabaseImport $import): array
    {
        $tables = DatabaseImportTable::where('database_import_id', $import->id)->with('columns')->get();
        $generatedMappings = [];

        foreach ($tables as $importTable) {
            $sourceTableName = $importTable->table_name;
            $targetTableName = $this->suggestTargetTable($sourceTableName);

            $targetColumns = $this->getTargetTableColumns($targetTableName);

            foreach ($importTable->columns as $sourceCol) {
                $sourceColName = $sourceCol->column_name;
                $match = $this->findBestColumnMatch($sourceColName, $targetColumns);

                $mapping = DatabaseFieldMapping::updateOrCreate(
                    [
                        'database_import_id' => $import->id,
                        'database_import_table_id' => $importTable->id,
                        'source_table' => $sourceTableName,
                        'source_column' => $sourceColName,
                    ],
                    [
                        'target_table' => $targetTableName,
                        'target_column' => $match['target_column'],
                        'confidence' => $match['confidence'],
                        'confidence_score' => $match['score'],
                        'is_confirmed' => $match['confidence'] === 'high',
                        'transformation_rule' => $this->suggestTransformationRule($sourceColName, $match['target_column']),
                    ]
                );

                $generatedMappings[] = $mapping;
            }
        }

        return $generatedMappings;
    }

    /**
     * Suggest most relevant CRM target table for a source table name.
     */
    public function suggestTargetTable(string $sourceTable): string
    {
        $normalized = strtolower(trim($sourceTable));

        if (str_contains($normalized, 'lead')) {
            return Schema::hasTable('crm_sales_leads') ? 'crm_sales_leads' : (Schema::hasTable('contacts') ? 'contacts' : $sourceTable);
        }

        if (str_contains($normalized, 'customer') || str_contains($normalized, 'client') || str_contains($normalized, 'contact')) {
            return Schema::hasTable('contacts') ? 'contacts' : $sourceTable;
        }

        if (str_contains($normalized, 'propert') || str_contains($normalized, 'listing') || str_contains($normalized, 'rental') || str_contains($normalized, 'estate')) {
            return Schema::hasTable('properties') ? 'properties' : $sourceTable;
        }

        if (str_contains($normalized, 'compan') || str_contains($normalized, 'account') || str_contains($normalized, 'org')) {
            return Schema::hasTable('companies') ? 'companies' : $sourceTable;
        }

        // If a table of the same name exists in CRM, map to it directly
        if (Schema::hasTable($sourceTable)) {
            return $sourceTable;
        }

        // Otherwise keep as dynamic self-table
        return $sourceTable;
    }

    /**
     * Get available target columns for mapping.
     */
    public function getTargetTableColumns(string $tableName): array
    {
        if (isset($this->standardCrmTables[$tableName])) {
            return $this->standardCrmTables[$tableName]['columns'];
        }

        if (Schema::hasTable($tableName)) {
            return Schema::getColumnListing($tableName);
        }

        return [];
    }

    /**
     * Find best matching column with confidence scoring.
     */
    public function findBestColumnMatch(string $sourceCol, array $targetColumns): array
    {
        $src = strtolower(trim($sourceCol));

        // 1. Exact match (100%)
        foreach ($targetColumns as $target) {
            if ($src === strtolower($target)) {
                return ['target_column' => $target, 'confidence' => 'high', 'score' => 1.00];
            }
        }

        // 2. Normalized match without prefix/suffix (e.g. property_title -> title, lead_email -> email)
        foreach ($targetColumns as $target) {
            $tgt = strtolower($target);
            if (str_ends_with($src, '_' . $tgt) || str_starts_with($src, $tgt . '_')) {
                return ['target_column' => $target, 'confidence' => 'high', 'score' => 0.95];
            }
        }

        // 3. Synonym Group Match
        foreach ($this->synonymGroups as $groupKey => $synonyms) {
            $srcInGroup = in_array($src, $synonyms);
            if (!$srcInGroup) {
                // Check prefix trimmed
                foreach ($synonyms as $syn) {
                    if (str_ends_with($src, '_' . $syn)) {
                        $srcInGroup = true;
                        break;
                    }
                }
            }

            if ($srcInGroup) {
                foreach ($targetColumns as $target) {
                    $tgt = strtolower($target);
                    if (in_array($tgt, $synonyms) || $tgt === $groupKey) {
                        return ['target_column' => $target, 'confidence' => 'high', 'score' => 0.90];
                    }
                }
            }
        }

        // 4. Levenshtein / Similar Text (Medium confidence 60-89%)
        $bestMatch = null;
        $highestPercent = 0;

        foreach ($targetColumns as $target) {
            similar_text($src, strtolower($target), $percent);
            if ($percent > $highestPercent) {
                $highestPercent = $percent;
                $bestMatch = $target;
            }
        }

        if ($highestPercent >= 70) {
            return ['target_column' => $bestMatch, 'confidence' => 'medium', 'score' => round($highestPercent / 100, 2)];
        }

        if ($highestPercent >= 50) {
            return ['target_column' => $bestMatch, 'confidence' => 'low', 'score' => round($highestPercent / 100, 2)];
        }

        // Fallback: Low confidence matching to source name directly
        return [
            'target_column' => $sourceCol,
            'confidence' => 'low',
            'score' => 0.30,
        ];
    }

    /**
     * Suggest normalization transformation rule.
     */
    protected function suggestTransformationRule(string $sourceCol, string $targetCol): ?string
    {
        $col = strtolower($sourceCol . ' ' . $targetCol);

        if (str_contains($col, 'phone') || str_contains($col, 'mobile') || str_contains($col, 'whatsapp')) {
            return 'phone_normalize';
        }

        if (str_contains($col, 'email')) {
            return 'email_normalize';
        }

        if (str_contains($col, 'date') || str_contains($col, 'time') || str_contains($col, 'created')) {
            return 'date_format';
        }

        if (str_contains($col, 'price') || str_contains($col, 'amount') || str_contains($col, 'rent')) {
            return 'currency_clean';
        }

        return null;
    }
}
