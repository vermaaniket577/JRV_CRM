<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
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
use App\Services\IndustryConfigurationService;
use App\Services\TenantDatabaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
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

    protected function resolveCurrentTenant(Request $request): Tenant
    {
        $tenant = null;
        if (app()->bound('current_tenant') && app('current_tenant')) {
            $tenant = app('current_tenant');
        } elseif ($request->hasSession() && $request->session()->has('tenant_id')) {
            $tenant = Tenant::with(['industry', 'businessType'])->find($request->session()->get('tenant_id'));
        } elseif ($request->user()?->tenant_id) {
            $tenant = Tenant::with(['industry', 'businessType'])->find($request->user()->tenant_id);
        }

        if (!$tenant) {
            $host = $request->getHost();
            $parts = explode('.', $host);
            if (count($parts) >= 2 && !in_array(strtolower($parts[0]), ['localhost', '127', 'www', 'admin', 'api'])) {
                $sub = $parts[0];
                $tenant = Tenant::with(['industry', 'businessType'])
                    ->where('subdomain', $sub)
                    ->orWhere('slug', $sub)
                    ->first();
            }
        }

        if (!$tenant) {
            $tenant = Tenant::with(['industry', 'businessType'])->where('subdomain', 'like', '%unlockrentals%')->first() 
                ?? Tenant::with(['industry', 'businessType'])->first();
        }

        if ($tenant && $request->hasSession()) {
            $request->session()->put('tenant_id', $tenant->id);
        }

        return $tenant;
    }

    public function index(Request $request): Response
    {
        $tenant = $this->resolveCurrentTenant($request);

        // 1. Discover all user tables in dedicated database if available
        $availableTables = [];
        $selectedTable = $request->query('table');

        if ($tenant->database_name) {
            $ignoreTables = [
                'migrations', 'sessions', 'cache', 'cache_locks', 'failed_jobs', 'jobs',
                'job_batches', 'password_reset_tokens', 'activity_logs', 'payment_logs',
                'logs', 'seo_keywords', 'crm_column_schemas', 'otp_verifications',
                'personal_access_tokens'
            ];
            try {
                $rawTables = DB::select("SHOW TABLES IN `{$tenant->database_name}`");
                foreach ($rawTables as $t) {
                    $tblName = array_values((array)$t)[0];
                    if (in_array(strtolower($tblName), $ignoreTables)) continue;
                    $rowCount = 0;
                    try {
                        $rowCount = (int) (DB::select("SELECT COUNT(*) as c FROM `{$tenant->database_name}`.`{$tblName}`")[0]->c ?? 0);
                    } catch (\Throwable $e) {}

                    $availableTables[] = [
                        'name' => $tblName,
                        'label' => Str::title(str_replace('_', ' ', $tblName)),
                        'count' => $rowCount,
                    ];
                }
            } catch (\Throwable $e) {}
        }

        // 2. Determine default active table
        if (!$selectedTable) {
            $customColTable = TenantCustomColumn::where('tenant_id', $tenant->id)->value('table_name');
            if ($customColTable && collect($availableTables)->contains('name', $customColTable)) {
                $selectedTable = $customColTable;
            } else {
                $bestTbl = collect($availableTables)
                    ->sortByDesc('count')
                    ->first(fn($t) => !in_array($t['name'], ['districts', 'localities', 'states']));
                $selectedTable = $bestTbl['name'] ?? 'properties';
            }
        }

        // 3. Load or generate columns for selected table
        $columns = TenantCustomColumn::where('tenant_id', $tenant->id)
            ->where('table_name', $selectedTable)
            ->orderBy('display_order')
            ->get();

        if ($columns->isEmpty()) {
            if ($tenant->database_name) {
                try {
                    $colsFromDb = DB::select(
                        "SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? ORDER BY ORDINAL_POSITION",
                        [$tenant->database_name, $selectedTable]
                    );
                    $order = 0;
                    foreach ($colsFromDb as $col) {
                        $k = $col->COLUMN_NAME;
                        if (in_array(strtolower($k), ['id', 'user_id', 'category_id', 'slug', 'created_at', 'updated_at', 'deleted_at', 'approved_at', 'video_path', 'latitude', 'longitude', 'password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes', 'token'])) {
                            continue;
                        }
                        $type = 'text';
                        $kLower = strtolower($k);
                        if (str_contains($kLower, 'price') || str_contains($kLower, 'amount') || str_contains($kLower, 'value') || str_contains($kLower, 'fee')) $type = 'currency';
                        elseif (str_contains($kLower, 'email') || str_contains($kLower, 'mail')) $type = 'email';
                        elseif (str_contains($kLower, 'phone') || str_contains($kLower, 'mobile')) $type = 'phone';
                        elseif (in_array($kLower, ['bedrooms', 'bathrooms', 'area_sqft', 'age', 'count', 'rating'])) $type = 'number';
                        elseif (in_array($kLower, ['status', 'type', 'purpose', 'furnishing', 'role', 'priority', 'deal_stage'])) $type = 'badge';

                        TenantCustomColumn::create([
                            'tenant_id' => $tenant->id,
                            'table_name' => $selectedTable,
                            'column_key' => $k,
                            'column_label' => Str::title(str_replace('_', ' ', $k)),
                            'column_type' => $type,
                            'is_required' => false,
                            'is_default' => true,
                            'is_visible' => true,
                            'display_order' => ++$order,
                        ]);
                    }
                    $columns = TenantCustomColumn::where('tenant_id', $tenant->id)
                        ->where('table_name', $selectedTable)
                        ->orderBy('display_order')
                        ->get();
                } catch (\Throwable $e) {}
            }

            if ($columns->isEmpty()) {
                $columns = TenantCustomColumn::where('tenant_id', $tenant->id)
                    ->orderBy('display_order')
                    ->get();
            }

            if ($columns->isEmpty()) {
                $defaultCols = $this->databaseService->getDefaultColumnsForTenant($tenant);
                $this->databaseService->syncTenantCustomColumns($tenant, $defaultCols);
                $columns = TenantCustomColumn::where('tenant_id', $tenant->id)
                    ->orderBy('display_order')
                    ->get();
            }
        }

        // 4. Fetch records from dedicated table if available, else from TenantCrmRecord
        $hasDedicatedTable = false;
        if ($tenant->database_name) {
            try {
                $tableCheck = DB::select(
                    "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?",
                    [$tenant->database_name, $selectedTable]
                );
                $hasDedicatedTable = !empty($tableCheck);
            } catch (\Throwable $e) {}
        }

        $records = null;
        $totalRecords = 0;
        $totalValue = 0;

        if ($hasDedicatedTable && $selectedTable !== 'crm_leads') {
            $tableQuery = DB::table("{$tenant->database_name}.{$selectedTable}");

            if ($request->filled('search')) {
                $search = $request->search;
                $searchableCols = $columns->pluck('column_key')->toArray();
                if (empty($searchableCols)) {
                    $searchableCols = ['title', 'name', 'email', 'phone', 'location', 'address', 'description'];
                }
                $tableQuery->where(function ($q) use ($search, $searchableCols) {
                    $first = true;
                    foreach ($searchableCols as $c) {
                        try {
                            if ($first) {
                                $q->where($c, 'like', "%{$search}%");
                                $first = false;
                            } else {
                                $q->orWhere($c, 'like', "%{$search}%");
                            }
                        } catch (\Throwable $e) {}
                    }
                });
            }

            if ($request->filled('status') && $request->status !== 'all') {
                try {
                    $tableQuery->where('status', $request->status);
                } catch (\Throwable $e) {}
            }

            $rawRecords = $tableQuery->latest('id')->paginate(15)->withQueryString();
            $totalRecords = $rawRecords->total();

            $transformed = $rawRecords->getCollection()->map(function ($rec) {
                $recArr = (array) $rec;
                $cName = $recArr['title'] ?? $recArr['name'] ?? $recArr['contact_name'] ?? $recArr['full_name'] ?? ('Record #' . $recArr['id']);
                $val = 0;
                foreach ($recArr as $k => $v) {
                    if (str_contains(strtolower($k), 'price') || str_contains(strtolower($k), 'amount') || str_contains(strtolower($k), 'value')) {
                        if (is_numeric($v)) {
                            $val = (float) $v;
                            break;
                        }
                    }
                }

                return array_merge([
                    'id' => $recArr['id'],
                    'title' => $cName,
                    'contact_name' => $cName,
                    'email' => $recArr['email'] ?? $recArr['contact_phone'] ?? null,
                    'phone' => $recArr['phone'] ?? $recArr['contact_phone'] ?? null,
                    'company' => $recArr['location'] ?? $recArr['address'] ?? ($recArr['company'] ?? null),
                    'status' => ucfirst($recArr['status'] ?? 'Active'),
                    'value' => $val,
                    'created_at' => isset($recArr['created_at']) ? date('M d, Y', strtotime($recArr['created_at'])) : null,
                ], $recArr);
            });

            $rawRecords->setCollection($transformed);
            $records = $rawRecords;

            try {
                $sumRes = DB::select("SELECT SUM(price) as s FROM `{$tenant->database_name}`.`{$selectedTable}`");
                $totalValue = (float) ($sumRes[0]->s ?? 0);
            } catch (\Throwable $e) {}

        } else {
            // Fallback to TenantCrmRecord
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
            $totalRecords = $records->total();
            $totalValue = (float) TenantCrmRecord::where('tenant_id', $tenant->id)->sum('value');

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
        }

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
            'table_name' => $selectedTable,
            'created_at' => $tenant->database_created_at ? $tenant->database_created_at->format('M d, Y H:i') : $tenant->created_at->format('M d, Y'),
        ];

        $stats = [
            'total_records' => $totalRecords,
            'total_value' => '₹' . number_format($totalValue),
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
            'availableTables' => $availableTables,
            'activeTable' => $selectedTable,
            'filters' => $request->only(['search', 'status', 'table']),
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

        $selectedTable = $request->query('table');
        if (!$selectedTable) {
            $customColTable = TenantCustomColumn::where('tenant_id', $tenant->id)->value('table_name');
            $selectedTable = $customColTable ?? 'properties';
        }

        $columns = TenantCustomColumn::where('tenant_id', $tenant->id)
            ->where(function($q) use ($selectedTable) {
                $q->where('table_name', $selectedTable)->orWhereNull('table_name');
            })
            ->where('is_visible', true)
            ->orderBy('display_order')
            ->get();

        $fileName = "crm_" . strtolower($selectedTable) . "_" . strtolower($tenant->slug ?: 'export') . "_" . date('Y_m_d_His') . ".csv";

        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$fileName}",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        // Check if dedicated table exists
        $hasDedicated = false;
        if ($tenant->database_name) {
            try {
                $hasDedicated = !empty(DB::select("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?", [$tenant->database_name, $selectedTable]));
            } catch (\Throwable $e) {}
        }

        if ($hasDedicated && $selectedTable !== 'crm_leads') {
            $rows = DB::table("{$tenant->database_name}.{$selectedTable}")->latest('id')->get();
            return response()->stream(function () use ($columns, $rows) {
                $handle = fopen('php://output', 'w');
                $headerRow = ['ID'];
                foreach ($columns as $col) {
                    $headerRow[] = $col->column_label;
                }
                fputcsv($handle, $headerRow);

                foreach ($rows as $rowObj) {
                    $rowArr = (array) $rowObj;
                    $line = [$rowArr['id'] ?? ''];
                    foreach ($columns as $col) {
                        $val = $rowArr[$col->column_key] ?? '';
                        if (is_array($val)) $val = implode(', ', $val);
                        $line[] = $val;
                    }
                    fputcsv($handle, $line);
                }
                fclose($handle);
            }, 200, $headers);
        }

        $records = TenantCrmRecord::where('tenant_id', $tenant->id)->latest()->get();

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

    /**
     * Upload database file (.sql, .csv, .xlsx, .json), create dynamic tables/columns, and populate CRM data
     */
    /**
     * Upload database file (.sql, .csv, .xlsx, .json), create dynamic tables/columns,
     * intelligently detect the sector, and automatically convert the CRM to match the uploaded database.
     */
    public function uploadDatabase(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:102400'], // up to 100MB
        ]);

        $tenant = $this->resolveCurrentTenant($request);

        $file = $request->file('file');
        $ext = strtolower($file->getClientOriginalExtension());
        $filePath = $file->getRealPath();

        $importedColsCount = 0;
        $importedRowsCount = 0;

        $baseFileName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $tableName = Str::snake(preg_replace('/[^a-zA-Z0-9_]/', '_', $baseFileName)) ?: 'crm_leads';

        $columnsData = [];
        $importedRows = [];

        try {
            if (in_array($ext, ['sql', 'dump'])) {
                $deployService = app(\App\Services\SqlDatabaseDeploymentService::class);
                $result = $deployService->deploySqlFile($tenant, $filePath, $file->getClientOriginalName(), $request->user()?->id);

                $industryNotice = $result['detected_industry']
                    ? "Your CRM has been automatically converted to '{$result['detected_industry']->name}' according to your database!"
                    : "Database table '{$result['primary_table']}' successfully configured in your CRM!";

                return redirect()->route('tenant.crm-records.index')->with(
                    'success',
                    "{$industryNotice} Successfully deployed table '{$result['primary_table']}' into database `{$result['database_name']}`, registered {$result['columns_registered']} custom columns and loaded {$result['rows_deployed']} records."
                );
            } else {
                // Spreadsheet (XLSX, XLS, CSV, TSV, JSON)
                $importService = app(\App\Services\UniversalImportService::class);
                $importedRows = $importService->parseFile($file);

                if (empty($importedRows)) {
                    return redirect()->back()->with('error', 'No rows found in the uploaded file.');
                }

                $firstRow = (array)$importedRows[0];

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
            }

            // Intelligently detect sector and automatically convert tenant CRM
            $detectedIndustry = $this->detectAndConvertCrm($tenant, $tableName, $columnsData, $importedRows, $file->getClientOriginalName());
            $tenant->refresh();

            // Register columns in TenantCustomColumn
            $existingKeys = TenantCustomColumn::where('tenant_id', $tenant->id)->pluck('column_key')->toArray();
            $maxOrder = TenantCustomColumn::where('tenant_id', $tenant->id)->max('display_order') ?? 0;

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
                        'created_by' => $request->user()?->id,
                    ]);

                    $this->syncSectorEntity($tenant, $rowArr, $tableName, $detectedIndustry);
                    $importedRowsCount++;
                }
            }

            ImportLog::create([
                'tenant_id' => $tenant->id,
                'import_source' => strtoupper($ext) . ' Database Upload',
                'entity_type' => 'crm_database_table',
                'file_name' => $file->getClientOriginalName(),
                'total_rows' => $importedRowsCount,
                'imported_rows' => $importedRowsCount,
                'failed_rows' => 0,
                'status' => 'Completed',
                'summary' => "Created database table '{$tableName}' with {$importedColsCount} schema columns and loaded {$importedRowsCount} records into CRM.",
            ]);

            $industryNotice = $detectedIndustry 
                ? "Your CRM has been automatically converted to '{$detectedIndustry->name}' according to your database!"
                : "Database table '{$tableName}' successfully configured in your CRM!";

            return redirect()->route('tenant.crm-records.index')->with(
                'success',
                "{$industryNotice} Registered {$importedColsCount} columns and loaded {$importedRowsCount} records."
            );
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Database Table Creation Error: ' . $e->getMessage());
        }
    }

    /**
     * Intelligently detect sector from uploaded database schema, table, and data,
     * and convert the tenant's CRM configuration (industry, navigation, pipeline, settings).
     */
    protected function detectAndConvertCrm(Tenant $tenant, string $tableName, array $columnsData, array $importedRows, string $fileName): ?Industry
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
            // Switch tenant's industry and re-provision modules, navigation, pipeline
            $configService = new IndustryConfigurationService();
            $configService->switchIndustry($tenant, $industry);

            TenantSetting::setByKey('industry_name', $industry->name, $tenant->id);
            TenantSetting::setByKey('industry_slug', $industry->slug, $tenant->id);
            TenantSetting::setByKey('industry_color', $industry->color, $tenant->id);
            TenantSetting::setByKey('active_crm_table', $tableName, $tenant->id);
            $tenant->refresh();
        }

        // Add prominent Navigation Item pointing to this uploaded CRM Database Table
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
                'display_order' => 2, // Right after Dashboard
                'is_enabled' => true,
            ]
        );

        return $industry;
    }

    /**
     * Intelligently sync imported database rows into Sector Tables (Properties, Contacts, Members, Leads)
     */
    protected function syncSectorEntity(Tenant $tenant, array $rowArr, ?string $tableName = null, ?Industry $detectedIndustry = null): void
    {
        $industrySlug = $detectedIndustry?->slug ?? $tenant->industry?->slug;

        $cName = $rowArr['name'] ?? $rowArr['contact_name'] ?? $rowArr['title'] ?? $rowArr['full_name'] ?? $rowArr['first_name'] ?? null;
        $email = $rowArr['email'] ?? $rowArr['mail'] ?? $rowArr['client_email'] ?? null;
        $phone = $rowArr['phone'] ?? $rowArr['mobile'] ?? $rowArr['contact'] ?? $rowArr['contact_phone'] ?? null;

        // 1. Property listings sync (Real Estate)
        $isProperty = ($industrySlug === 'real-estate')
            || in_array(strtolower((string)$tableName), ['properties', 'property', 'real_estate', 'listings', 'flats', 'units', 'apartments'])
            || isset($rowArr['property_type'])
            || isset($rowArr['bedrooms'])
            || isset($rowArr['bhk'])
            || isset($rowArr['carpet_area_sqft'])
            || (isset($rowArr['locality']) && isset($rowArr['price']));

        if ($isProperty) {
            $propTitle = $rowArr['title'] ?? $rowArr['property_name'] ?? $rowArr['name'] ?? $rowArr['unit_name'] ?? $rowArr['property_title'] ?? 'Real Estate Property';
            $pType = $rowArr['property_type'] ?? $rowArr['type'] ?? 'Apartment';
            $pCount = Property::where('tenant_id', $tenant->id)->count() + 1;
            $pCode = $rowArr['property_code'] ?? ('PROP-' . strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $pType) ?: 'PRP', 0, 3)) . '-' . str_pad($pCount, 4, '0', STR_PAD_LEFT));
            $bhk = $rowArr['bedrooms'] ?? $rowArr['bhk'] ?? 2;

            Property::updateOrCreate(
                ['tenant_id' => $tenant->id, 'title' => $propTitle],
                [
                    'property_code' => $pCode,
                    'listing_type' => $rowArr['listing_type'] ?? $rowArr['purpose'] ?? 'For Rent',
                    'property_type' => $pType,
                    'price' => (float)($rowArr['price'] ?? $rowArr['rent'] ?? $rowArr['amount'] ?? 25000),
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

        // 2. Matrimonial Members sync
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

        // 3. Contacts & Universal Leads sync
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
}
