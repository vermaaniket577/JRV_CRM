<?php

use App\Http\Controllers\Api\v1\PublicMemberApiController;
use App\Http\Controllers\AppDashboardController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\AutoUpdateController;
use App\Http\Controllers\BroadcastMessageController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DealController;
use App\Http\Controllers\EmbedFormController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\GlobalSearchController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\MemberVerificationController;
use App\Http\Controllers\OnlineUserController;
use App\Http\Controllers\Onboarding\OnboardingController;
use App\Http\Controllers\PadhadhikariController;
use App\Http\Controllers\StaffRecruitmentController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskDashboardController;
use App\Http\Controllers\Tenant\NavigationCustomizerController;
use App\Http\Controllers\Tenant\RoleAndPermissionController;
use App\Http\Controllers\Tenant\TenantMailSettingController;
use App\Http\Controllers\CrmSalesPanelController;
use App\Http\Controllers\CrmSellingPanelController;
use App\Http\Controllers\AdminPanelController;
use App\Http\Controllers\AdminLoginController;
use App\Http\Middleware\EnsureMasterAdmin;
use App\Http\Controllers\Tenant\WebsiteIntegrationController;
use Illuminate\Support\Facades\Route;

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

// Onboarding Routes
Route::middleware('auth')->prefix('onboarding')->name('onboarding.')->group(function () {
    Route::get('/', [OnboardingController::class, 'index'])->name('index');
    Route::get('/business-types', [OnboardingController::class, 'getBusinessTypes'])->name('business-types');
    Route::post('/complete', [OnboardingController::class, 'complete'])->name('complete');
});

Route::get('/', AppDashboardController::class)->name('home');
Route::get('/app', AppDashboardController::class)->name('app.dashboard');
Route::get('/dashboard', DashboardController::class)->name('dashboard');
Route::get('/crm-overview', DashboardController::class)->name('crm.overview');

Route::get('/task-dashboard', TaskDashboardController::class)->name('task-dashboard');

Route::get('/employee-management', EmployeeController::class)->name('employee-management');
Route::post('/employee-management', [EmployeeController::class, 'store'])->name('employee-management.store');
Route::put('/employee-management/{employee}', [EmployeeController::class, 'update'])->name('employee-management.update');
Route::delete('/employee-management/{employee}', [EmployeeController::class, 'destroy'])->name('employee-management.destroy');

// Standalone Embed Bio-data Registration Form Routes (For External Website iFrames)
Route::get('/embed/register', [EmbedFormController::class, 'show'])->name('embed.register');
Route::post('/embed/register', [EmbedFormController::class, 'store'])->name('embed.store');

// Public REST API Endpoints (CORS Enabled for Website Integration)
Route::prefix('api/v1')->group(function () {
    Route::get('/members/search', [PublicMemberApiController::class, 'search']);
    Route::post('/members/register', [PublicMemberApiController::class, 'register']);
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

// Matrimonial & Community Directory Bio-Data Routes
Route::prefix('matrimonial')->name('matrimonial.')->group(function () {
    Route::get('/directory', [MemberController::class, 'index'])->name('directory');
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
Route::get('/companies', [CrmSellingPanelController::class, 'index'])->name('companies.index');
Route::get('/tasks', TaskDashboardController::class)->name('tasks.index');
Route::get('/reports', DashboardController::class)->name('reports.index');
Route::get('/cases', [DealController::class, 'index'])->name('cases.index');
Route::get('/hearings', TaskDashboardController::class)->name('hearings.index');
Route::get('/documents', TaskDashboardController::class)->name('documents.index');
Route::get('/doctors', [EmployeeController::class, 'index'])->name('doctors.index');
Route::get('/appointments', TaskDashboardController::class)->name('appointments.index');
Route::get('/departments', [EmployeeController::class, 'index'])->name('departments.index');
Route::get('/treatments', [DealController::class, 'index'])->name('treatments.index');
Route::get('/follow-ups', TaskDashboardController::class)->name('follow-ups.index');

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
