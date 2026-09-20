<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\ImportLog;
use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Models\Tenant;
use App\Models\TenantSetting;
use App\Models\User;
use App\Services\IndustrySchemaService;
use App\Services\UniversalImportService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataImportController extends Controller
{
    protected UniversalImportService $importService;

    public function __construct(UniversalImportService $importService)
    {
        $this->importService = $importService;
    }

    /**
     * Show Data Import Hub page dynamically tailored to the tenant's industry
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $isSubdomain = app()->bound('is_tenant_subdomain') && app('is_tenant_subdomain');
        $currentTenant = app()->bound('current_tenant') ? app('current_tenant') : null;
        $tenantId = ($isSubdomain && $currentTenant) ? $currentTenant->id : (session('tenant_id') ?? $user?->tenant_id ?? ($request->hasSession() ? $request->session()->get('current_tenant_id') : null));
        $tenant = $tenantId ? Tenant::with(['industry', 'businessType'])->find($tenantId) : null;

        $industrySlug = $tenant?->industry?->slug ?? TenantSetting::getByKey('industry_slug', 'insurance', $tenantId);
        $industryConfig = IndustrySchemaService::getIndustryConfig($industrySlug);

        $logQuery = ImportLog::query();
        if ($tenantId) {
            $logQuery->where('tenant_id', $tenantId);
        } elseif ($isSubdomain) {
            $logQuery->whereRaw('1 = 0');
        }
        $logs = $logQuery->latest()->take(20)->get();

        $importCount = ImportLog::query();
        $candCount = JobApplication::query();
        $postCount = JobPosting::query();
        $contactCount = Contact::query();

        if ($tenantId) {
            $importCount->where('tenant_id', $tenantId);
            $candCount->where('tenant_id', $tenantId);
            $postCount->where('tenant_id', $tenantId);
            $contactCount->where('tenant_id', $tenantId);
        } elseif ($isSubdomain) {
            $importCount->whereRaw('1 = 0');
            $candCount->whereRaw('1 = 0');
            $postCount->whereRaw('1 = 0');
            $contactCount->whereRaw('1 = 0');
        }

        $stats = [
            'total_imports' => $importCount->count(),
            'total_candidates' => $candCount->count(),
            'total_vacancies' => $postCount->count(),
            'total_contacts' => $contactCount->count(),
        ];

        return Inertia::render('DataImportHub', [
            'logs' => $logs,
            'stats' => $stats,
            'industryConfig' => $industryConfig,
        ]);
    }

    /**
     * Import from Excel / CSV / TSV / JSON File
     */
    public function importExcel(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:20480'], // 20MB max
            'entity_type' => ['required', 'string'],
        ]);

        $file = $request->file('file');
        $entityType = $request->input('entity_type');
        $user = $request->user();
        $tenantId = session('tenant_id') ?? $user?->tenant_id ?? ($request->hasSession() ? $request->session()->get('current_tenant_id') : null);
        if (!$tenantId && app()->bound('current_tenant') && app('current_tenant')) {
            $tenantId = app('current_tenant')->id;
        if (!$tenantId) {
            $host = $request->getHost();
            $parts = explode('.', $host);
            if (count($parts) >= 2 && !in_array(strtolower($parts[0]), ['localhost', '127', 'www', 'admin', 'api'])) {
                $tenantId = Tenant::where('subdomain', $parts[0])->orWhere('slug', $parts[0])->value('id');
            }
        }
        if (!$tenantId) {
            return redirect()->back()->with('error', 'Cannot import: tenant context could not be identified.');
        }

        try {
            $rows = $this->importService->parseFile($file);

            if (empty($rows)) {
                return redirect()->back()->with('error', 'No valid rows found in the uploaded file. Please ensure columns and headers are present.');
            }

            $result = $this->importService->importToEntity($entityType, $rows, $tenantId);

            ImportLog::create([
                'tenant_id' => $tenantId,
                'import_source' => strtoupper($file->getClientOriginalExtension()) . ' File',
                'entity_type' => $entityType,
                'file_name' => $file->getClientOriginalName(),
                'total_rows' => $result['total'],
                'imported_rows' => $result['imported'],
                'failed_rows' => $result['failed'],
                'status' => $result['failed'] > 0 ? 'Partial' : 'Completed',
                'summary' => "Imported {$result['imported']} of {$result['total']} records successfully into {$entityType}.",
            ]);

            return redirect()->back()->with('success', "Successfully imported {$result['imported']} records from {$file->getClientOriginalName()} into {$entityType}!");
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'File Import Failed: ' . $e->getMessage());
        }
    }

    /**
     * Upload and execute raw .sql Database Backup File, register schema, and populate CRM data
     */
    public function importSqlDump(Request $request): RedirectResponse
    {
        return app(\App\Http\Controllers\Tenant\TenantDatabaseManagerController::class)->uploadDatabase($request);
    }

    /**
     * Test external Database Connection
     */
    public function testDatabaseConnection(Request $request): JsonResponse
    {
        $config = $request->validate([
            'driver' => ['nullable', 'string'],
            'host' => ['required', 'string'],
            'port' => ['required', 'numeric'],
            'database' => ['required', 'string'],
            'username' => ['required', 'string'],
            'password' => ['nullable', 'string'],
        ]);

        $result = $this->importService->testConnection($config);

        return response()->json($result);
    }

    /**
     * Sync data from external Database Table
     */
    public function syncDatabaseTable(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'driver' => ['nullable', 'string'],
            'host' => ['required', 'string'],
            'port' => ['required', 'numeric'],
            'database' => ['required', 'string'],
            'username' => ['required', 'string'],
            'password' => ['nullable', 'string'],
            'source_table' => ['required', 'string'],
            'target_entity' => ['required', 'string'],
        ]);

        $user = $request->user();
        $tenantId = session('tenant_id') ?? $user?->tenant_id ?? ($request->hasSession() ? $request->session()->get('current_tenant_id') : null);

        try {
            $rows = $this->importService->fetchRowsFromExternalTable($validated, $validated['source_table']);

            if (empty($rows)) {
                return redirect()->back()->with('error', "No rows found in table '{$validated['source_table']}' to import.");
            }

            $result = $this->importService->importToEntity($validated['target_entity'], $rows, $tenantId);

            ImportLog::create([
                'tenant_id' => $tenantId,
                'import_source' => "Live DB ({$validated['host']}:{$validated['database']}.{$validated['source_table']})",
                'entity_type' => $validated['target_entity'],
                'file_name' => "{$validated['database']}.{$validated['source_table']}",
                'total_rows' => $result['total'],
                'imported_rows' => $result['imported'],
                'failed_rows' => $result['failed'],
                'status' => $result['failed'] > 0 ? 'Partial' : 'Completed',
                'summary' => "Extracted {$result['imported']} records from {$validated['source_table']} into {$validated['target_entity']}.",
            ]);

            return redirect()->back()->with('success', "Database Sync Successful! Imported {$result['imported']} rows from '{$validated['source_table']}' into {$validated['target_entity']}.");
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Database Sync Failed: ' . $e->getMessage());
        }
    }

    /**
     * Download Sample CSV Template tailored to the tenant's current industry
     */
    public function downloadSample(Request $request, string $entity): StreamedResponse
    {
        $user = $request->user();
        $tenantId = session('tenant_id') ?? $user?->tenant_id;
        $tenant = $tenantId ? Tenant::with('industry')->find($tenantId) : null;
        $industrySlug = $tenant?->industry?->slug ?? TenantSetting::getByKey('industry_slug', 'insurance', $tenantId);
        $industryConfig = IndustrySchemaService::getIndustryConfig($industrySlug);

        // Find entity matching ID
        $matchedEntity = null;
        foreach ($industryConfig['entities'] as $e) {
            if ($e['id'] === $entity) {
                $matchedEntity = $e;
                break;
            }
        }

        $filename = "{$entity}_sample_template.csv";
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($matchedEntity) {
            $handle = fopen('php://output', 'w');
            // Write UTF-8 BOM
            fputs($handle, "\xEF\xBB\xBF");

            if ($matchedEntity && !empty($matchedEntity['columns'])) {
                fputcsv($handle, $matchedEntity['columns']);
                if (!empty($matchedEntity['sample_rows'])) {
                    foreach ($matchedEntity['sample_rows'] as $row) {
                        fputcsv($handle, $row);
                    }
                }
            } else {
                fputcsv($handle, ['name', 'email', 'phone', 'notes', 'status']);
                fputcsv($handle, ['Sample Record', 'sample@example.com', '+1 (555) 000-1111', 'Sample notes', 'Active']);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * 1-Click Sector Dynamic Demo Data Seeder
     */
    public function seedHealthcareDemo(Request $request): RedirectResponse
    {
        $user = $request->user();
        $tenantId = session('tenant_id') ?? $user?->tenant_id ?? ($request->hasSession() ? $request->session()->get('current_tenant_id') : null);
        $tenant = $tenantId ? Tenant::with('industry')->find($tenantId) : null;
        $industrySlug = $tenant?->industry?->slug ?? TenantSetting::getByKey('industry_slug', 'insurance', $tenantId);
        $industryConfig = IndustrySchemaService::getIndustryConfig($industrySlug);

        $demoCount = 0;

        if ($industrySlug === 'insurance') {
            // Seed Insurance Vacancies
            $vacancies = [
                [
                    'title' => 'Senior Health & Corporate Underwriter',
                    'department' => 'Health Underwriting Division',
                    'location' => 'Mumbai BKC Corporate Hub',
                    'employment_type' => 'Full-Time',
                    'salary_min' => 950000,
                    'salary_max' => 1600000,
                    'description' => 'Assess complex high-value corporate group mediclaim and retail underwriting risk proposals.',
                ],
                [
                    'title' => 'Motor Claims Surveyor & Loss Adjuster',
                    'department' => 'Motor Claims Assessment',
                    'location' => 'Delhi NCR Regional Office',
                    'employment_type' => 'Full-Time',
                    'salary_min' => 650000,
                    'salary_max' => 1100000,
                    'description' => 'On-field accidental damage inspection, cashless garage approvals, and salvage evaluation.',
                ],
            ];

            foreach ($vacancies as $v) {
                JobPosting::firstOrCreate(['title' => $v['title']], array_merge($v, ['status' => 'Active']));
                $demoCount++;
            }

            // Seed Insurance Policyholders (Contacts)
            $policyholders = [
                ['name' => 'Rahul Sharma', 'email' => 'rahul.sharma@gmail.com', 'phone' => '+91 98765 43210', 'notes' => 'Policy: Comprehensive Health Shield (POL-984210) • Premium: ₹24,500/yr', 'stage' => 'Active Policy'],
                ['name' => 'Priya Deshmukh', 'email' => 'priya.deshmukh@yahoo.com', 'phone' => '+91 91234 56789', 'notes' => 'Policy: Motor Zero-Depreciation Auto (POL-773192) • Premium: ₹14,200/yr', 'stage' => 'Active Policy'],
                ['name' => 'Aniket Verma', 'email' => 'aniket.v@corporate.com', 'phone' => '+91 99887 76655', 'notes' => 'Policy: Term Life Cover (1 Crore) (POL-551094) • Premium: ₹18,900/yr', 'stage' => 'Active Policy'],
            ];

            foreach ($policyholders as $p) {
                Contact::updateOrCreate(['email' => $p['email']], [
                    'tenant_id' => $tenantId,
                    'first_name' => explode(' ', $p['name'])[0],
                    'last_name' => explode(' ', $p['name'])[1] ?? '',
                    'phone' => $p['phone'],
                    'notes' => $p['notes'],
                    'lifecycle_stage' => $p['stage'],
                ]);
                $demoCount++;
            }
        } elseif ($industrySlug === 'healthcare') {
            $vacancies = [
                ['title' => 'Chief of Surgery & Laparoscopic Specialist', 'department' => 'Surgery', 'location' => 'Metro Hospital, NY', 'employment_type' => 'Full-Time', 'salary_min' => 220000, 'salary_max' => 310000, 'description' => 'Lead surgical rounds and trauma theater.'],
                ['title' => 'Consultant Pediatrician & Neonatologist', 'department' => 'Pediatrics', 'location' => 'Children Wing, Boston', 'employment_type' => 'Full-Time', 'salary_min' => 170000, 'salary_max' => 240000, 'description' => 'NICU oversight and pediatric diagnostics.'],
            ];

            foreach ($vacancies as $v) {
                JobPosting::firstOrCreate(['title' => $v['title']], array_merge($v, ['status' => 'Active']));
                $demoCount++;
            }

            $patients = [
                ['name' => 'Dr. Siddharth Sen, MD', 'email' => 'siddharth.sen@cardiocenter.org', 'phone' => '+1 (555) 765-4321', 'notes' => 'Cardiologist candidate with TAVR expertise.', 'stage' => 'Interview Scheduled'],
                ['name' => 'Nurse Rebecca Moore, MSN', 'email' => 'rebecca.moore@traumacare.com', 'phone' => '+1 (555) 654-9870', 'notes' => 'Acute trauma & CRRT dialysis certified.', 'stage' => 'Offer Sent'],
            ];

            foreach ($patients as $p) {
                JobApplication::updateOrCreate(['email' => $p['email']], [
                    'job_posting_id' => JobPosting::first()->id ?? null,
                    'applicant_name' => $p['name'],
                    'phone' => $p['phone'],
                    'experience_years' => '7+ years',
                    'stage' => $p['stage'],
                    'notes' => $p['notes'],
                ]);
                $demoCount++;
            }
        } else {
            // General / Real Estate / Education demo
            $contacts = [
                ['name' => 'Vikram Sethi', 'email' => 'vikram.sethi@business.com', 'phone' => '+1 (555) 334-5566', 'notes' => 'Enterprise Prospect lead', 'stage' => 'Qualified'],
                ['name' => 'Meera Kapoor', 'email' => 'meera.k@consulting.org', 'phone' => '+1 (555) 445-6677', 'notes' => 'Commercial partnership enquiry', 'stage' => 'Negotiation'],
            ];

            foreach ($contacts as $c) {
                Contact::updateOrCreate(['email' => $c['email']], [
                    'tenant_id' => $tenantId,
                    'first_name' => explode(' ', $c['name'])[0],
                    'last_name' => explode(' ', $c['name'])[1] ?? '',
                    'phone' => $c['phone'],
                    'notes' => $c['notes'],
                    'lifecycle_stage' => $c['stage'],
                ]);
                $demoCount++;
            }
        }

        ImportLog::create([
            'tenant_id' => $tenantId,
            'import_source' => "{$industryConfig['name']} Demo Seeder",
            'entity_type' => "{$industryConfig['name']} Records",
            'file_name' => "{$industrySlug}_preset_bundle.sql",
            'total_rows' => $demoCount,
            'imported_rows' => $demoCount,
            'failed_rows' => 0,
            'status' => 'Completed',
            'summary' => "Generated {$demoCount} sector-tailored {$industryConfig['name']} records.",
        ]);

        return redirect()->back()->with('success', "1-Click {$industryConfig['name']} Database Seeder applied successfully! {$demoCount} records imported.");
    }
}
