<?php

namespace App\Http\Controllers\DynamicCrm;

use App\Http\Controllers\Controller;
use App\Models\CrmActivityLog;
use App\Models\CrmDatabase;
use App\Models\CrmTable;
use App\Models\Tenant;
use App\Services\DynamicCrm\DynamicTableService;
use App\Services\DynamicCrm\RelationshipService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    protected DynamicTableService $tableService;
    protected RelationshipService $relationshipService;

    public function __construct(DynamicTableService $tableService, RelationshipService $relationshipService)
    {
        $this->tableService = $tableService;
        $this->relationshipService = $relationshipService;
    }

    public function index(Request $request): Response
    {
        $tenant = $this->resolveTenant($request);

        // Get all active tables with record counts
        $tables = CrmTable::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('menu_order')
            ->get()
            ->map(fn($t) => [
                'table_name' => $t->table_name,
                'display_name' => $t->display_name,
                'icon' => $t->icon,
                'record_count' => $t->record_count,
                'is_visible_in_menu' => $t->is_visible_in_menu,
            ]);

        // Database info
        $crmDb = CrmDatabase::where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->latest()
            ->first();

        $dbInfo = null;
        if ($crmDb) {
            $dbInfo = [
                'name' => $crmDb->name,
                'database_name' => $crmDb->database_name,
                'tables_count' => $crmDb->tables_count,
                'relationships_count' => $crmDb->relationships_count,
                'total_records' => $crmDb->total_records,
                'original_file' => $crmDb->original_file,
                'status' => $crmDb->status,
                'imported_at' => $crmDb->created_at?->diffForHumans(),
                'import_summary' => $crmDb->import_summary,
            ];
        }

        // Total stats
        $totalRecords = $tables->sum('record_count');
        $totalTables = $tables->count();

        // Recent activity
        $recentActivity = CrmActivityLog::where('tenant_id', $tenant->id)
            ->latest()
            ->limit(15)
            ->get()
            ->map(fn($a) => [
                'id' => $a->id,
                'action' => $a->action,
                'table_name' => $a->table_name,
                'record_id' => $a->record_id,
                'user_id' => $a->user_id,
                'time' => $a->created_at?->diffForHumans(),
                'timestamp' => $a->created_at?->toIso8601String(),
            ]);

        // Relationship map
        $relationships = $this->relationshipService->getRelationshipMap($tenant);

        return Inertia::render('DynamicCrm/Dashboard', [
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'subdomain' => $tenant->subdomain,
            ],
            'tables' => $tables,
            'dbInfo' => $dbInfo,
            'stats' => [
                'total_records' => $totalRecords,
                'total_tables' => $totalTables,
                'total_relationships' => count($relationships),
            ],
            'recentActivity' => $recentActivity,
            'relationships' => $relationships,
        ]);
    }

    protected function resolveTenant(Request $request): Tenant
    {
        $tenantId = session('tenant_id') ?? $request->user()?->tenant_id;
        $tenant = Tenant::find($tenantId);

        if (!$tenant) {
            $tenant = Tenant::first();
        }

        return $tenant;
    }
}
