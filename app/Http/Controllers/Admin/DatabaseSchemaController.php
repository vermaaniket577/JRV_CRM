<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DatabaseImport;
use App\Models\DatabaseImportColumn;
use App\Models\DatabaseImportTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DatabaseSchemaController extends Controller
{
    /**
     * Get detailed schema comparison for an import session.
     */
    public function compare(int $id): JsonResponse
    {
        $import = DatabaseImport::with(['tables.columns'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'tables' => $import->tables,
            'summary' => $import->schema_changes_summary,
        ]);
    }

    /**
     * Approve or reject specific dangerous / modified columns.
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $import = DatabaseImport::findOrFail($id);

        $request->validate([
            'column_ids' => 'required|array',
            'column_ids.*' => 'integer',
            'approved' => 'required|boolean',
        ]);

        $columnIds = $request->input('column_ids');
        $approved = $request->boolean('approved');

        DatabaseImportColumn::whereIn('id', $columnIds)
            ->whereHas('importTable', fn($q) => $q->where('database_import_id', $import->id))
            ->update([
                'is_approved' => $approved,
                'action' => $approved ? 'modify' : 'keep',
            ]);

        return response()->json([
            'success' => true,
            'message' => $approved ? 'Selected column modifications approved.' : 'Selected column modifications rejected.',
            'columns' => DatabaseImportColumn::whereIn('id', $columnIds)->get(),
        ]);
    }
}
