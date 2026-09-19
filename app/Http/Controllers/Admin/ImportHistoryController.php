<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DatabaseImport;
use App\Services\DatabaseBackupService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ImportHistoryController extends Controller
{
    protected DatabaseBackupService $backupService;

    public function __construct(DatabaseBackupService $backupService)
    {
        $this->backupService = $backupService;
    }

    /**
     * List all historical database imports.
     */
    public function index(Request $request): Response
    {
        $imports = DatabaseImport::with(['user', 'backup'])
            ->latest()
            ->paginate(15);

        return Inertia::render('Admin/Database/ImportHistory', [
            'imports' => $imports,
        ]);
    }

    /**
     * Show detailed audit report for an import session.
     */
    public function show(int $id): Response
    {
        $import = DatabaseImport::with([
            'user',
            'backup',
            'tables.columns',
            'mappings',
            'errors' => fn($q) => $q->take(100),
        ])->findOrFail($id);

        return Inertia::render('Admin/Database/ImportDetail', [
            'databaseImport' => $import,
            'import' => $import,
            'errorsCount' => $import->errors()->count(),
        ]);
    }


    /**
     * Rollback import using associated database backup snapshot.
     */
    public function rollback(Request $request, int $id): JsonResponse
    {
        $import = DatabaseImport::with('backup')->findOrFail($id);

        if (!$import->backup) {
            return response()->json([
                'success' => false,
                'message' => 'No database backup snapshot found for this import session.',
            ], 404);
        }

        try {
            $this->backupService->rollback($import->backup);
            $import->update(['status' => 'ROLLED_BACK']);

            return response()->json([
                'success' => true,
                'message' => "Database rolled back successfully from snapshot {$import->backup->backup_name}.",
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Rollback failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}
