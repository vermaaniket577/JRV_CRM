<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CrmMenuSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'id' => 1,
                'key' => 'app',
                'label' => 'App Dashboard',
                'route' => '/',
                'icon' => 'HomeIcon',
                'display_order' => 1,
                'is_enabled' => true,
            ],
            [
                'id' => 2,
                'key' => 'crm_sales',
                'label' => 'CRM Sales Panel',
                'route' => '/crm-sales-panel',
                'icon' => 'ChartBarIcon',
                'display_order' => 2,
                'is_enabled' => true,
            ],
            [
                'id' => 3,
                'key' => 'crm_selling',
                'label' => 'CRM Selling Panel',
                'route' => '/crm-selling-panel',
                'icon' => 'RocketLaunchIcon',
                'display_order' => 3,
                'is_enabled' => true,
            ],
            [
                'id' => 4,
                'key' => 'online_user',
                'label' => 'Customer Directory',
                'route' => '/online-users',
                'icon' => 'UserIcon',
                'display_order' => 4,
                'is_enabled' => true,
            ],
            [
                'id' => 5,
                'key' => 'deals_pipeline',
                'label' => 'Deals Pipeline',
                'route' => '/crm-sales-panel?tab=deals',
                'icon' => 'FunnelIcon',
                'display_order' => 5,
                'is_enabled' => true,
            ],
            [
                'id' => 6,
                'key' => 'subscriptions',
                'label' => 'Subscriptions',
                'route' => '/crm-sales-panel?tab=subscriptions',
                'icon' => 'CreditCardIcon',
                'display_order' => 6,
                'is_enabled' => true,
            ],
            [
                'id' => 7,
                'key' => 'leads_data',
                'label' => 'Leads Data',
                'route' => '/crm-sales-panel?tab=leads',
                'icon' => 'UserGroupIcon',
                'display_order' => 7,
                'is_enabled' => true,
            ],
            [
                'id' => 8,
                'key' => 'staff_management',
                'label' => 'Staff Management',
                'route' => '/employee-management',
                'icon' => 'UserGroupIcon',
                'display_order' => 8,
                'is_enabled' => true,
            ],
            [
                'id' => 9,
                'key' => 'task_dashboard',
                'label' => 'Task Dashboard',
                'route' => '/task-dashboard',
                'icon' => 'ClipboardDocumentListIcon',
                'display_order' => 9,
                'is_enabled' => true,
            ],
            [
                'id' => 10,
                'key' => 'broadcast',
                'label' => 'BroadCast Message',
                'route' => '/broadcast-message',
                'icon' => 'SignalIcon',
                'display_order' => 10,
                'is_enabled' => true,
            ],
        ];

        foreach ($items as $item) {
            DB::table('navigation_items')->updateOrInsert(
                ['id' => $item['id']],
                array_merge($item, [
                    'updated_at' => now(),
                    'created_at' => now(),
                ])
            );
        }
    }
}
