<?php

namespace App\Http\Controllers\DynamicCrm;

use App\Http\Controllers\Controller;
use App\Models\CrmTable;
use App\Models\Tenant;
use App\Services\DynamicCrm\DynamicFormService;
use App\Services\DynamicCrm\DynamicTableService;
use App\Services\DynamicCrm\RelationshipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Single reusable controller that handles ALL dynamic tables.
 * No per-table controller generation needed.
 */
class DynamicCrudController extends Controller
{
    protected DynamicTableService $tableService;
    protected DynamicFormService $formService;
    protected RelationshipService $relationshipService;

    public function __construct(
        DynamicTableService $tableService,
        DynamicFormService $formService,
        RelationshipService $relationshipService,
    ) {
        $this->tableService = $tableService;
        $this->formService = $formService;
        $this->relationshipService = $relationshipService;
    }

    /**
     * List records for a table with search, filtering, sorting, pagination.
     */
    public function index(Request $request, string $table): Response
    {
        $tenant = $this->resolveTenant($request);
        $tableMeta = $this->tableService->getTableMeta($tenant, $table);

        if (!$tableMeta) abort(404, "Table not found.");

        // Get table headers
        $headers = $this->formService->getTableHeaders($tenant, $table);

        // Get paginated records
        $records = $this->tableService->getRecords($tenant, $table, [
            'search' => $request->get('search'),
            'filters' => $request->get('filters', []),
            'sort' => $request->get('sort'),
            'direction' => $request->get('direction'),
            'per_page' => $request->get('per_page', 15),
            'page' => $request->get('page', 1),
        ]);

        // Resolve FK display values for listed records
        $resolvedRecords = $this->tableService->resolveRecordsForeignKeys($tenant, $table, $records->items());

        // Get all active tables for sidebar
        $allTables = $this->tableService->getActiveTables($tenant)->map(fn($t) => [
            'table_name' => $t->table_name,
            'display_name' => $t->display_name,
            'icon' => $t->icon,
            'record_count' => $t->record_count,
        ]);

        // Get filter definitions
        $filterDefs = $this->formService->getFilterDefinitions($tenant, $table);

        return Inertia::render('DynamicCrm/TableIndex', [
            'tenant' => ['id' => $tenant->id, 'name' => $tenant->name],
            'tableMeta' => [
                'table_name' => $tableMeta->table_name,
                'display_name' => $tableMeta->display_name,
                'icon' => $tableMeta->icon,
                'record_count' => $tableMeta->record_count,
                'primary_key' => $tableMeta->primary_key_column,
            ],
            'headers' => $headers,
            'records' => $records,
            'resolvedRecords' => $resolvedRecords,
            'allTables' => $allTables,
            'filterDefinitions' => $filterDefs,
            'filters' => $request->only(['search', 'sort', 'direction', 'per_page', 'filters']),
        ]);
    }

    /**
     * Show the create form for a table.
     */
    public function create(Request $request, string $table): Response
    {
        $tenant = $this->resolveTenant($request);
        $tableMeta = $this->tableService->getTableMeta($tenant, $table);
        if (!$tableMeta) abort(404);

        $formFields = $this->formService->generateFormFields($tenant, $table);

        $allTables = $this->tableService->getActiveTables($tenant)->map(fn($t) => [
            'table_name' => $t->table_name,
            'display_name' => $t->display_name,
            'icon' => $t->icon,
            'record_count' => $t->record_count,
        ]);

        return Inertia::render('DynamicCrm/RecordForm', [
            'tenant' => ['id' => $tenant->id, 'name' => $tenant->name],
            'tableMeta' => [
                'table_name' => $tableMeta->table_name,
                'display_name' => $tableMeta->display_name,
                'icon' => $tableMeta->icon,
            ],
            'formFields' => $formFields,
            'mode' => 'create',
            'record' => null,
            'allTables' => $allTables,
        ]);
    }

    /**
     * Store a new record.
     */
    public function store(Request $request, string $table): RedirectResponse|JsonResponse
    {
        $tenant = $this->resolveTenant($request);

        // Generate and apply validation rules
        $rules = $this->tableService->generateValidationRules($tenant, $table);
        $validated = $request->validate($rules);

        try {
            $id = $this->tableService->createRecord($tenant, $table, $validated, $request->user()?->id);

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'id' => $id]);
            }

            return redirect("/dynamic-crm/{$table}")
                ->with('success', 'Record created successfully.');
        } catch (\Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
            }
            return back()->withErrors(['error' => 'Failed to create record: ' . $e->getMessage()]);
        }
    }

    /**
     * Show a single record with related data.
     */
    public function show(Request $request, string $table, int $id): Response
    {
        $tenant = $this->resolveTenant($request);
        $tableMeta = $this->tableService->getTableMeta($tenant, $table);
        if (!$tableMeta) abort(404);

        $record = $this->tableService->getRecord($tenant, $table, $id);
        if (!$record) abort(404, 'Record not found.');

        // Get columns metadata for display
        $headers = $this->formService->getTableHeaders($tenant, $table);

        // Get relationships
        $relationships = $this->relationshipService->getRelationshipsForTable($tenant, $table);
        $relatedRecords = $this->relationshipService->getRelatedRecords($tenant, $table, $id);

        $allTables = $this->tableService->getActiveTables($tenant)->map(fn($t) => [
            'table_name' => $t->table_name,
            'display_name' => $t->display_name,
            'icon' => $t->icon,
            'record_count' => $t->record_count,
        ]);

        return Inertia::render('DynamicCrm/RecordShow', [
            'tenant' => ['id' => $tenant->id, 'name' => $tenant->name],
            'tableMeta' => [
                'table_name' => $tableMeta->table_name,
                'display_name' => $tableMeta->display_name,
                'icon' => $tableMeta->icon,
                'primary_key' => $tableMeta->primary_key_column,
                'display_column' => $tableMeta->display_column,
            ],
            'record' => $record,
            'headers' => $headers,
            'relationships' => $relationships,
            'relatedRecords' => $relatedRecords,
            'allTables' => $allTables,
        ]);
    }

    /**
     * Show the edit form for a record.
     */
    public function edit(Request $request, string $table, int $id): Response
    {
        $tenant = $this->resolveTenant($request);
        $tableMeta = $this->tableService->getTableMeta($tenant, $table);
        if (!$tableMeta) abort(404);

        $record = $this->tableService->getRecord($tenant, $table, $id);
        if (!$record) abort(404, 'Record not found.');

        $formFields = $this->formService->generateFormFields($tenant, $table, $record);

        $allTables = $this->tableService->getActiveTables($tenant)->map(fn($t) => [
            'table_name' => $t->table_name,
            'display_name' => $t->display_name,
            'icon' => $t->icon,
            'record_count' => $t->record_count,
        ]);

        return Inertia::render('DynamicCrm/RecordForm', [
            'tenant' => ['id' => $tenant->id, 'name' => $tenant->name],
            'tableMeta' => [
                'table_name' => $tableMeta->table_name,
                'display_name' => $tableMeta->display_name,
                'icon' => $tableMeta->icon,
                'primary_key' => $tableMeta->primary_key_column,
            ],
            'formFields' => $formFields,
            'mode' => 'edit',
            'record' => $record,
            'recordId' => $id,
            'allTables' => $allTables,
        ]);
    }

    /**
     * Update a record.
     */
    public function update(Request $request, string $table, int $id): RedirectResponse|JsonResponse
    {
        $tenant = $this->resolveTenant($request);

        $rules = $this->tableService->generateValidationRules($tenant, $table, true);
        $validated = $request->validate($rules);

        try {
            $this->tableService->updateRecord($tenant, $table, $id, $validated, $request->user()?->id);

            if ($request->wantsJson()) {
                return response()->json(['success' => true]);
            }

            return redirect("/dynamic-crm/{$table}/{$id}")
                ->with('success', 'Record updated successfully.');
        } catch (\Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
            }
            return back()->withErrors(['error' => 'Failed to update record: ' . $e->getMessage()]);
        }
    }

    /**
     * Delete a record.
     */
    public function destroy(Request $request, string $table, int $id): RedirectResponse|JsonResponse
    {
        $tenant = $this->resolveTenant($request);

        try {
            $this->tableService->deleteRecord($tenant, $table, $id, $request->user()?->id);

            if ($request->wantsJson()) {
                return response()->json(['success' => true]);
            }

            return redirect("/dynamic-crm/{$table}")
                ->with('success', 'Record deleted successfully.');
        } catch (\Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
            }
            return back()->withErrors(['error' => 'Failed to delete record: ' . $e->getMessage()]);
        }
    }

    protected function resolveTenant(Request $request): Tenant
    {
        $tenantId = session('tenant_id') ?? $request->user()?->tenant_id;
        $tenant = Tenant::find($tenantId);
        if (!$tenant) $tenant = Tenant::first();
        return $tenant;
    }
}
