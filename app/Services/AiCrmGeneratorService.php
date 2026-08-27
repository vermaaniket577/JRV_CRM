<?php

namespace App\Services;

use App\Models\NavigationItem;
use App\Models\TenantCustomColumn;
use App\Models\TenantSetting;
use Illuminate\Support\Str;

class AiCrmGeneratorService
{
    /**
     * Preset AI Templates tailored to sector or universal
     */
    public function getPresetTemplates(?string $industrySlug = null): array
    {
        $allPresets = [
            // 1. INSURANCE SECTOR PRESETS
            [
                'id' => 'health_motor_insurance',
                'industry' => 'insurance',
                'name' => 'Health & Motor Insurance Hub',
                'icon' => '🛡️',
                'color' => 'teal',
                'description' => 'Policy issuance, cashless TPA hospital desk, and motor claim surveyor dispatch.',
                'prompt' => 'Transform my CRM into a comprehensive Health and Auto Insurance Platform with policy issuance stages, cashless TPA hospital desk, and surveyor claim triage.',
                'specialty' => 'General & Health Insurance',
                'pipeline_stages' => [
                    ['name' => 'Quote Generated & Proposal', 'color' => 'teal', 'sla_hours' => 4],
                    ['name' => 'Medical / Vehicle Inspection', 'color' => 'indigo', 'sla_hours' => 24],
                    ['name' => 'Underwriting Risk Assessment', 'color' => 'purple', 'sla_hours' => 12],
                    ['name' => 'Policy Active & Bound', 'color' => 'emerald', 'sla_hours' => 1],
                    ['name' => 'Claim Triage & Cashless Despatch', 'color' => 'rose', 'sla_hours' => 6],
                    ['name' => 'Settlement & Renewal Recall', 'color' => 'cyan', 'sla_hours' => 720],
                ],
                'custom_fields' => [
                    ['key' => 'policy_number', 'label' => 'Policy Number', 'type' => 'text', 'default' => 'POL-2026-XXXX'],
                    ['key' => 'sum_insured', 'label' => 'Sum Insured (₹ / $)', 'type' => 'number', 'default' => '1000000'],
                    ['key' => 'premium_amount', 'label' => 'Annual Premium', 'type' => 'number', 'default' => '22500'],
                    ['key' => 'tpa_cashless_desk', 'label' => 'TPA Cashless Network Hospital', 'type' => 'text', 'default' => 'Apollo Specialty Hospital'],
                    ['key' => 'claim_status', 'label' => 'Claim Status', 'type' => 'select', 'options' => ['No Active Claim', 'Cashless Approved', 'Surveyor Review', 'Settlement Cleared', 'Claim Denied']],
                ],
                'nav_items' => [
                    ['key' => 'dashboard', 'label' => 'Insurance Command', 'route' => '/', 'icon' => 'ChartBarIcon'],
                    ['key' => 'policyholders', 'label' => 'Policyholders', 'route' => '/contacts', 'icon' => 'UserGroupIcon'],
                    ['key' => 'claims', 'label' => 'Claims Pipeline', 'route' => '/deals', 'icon' => 'Square3Stack3DIcon'],
                    ['key' => 'staff_recruit', 'label' => 'Underwriters & Staff', 'route' => '/staff-recruitment', 'icon' => 'BriefcaseIcon'],
                    ['key' => 'data_import', 'label' => 'Data Import Hub', 'route' => '/data-import', 'icon' => 'TableCellsIcon'],
                    ['key' => 'ai_crm', 'label' => 'AI CRM Studio', 'route' => '/ai-crm-modifier', 'icon' => 'SparklesIcon'],
                ],
                'automations' => [
                    'Automated policy renewal WhatsApp alert 30 days before expiration date',
                    'Instant alert to Chief Underwriter if proposal sum insured > ₹1,00,00,000',
                    'Auto-generate digitally signed Policy Schedule PDF upon premium clearance',
                ],
            ],
            [
                'id' => 'life_term_insurance',
                'industry' => 'insurance',
                'name' => 'Term Life & Pension Hub',
                'icon' => '💼',
                'color' => 'indigo',
                'description' => 'Term life underwriting, tele-MER medical interviews, and nominee assignment.',
                'prompt' => 'Adapt my CRM for a Life Insurance Agency with term plan underwriting, tele-medical verification, and nominee allocation tracking.',
                'specialty' => 'Life & Pension Products',
                'pipeline_stages' => [
                    ['name' => 'Lead Inquiry & Term Quote', 'color' => 'indigo', 'sla_hours' => 6],
                    ['name' => 'Tele-MER (Medical Phone Check)', 'color' => 'cyan', 'sla_hours' => 24],
                    ['name' => 'Diagnostic Blood / ECG Report', 'color' => 'purple', 'sla_hours' => 48],
                    ['name' => 'Chief Underwriter Assessment', 'color' => 'amber', 'sla_hours' => 24],
                    ['name' => 'Policy Bound & Dispatched', 'color' => 'emerald', 'sla_hours' => 12],
                ],
                'custom_fields' => [
                    ['key' => 'term_cover_amount', 'label' => 'Term Cover Sum Insured', 'type' => 'select', 'options' => ['₹50 Lakhs', '₹1 Crore', '₹2 Crores', '₹5 Crores+']],
                    ['key' => 'smoker_status', 'label' => 'Tobacco / Nicotine Usage', 'type' => 'select', 'options' => ['Non-Smoker', 'Smoker (<10/day)', 'Smoker (>10/day)']],
                    ['key' => 'nominee_name', 'label' => 'Primary Nominee Name', 'type' => 'text', 'default' => 'Spouse / Parent'],
                ],
                'nav_items' => [
                    ['key' => 'dashboard', 'label' => 'Term Life Hub', 'route' => '/', 'icon' => 'ChartBarIcon'],
                    ['key' => 'policyholders', 'label' => 'Life Insured Members', 'route' => '/contacts', 'icon' => 'UserGroupIcon'],
                    ['key' => 'underwriting', 'label' => 'Underwriting Queue', 'route' => '/deals', 'icon' => 'Square3Stack3DIcon'],
                    ['key' => 'staff_recruit', 'label' => 'Agency Force', 'route' => '/staff-recruitment', 'icon' => 'BriefcaseIcon'],
                    ['key' => 'data_import', 'label' => 'Data Import Hub', 'route' => '/data-import', 'icon' => 'TableCellsIcon'],
                    ['key' => 'ai_crm', 'label' => 'AI CRM Studio', 'route' => '/ai-crm-modifier', 'icon' => 'SparklesIcon'],
                ],
                'automations' => [
                    'Auto-schedule at-home diagnostic blood collection on proposal submission',
                    'Send annual tax benefit certificate (80C / 10(10D)) at end of financial year',
                ],
            ],

            // 2. HEALTHCARE PRESETS
            [
                'id' => 'cardiology_clinic',
                'industry' => 'healthcare',
                'name' => 'Cardiology & Cath-Lab Center',
                'icon' => '❤️',
                'color' => 'rose',
                'description' => 'ECG triage, Angiography scheduling, Holter monitoring, and Cardiac ICU bed allocation.',
                'prompt' => 'Transform my CRM into a full-scale Cardiology and Cardiovascular Surgery Center with triage stages, risk factor scoring, and post-op rehabilitation tracking.',
                'specialty' => 'Cardiology & Cardiovascular Care',
                'pipeline_stages' => [
                    ['name' => 'Emergency / Outpatient Triage', 'color' => 'rose', 'sla_hours' => 2],
                    ['name' => 'ECG & Echo Diagnostics', 'color' => 'amber', 'sla_hours' => 12],
                    ['name' => 'Cardiologist Consultation', 'color' => 'indigo', 'sla_hours' => 24],
                    ['name' => 'Cath-Lab / Angiography', 'color' => 'purple', 'sla_hours' => 48],
                    ['name' => 'Cardiac ICU / Post-Op Care', 'color' => 'teal', 'sla_hours' => 72],
                    ['name' => 'Discharge & Cardiac Rehab', 'color' => 'emerald', 'sla_hours' => 168],
                ],
                'custom_fields' => [
                    ['key' => 'blood_pressure', 'label' => 'Blood Pressure (mmHg)', 'type' => 'text', 'default' => '120/80'],
                    ['key' => 'cardiac_risk_score', 'label' => 'Framingham Risk Score', 'type' => 'select', 'options' => ['Low (<10%)', 'Moderate (10-20%)', 'High (>20%)']],
                    ['key' => 'ecg_interpretation', 'label' => 'ECG Finding Summary', 'type' => 'text', 'default' => 'Normal Sinus Rhythm'],
                    ['key' => 'lead_cardiologist', 'label' => 'Assigned Cardiologist', 'type' => 'text', 'default' => 'Dr. Mehta, MD'],
                ],
                'nav_items' => [
                    ['key' => 'dashboard', 'label' => 'Cardiac Dashboard', 'route' => '/', 'icon' => 'ChartBarIcon'],
                    ['key' => 'patients', 'label' => 'Cardiac Patients', 'route' => '/contacts', 'icon' => 'UserGroupIcon'],
                    ['key' => 'cath_pipeline', 'label' => 'Cath-Lab Pipeline', 'route' => '/deals', 'icon' => 'Square3Stack3DIcon'],
                    ['key' => 'staff_recruit', 'label' => 'Medical Staff', 'route' => '/staff-recruitment', 'icon' => 'BriefcaseIcon'],
                    ['key' => 'data_import', 'label' => 'Data Import Hub', 'route' => '/data-import', 'icon' => 'TableCellsIcon'],
                    ['key' => 'ai_crm', 'label' => 'AI CRM Studio', 'route' => '/ai-crm-modifier', 'icon' => 'SparklesIcon'],
                ],
                'automations' => [
                    'Auto-notify On-Duty Cardiologist if Blood Pressure systolic > 180 mmHg',
                    'Schedule 30-day Post-Angioplasty Follow-up consultation automatically',
                ],
            ],

            // 3. REAL ESTATE PRESETS
            [
                'id' => 'real_estate_luxury',
                'industry' => 'real-estate',
                'name' => 'Luxury Real Estate & Brokerage',
                'icon' => '🏠',
                'color' => 'amber',
                'description' => 'Property buyer inquiries, site visit booking, inventory matching, and token payment closure.',
                'prompt' => 'Transform my CRM into a Real Estate Sales Platform with site visits, developer inventory, and buyer token stages.',
                'specialty' => 'Real Estate & Property Development',
                'pipeline_stages' => [
                    ['name' => 'Property Lead Inflow', 'color' => 'amber', 'sla_hours' => 2],
                    ['name' => 'Budget & BHK Preference Match', 'color' => 'cyan', 'sla_hours' => 12],
                    ['name' => 'Physical / Virtual Site Visit', 'color' => 'indigo', 'sla_hours' => 48],
                    ['name' => 'Token & Expression of Interest (EOI)', 'color' => 'purple', 'sla_hours' => 24],
                    ['name' => 'Sale Deed Registered & Handover', 'color' => 'emerald', 'sla_hours' => 720],
                ],
                'custom_fields' => [
                    ['key' => 'budget_range', 'label' => 'Budget Bracket', 'type' => 'select', 'options' => ['₹50L - ₹1Cr', '₹1Cr - ₹2.5Cr', '₹2.5Cr - ₹5Cr', '₹5Cr+']],
                    ['key' => 'preferred_bhk', 'label' => 'BHK Preference', 'type' => 'select', 'options' => ['1 BHK', '2 BHK', '3 BHK Luxury', '4 BHK Penthouse', 'Villa']],
                    ['key' => 'target_locality', 'label' => 'Target Neighborhood', 'type' => 'text', 'default' => 'City Prime Area'],
                ],
                'nav_items' => [
                    ['key' => 'dashboard', 'label' => 'Real Estate Hub', 'route' => '/', 'icon' => 'ChartBarIcon'],
                    ['key' => 'buyers', 'label' => 'Property Buyers', 'route' => '/contacts', 'icon' => 'UserGroupIcon'],
                    ['key' => 'properties', 'label' => 'Listings & Units', 'route' => '/properties', 'icon' => 'BuildingOfficeIcon'],
                    ['key' => 'deals', 'label' => 'Sales Pipeline', 'route' => '/deals', 'icon' => 'Square3Stack3DIcon'],
                    ['key' => 'data_import', 'label' => 'Data Import Hub', 'route' => '/data-import', 'icon' => 'TableCellsIcon'],
                    ['key' => 'ai_crm', 'label' => 'AI CRM Studio', 'route' => '/ai-crm-modifier', 'icon' => 'SparklesIcon'],
                ],
                'automations' => [
                    'Automated SMS with Google Maps direction sent 2h before scheduled Site Visit',
                    'Instant alert to Sales Lead if buyer expresses interest in penthouses > ₹3Cr',
                ],
            ],

            // 4. EDUCATION PRESETS
            [
                'id' => 'education_admissions',
                'industry' => 'education',
                'name' => 'University Admissions & Counseling',
                'icon' => '🎓',
                'color' => 'indigo',
                'description' => 'Student enquiry triage, entrance exam scoring, fee installment tracking, and batch allotment.',
                'prompt' => 'Transform CRM into an Academic Admissions & Enrollment Engine with counseling stages and fee tracking.',
                'specialty' => 'Higher Education & Admissions',
                'pipeline_stages' => [
                    ['name' => 'Online Inquiry / Open Day', 'color' => 'indigo', 'sla_hours' => 4],
                    ['name' => 'Counselor Call & Eligibility Check', 'color' => 'cyan', 'sla_hours' => 24],
                    ['name' => 'Document Verification & Entrance Score', 'color' => 'purple', 'sla_hours' => 48],
                    ['name' => 'Provisional Admission & Fee Paid', 'color' => 'emerald', 'sla_hours' => 24],
                    ['name' => 'Batch Assigned & ID Issued', 'color' => 'teal', 'sla_hours' => 72],
                ],
                'custom_fields' => [
                    ['key' => 'course_interested', 'label' => 'Course / Program', 'type' => 'text', 'default' => 'B.Tech / MBA'],
                    ['key' => 'entrance_score', 'label' => 'Entrance Percentile', 'type' => 'text', 'default' => '92%'],
                    ['key' => 'assigned_counselor', 'label' => 'Admission Counselor', 'type' => 'text', 'default' => 'Ms. Sharma'],
                ],
                'nav_items' => [
                    ['key' => 'dashboard', 'label' => 'Admissions Overview', 'route' => '/', 'icon' => 'ChartBarIcon'],
                    ['key' => 'students', 'label' => 'Applicants', 'route' => '/contacts', 'icon' => 'AcademicCapIcon'],
                    ['key' => 'pipeline', 'label' => 'Admission Funnel', 'route' => '/deals', 'icon' => 'Square3Stack3DIcon'],
                    ['key' => 'data_import', 'label' => 'Data Import Hub', 'route' => '/data-import', 'icon' => 'TableCellsIcon'],
                    ['key' => 'ai_crm', 'label' => 'AI CRM Studio', 'route' => '/ai-crm-modifier', 'icon' => 'SparklesIcon'],
                ],
                'automations' => [
                    'Auto-send prospectus PDF and entrance syllabus on initial student form submission',
                ],
            ],
        ];

        if ($industrySlug) {
            // Put presets matching current industry first
            usort($allPresets, function ($a, $b) use ($industrySlug) {
                $aMatch = ($a['industry'] ?? '') === $industrySlug ? 1 : 0;
                $bMatch = ($b['industry'] ?? '') === $industrySlug ? 1 : 0;
                return $bMatch <=> $aMatch;
            });
        }

        return $allPresets;
    }

    /**
     * Generate CRM schema and modifications from any natural language prompt or preset
     */
    public function generateFromPrompt(string $prompt, ?string $presetId = null, ?string $industrySlug = null): array
    {
        $presets = $this->getPresetTemplates($industrySlug);
        
        if ($presetId) {
            foreach ($presets as $p) {
                if ($p['id'] === $presetId) {
                    return $p;
                }
            }
        }

        $lower = strtolower($prompt);

        // Insurance keyword matches
        if (str_contains($lower, 'insur') || str_contains($lower, 'policy') || str_contains($lower, 'claim') || str_contains($lower, 'underwrit') || str_contains($lower, 'motor') || str_contains($lower, 'tpa')) {
            return $presets[0]; // Health & Motor Insurance
        }
        if (str_contains($lower, 'cardio') || str_contains($lower, 'heart')) {
            return $presets[2];
        }
        if (str_contains($lower, 'real estate') || str_contains($lower, 'propert') || str_contains($lower, 'realt')) {
            return $presets[3];
        }
        if (str_contains($lower, 'admiss') || str_contains($lower, 'student') || str_contains($lower, 'educat')) {
            return $presets[4];
        }

        // Dynamic synthesis for custom prompt
        $words = explode(' ', trim($prompt));
        $firstFew = array_slice($words, 0, 4);
        $title = ucwords(implode(' ', $firstFew)) . ' Suite';

        return [
            'id' => 'custom_' . Str::random(6),
            'name' => $title,
            'icon' => '⚡',
            'color' => 'teal',
            'description' => 'Custom AI Generated CRM configured from your prompt specifications.',
            'prompt' => $prompt,
            'specialty' => ucwords(implode(' ', array_slice($words, 0, 3))),
            'pipeline_stages' => [
                ['name' => 'Initial Enquiry & Intake', 'color' => 'teal', 'sla_hours' => 24],
                ['name' => 'Needs Assessment & Proposal', 'color' => 'indigo', 'sla_hours' => 48],
                ['name' => 'Review & Negotiation', 'color' => 'purple', 'sla_hours' => 72],
                ['name' => 'Contract Execution / Binding', 'color' => 'emerald', 'sla_hours' => 24],
                ['name' => 'Account Active & Servicing', 'color' => 'cyan', 'sla_hours' => 720],
            ],
            'custom_fields' => [
                ['key' => 'account_reference', 'label' => 'Reference ID', 'type' => 'text', 'default' => 'REF-2026-001'],
                ['key' => 'deal_value', 'label' => 'Contract / Deal Value', 'type' => 'number', 'default' => '50000'],
                ['key' => 'account_tier', 'label' => 'Account Priority', 'type' => 'select', 'options' => ['Standard', 'Silver', 'Gold', 'Platinum VIP']],
            ],
            'nav_items' => [
                ['key' => 'dashboard', 'label' => 'Dashboard', 'route' => '/', 'icon' => 'ChartBarIcon'],
                ['key' => 'contacts', 'label' => 'Clients & Leads', 'route' => '/contacts', 'icon' => 'UserGroupIcon'],
                ['key' => 'pipeline', 'label' => 'Pipeline Stages', 'route' => '/deals', 'icon' => 'Square3Stack3DIcon'],
                ['key' => 'staff_recruit', 'label' => 'Team & Careers', 'route' => '/staff-recruitment', 'icon' => 'BriefcaseIcon'],
                ['key' => 'data_import', 'label' => 'Data Import Hub', 'route' => '/data-import', 'icon' => 'TableCellsIcon'],
                ['key' => 'ai_crm', 'label' => 'AI CRM Studio', 'route' => '/ai-crm-modifier', 'icon' => 'SparklesIcon'],
            ],
            'automations' => [
                'Automated status change alerts dispatched to account owners',
                'Daily pipeline SLA summary sent at 09:00 AM',
            ],
        ];
    }

    /**
     * Apply Generated Schema & Navigation & Columns to live Workspace
     */
    public function applyToWorkspace(array $config, ?int $tenantId = null): array
    {
        $businessName = $config['name'] ?? 'JRV CRM Workspace';
        $businessIcon = $config['icon'] ?? '⚡';
        $brandColor = $config['color'] ?? 'teal';
        $specialty = $config['specialty'] ?? 'Universal Business Suite';
        $prompt = $config['prompt'] ?? '';

        // 1. Update Tenant Settings
        TenantSetting::setByKey('business_name', $businessName, $tenantId);
        TenantSetting::setByKey('business_icon', $businessIcon, $tenantId);
        TenantSetting::setByKey('brand_color', $brandColor, $tenantId);
        TenantSetting::setByKey('specialty_name', $specialty, $tenantId);
        TenantSetting::setByKey('ai_crm_last_prompt', $prompt, $tenantId);
        TenantSetting::setByKey('ai_crm_last_updated_at', now()->toDateTimeString(), $tenantId);

        // 2. Provision Custom Database Columns
        if (!empty($config['custom_fields']) && is_array($config['custom_fields'])) {
            foreach ($config['custom_fields'] as $field) {
                TenantCustomColumn::updateOrCreate(
                    [
                        'tenant_id' => $tenantId,
                        'column_name' => $field['key'],
                    ],
                    [
                        'label' => $field['label'],
                        'column_type' => $field['type'] ?? 'text',
                        'options' => $field['options'] ?? null,
                        'is_required' => false,
                        'is_searchable' => true,
                        'is_visible' => true,
                    ]
                );
            }
        }

        // 3. Update or Add Navigation Items
        if (!empty($config['nav_items']) && is_array($config['nav_items'])) {
            $order = 1;
            foreach ($config['nav_items'] as $nav) {
                NavigationItem::updateOrCreate(
                    [
                        'tenant_id' => $tenantId,
                        'key' => $nav['key'],
                    ],
                    [
                        'label' => $nav['label'],
                        'route' => $nav['route'],
                        'icon' => $nav['icon'] ?? 'HomeIcon',
                        'display_order' => $order++,
                        'is_enabled' => true,
                    ]
                );
            }
        }

        return [
            'success' => true,
            'message' => "AI successfully customized workspace for {$businessName}.",
            'config' => $config,
        ];
    }
}
