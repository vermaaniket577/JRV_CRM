<?php

use App\Http\Controllers\Api\v1\PublicMemberApiController;
use App\Http\Controllers\Api\v1\WebsiteIntegrationApiController;
use App\Http\Controllers\AppDashboardController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\AutoUpdateController;
use App\Http\Controllers\BroadcastMessageController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DealController;
use App\Http\Controllers\EmbedFormController;
use App\Http\Controllers\EmbedPaymentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\GlobalSearchController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\MemberVerificationController;
use App\Http\Controllers\OnlineUserController;
use App\Http\Controllers\Onboarding\OnboardingController;
use App\Http\Controllers\PadhadhikariController;
use App\Http\Controllers\PaymentPlanController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\StaffRecruitmentController;
use App\Http\Controllers\DataImportController;
use App\Http\Controllers\AiCrmModifierController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskDashboardController;
use App\Http\Controllers\Tenant\NavigationCustomizerController;
use App\Http\Controllers\Tenant\RoleAndPermissionController;
use App\Http\Controllers\Tenant\TenantMailSettingController;
use App\Http\Controllers\CrmSalesPanelController;
use App\Http\Controllers\CrmSellingPanelController;
use App\Http\Controllers\AdminPanelController;
use App\Http\Controllers\AdminLoginController;
use App\Http\Controllers\Tenant\TenantDatabaseManagerController;
use App\Http\Controllers\Tenant\WebsiteIntegrationController;
use App\Http\Controllers\SessionCookieController;
use App\Http\Controllers\SystemSettingController;
use App\Http\Middleware\EnsureMasterAdmin;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\LoadBalancerController;
use App\Http\Controllers\Api\HealthCheckController;

// Dynamic CRM Engine Controllers
use App\Http\Controllers\DynamicCrm\DashboardController as DynamicCrmDashboardController;
use App\Http\Controllers\DynamicCrm\DatabaseUploadController;
use App\Http\Controllers\DynamicCrm\DynamicCrudController;
use App\Http\Controllers\DynamicCrm\TableConfigurationController;
use App\Http\Controllers\DynamicCrm\GlobalSearchController as DynamicCrmSearchController;
use App\Http\Middleware\ValidateDynamicTable;

// Cluster Health Probes & Software Load Balancer Gateway Routes
Route::get('/up', [HealthCheckController::class, 'check'])->name('health.up');
Route::get('/api/health', [HealthCheckController::class, 'check'])->name('health.check');
Route::get('/lb/status', [LoadBalancerController::class, 'clusterStatus'])->name('lb.status');
Route::any('/lb/gateway', [LoadBalancerController::class, 'proxyGateway'])->name('lb.gateway');
Route::any('/lb/proxy/{any}', [LoadBalancerController::class, 'proxyGateway'])->where('any', '.*')->name('lb.proxy');

// Master Admin Authentication Routes
Route::get('/admin/login', [AdminLoginController::class, 'showLoginForm'])->name('admin.login');
Route::post('/admin/login', [AdminLoginController::class, 'login']);
Route::post('/admin/logout', [AdminLoginController::class, 'logout'])->name('admin.logout');

// Master Admin Protected Control Panel Routes
Route::prefix('admin')->middleware(EnsureMasterAdmin::class)->name('admin.')->group(function () {
    Route::get('/', [AdminPanelController::class, 'index'])->name('index');
    Route::post('/leads', [AdminPanelController::class, 'storeLead'])->name('leads.store');
    Route::patch('/leads/{id}/status', [AdminPanelController::class, 'updateStatus'])->name('leads.status');
    Route::post('/leads/{id}/provision', [AdminPanelController::class, 'provisionLead'])->name('leads.provision');
    Route::put('/plans/{id}', [AdminPanelController::class, 'updatePlan'])->name('plans.update');

    Route::get('/paid-users', [AdminPanelController::class, 'paidUsers'])->name('paid-users.index');
    Route::get('/settings', [AdminPanelController::class, 'settings'])->name('settings.index');
    Route::post('/settings', [AdminPanelController::class, 'updateSettings'])->name('settings.update');
    Route::post('/subscription/process-payment', [AdminPanelController::class, 'processPayment'])->name('subscription.process-payment');

    // Load Balancer Cluster Management Control Center
    Route::get('/load-balancer', [LoadBalancerController::class, 'index'])->name('load-balancer.index');
    Route::post('/load-balancer/nodes', [LoadBalancerController::class, 'storeNode'])->name('load-balancer.nodes.store');
    Route::put('/load-balancer/nodes/{node}', [LoadBalancerController::class, 'updateNode'])->name('load-balancer.nodes.update');
    Route::delete('/load-balancer/nodes/{node}', [LoadBalancerController::class, 'destroyNode'])->name('load-balancer.nodes.destroy');
    Route::post('/load-balancer/nodes/{node}/drain', [LoadBalancerController::class, 'toggleDrain'])->name('load-balancer.nodes.drain');
    Route::post('/load-balancer/nodes/{node}/toggle', [LoadBalancerController::class, 'toggleEnabled'])->name('load-balancer.nodes.toggle');
    Route::post('/load-balancer/nodes/{node}/ping', [LoadBalancerController::class, 'pingNode'])->name('load-balancer.nodes.ping');
    Route::post('/load-balancer/ping-all', [LoadBalancerController::class, 'pingAll'])->name('load-balancer.ping-all');
    Route::post('/load-balancer/algorithm', [LoadBalancerController::class, 'updateAlgorithm'])->name('load-balancer.algorithm');
    Route::post('/load-balancer/simulate', [LoadBalancerController::class, 'simulate'])->name('load-balancer.simulate');
    Route::get('/load-balancer/export', [LoadBalancerController::class, 'exportConfig'])->name('load-balancer.export');

    // Admin prefixed module routes
    Route::get('/broadcast-message', [BroadcastMessageController::class, 'index'])->name('broadcast.index');
    Route::post('/broadcast-message', [BroadcastMessageController::class, 'store'])->name('broadcast.store');
    Route::get('/auto-update', [AutoUpdateController::class, 'index'])->name('auto-update.index');
    Route::get('/crm-sales-panel', [CrmSalesPanelController::class, 'index'])->name('sales-panel.index');
    Route::get('/crm-selling-panel', [CrmSellingPanelController::class, 'index'])->name('selling-panel.index');
});

// CRM Sales & Subscriptions Control Panel Routes
Route::prefix('crm-sales-panel')->name('crm-sales-panel.')->group(function () {
    Route::get('/', [CrmSalesPanelController::class, 'index'])->name('index');
    Route::post('/leads', [CrmSalesPanelController::class, 'storeLead'])->name('leads.store');
    Route::patch('/leads/{id}/stage', [CrmSalesPanelController::class, 'updateLeadStage'])->name('leads.stage');
});

// CRM Selling & Provisioning Control Panel Routes
Route::prefix('crm-selling-panel')->name('crm-selling-panel.')->group(function () {
    Route::get('/', [CrmSellingPanelController::class, 'index'])->name('index');
    Route::post('/provision', [CrmSellingPanelController::class, 'provisionCrm'])->name('provision');
    Route::post('/tenants/{tenant}/assign-industry', [CrmSellingPanelController::class, 'assignIndustry'])->name('assign-industry');
    Route::post('/tenants/{tenant}/launch', [CrmSellingPanelController::class, 'launchWorkspace'])->name('launch');
});

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
});
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
Route::post('/keep-alive', function () {
    return response()->json(['status' => 'ok', 'timestamp' => now()->toIso8601String()]);
})->name('keep-alive');

// Onboarding Routes
Route::middleware('auth')->prefix('onboarding')->name('onboarding.')->group(function () {
    Route::get('/', [OnboardingController::class, 'index'])->name('index');
    Route::get('/business-types', [OnboardingController::class, 'getBusinessTypes'])->name('business-types');
    Route::get('/industry-columns', [OnboardingController::class, 'getIndustryColumns'])->name('industry-columns');
    Route::post('/upload-database', [OnboardingController::class, 'uploadDatabase'])->name('upload-database');
    Route::post('/complete', [OnboardingController::class, 'complete'])->name('complete');
});

use App\Http\Controllers\RazorpayController;

// Dynamic Tenant CRM Database & Custom Columns Management Routes
Route::middleware('auth')->group(function () {
    Route::get('/tenant/crm-records', [TenantDatabaseManagerController::class, 'index'])->name('tenant.crm-records.index');
    Route::post('/tenant/crm-records', [TenantDatabaseManagerController::class, 'storeRecord'])->name('tenant.crm-records.store');
    Route::get('/tenant/crm-records/export', [TenantDatabaseManagerController::class, 'exportCsv'])->name('tenant.crm-records.export');
    Route::post('/tenant/database/columns', [TenantDatabaseManagerController::class, 'addColumn'])->name('tenant.database.columns.add');
    Route::delete('/tenant/database/columns/{column}', [TenantDatabaseManagerController::class, 'deleteColumn'])->name('tenant.database.columns.delete');
    Route::post('/tenant/database/upload', [TenantDatabaseManagerController::class, 'uploadDatabase'])->name('tenant.database.upload');

    // Razorpay Payment Gateway Routes
    Route::post('/razorpay/create-order', [RazorpayController::class, 'createOrder'])->name('razorpay.create-order');
    Route::post('/razorpay/verify-payment', [RazorpayController::class, 'verifyPayment'])->name('razorpay.verify-payment');
});

// Razorpay Webhook Public Endpoint
Route::post('/api/v1/razorpay/webhook', [RazorpayController::class, 'webhook']);

Route::get('/', AppDashboardController::class)->name('home');
Route::get('/app', AppDashboardController::class)->name('app.dashboard');
Route::get('/dashboard', DashboardController::class)->name('dashboard');
Route::get('/crm-overview', DashboardController::class)->name('crm.overview');
Route::get('/global-search', GlobalSearchController::class)->name('global-search');

Route::get('/task-dashboard', TaskDashboardController::class)->name('task-dashboard');

Route::get('/employee-management', EmployeeController::class)->name('employee-management');
Route::get('/employee-management/export', [EmployeeController::class, 'export'])->name('employee-management.export');
Route::get('/employee-management/sample-csv', [EmployeeController::class, 'downloadSample'])->name('employee-management.sample');
Route::post('/employee-management/import', [EmployeeController::class, 'import'])->name('employee-management.import');
Route::post('/employee-management', [EmployeeController::class, 'store'])->name('employee-management.store');
Route::put('/employee-management/{employee}', [EmployeeController::class, 'update'])->name('employee-management.update');
Route::delete('/employee-management/{employee}', [EmployeeController::class, 'destroy'])->name('employee-management.destroy');

// Payment Plans & Customer Invoices Management (User Panel)
Route::resource('payment-plans', PaymentPlanController::class);
Route::post('/payment-plans/installments/{installment}/mark-paid', [PaymentPlanController::class, 'markInstallmentPaid'])->name('payment-plans.installments.mark-paid');

// Standalone Embed Bio-data Registration Form Routes (For External Website iFrames)
Route::get('/embed/register', [EmbedFormController::class, 'show'])->name('embed.register');
Route::post('/embed/register', [EmbedFormController::class, 'store'])->name('embed.store');

// Standalone 1-Click Payment Checkout & Fee Collection Portal
Route::get('/embed/pay', [EmbedPaymentController::class, 'show'])->name('embed.pay');
Route::post('/embed/pay', [EmbedPaymentController::class, 'store'])->name('embed.pay.store');
Route::get('/pay/{tenant?}', [EmbedPaymentController::class, 'show'])->name('public.pay');

// Customer Payment Plan & Invoice Checkout Routes
Route::get('/pay/plan/{token}', [EmbedPaymentController::class, 'showPlan'])->name('public.pay.plan');
Route::post('/pay/plan/{token}', [EmbedPaymentController::class, 'submitPlanPayment'])->name('public.pay.plan.submit');

// Public REST API Endpoints (CORS Enabled for Website Integration)
Route::get('/api/health', [\App\Http\Controllers\Api\HealthCheckController::class, 'check'])->name('api.health');

Route::prefix('api/v1')->group(function () {
    Route::get('/members/search', [PublicMemberApiController::class, 'search']);
    Route::post('/members/register', [PublicMemberApiController::class, 'register']);
    Route::post('/integration/auto-capture', [WebsiteIntegrationApiController::class, 'autoCapture']);
    Route::post('/integration/test-ping', [WebsiteIntegrationApiController::class, 'testPing']);
    Route::get('/integration/download-wordpress-plugin', [WebsiteIntegrationApiController::class, 'downloadWordPressPlugin'])->name('integration.download-plugin');
});

// Left Sidebar Custom Modules Routes
Route::prefix('broadcast-message')->name('broadcast.')->group(function () {
    Route::get('/', [BroadcastMessageController::class, 'index'])->name('index');
    Route::post('/', [BroadcastMessageController::class, 'store'])->name('store');
});

Route::prefix('auto-update')->name('auto-update.')->group(function () {
    Route::get('/', [AutoUpdateController::class, 'index'])->name('index');
    Route::post('/{rule}/toggle', [AutoUpdateController::class, 'toggle'])->name('toggle');
    Route::post('/{rule}/run', [AutoUpdateController::class, 'runNow'])->name('run');
});

Route::prefix('padhadhikari-directory')->name('padhadhikari.')->group(function () {
    Route::get('/', [PadhadhikariController::class, 'index'])->name('index');
    Route::post('/', [PadhadhikariController::class, 'store'])->name('store');
});

Route::prefix('staff-recruitment')->name('recruitment.')->group(function () {
    Route::get('/', [StaffRecruitmentController::class, 'index'])->name('index');
    Route::post('/jobs', [StaffRecruitmentController::class, 'storeJob'])->name('jobs.store');
    Route::post('/applications/{application}/stage', [StaffRecruitmentController::class, 'updateStage'])->name('applications.stage');
});

// Universal Data Import Center Routes (Excel / CSV & External Database)
Route::prefix('data-import')->name('data-import.')->group(function () {
    Route::get('/', [DataImportController::class, 'index'])->name('index');
    Route::post('/excel', [DataImportController::class, 'importExcel'])->name('excel');
    Route::post('/sql', [DataImportController::class, 'importSqlDump'])->name('sql');
    Route::post('/database-test', [DataImportController::class, 'testDatabase'])->name('database.test');
    Route::post('/database-sync', [DataImportController::class, 'importDatabase'])->name('database.sync');
    Route::get('/sample/{entity}', [DataImportController::class, 'downloadSample'])->name('sample');
    Route::post('/demo-seed', [DataImportController::class, 'seedHealthcareDemo'])->name('demo-seed');
});

// Matrimonial & Community Directory Bio-Data Routes
Route::prefix('matrimonial')->name('matrimonial.')->group(function () {
    Route::get('/', [MemberController::class, 'index'])->name('index');
    Route::get('/directory', [MemberController::class, 'index'])->name('directory');
    Route::get('/biodata', [MemberController::class, 'index'])->name('biodata');
    Route::get('/verified', [MemberController::class, 'verified'])->name('verified');
    Route::get('/shortlist', [MemberController::class, 'shortlistIndex'])->name('shortlist.index');
    Route::get('/export', [MemberController::class, 'export'])->name('export');
    Route::get('/sample-csv', [MemberController::class, 'downloadSample'])->name('sample');
    Route::post('/import', [MemberController::class, 'import'])->name('import');
    Route::post('/members', [MemberController::class, 'store'])->name('members.store');
    Route::post('/members/{member}/verify', [MemberVerificationController::class, 'approve'])->name('members.verify');
    Route::post('/members/{member}/reject', [MemberVerificationController::class, 'reject'])->name('members.reject');
    Route::post('/members/{member}/shortlist', [MemberController::class, 'shortlist'])->name('members.shortlist');
    Route::post('/members/{member}/interactions', [MemberController::class, 'addInteraction'])->name('members.interactions');
});

Route::get('/online-users', [OnlineUserController::class, 'index'])->name('online-users');
Route::post('/online-users', [OnlineUserController::class, 'store'])->name('online-users.store');
Route::get('/global-search', GlobalSearchController::class)->name('global-search');

// Industry Module Routes (Preventing 404 Errors)
Route::get('/leads', [CrmSalesPanelController::class, 'index'])->name('leads.index');
Route::get('/contacts', [OnlineUserController::class, 'index'])->name('contacts.index');

Route::prefix('companies')->name('companies.')->group(function () {
    Route::get('/', [CompanyController::class, 'index'])->name('index');
    Route::post('/', [CompanyController::class, 'store'])->name('store');
    Route::put('/{company}', [CompanyController::class, 'update'])->name('update');
    Route::delete('/{company}', [CompanyController::class, 'destroy'])->name('destroy');
});

// Education & Training Modules
Route::get('/courses', [TenantDatabaseManagerController::class, 'index'])->name('courses.index');
Route::get('/applications', [TenantDatabaseManagerController::class, 'index'])->name('applications.index');
Route::get('/admissions', [TenantDatabaseManagerController::class, 'index'])->name('admissions.index');
Route::get('/counselors', EmployeeController::class)->name('counselors.index');
Route::get('/fees', [DealController::class, 'index'])->name('fees.index');

// Recruitment & Staffing Modules
Route::get('/jobs', [StaffRecruitmentController::class, 'index'])->name('jobs.index');
Route::get('/interviews', TaskDashboardController::class)->name('interviews.index');
Route::get('/offers', [DealController::class, 'index'])->name('offers.index');
Route::get('/placements', [DealController::class, 'index'])->name('placements.index');

// Dynamic CRM Directory Hub Aliases
Route::get('/records', [TenantDatabaseManagerController::class, 'index'])->name('crm.records');
Route::get('/directory', function () {
    $user = auth()->user();
    $tenantId = session('tenant_id') ?? $user?->tenant_id;
    $tenant = $tenantId ? \App\Models\Tenant::with('industry')->find($tenantId) : null;
    $industrySlug = $tenant?->industry?->slug ?? \App\Models\TenantSetting::getByKey('industry_slug', 'education', $tenantId);

    if ($industrySlug === 'real-estate') {
        return redirect()->route('properties.index');
    }
    if ($industrySlug === 'matrimonial') {
        return redirect()->route('matrimonial.directory');
    }
    return redirect()->route('tenant.crm-records.index');
})->name('directory');

Route::get('/{industry}/directory', function ($industry) {
    if ($industry === 'matrimonial') {
        return app(MemberController::class)->index(request());
    }
    if ($industry === 'real-estate') {
        return redirect()->route('properties.index');
    }
    return redirect()->route('tenant.crm-records.index');
})->where('industry', '[a-zA-Z0-9_\-]+');

// Real Estate Properties & Rental Listings Routes
Route::prefix('properties')->name('properties.')->group(function () {
    Route::get('/', [PropertyController::class, 'index'])->name('index');
    Route::post('/', [PropertyController::class, 'store'])->name('store');
    Route::patch('/{property}/status', [PropertyController::class, 'updateStatus'])->name('update-status');
    Route::get('/export', [PropertyController::class, 'export'])->name('export');
    Route::get('/sample-csv', [PropertyController::class, 'downloadSample'])->name('sample');
    Route::post('/import', [PropertyController::class, 'import'])->name('import');
});

Route::get('/projects', [PropertyController::class, 'index'])->name('projects.index');
Route::get('/site-visits', TaskDashboardController::class)->name('site-visits.index');
Route::get('/bookings', [PropertyController::class, 'index'])->name('bookings.index');
Route::get('/agents', EmployeeController::class)->name('agents.index');
Route::get('/tasks', TaskDashboardController::class)->name('tasks.index');
Route::get('/reports', DashboardController::class)->name('reports.index');
Route::get('/cases', [DealController::class, 'index'])->name('cases.index');
Route::get('/hearings', TaskDashboardController::class)->name('hearings.index');
Route::get('/documents', TaskDashboardController::class)->name('documents.index');
Route::get('/doctors', EmployeeController::class)->name('doctors.index');
Route::get('/appointments', TaskDashboardController::class)->name('appointments.index');
Route::get('/departments', EmployeeController::class)->name('departments.index');
Route::get('/treatments', [DealController::class, 'index'])->name('treatments.index');
Route::get('/follow-ups', TaskDashboardController::class)->name('follow-ups.index');
Route::get('/biodata', [MemberController::class, 'index'])->name('biodata.direct');
Route::get('/padhadhikari', [PadhadhikariController::class, 'index'])->name('padhadhikari.direct');
Route::get('/broadcast', [BroadcastMessageController::class, 'index'])->name('broadcast.direct');
Route::get('/recruitment', [StaffRecruitmentController::class, 'index'])->name('recruitment.direct');
Route::get('/settings', [SystemSettingController::class, 'index'])->name('settings.direct');

// AI CRM Studio & Workflow Modifier (Paid Users Feature)
Route::prefix('ai-crm-modifier')->name('ai.crm.modifier.')->group(function () {
    Route::get('/', [AiCrmModifierController::class, 'index'])->name('index');
    Route::post('/generate', [AiCrmModifierController::class, 'generate'])->name('generate');
    Route::post('/apply', [AiCrmModifierController::class, 'apply'])->name('apply');
    Route::post('/activate-paid', [AiCrmModifierController::class, 'activatePaid'])->name('activate-paid');
});

// Universal Data Import Hub (Excel & Database)
Route::prefix('data-import')->name('data-import.')->group(function () {
    Route::get('/', [DataImportController::class, 'index'])->name('index');
    Route::post('/excel', [DataImportController::class, 'importExcel'])->name('excel');
    Route::post('/database-test', [DataImportController::class, 'testDatabaseConnection'])->name('database-test');
    Route::post('/database-sync', [DataImportController::class, 'syncDatabaseTable'])->name('database-sync');
    Route::get('/sample/{entity}', [DataImportController::class, 'downloadSample'])->name('sample');
    Route::post('/demo-seed', [DataImportController::class, 'seedHealthcareDemo'])->name('demo-seed');
});

Route::prefix('deals')->name('deals.')->group(function () {
    Route::get('/', [DealController::class, 'index'])->name('index');
    Route::post('/', [DealController::class, 'store'])->name('store');
    Route::patch('/{deal}/stage', [DealController::class, 'updateStage'])->name('update-stage');
});

Route::prefix('tasks')->name('tasks.')->group(function () {
    Route::post('/', [TaskController::class, 'store'])->name('store');
    Route::patch('/{task}/status', [TaskController::class, 'updateStatus'])->name('update-status');
});

// Multi-Tenant RBAC, Navigation Customizer & Official Mail Routes
Route::prefix('tenant/settings')->name('tenant.settings.')->group(function () {
    Route::get('/roles', [RoleAndPermissionController::class, 'index'])->name('roles.index');
    Route::post('/roles', [RoleAndPermissionController::class, 'store'])->name('roles.store');
    Route::put('/roles/{role}', [RoleAndPermissionController::class, 'update'])->name('roles.update');
    Route::delete('/roles/{role}', [RoleAndPermissionController::class, 'destroy'])->name('roles.destroy');

    Route::get('/navigation', [NavigationCustomizerController::class, 'index'])->name('navigation.index');
    Route::post('/navigation', [NavigationCustomizerController::class, 'update'])->name('navigation.update');
    Route::delete('/navigation/{id}', [NavigationCustomizerController::class, 'destroy'])->name('navigation.destroy');

    Route::get('/integration', [WebsiteIntegrationController::class, 'index'])->name('integration.index');

    Route::get('/mail', [TenantMailSettingController::class, 'index'])->name('mail.index');
    Route::post('/mail', [TenantMailSettingController::class, 'update'])->name('mail.update');
    Route::post('/mail/send-direct', [TenantMailSettingController::class, 'sendDirectMail'])->name('mail.send-direct');
    Route::post('/mail/send-test', [TenantMailSettingController::class, 'sendTestMail'])->name('mail.send-test');
});

// Session & Cookie Management Routes
Route::prefix('settings/session-cookies')->name('session-cookies.')->group(function () {
    Route::get('/', [SessionCookieController::class, 'index'])->name('index');
    Route::get('/active-sessions', [SessionCookieController::class, 'getActiveSessionsApi'])->name('api-sessions');
    Route::post('/consent', [SessionCookieController::class, 'updateConsent'])->name('consent');
    Route::post('/preferences', [SessionCookieController::class, 'updatePreferences'])->name('preferences');
    Route::post('/revoke-sessions', [SessionCookieController::class, 'revokeOtherSessions'])->name('revoke-sessions');
    Route::delete('/session/{id}', [SessionCookieController::class, 'destroySession'])->name('destroy');
    Route::post('/clear-cookies', [SessionCookieController::class, 'clearCookies'])->name('clear-cookies');
});

// User Panel CRM System Settings & Website Integration Hub
Route::prefix('system-settings')->name('system-settings.')->group(function () {
    Route::get('/', [SystemSettingController::class, 'index'])->name('index');
    Route::post('/general', [SystemSettingController::class, 'updateGeneral'])->name('general');
    Route::post('/password', [SystemSettingController::class, 'updatePassword'])->name('password');
    Route::post('/payments', [SystemSettingController::class, 'updatePayments'])->name('payments');
    Route::post('/social', [SystemSettingController::class, 'updateSocial'])->name('social');
});

// ╔══════════════════════════════════════════════════════════════════╗
// ║  Dynamic CRM Engine — Metadata-Driven Multi-Table CRUD Routes  ║
// ╚══════════════════════════════════════════════════════════════════╝
Route::middleware(['auth'])->prefix('dynamic-crm')->name('dynamic-crm.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [DynamicCrmDashboardController::class, 'index'])->name('dashboard');

    // Database Upload
    Route::get('/database/upload', [DatabaseUploadController::class, 'show'])->name('database.upload');
    Route::post('/database/upload', [DatabaseUploadController::class, 'upload'])->name('database.upload.store');
    Route::get('/database/status', [DatabaseUploadController::class, 'status'])->name('database.status');

    // Global Search
    Route::get('/search', [DynamicCrmSearchController::class, 'search'])->name('search');

    // Table Configuration (before CRUD catch-all)
    Route::get('/configure/{table}', [TableConfigurationController::class, 'show'])->name('configure');
    Route::put('/configure/{table}', [TableConfigurationController::class, 'update'])->name('configure.update');

    // Dynamic CRUD — ValidateDynamicTable middleware ensures table name is whitelisted
    Route::middleware([ValidateDynamicTable::class])->group(function () {
        Route::get('/{table}', [DynamicCrudController::class, 'index'])->name('table.index');
        Route::get('/{table}/create', [DynamicCrudController::class, 'create'])->name('table.create');
        Route::post('/{table}', [DynamicCrudController::class, 'store'])->name('table.store');
        Route::get('/{table}/{id}', [DynamicCrudController::class, 'show'])->name('table.show')->where('id', '[0-9]+');
        Route::get('/{table}/{id}/edit', [DynamicCrudController::class, 'edit'])->name('table.edit')->where('id', '[0-9]+');
        Route::put('/{table}/{id}', [DynamicCrudController::class, 'update'])->name('table.update')->where('id', '[0-9]+');
        Route::delete('/{table}/{id}', [DynamicCrudController::class, 'destroy'])->name('table.destroy')->where('id', '[0-9]+');
    });
});

// Graceful fallback for any undefined or custom module routes (prevents 404 popups)
Route::fallback(function () {
    $user = auth()->user();
    $tenantId = session('tenant_id') ?? $user?->tenant_id;
    $tenant = $tenantId ? \App\Models\Tenant::with('industry')->find($tenantId) : null;
    $industrySlug = $tenant?->industry?->slug ?? \App\Models\TenantSetting::getByKey('industry_slug', 'education', $tenantId);

    if ($industrySlug === 'real-estate') {
        return redirect()->route('properties.index');
    }
    if ($industrySlug === 'matrimonial') {
        return redirect()->route('matrimonial.directory');
    }
    return redirect()->route('tenant.crm-records.index');
});


