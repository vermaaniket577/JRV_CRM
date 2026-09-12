<?php

namespace App\Http\Controllers\DynamicCrm;

use App\Http\Controllers\Controller;
use App\Models\CrmActivityLog;
use App\Models\Tenant;
use App\Services\DynamicCrm\GlobalSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GlobalSearchController extends Controller
{
    protected GlobalSearchService $searchService;

    public function __construct(GlobalSearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    /**
     * Search across all dynamic CRM tables.
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:200'],
        ]);

        $tenant = $this->resolveTenant($request);
        $query = $request->get('q');

        $results = $this->searchService->search($tenant, $query);

        // Log search
        CrmActivityLog::log($tenant->id, 'searched', null, null, ['query' => $query]);

        return response()->json([
            'query' => $query,
            'results' => $results,
            'total_groups' => count($results),
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
