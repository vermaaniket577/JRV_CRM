<?php

namespace App\Services\DynamicCrm;

use App\Models\CrmRelationship;
use App\Models\CrmTable;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Relationship resolution engine.
 * Detects, resolves, and provides related data for FK relationships.
 */
class RelationshipService
{
    protected DynamicDatabaseManager $dbManager;

    public function __construct(DynamicDatabaseManager $dbManager)
    {
        $this->dbManager = $dbManager;
    }

    /**
     * Get all relationships for a table (both incoming and outgoing).
     */
    public function getRelationshipsForTable(Tenant $tenant, string $tableName): array
    {
        $outgoing = CrmRelationship::where('tenant_id', $tenant->id)
            ->where('source_table', $tableName)
            ->get()
            ->map(fn($r) => [
                'direction' => 'outgoing',
                'type' => 'belongsTo',
                'local_column' => $r->source_column,
                'related_table' => $r->target_table,
                'related_column' => $r->target_column,
                'display_label' => $r->display_label ?? Str::title(str_replace('_', ' ', $r->target_table)),
            ])
            ->toArray();

        $incoming = CrmRelationship::where('tenant_id', $tenant->id)
            ->where('target_table', $tableName)
            ->get()
            ->map(fn($r) => [
                'direction' => 'incoming',
                'type' => 'hasMany',
                'local_column' => $r->target_column,
                'related_table' => $r->source_table,
                'related_column' => $r->source_column,
                'display_label' => Str::title(str_replace('_', ' ', $r->source_table)),
            ])
            ->toArray();

        return [
            'outgoing' => $outgoing,  // This table belongs to...
            'incoming' => $incoming,  // Related tables that reference this table (hasMany)
        ];
    }

    /**
     * Get related records for a specific record (hasMany direction).
     */
    public function getRelatedRecords(Tenant $tenant, string $tableName, int $recordId, int $limit = 50): array
    {
        $relationships = $this->getRelationshipsForTable($tenant, $tableName);
        $relatedData = [];

        $this->dbManager->connectToTenantDb($tenant);

        foreach ($relationships['incoming'] as $rel) {
            try {
                $records = DB::connection('dynamic_crm')
                    ->table($rel['related_table'])
                    ->where($rel['related_column'], $recordId)
                    ->limit($limit)
                    ->get()
                    ->map(fn($r) => (array) $r)
                    ->toArray();

                $relTable = CrmTable::where('tenant_id', $tenant->id)
                    ->where('table_name', $rel['related_table'])
                    ->first();

                $relatedData[] = [
                    'table_name' => $rel['related_table'],
                    'display_name' => $relTable?->display_name ?? $rel['display_label'],
                    'icon' => $relTable?->icon ?? 'TableCellsIcon',
                    'foreign_column' => $rel['related_column'],
                    'records' => $records,
                    'count' => count($records),
                    'total' => $this->countRelatedRecords($tenant, $rel['related_table'], $rel['related_column'], $recordId),
                ];
            } catch (\Throwable $e) {
                Log::debug("Could not load related records from {$rel['related_table']}: " . $e->getMessage());
            }
        }

        return $relatedData;
    }

    /**
     * Count related records efficiently.
     */
    protected function countRelatedRecords(Tenant $tenant, string $table, string $column, $value): int
    {
        try {
            $this->dbManager->connectToTenantDb($tenant);
            return DB::connection('dynamic_crm')
                ->table($table)
                ->where($column, $value)
                ->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Resolve a FK value to a human-readable display string.
     */
    public function resolveDisplayValue(Tenant $tenant, string $targetTable, string $targetColumn, $value): ?string
    {
        $tableMeta = CrmTable::where('tenant_id', $tenant->id)
            ->where('table_name', $targetTable)
            ->first();

        if (!$tableMeta || !$tableMeta->display_column) {
            return (string) $value;
        }

        try {
            $this->dbManager->connectToTenantDb($tenant);
            $record = DB::connection('dynamic_crm')
                ->table($targetTable)
                ->where($targetColumn, $value)
                ->value($tableMeta->display_column);

            return $record ?: (string) $value;
        } catch (\Throwable $e) {
            return (string) $value;
        }
    }

    /**
     * Get a relationship map (for dashboard / visualization).
     */
    public function getRelationshipMap(Tenant $tenant): array
    {
        $rels = CrmRelationship::where('tenant_id', $tenant->id)->get();

        return $rels->map(fn($r) => [
            'from' => $r->source_table,
            'from_column' => $r->source_column,
            'to' => $r->target_table,
            'to_column' => $r->target_column,
            'type' => $r->relationship_type,
        ])->toArray();
    }
}
