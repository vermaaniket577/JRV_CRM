<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\NavigationItem;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CRMDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Default Sales Users
        $user = User::firstOrCreate(
            ['email' => 'admin@crm.test'],
            [
                'name' => 'Alex Mercer',
                'password' => Hash::make('password'),
                'status' => 'active',
            ]
        );

        $adminUser = User::firstOrCreate(
            ['email' => 'admin@acme-saas.com'],
            [
                'name' => 'Sarah Connor',
                'password' => Hash::make('password'),
                'is_tenant_admin' => true,
                'status' => 'active',
            ]
        );

        // 2. Create Customizable Navigation Items
        $navItems = [
            ['key' => 'broadcast', 'label' => 'BroadCast Message', 'route' => '/broadcast-message', 'icon' => 'SignalIcon', 'display_order' => 1],
            ['key' => 'online_user', 'label' => 'Online User', 'route' => '/online-users', 'icon' => 'UserIcon', 'display_order' => 2],
            ['key' => 'auto_update', 'label' => 'Auto Update', 'route' => '/auto-update', 'icon' => 'ArrowPathIcon', 'display_order' => 3],
            ['key' => 'staff_recruit', 'label' => 'Staff Recruit', 'route' => '/staff-recruitment', 'icon' => 'BriefcaseIcon', 'display_order' => 4],
            ['key' => 'staff_management', 'label' => 'Staff Management', 'route' => '/employee-management', 'icon' => 'UserGroupIcon', 'display_order' => 5],
            ['key' => 'task_dashboard', 'label' => 'Task Dashboard', 'route' => '/task-dashboard', 'icon' => 'ClipboardDocumentListIcon', 'display_order' => 6],
            ['key' => 'universal', 'label' => 'Universal Data', 'route' => '/online-users', 'icon' => 'GlobeAltIcon', 'display_order' => 7],
            ['key' => 'data_import', 'label' => 'Data Import Hub', 'route' => '/data-import', 'icon' => 'TableCellsIcon', 'display_order' => 8],
            ['key' => 'ai_crm', 'label' => 'AI CRM Studio', 'route' => '/ai-crm-modifier', 'icon' => 'SparklesIcon', 'display_order' => 9],
        ];

        foreach ($navItems as $item) {
            NavigationItem::updateOrCreate(['key' => $item['key']], $item);
        }

        // 3. Create Standard Sales Pipeline
        $pipeline = Pipeline::create([
            'name' => 'Standard Sales Pipeline',
            'is_default' => true,
            'is_active' => true,
        ]);

        $stages = [
            ['name' => 'New Lead', 'display_order' => 1, 'win_probability' => 10, 'stage_type' => 'open'],
            ['name' => 'Qualified Contact', 'display_order' => 2, 'win_probability' => 30, 'stage_type' => 'open'],
            ['name' => 'Proposal Sent', 'display_order' => 3, 'win_probability' => 60, 'stage_type' => 'open'],
            ['name' => 'Negotiation', 'display_order' => 4, 'win_probability' => 80, 'stage_type' => 'open'],
            ['name' => 'Closed Won', 'display_order' => 5, 'win_probability' => 100, 'stage_type' => 'won'],
            ['name' => 'Closed Lost', 'display_order' => 6, 'win_probability' => 0, 'stage_type' => 'lost'],
        ];

        $createdStages = [];
        foreach ($stages as $stageData) {
            $createdStages[] = PipelineStage::create(array_merge($stageData, ['pipeline_id' => $pipeline->id]));
        }

        // 4. Create Sample Companies & Contacts
        $acme = Company::create([
            'name' => 'Acme Corporation',
            'domain' => 'acme.com',
            'industry' => 'Software & Tech',
            'annual_revenue' => 1500000.00,
            'city' => 'San Francisco',
            'country' => 'USA',
            'owner_id' => $user->id,
        ]);

        $john = Contact::create([
            'company_id' => $acme->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@acme.com',
            'phone' => '+1 (555) 234-5678',
            'job_title' => 'VP of Engineering',
            'status' => 'prospect',
            'owner_id' => $user->id,
        ]);

        // 5. Create Sample Deals
        Deal::create([
            'title' => 'Acme Enterprise License Upgrade',
            'value' => 45000.00,
            'currency' => 'INR',
            'pipeline_id' => $pipeline->id,
            'stage_id' => $createdStages[2]->id,
            'company_id' => $acme->id,
            'contact_id' => $john->id,
            'assigned_to' => $user->id,
            'expected_close_date' => now()->addDays(14),
        ]);

        Deal::create([
            'title' => 'Cloud Migration Consulting',
            'value' => 18500.00,
            'currency' => 'INR',
            'pipeline_id' => $pipeline->id,
            'stage_id' => $createdStages[0]->id,
            'company_id' => $acme->id,
            'contact_id' => $john->id,
            'assigned_to' => $user->id,
            'expected_close_date' => now()->addDays(30),
        ]);

        // 6. Create Tasks
        Task::create([
            'title' => 'Send revised proposal to John Doe',
            'description' => 'Include custom SLA addendum as requested during call.',
            'due_at' => now()->addDays(1),
            'priority' => 'high',
            'status' => 'pending',
            'assigned_to' => $adminUser->id,
            'created_by' => $user->id,
        ]);
    }
}
