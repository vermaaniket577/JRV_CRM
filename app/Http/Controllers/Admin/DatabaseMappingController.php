<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DatabaseFieldMapping;
use App\Models\DatabaseImport;
use App\Services\FieldMappingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class DatabaseMappingController extends Controller
{
    protected FieldMappingService $mappingService;

    public function __construct(FieldMappingService $mappingService)
    {
        $this->mappingService = $mappingService;
    }

    /**
     * Show mapping screen or return JSON mapping data for an import.
     */
    public function show(Request $request, int $id): JsonResponse|Response
    {
        $import = DatabaseImport::with(['tables.columns', 'mappings'])->findOrFail($id);

        $standardTables = [
            'contacts' => ['label' => 'Contacts / Customers', 'columns' => Schema::hasTable('contacts') ? Schema::getColumnListing('contacts') : []],
            'crm_sales_leads' => ['label' => 'Sales Leads', 'columns' => Schema::hasTable('crm_sales_leads') ? Schema::getColumnListing('crm_sales_leads') : []],
            'properties' => ['label' => 'Properties', 'columns' => Schema::hasTable('properties') ? Schema::getColumnListing('properties') : []],
            'companies' => ['label' => 'Companies', 'columns' => Schema::hasTable('companies') ? Schema::getColumnListing('companies') : []],
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'mappings' => $import->mappings,
                'tables' => $import->tables,
                'standardTables' => $standardTables,
            ]);
        }

        return Inertia::render('Admin/Database/FieldMapping', [
            'databaseImport' => $import,
            'import' => $import,
            'mappings' => $import->mappings,
            'tables' => $import->tables,
            'standardTables' => $standardTables,
        ]);
    }


    /**
     * Update manual field mappings for an import session.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $import = DatabaseImport::findOrFail($id);

        $request->validate([
            'mappings' => 'required|array',
            'mappings.*.id' => 'required|integer',
            'mappings.*.target_table' => 'required|string',
            'mappings.*.target_column' => 'required|string',
            'mappings.*.is_confirmed' => 'sometimes|boolean',
            'mappings.*.transformation_rule' => 'nullable|string',
        ]);

        $updated = [];
        foreach ($request->input('mappings') as $item) {
            $mapping = DatabaseFieldMapping::where('database_import_id', $import->id)
                ->where('id', $item['id'])
                ->first();

            if ($mapping) {
                $mapping->update([
                    'target_table' => $item['target_table'],
                    'target_column' => $item['target_column'],
                    'is_confirmed' => $item['is_confirmed'] ?? true,
                    'transformation_rule' => $item['transformation_rule'] ?? $mapping->transformation_rule,
                    'confidence' => 'custom',
                    'confidence_score' => 1.00,
                ]);
                $updated[] = $mapping;
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Field mappings updated successfully.',
            'mappings' => $updated,
        ]);
    }
}
