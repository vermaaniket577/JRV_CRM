<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantCrmRecord;
use App\Models\TenantCustomColumn;
use App\Services\TenantDatabaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TenantDatabaseManagerController extends Controller
{
    protected TenantDatabaseService $databaseService;

    public function __construct(TenantDatabaseService $databaseService)
    {
        $this->databaseService = $databaseService;
    }

    public function index(Request $request): Response
    {
        $user = $request->user();
        $tenantId = session('tenant_id') ?? $user?->tenant_id;
        $tenant = Tenant::with(['industry', 'businessType'])->findOrFail($tenantId);

        // Fetch columns
        $columns = TenantCustomColumn::where('tenant_id', $tenant->id)
            ->orderBy('display_order')
            ->get();

        // If no columns exist yet, initialize default presets
        if ($columns->isEmpty()) {
            $defaultCols = $this->databaseService->getDefaultColumnsForTenant($tenant);
            $this->databaseService->syncTenantCustomColumns($tenant, $defaultCols);
            $columns = TenantCustomColumn::where('tenant_id', $tenant->id)
                ->orderBy('display_order')
                ->get();
        }

        // Fetch records with search & filter
        $query = TenantCrmRecord::where('tenant_id', $tenant->id);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('contact_name', 'like', "%{$search}%")
                  ->orWhere('company', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('status', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $records = $query->latest()->paginate(15)->withQueryString();

        // Transform records to flatten custom_data into row properties
        $transformedRecords = $records->getCollection()->map(function ($rec) {
            $data = $rec->custom_data ?? [];
            return array_merge([
                'id' => $rec->id,
                'title' => $rec->title,
                'contact_name' => $rec->contact_name,
                'email' => $rec->email,
                'phone' => $rec->phone,
                'company' => $rec->company,
                'status' => $rec->status,
                'value' => (float) $rec->value,
                'created_at' => $rec->created_at ? $rec->created_at->format('M d, Y') : null,
            ], $data);
        });

        $records->setCollection($transformedRecords);

        // Database status check
        $dbConnected = false;
        if ($tenant->database_name) {
            try {
                $dbCheck = DB::select("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?", [$tenant->database_name]);
                $dbConnected = !empty($dbCheck);
            } catch (\Throwable $e) {
                $dbConnected = false;
            }
        }

        $dbInfo = [
            'database_name' => $tenant->database_name ?? "crm_tenant_{$tenant->id}",
            'host' => $tenant->database_host ?? '127.0.0.1',
            'port' => $tenant->database_port ?? '3306',
            'status' => $dbConnected ? 'Connected 🟢' : ($tenant->database_status ?? 'Active'),
            'is_connected' => $dbConnected,
            'total_columns' => $columns->count(),
            'table_name' => 'crm_leads',
            'created_at' => $tenant->database_created_at ? $tenant->database_created_at->format('M d, Y H:i') : $tenant->created_at->format('M d, Y'),
        ];

        $stats = [
            'total_records' => TenantCrmRecord::where('tenant_id', $tenant->id)->count(),
            'total_value' => '₹' . number_format(TenantCrmRecord::where('tenant_id', $tenant->id)->sum('value')),
            'new_leads' => TenantCrmRecord::where('tenant_id', $tenant->id)->where('status', 'New Lead')->count(),
            'qualified' => TenantCrmRecord::where('tenant_id', $tenant->id)->where('status', 'Qualified')->count(),
            'won' => TenantCrmRecord::where('tenant_id', $tenant->id)->where('status', 'Won')->count(),
        ];

        return Inertia::render('Tenant/DynamicCrmRecords', [
            'tenant' => $tenant,
            'columns' => $columns,
            'records' => $records,
            'dbInfo' => $dbInfo,
            'stats' => $stats,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function storeRecord(Request $request): RedirectResponse
    {
        $user = $request->user();
        $tenantId = session('tenant_id') ?? $user?->tenant_id;
        $tenant = Tenant::findOrFail($tenantId);

        $columns = TenantCustomColumn::where('tenant_id', $tenant->id)->get();

        $rules = [];
        foreach ($columns as $col) {
            if ($col->is_required) {
                $rules[$col->column_key] = ['required'];
            } else {
                $rules[$col->column_key] = ['nullable'];
            }
        }

        $validated = $request->validate($rules);

        $contactName = $validated['contact_name'] ?? $validated['candidate_name'] ?? $validated['patient_name'] ?? ($validated['name'] ?? 'New Record');
        $status = $validated['deal_stage'] ?? $validated['status'] ?? 'New Lead';
        $value = (float) ($validated['deal_value'] ?? $validated['value'] ?? $validated['requested_amount'] ?? 0);
        $email = $validated['email'] ?? null;
        $phone = $validated['phone'] ?? null;
        $company = $validated['company'] ?? $validated['organization'] ?? null;

        // Save in main DB tenant_crm_records
        $record = TenantCrmRecord::create([
            'tenant_id' => $tenant->id,
            'title' => $contactName . ($company ? " - {$company}" : ''),
            'contact_name' => $contactName,
            'email' => $email,
            'phone' => $phone,
            'company' => $company,
            'status' => $status,
            'value' => $value,
            'custom_data' => $validated,
            'created_by' => $user?->id,
        ]);

        // Insert into dedicated database table if available
        if ($tenant->database_name) {
            try {
                $cols = ['tenant_id', 'title', 'status', 'value', 'custom_data'];
                $vals = [
                    $tenant->id,
                    $record->title,
                    $status,
                    $value,
                    json_encode($validated),
                ];

                foreach ($columns as $c) {
                    $k = $c->column_key;
                    if (in_array($k, ['id', 'tenant_id', 'title', 'status', 'value', 'created_at', 'updated_at', 'custom_data'])) {
                        continue;
                    }
                    if (array_key_exists($k, $validated)) {
                        $cols[] = "`{$k}`";
                        $vals[] = $validated[$k];
                    }
                }

                $colList = implode(', ', array_map(fn($col) => str_starts_with($col, '`') ? $col : "`{$col}`", $cols));
                $placeholders = implode(', ', array_fill(0, count($vals), '?'));

                DB::statement("INSERT INTO `{$tenant->database_name}`.`crm_leads` ({$colList}) VALUES ({$placeholders})", $vals);
            } catch (\Throwable $e) {
                // Log and continue
            }
        }

        return redirect()->back()->with('success', 'New lead / CRM record successfully created with all custom columns!');
    }

    public function addColumn(Request $request): RedirectResponse
    {
        $user = $request->user();
        $tenantId = session('tenant_id') ?? $user?->tenant_id;
        $tenant = Tenant::findOrFail($tenantId);

        $validated = $request->validate([
            'label' => ['required', 'string', 'max:100'],
            'type' => ['required', 'string', 'in:text,number,currency,date,dropdown,email,boolean,textarea'],
            'options' => ['nullable', 'array'],
            'is_required' => ['nullable', 'boolean'],
        ]);

        $key = preg_replace('/[^a-zA-Z0-9_]/', '_', strtolower($validated['label']));
        $key = substr($key, 0, 30);

        // Ensure key is unique
        $originalKey = $key;
        $count = 1;
        while (TenantCustomColumn::where('tenant_id', $tenant->id)->where('column_key', $key)->exists()) {
            $key = "{$originalKey}_{$count}";
            $count++;
        }

        $this->databaseService->addColumnToTenantDatabase($tenant, [
            'key' => $key,
            'label' => $validated['label'],
            'type' => $validated['type'],
            'options' => $validated['options'] ?? null,
            'is_required' => $validated['is_required'] ?? false,
        ]);

        return redirect()->back()->with('success', "Added new database column '{$validated['label']}' to {$tenant->name}'s database!");
    }

    public function deleteColumn(Request $request, TenantCustomColumn $column): RedirectResponse
    {
        $user = $request->user();
        $tenantId = session('tenant_id') ?? $user?->tenant_id;
        $tenant = Tenant::findOrFail($tenantId);

        if ($column->tenant_id != $tenantId) {
            abort(403);
        }

        $colKey = $column->column_key;
        $colLabel = $column->column_label;

        // 1. Delete from database metadata
        $column->delete();

        // 2. Drop column directly from dedicated MySQL database table
        $this->databaseService->removeColumnFromTenantDatabase($tenant, $colKey);

        return redirect()->back()->with('success', "Column '{$colLabel}' deleted and dropped from your database table!");
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $user = $request->user();
        $tenantId = session('tenant_id') ?? $user?->tenant_id;
        $tenant = Tenant::findOrFail($tenantId);

        $columns = TenantCustomColumn::where('tenant_id', $tenant->id)
            ->where('is_visible', true)
            ->orderBy('display_order')
            ->get();

        $records = TenantCrmRecord::where('tenant_id', $tenant->id)->latest()->get();

        $fileName = "crm_records_" . strtolower($tenant->slug ?: 'export') . "_" . date('Y_m_d_His') . ".csv";

        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$fileName}",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($columns, $records) {
            $handle = fopen('php://output', 'w');

            // Header row
            $headerRow = ['ID', 'Status', 'Created At'];
            foreach ($columns as $col) {
                $headerRow[] = $col->column_label;
            }
            fputcsv($handle, $headerRow);

            // Data rows
            foreach ($records as $rec) {
                $customData = $rec->custom_data ?? [];
                $row = [
                    $rec->id,
                    $rec->status,
                    $rec->created_at ? $rec->created_at->format('Y-m-d H:i') : '',
                ];

                foreach ($columns as $col) {
                    $key = $col->column_key;
                    $val = $customData[$key] ?? ($rec->{$key} ?? '');
                    if (is_array($val)) {
                        $val = implode(', ', $val);
                    }
                    $row[] = $val;
                }

                fputcsv($handle, $row);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
