<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CrmSalesPanelSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed CRM Plans
        DB::table('crm_plans')->insertOrIgnore([
            [
                'id' => 1,
                'name' => 'Starter Plan',
                'slug' => 'starter',
                'price_monthly' => 49.00,
                'price_annual' => 490.00,
                'max_users' => 5,
                'max_contacts' => 1000,
                'features' => json_encode(['Basic CRM', 'Email Templates', '5 Users', 'Community Support']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'name' => 'Pro Plan',
                'slug' => 'pro',
                'price_monthly' => 149.00,
                'price_annual' => 1490.00,
                'max_users' => 25,
                'max_contacts' => 10000,
                'features' => json_encode(['Multi-Industry Engine', 'Automation Workflows', '25 Users', 'Priority Support']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'name' => 'Enterprise Plan',
                'slug' => 'enterprise',
                'price_monthly' => 399.00,
                'price_annual' => 3990.00,
                'max_users' => 100,
                'max_contacts' => 100000,
                'features' => json_encode(['Custom Pipeline Engine', 'Dedicated Account Manager', '100 Users', '24/7 Phone Support']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // 2. Seed Transactions (Recent CRM Sales)
        $sales = [
            ['name' => 'Acme Global Solutions', 'email' => 'contact@acme.com', 'tier' => 'Enterprise', 'amount' => 399.00, 'status' => 'Paid', 'date' => now()->subDays(1)],
            ['name' => 'St. Xavier School', 'email' => 'info@stxaviers.edu', 'tier' => 'Pro', 'amount' => 149.00, 'status' => 'Paid', 'date' => now()->subDays(2)],
            ['name' => 'Apex Health Clinic', 'email' => 'admin@apexhealth.org', 'tier' => 'Pro', 'amount' => 149.00, 'status' => 'Paid', 'date' => now()->subDays(3)],
            ['name' => 'Nexus Talent Agency', 'email' => 'hr@nexustalent.io', 'tier' => 'Starter', 'amount' => 49.00, 'status' => 'Pending', 'date' => now()->subDays(4)],
            ['name' => 'Vanguard Real Estate', 'email' => 'sales@vanguardrealty.com', 'tier' => 'Enterprise', 'amount' => 399.00, 'status' => 'Paid', 'date' => now()->subDays(5)],
            ['name' => 'CloudScale Software', 'email' => 'billing@cloudscale.tech', 'tier' => 'Pro', 'amount' => 149.00, 'status' => 'Paid', 'date' => now()->subDays(6)],
            ['name' => 'BrightMind Tutoring', 'email' => 'hello@brightmind.com', 'tier' => 'Starter', 'amount' => 49.00, 'status' => 'Refunded', 'date' => now()->subDays(7)],
            ['name' => 'Metro Logistics Corp', 'email' => 'ops@metrologistics.com', 'tier' => 'Enterprise', 'amount' => 399.00, 'status' => 'Paid', 'date' => now()->subDays(8)],
            ['name' => 'Dr. Sharma Dental Hub', 'email' => 'drsharma@dentalhub.in', 'tier' => 'Starter', 'amount' => 49.00, 'status' => 'Paid', 'date' => now()->subDays(9)],
            ['name' => 'GreenField Organics', 'email' => 'support@greenfield.org', 'tier' => 'Pro', 'amount' => 149.00, 'status' => 'Pending', 'date' => now()->subDays(10)],
        ];

        foreach ($sales as $idx => $s) {
            DB::table('crm_transactions')->insertOrIgnore([
                'id' => $idx + 1,
                'transaction_code' => 'TXN-' . Str::upper(Str::random(8)),
                'customer_name' => $s['name'],
                'customer_email' => $s['email'],
                'plan_tier' => $s['tier'],
                'amount' => $s['amount'],
                'payment_status' => $s['status'],
                'payment_method' => 'Credit Card',
                'purchase_date' => $s['date'],
                'created_at' => $s['date'],
                'updated_at' => $s['date'],
            ]);
        }

        // 3. Seed Pipeline Deals (Leads)
        $leads = [
            ['customer_name' => 'Global Logistics Inc', 'company' => 'Global Logistics', 'email' => 'deal@globallog.com', 'industry' => 'Logistics', 'stage' => 'Proposal', 'mrr' => 399.00],
            ['customer_name' => 'BioTech Research Lab', 'company' => 'BioTech Labs', 'email' => 'info@biotech.org', 'industry' => 'Healthcare', 'stage' => 'Qualified', 'mrr' => 149.00],
            ['customer_name' => 'Horizon Academy', 'company' => 'Horizon Edu', 'email' => 'admin@horizon.edu', 'industry' => 'Education', 'stage' => 'Proposal', 'mrr' => 149.00],
            ['customer_name' => 'Skyline Properties', 'company' => 'Skyline Realty', 'email' => 'sales@skylinerealty.com', 'industry' => 'Real Estate', 'stage' => 'Won', 'mrr' => 399.00],
            ['customer_name' => 'FinTech Innovators', 'company' => 'FinTech Ltd', 'email' => 'hello@fintech.io', 'industry' => 'IT / Software', 'stage' => 'New', 'mrr' => 149.00],
            ['customer_name' => 'Elite HR Consultants', 'company' => 'Elite HR', 'email' => 'jobs@elitehr.com', 'industry' => 'Recruitment', 'stage' => 'Qualified', 'mrr' => 49.00],
        ];

        foreach ($leads as $idx => $l) {
            DB::table('crm_sales_leads')->insertOrIgnore([
                'id' => $idx + 1,
                'customer_name' => $l['customer_name'],
                'company_name' => $l['company'],
                'email' => $l['email'],
                'industry' => $l['industry'],
                'deal_stage' => $l['stage'],
                'estimated_mrr' => $l['mrr'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
