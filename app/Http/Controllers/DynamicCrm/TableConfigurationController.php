<?php

namespace App\Http\Controllers\DynamicCrm;

use App\Http\Controllers\Controller;
use App\Models\CrmColumn;
use App\Models\CrmTable;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TableConfigurationController extends Controller
{
    /**
     * Show the configuration panel for a table.
     */
    public function show(Request $request, string $table): Response
    {
        $tenant = $this->resolveTenant($request);

        $tableMeta = CrmTable::where('tenant_id', $tenant->id)
            ->where('table_name', $table)
            ->first();

        if (!$tableMeta) abort(404, "Table not found.");

        $columns = CrmColumn::where('table_id', $tableMeta->id)
            ->orderBy('display_order')
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'column_name' => $c->column_name,
                'display_name' => $c->display_name,
                'data_type' => $c->data_type,
                'form_type' => $c->form_type,
                'is_primary' => $c->is_primary,
                'is_visible' => $c->is_visible,
                'is_searchable' => $c->is_searchable,
                'is_editable' => $c->is_editable,
                'is_required' => $c->is_required,
                'is_foreign_key' => $c->is_foreign_key,
                'references_table' => $c->references_table,
                'display_order' => $c->display_order,
                'enum_values' => $c->enum_values,
            ]);

        $allTables = CrmTable::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('menu_order')
            ->get()
            ->map(fn($t) => [
                'table_name' => $t->table_name,
                'display_name' => $t->display_name,
                'icon' => $t->icon,
                'record_count' => $t->record_count,
            ]);

        return Inertia::render('DynamicCrm/TableConfiguration', [
            'tenant' => ['id' => $tenant->id, 'name' => $tenant->name],
            'tableMeta' => [
                'id' => $tableMeta->id,
                'table_name' => $tableMeta->table_name,
                'display_name' => $tableMeta->display_name,
                'icon' => $tableMeta->icon,
                'is_visible_in_menu' => $tableMeta->is_visible_in_menu,
                'menu_order' => $tableMeta->menu_order,
                'display_column' => $tableMeta->display_column,
            ],
            'columns' => $columns,
            'allTables' => $allTables,
        ]);
    }

    /**
     * Update table and column configuration.
     */
    public function update(Request $request, string $table): RedirectResponse|JsonResponse
    {
        $tenant = $this->resolveTenant($request);

        $tableMeta = CrmTable::where('tenant_id', $tenant->id)
            ->where('table_name', $table)
            ->first();

        if (!$tableMeta) abort(404);

        $validated = $request->validate([
            'display_name' => ['nullable', 'string', 'max:100'],
            'icon' => ['nullable', 'string', 'max:50'],
            'is_visible_in_menu' => ['nullable', 'boolean'],
            'menu_order' => ['nullable', 'integer', 'min:0'],
            'display_column' => ['nullable', 'string', 'max:64'],
            'columns' => ['nullable', 'array'],
            'columns.*.id' => ['required', 'integer'],
            'columns.*.display_name' => ['nullable', 'string', 'max:100'],
            'columns.*.is_visible' => ['nullable', 'boolean'],
            'columns.*.is_searchable' => ['nullable', 'boolean'],
            'columns.*.is_editable' => ['nullable', 'boolean'],
            'columns.*.is_required' => ['nullable', 'boolean'],
            'columns.*.display_order' => ['nullable', 'integer'],
        ]);

        // Update table metadata
        $tableUpdates = array_filter([
            'display_name' => $validated['display_name'] ?? null,
            'icon' => $validated['icon'] ?? null,
            'is_visible_in_menu' => $validated['is_visible_in_menu'] ?? null,
            'menu_order' => $validated['menu_order'] ?? null,
            'display_column' => $validated['display_column'] ?? null,
        ], fn($v) => $v !== null);

        if (!empty($tableUpdates)) {
            $tableMeta->update($tableUpdates);
        }

        // Update column configurations
        if (!empty($validated['columns'])) {
            foreach ($validated['columns'] as $colUpdate) {
                $column = CrmColumn::where('id', $colUpdate['id'])
                    ->where('tenant_id', $tenant->id)
                    ->first();

                if (!$column) continue;

                $colData = array_filter([
                    'display_name' => $colUpdate['display_name'] ?? null,
                    'is_visible' => $colUpdate['is_visible'] ?? null,
                    'is_searchable' => $colUpdate['is_searchable'] ?? null,
                    'is_editable' => $colUpdate['is_editable'] ?? null,
                    'is_required' => $colUpdate['is_required'] ?? null,
                    'display_order' => $colUpdate['display_order'] ?? null,
                ], fn($v) => $v !== null);

                if (!empty($colData)) {
                    $column->update($colData);
                }
            }
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Configuration updated.']);
        }

        return redirect()->back()->with('success', 'Table configuration updated.');
    }

    protected function resolveTenant(Request $request): Tenant
    {
        $tenantId = session('tenant_id') ?? $request->user()?->tenant_id;
        $tenant = Tenant::find($tenantId);
        if (!$tenant) $tenant = Tenant::first();
        return $tenant;
    }
}
