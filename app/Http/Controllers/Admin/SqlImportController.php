<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DatabaseImport;
use App\Models\DatabaseImportColumn;
use App\Models\DatabaseImportTable;
use App\Services\DatabaseBackupService;
use App\Services\DataImportService;
use App\Services\FieldMappingService;
use App\Services\ImportLoggerService;
use App\Services\SchemaAnalyzerService;
use App\Services\SchemaMigrationService;
use App\Services\SqlParserService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SqlImportController extends Controller
{
    protected SqlParserService $parserService;
    protected SchemaAnalyzerService $analyzerService;
    protected SchemaMigrationService $migrationService;
    protected FieldMappingService $mappingService;
    protected DatabaseBackupService $backupService;
    protected DataImportService $importService;
    protected ImportLoggerService $loggerService;

    public function __construct(
        SqlParserService $parserService,
        SchemaAnalyzerService $analyzerService,
        SchemaMigrationService $migrationService,
        FieldMappingService $mappingService,
        DatabaseBackupService $backupService,
        DataImportService $importService,
        ImportLoggerService $loggerService
    ) {
        $this->parserService = $parserService;
        $this->analyzerService = $analyzerService;
        $this->migrationService = $migrationService;
        $this->mappingService = $mappingService;
        $this->backupService = $backupService;
        $this->importService = $importService;
        $this->loggerService = $loggerService;
    }

    /**
     * Display the 10-step SQL Database Import Admin UI.
     */
    public function index(Request $request): Response
    {
        $recentImports = DatabaseImport::with('backup')
            ->latest()
            ->take(10)
            ->get();

        $dashboardStats = $this->loggerService->getDashboardSummary();

        // Check if an import id is provided in query params to resume
        $activeImportId = $request->query('import_id');
        $activeImport = $activeImportId ? DatabaseImport::with(['tables.columns', 'mappings', 'backup'])->find($activeImportId) : null;

        return Inertia::render('Admin/Database/Import', [
            'recentImports' => $recentImports,
            'summaryStats' => $dashboardStats,
            'activeImport' => $activeImport,
            'maxUploadSizeMb' => (int) ini_get('upload_max_filesize') ?: 50,
        ]);
    }

    /**
     * Upload .sql file and initialize DatabaseImport session.
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'sql_file' => 'required|file|max:102400', // 100MB max
        ]);

        $file = $request->file('sql_file');
        $extension = strtolower($file->getClientOriginalExtension());

        if (!in_array($extension, ['sql', 'txt'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid file format. Please upload a standard MySQL .sql dump file.',
            ], 422);
        }

        $importDir = storage_path('app/sql_imports');
        if (!File::isDirectory($importDir)) {
            File::makeDirectory($importDir, 0755, true);
        }

        $origName = $file->getClientOriginalName();
        $storedName = 'import_' . date('Ymd_His') . '_' . uniqid() . '.sql';
        $destinationPath = $importDir . DIRECTORY_SEPARATOR . $storedName;

        $file->move($importDir, $storedName);

        $import = DatabaseImport::create([
            'user_id' => auth()->id(),
            'file_name' => $origName,
            'file_path' => $destinationPath,
            'file_size' => File::size($destinationPath),
            'status' => 'PENDING',
            'import_mode' => 'upsert',
        ]);

        return response()->json([
            'success' => true,
            'import_id' => $import->id,
            'file_name' => $origName,
            'file_size' => $import->file_size,
            'message' => 'File uploaded successfully. Ready for SQL analysis.',
        ]);
    }

    /**
     * Analyze uploaded SQL and compare against live CRM database.
     */
    public function analyze(Request $request, int $id): JsonResponse
    {
        $import = DatabaseImport::findOrFail($id);
        $import->update(['status' => 'ANALYZING']);

        try {
            // 1. Parse SQL file
            $parsed = $this->parserService->parseFile($import->file_path);

            // 2. Analyze against live CRM schema
            $analysis = $this->analyzerService->analyze($parsed['tables']);

            // 3. Clear existing table & column records if re-analyzing
            DatabaseImportTable::where('database_import_id', $import->id)->delete();

            // 4. Save parsed tables and columns into DB
            foreach ($analysis['tables'] as $tbl) {
                $importTable = DatabaseImportTable::create([
                    'database_import_id' => $import->id,
                    'table_name' => $tbl['name'],
                    'target_table_name' => $this->mappingService->suggestTargetTable($tbl['name']),
                    'status' => $tbl['status'],
                    'action' => $tbl['action'],
                    'records_count' => $tbl['records_count'],
                    'columns_count' => $tbl['columns_count'],
                    'create_statement' => $tbl['create_statement'],
                    'primary_key' => $tbl['primary_key'],
                    'is_dangerous' => $tbl['is_dangerous'],
                    'requires_approval' => $tbl['requires_approval'],
                ]);

                foreach ($tbl['columns'] as $col) {
                    DatabaseImportColumn::create([
                        'database_import_table_id' => $importTable->id,
                        'column_name' => $col['name'],
                        'data_type' => $col['type'],
                        'is_nullable' => $col['nullable'],
                        'is_primary' => $col['is_primary'],
                        'is_auto_increment' => $col['is_auto_increment'],
                        'default_value' => $col['default'],
                        'status' => $col['status'],
                        'existing_data_type' => $col['existing_type'],
                        'existing_nullable' => $col['existing_nullable'],
                        'existing_default' => $col['existing_default'],
                        'action' => $col['action'],
                        'requires_approval' => $col['requires_approval'],
                    ]);
                }
            }

            // 5. Generate intelligent field mappings
            $this->mappingService->generateMappings($import);

            // 6. Update import metadata
            $import->update([
                'status' => 'READY',
                'tables_detected' => $parsed['stats']['tables_count'],
                'columns_detected' => $parsed['stats']['columns_count'],
                'records_detected' => $parsed['stats']['records_count'],
                'schema_changes_summary' => $analysis['summary'],
            ]);

            return response()->json([
                'success' => true,
                'import' => $import->fresh(['tables.columns', 'mappings']),
                'analysis' => $analysis,
                'relationships' => $parsed['relationships'],
                'stats' => $parsed['stats'],
            ]);
        } catch (\Throwable $e) {
            $import->update([
                'status' => 'FAILED',
                'error_message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Analysis failed: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Return preview of all changes and mappings before execution.
     */
    public function preview(int $id): JsonResponse
    {
        $import = DatabaseImport::with(['tables.columns', 'mappings'])->findOrFail($id);

        $tables = $import->tables;
        $mappings = $import->mappings;

        $newTablesCount = $tables->where('status', 'NEW')->count();
        $existingTablesCount = $tables->whereIn('status', ['EXISTS', 'MODIFIED', 'IDENTICAL'])->count();

        $columnsToAdd = 0;
        $columnsRequiringApproval = 0;

        foreach ($tables as $t) {
            foreach ($t->columns as $c) {
                if ($c->action === 'add') $columnsToAdd++;
                if ($c->requires_approval) $columnsRequiringApproval++;
            }
        }

        return response()->json([
            'success' => true,
            'import' => $import,
            'summary' => [
                'tables_found' => $tables->count(),
                'new_tables' => $newTablesCount,
                'existing_tables' => $existingTablesCount,
                'columns_to_add' => $columnsToAdd,
                'columns_requiring_approval' => $columnsRequiringApproval,
                'records_to_import' => $import->records_detected,
                'dangerous_operations' => $columnsRequiringApproval,
            ],
            'tables' => $tables,
            'mappings' => $mappings,
        ]);
    }

    /**
     * Execute Database Backup, Schema Migrations, and Data Import.
     */
    public function execute(Request $request, int $id): JsonResponse
    {
        $import = DatabaseImport::findOrFail($id);

        $importMode = $request->input('import_mode', $import->import_mode ?? 'upsert');
        $approvedColumnIds = $request->input('approved_columns', []);
        $createBackup = $request->boolean('create_backup', true);

        $import->update(['import_mode' => $importMode]);

        try {
            // 1. Automatic Database Backup
            if ($createBackup) {
                $backup = $this->backupService->createBackup(
                    adminId: auth()->id(),
                    adminEmail: auth()->user()?->email,
                    sourceFilename: $import->file_name
                );
                $import->update(['backup_id' => $backup->id]);
            }

            // 2. Apply Schema Migrations
            $migrationResults = $this->migrationService->applyMigrations($import, $approvedColumnIds);

            // 3. Import Data
            $importResults = $this->importService->import($import);

            return response()->json([
                'success' => true,
                'message' => 'Database imported successfully.',
                'summary' => [
                    'tables_created' => $migrationResults['tables_created'],
                    'tables_updated' => $migrationResults['tables_updated'],
                    'columns_added' => $migrationResults['columns_added'],
                    'columns_modified' => $migrationResults['columns_modified'],
                    'records_inserted' => $importResults['inserted'],
                    'records_updated' => $importResults['updated'],
                    'records_skipped' => $importResults['skipped'],
                    'records_failed' => $importResults['failed'],
                    'backup_name' => $import->backup?->backup_name ?? 'None',
                ],
                'status' => $import->fresh()->status,
            ]);
        } catch (\Throwable $e) {
            $import->update([
                'status' => 'FAILED',
                'error_message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Import execution failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Poll real-time status and row count for import progress bar.
     */
    public function status(int $id): JsonResponse
    {
        $import = DatabaseImport::findOrFail($id);

        $total = max(1, $import->records_detected);
        $processed = $import->records_inserted + $import->records_updated + $import->records_skipped + $import->records_failed;
        $percent = min(100, round(($processed / $total) * 100));

        return response()->json([
            'status' => $import->status,
            'percent' => $percent,
            'records_detected' => $import->records_detected,
            'records_processed' => $processed,
            'records_inserted' => $import->records_inserted,
            'records_updated' => $import->records_updated,
            'records_skipped' => $import->records_skipped,
            'records_failed' => $import->records_failed,
            'error_message' => $import->error_message,
        ]);
    }

    /**
     * Export downloadable error report CSV.
     */
    public function exportErrors(int $id): StreamedResponse
    {
        $import = DatabaseImport::findOrFail($id);
        return $this->loggerService->exportErrorsCsv($import);
    }

    /**
     * Upload and immediately analyze (convenience route).
     */
    public function uploadAndAnalyze(Request $request): JsonResponse
    {
        $importId = $request->input('import_id');
        if (!$importId) {
            $uploadRes = $this->upload($request);
            $data = $uploadRes->getData(true);
            if (!($data['success'] ?? false)) {
                return $uploadRes;
            }
            $importId = $data['import_id'];
        }

        return $this->analyze($request, (int)$importId);
    }

    /**
     * Preview latest or specified import.
     */
    public function latestPreview(Request $request): JsonResponse
    {
        $importId = $request->query('import_id');
        $import = $importId ? DatabaseImport::find($importId) : DatabaseImport::latest()->first();
        if (!$import) {
            return response()->json(['success' => false, 'message' => 'No import session found.'], 404);
        }
        return $this->preview($import->id);
    }

    /**
     * Execute latest or specified import.
     */
    public function executeLatest(Request $request): JsonResponse
    {
        $importId = $request->input('import_id');
        $import = $importId ? DatabaseImport::find($importId) : DatabaseImport::latest()->first();
        if (!$import) {
            return response()->json(['success' => false, 'message' => 'No import session found.'], 404);
        }
        return $this->execute($request, $import->id);
    }
}

