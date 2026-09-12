<?php

namespace App\Services\DynamicCrm;

use App\Models\CrmColumn;
use App\Models\CrmTable;
use App\Models\Tenant;

/**
 * Auto-generates form field definitions from column metadata.
 * Maps database column types to appropriate HTML form input types.
 */
class DynamicFormService
{
    protected DynamicTableService $tableService;

    public function __construct(DynamicTableService $tableService)
    {
        $this->tableService = $tableService;
    }

    /**
     * Generate form fields definition for a table (for create/edit forms).
     */
    public function generateFormFields(Tenant $tenant, string $tableName, ?array $existingData = null): array
    {
        $table = $this->tableService->getTableMeta($tenant, $tableName);
        if (!$table) return [];

        $columns = $table->editableColumns()->get();
        $fields = [];

        foreach ($columns as $col) {
            $field = [
                'name' => $col->column_name,
                'label' => $col->display_name,
                'type' => $col->form_type,
                'required' => $col->is_required,
                'nullable' => $col->is_nullable,
                'value' => $existingData[$col->column_name] ?? $col->default_value ?? null,
                'placeholder' => $this->generatePlaceholder($col),
                'data_type' => $col->data_type,
            ];

            // ENUM/SET → select with options
            if (in_array($col->data_type, ['enum', 'set']) && !empty($col->enum_values)) {
                $field['type'] = 'select';
                $field['options'] = array_map(fn($v) => ['value' => $v, 'label' => $v], $col->enum_values);
            }

            // Foreign key → related record select
            if ($col->is_foreign_key && $col->references_table) {
                $field['type'] = 'foreign_key_select';
                $field['references_table'] = $col->references_table;
                $field['references_column'] = $col->references_column ?? 'id';
                $field['options'] = $this->tableService->getForeignKeyOptions(
                    $tenant,
                    $col->references_table,
                    $col->references_column ?? 'id'
                );
            }

            // Boolean / tinyint(1) → checkbox
            if ($col->data_type === 'tinyint') {
                $field['type'] = 'checkbox';
                $field['value'] = (bool) ($field['value'] ?? false);
            }

            // Number formatting hints
            if (in_array($col->data_type, ['decimal', 'float', 'double'])) {
                $field['step'] = '0.01';
            }

            // Text area sizing
            if ($col->form_type === 'textarea') {
                $field['rows'] = 4;
            }

            $fields[] = $field;
        }

        return $fields;
    }

    /**
     * Generate a sensible placeholder text for a column.
     */
    protected function generatePlaceholder(CrmColumn $col): string
    {
        $lower = strtolower($col->column_name);

        if (str_contains($lower, 'email')) return 'e.g. john@example.com';
        if (str_contains($lower, 'phone') || str_contains($lower, 'mobile')) return 'e.g. +91 9876543210';
        if (str_contains($lower, 'name')) return 'Enter name';
        if (str_contains($lower, 'address')) return 'Enter address';
        if (str_contains($lower, 'url') || str_contains($lower, 'website')) return 'https://';
        if (str_contains($lower, 'price') || str_contains($lower, 'amount') || str_contains($lower, 'cost')) return '0.00';
        if (str_contains($lower, 'date')) return 'Select date';
        if (str_contains($lower, 'description') || str_contains($lower, 'note')) return 'Enter description...';

        return 'Enter ' . strtolower($col->display_name);
    }

    /**
     * Get column information formatted for table headers (listing page).
     */
    public function getTableHeaders(Tenant $tenant, string $tableName): array
    {
        $table = $this->tableService->getTableMeta($tenant, $tableName);
        if (!$table) return [];

        $columns = $table->visibleColumns()->get();

        return $columns->map(fn($col) => [
            'key' => $col->column_name,
            'label' => $col->display_name,
            'type' => $col->data_type,
            'form_type' => $col->form_type,
            'sortable' => true,
            'is_primary' => $col->is_primary,
            'is_foreign_key' => $col->is_foreign_key,
            'references_table' => $col->references_table,
        ])->toArray();
    }

    /**
     * Get filter definitions for a table (for filter panel).
     */
    public function getFilterDefinitions(Tenant $tenant, string $tableName): array
    {
        $table = $this->tableService->getTableMeta($tenant, $tableName);
        if (!$table) return [];

        $filters = [];
        $columns = $table->columns()
            ->where('is_visible', true)
            ->get();

        foreach ($columns as $col) {
            // Enum columns → dropdown filter
            if ($col->data_type === 'enum' && !empty($col->enum_values)) {
                $filters[] = [
                    'name' => $col->column_name,
                    'label' => $col->display_name,
                    'type' => 'select',
                    'options' => $col->enum_values,
                ];
            }

            // Date columns → date range filter
            if (in_array($col->data_type, ['date', 'datetime', 'timestamp'])) {
                $filters[] = [
                    'name' => $col->column_name,
                    'label' => $col->display_name,
                    'type' => 'date_range',
                ];
            }

            // Status-like columns → detect from column name
            $lower = strtolower($col->column_name);
            if (str_contains($lower, 'status') || str_contains($lower, 'state') || str_contains($lower, 'type')) {
                if ($col->data_type !== 'enum') {
                    // Try to get distinct values
                    $filters[] = [
                        'name' => $col->column_name,
                        'label' => $col->display_name,
                        'type' => 'dynamic_select',
                    ];
                }
            }
        }

        return $filters;
    }
}
