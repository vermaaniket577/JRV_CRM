<?php

namespace App\Http\Controllers\DynamicCrm;

use App\Http\Controllers\Controller;
use App\Models\CrmDatabase;
use App\Models\Tenant;
use App\Services\DynamicCrm\SqlImportService;
use App\Services\DynamicCrm\SqlParserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class DatabaseUploadController extends Controller
{
    protected SqlParserService $parser;
    protected SqlImportService $importer;

    public function __construct(SqlParserService $parser, SqlImportService $importer)
    {
        $this->parser = $parser;
        $this->importer = $importer;
    }

    /**
     * Show the database upload page.
     */
    public function show(Request $request): Response
    {
        $tenant = $this->resolveTenant($request);

        $existingDatabases = CrmDatabase::where('tenant_id', $tenant->id)
            ->latest()
            ->get()
            ->map(fn($db) => [
                'id' => $db->id,
                'name' => $db->name,
                'database_name' => $db->database_name,
                'original_file' => $db->original_file,
                'status' => $db->status,
                'tables_count' => $db->tables_count,
                'relationships_count' => $db->relationships_count,
                'total_records' => $db->total_records,
                'imported_at' => $db->created_at?->diffForHumans(),
                'import_summary' => $db->import_summary,
            ]);

        return Inertia::render('DynamicCrm/Upload', [
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
            ],
            'existingDatabases' => $existingDatabases,
        ]);
    }

    /**
     * Handle the database file upload and import.
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:102400'], // 100MB max
        ]);

        $tenant = $this->resolveTenant($request);
        $file = $request->file('file');
        $ext = strtolower($file->getClientOriginalExtension());
        $originalName = $file->getClientOriginalName();

        // Validate file extension
        $allowedExtensions = ['sql', 'txt', 'dump'];
        if (!in_array($ext, $allowedExtensions)) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid file type. Allowed: .sql, .txt, .dump',
            ], 422);
        }

        // Read file content
        $sql = file_get_contents($file->getRealPath());

        if (empty(trim($sql))) {
            return response()->json([
                'success' => false,
                'error' => 'The uploaded file is empty.',
            ], 422);
        }

        // Validate SQL content
        $errors = $this->parser->validate($sql);
        if (!empty($errors)) {
            return response()->json([
                'success' => false,
                'error' => 'SQL validation failed: ' . implode('; ', $errors),
                'details' => $errors,
            ], 422);
        }

        // Store original file securely
        $storagePath = $file->store("tenant-databases/{$tenant->id}", 'local');

        try {
            $result = $this->importer->import($tenant, $sql, $originalName, $request->user()?->id);

            return response()->json([
                'success' => true,
                'message' => "Database imported successfully! {$result['tables_count']} tables, {$result['relationships_count']} relationships, {$result['total_records']} records detected.",
                'details' => [
                    'database_name' => $result['database_name'],
                    'tables_created' => $result['tables_created'],
                    'tables_count' => $result['tables_count'],
                    'relationships_count' => $result['relationships_count'],
                    'total_records' => $result['total_records'],
                    'statements_executed' => $result['statements_executed'],
                    'warnings' => $result['warnings'],
                ],
                'redirect_url' => '/dynamic-crm/dashboard',
            ]);
        } catch (\Throwable $e) {
            Log::error("Database upload failed: " . $e->getMessage(), [
                'tenant_id' => $tenant->id,
                'file' => $originalName,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Database import failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get the status of the latest database import.
     */
    public function status(Request $request): JsonResponse
    {
        $tenant = $this->resolveTenant($request);

        $db = CrmDatabase::where('tenant_id', $tenant->id)
            ->latest()
            ->first();

        if (!$db) {
            return response()->json(['status' => 'none', 'message' => 'No database uploaded yet.']);
        }

        return response()->json([
            'status' => $db->status,
            'name' => $db->name,
            'tables_count' => $db->tables_count,
            'relationships_count' => $db->relationships_count,
            'total_records' => $db->total_records,
            'import_summary' => $db->import_summary,
        ]);
    }

    protected function resolveTenant(Request $request): Tenant
    {
        $tenantId = session('tenant_id') ?? $request->user()?->tenant_id;
        $tenant = Tenant::find($tenantId);
        if (!$tenant) $tenant = Tenant::first();
        return $tenant;
    }
}
