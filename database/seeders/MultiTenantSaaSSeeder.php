<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MultiTenantSaaSSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed Global Section Permissions
        $sectionsPermissions = [
            'Leads' => ['view:leads', 'create:leads', 'edit:leads', 'delete:leads'],
            'Deals' => ['view:deals', 'create:deals', 'edit:deals', 'delete:deals'],
            'Finance' => ['view:finance', 'manage:invoices'],
            'Administration' => ['manage:users', 'manage:roles', 'access:settings'],
        ];

        $permissionIds = [];
        foreach ($sectionsPermissions as $section => $perms) {
            foreach ($perms as $permName) {
                $p = Permission::firstOrCreate([
                    'name' => $permName,
                    'section' => $section,
                    'guard_name' => 'web',
                ]);
                $permissionIds[$section][] = $p->id;
            }
        }

        // 2. Seed Subscription Plans
        $proPlan = SubscriptionPlan::firstOrCreate(
            ['slug' => 'pro-plan'],
            [
                'name' => 'Pro Plan',
                'price_monthly' => 49.00,
                'price_yearly' => 490.00,
                'max_users' => 15,
                'max_deals' => 500,
                'max_storage_mb' => 5120,
            ]
        );

        // 3. Seed Acme Tenant Organization
        $acmeTenant = Tenant::firstOrCreate(
            ['slug' => 'acme-saas'],
            [
                'uuid' => Str::uuid(),
                'name' => 'Acme Inc SaaS',
                'primary_color_hex' => '#4F46E5',
                'currency' => 'USD',
                'status' => 'active',
            ]
        );

        // Create Active Subscription
        \Illuminate\Support\Facades\DB::table('subscriptions')->insert([
            'tenant_id' => $acmeTenant->id,
            'plan_id' => $proPlan->id,
            'status' => 'active',
            'current_period_starts_at' => now(),
            'current_period_ends_at' => now()->addYear(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 4. Create Tenant Admin User
        $tenantAdmin = User::create([
            'tenant_id' => $acmeTenant->id,
            'name' => 'Sarah Connor',
            'email' => 'admin@acme-saas.com',
            'password' => bcrypt('password'),
            'is_tenant_admin' => true,
            'status' => 'active',
        ]);

        // 5. Create Custom Tenant Role (Sales Specialist)
        session(['tenant_id' => $acmeTenant->id]);

        $salesRole = Role::create([
            'tenant_id' => $acmeTenant->id,
            'name' => 'Sales Specialist',
            'guard_name' => 'web',
        ]);

        // Assign Leads and Deals permissions
        $salesPerms = array_merge($permissionIds['Leads'], $permissionIds['Deals']);
        $salesRole->permissions()->sync($salesPerms);
    }
}
