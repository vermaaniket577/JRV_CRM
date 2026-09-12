<?php

namespace App\Services\DynamicCrm;

use App\Models\CrmColumn;
use App\Models\CrmTable;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Cross-table global search engine for the Dynamic CRM.
 * Searches only searchable text columns, returns grouped results.
 */
class GlobalSearchService
{
    protected DynamicDatabaseManager $dbManager;

    public function __construct(DynamicDatabaseManager $dbManager)
    {
        $this->dbManager = $dbManager;
    }

    /**
     * Search across all active tables in the tenant's CRM database.
     *
     * @return array<array{table_name: string, display_name: string, icon: string, results: array}>
     */
    public function search(Tenant $tenant, string $query, int $limitPerTable = 5): array
    {
        if (empty(trim($query)) || strlen(trim($query)) < 2) {
            return [];
        }

        $searchTerm = trim($query);
        $tables = CrmTable::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('menu_order')
            ->get();

        if ($tables->isEmpty()) {
            return [];
        }

        $this->dbManager->connectToTenantDb($tenant);
        $results = [];

        foreach ($tables as $table) {
            $searchableColumns = CrmColumn::where('table_id', $table->id)
                ->where('is_searchable', true)
                ->pluck('column_name')
                ->toArray();

            if (empty($searchableColumns)) continue;

            try {
                $queryBuilder = DB::connection('dynamic_crm')
                    ->table($table->table_name)
                    ->where(function ($q) use ($searchableColumns, $searchTerm) {
                        foreach ($searchableColumns as $col) {
                            $q->orWhere($col, 'LIKE', "%{$searchTerm}%");
                        }
                    })
                    ->limit($limitPerTable);

                $records = $queryBuilder->get()->map(function ($r) use ($table) {
                    $row = (array) $r;
                    // Get display value
                    $displayValue = null;
                    if ($table->display_column && isset($row[$table->display_column])) {
                        $displayValue = $row[$table->display_column];
                    }
                    if (!$displayValue) {
                        // Fallback to first non-id text value
                        foreach ($row as $k => $v) {
                            if ($k !== 'id' && $k !== $table->primary_key_column && is_string($v) && strlen($v) > 0 && strlen($v) < 200) {
                                $displayValue = $v;
                                break;
                            }
                        }
                    }
                    $row['_display_value'] = $displayValue ?? 'Record #' . ($row[$table->primary_key_column] ?? '?');
                    $row['_primary_key'] = $row[$table->primary_key_column] ?? null;
                    return $row;
                })->toArray();

                if (!empty($records)) {
                    // Count total matches
                    $totalCount = DB::connection('dynamic_crm')
                        ->table($table->table_name)
                        ->where(function ($q) use ($searchableColumns, $searchTerm) {
                            foreach ($searchableColumns as $col) {
                                $q->orWhere($col, 'LIKE', "%{$searchTerm}%");
                            }
                        })
                        ->count();

                    $results[] = [
                        'table_name' => $table->table_name,
                        'display_name' => $table->display_name,
                        'icon' => $table->icon,
                        'results' => $records,
                        'total_count' => $totalCount,
                        'shown' => count($records),
                    ];
                }
            } catch (\Throwable $e) {
                Log::debug("Global search failed for table {$table->table_name}: " . $e->getMessage());
            }
        }

        return $results;
    }
}
