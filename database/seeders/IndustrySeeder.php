<?php

namespace Database\Seeders;

use App\Models\Industry;
use App\Models\BusinessType;
use App\Models\IndustryModule;
use App\Models\IndustryPipelineTemplate;
use App\Models\IndustryPipelineStageTemplate;
use App\Models\IndustryField;
use App\Models\IndustryDashboardWidget;
use Illuminate\Database\Seeder;

class IndustrySeeder extends Seeder
{
    public function run(): void
    {
        $industries = $this->getIndustries();

        foreach ($industries as $order => $data) {
            $industry = Industry::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'name' => $data['name'],
                    'icon' => $data['icon'],
                    'description' => $data['description'],
                    'color' => $data['color'],
                    'display_order' => $order + 1,
                    'is_active' => true,
                ]
            );

            // Seed business types
            if (!empty($data['business_types'])) {
                foreach ($data['business_types'] as $btOrder => $bt) {
                    BusinessType::updateOrCreate(
                        ['industry_id' => $industry->id, 'slug' => $bt['slug']],
                        [
                            'name' => $bt['name'],
                            'description' => $bt['description'] ?? null,
                            'display_order' => $btOrder + 1,
                        ]
                    );
                }
            }

            // Seed modules
            if (!empty($data['modules'])) {
                foreach ($data['modules'] as $mOrder => $mod) {
                    IndustryModule::updateOrCreate(
                        ['industry_id' => $industry->id, 'module_key' => $mod['key']],
                        [
                            'label' => $mod['label'],
                            'icon' => $mod['icon'],
                            'route' => $mod['route'],
                            'display_order' => $mOrder + 1,
                        ]
                    );
                }
            }

            // Seed pipeline templates
            if (!empty($data['pipeline'])) {
                $template = IndustryPipelineTemplate::updateOrCreate(
                    ['industry_id' => $industry->id, 'name' => $data['pipeline']['name']],
                    ['is_default' => true]
                );

                foreach ($data['pipeline']['stages'] as $sOrder => $stage) {
                    IndustryPipelineStageTemplate::updateOrCreate(
                        ['template_id' => $template->id, 'name' => $stage['name']],
                        [
                            'display_order' => $sOrder + 1,
                            'win_probability' => $stage['probability'],
                            'stage_type' => $stage['type'],
                        ]
                    );
                }
            }

            // Seed industry-specific fields
            if (!empty($data['fields'])) {
                foreach ($data['fields'] as $fOrder => $field) {
                    IndustryField::updateOrCreate(
                        ['industry_id' => $industry->id, 'module_key' => $field['module'] ?? 'leads', 'field_key' => $field['key']],
                        [
                            'label' => $field['label'],
                            'field_type' => $field['type'],
                            'options' => $field['options'] ?? null,
                            'is_required' => $field['required'] ?? false,
                            'display_order' => $fOrder + 1,
                        ]
                    );
                }
            }

            // Seed dashboard widgets
            if (!empty($data['widgets'])) {
                foreach ($data['widgets'] as $wOrder => $widget) {
                    IndustryDashboardWidget::updateOrCreate(
                        ['industry_id' => $industry->id, 'widget_key' => $widget['key']],
                        [
                            'label' => $widget['label'],
                            'widget_type' => $widget['type'],
                            'config' => $widget['config'] ?? null,
                            'display_order' => $wOrder + 1,
                            'grid_cols' => $widget['cols'] ?? 1,
                        ]
                    );
                }
            }
        }
    }

    private function getIndustries(): array
    {
        return [
            // ========== MATRIMONIAL & MATCHMAKING SERVICES ==========
            [
                'name' => 'Matrimonial & Matchmaking',
                'slug' => 'matrimonial',
                'icon' => '💍',
                'description' => 'Matrimonial bureaus, community matchmaking, marriage bureau, Jain/Hindu/Sikh matrimony',
                'color' => 'red',
                'business_types' => [
                    ['name' => 'Matrimonial Bureau', 'slug' => 'matrimonial-bureau', 'description' => 'Personalized matchmaking bureau'],
                    ['name' => 'Community Matchmaking', 'slug' => 'community-matchmaking', 'description' => 'Cast / Community specific matrimony'],
                    ['name' => 'VIP / Elite Matchmaking', 'slug' => 'vip-matchmaking', 'description' => 'High profile & HNIs matchmaking'],
                    ['name' => 'Online Marriage Portal', 'slug' => 'online-marriage-portal', 'description' => 'Digital matrimony platform'],
                ],
                'modules' => [
                    ['key' => 'biodata', 'label' => 'Biodata Directory', 'icon' => '📄', 'route' => '/matrimonial'],
                    ['key' => 'verified_members', 'label' => 'Verified Profiles', 'icon' => 'ShieldCheckIcon', 'route' => '/matrimonial'],
                    ['key' => 'shortlist', 'label' => 'Shortlist & Matches', 'icon' => 'HeartIcon', 'route' => '/matrimonial'],
                ],
                'pipeline' => [
                    'name' => 'Matrimonial Match Pipeline',
                    'stages' => [
                        ['name' => 'New Registration', 'probability' => 20, 'type' => 'open'],
                        ['name' => 'Biodata Received', 'probability' => 40, 'type' => 'open'],
                        ['name' => 'Verification Done', 'probability' => 60, 'type' => 'open'],
                        ['name' => 'Match Shortlisted', 'probability' => 80, 'type' => 'open'],
                        ['name' => 'Match Confirmed / Marriage Fixed', 'probability' => 100, 'type' => 'won'],
                    ]
                ]
            ],

            // ========== 1. EDUCATION (FULLY CONFIGURED) ==========
            [
                'name' => 'Education & Training',
                'slug' => 'education',
                'icon' => '🎓',
                'description' => 'Schools, colleges, coaching institutes, EdTech, study abroad consultants',
                'color' => 'indigo',
                'business_types' => [
                    ['name' => 'School', 'slug' => 'school', 'description' => 'K-12 schools & academies'],
                    ['name' => 'College', 'slug' => 'college', 'description' => 'Colleges & universities'],
                    ['name' => 'University', 'slug' => 'university', 'description' => 'Universities & research institutions'],
                    ['name' => 'Coaching Institute', 'slug' => 'coaching', 'description' => 'Test prep & coaching centers'],
                    ['name' => 'EdTech', 'slug' => 'edtech', 'description' => 'Online education platforms'],
                    ['name' => 'Online Courses', 'slug' => 'online-courses', 'description' => 'Online course providers'],
                    ['name' => 'Study Abroad', 'slug' => 'study-abroad', 'description' => 'Overseas education consultants'],
                    ['name' => 'Training Center', 'slug' => 'training-center', 'description' => 'Skill & corporate training'],
                    ['name' => 'Language Institute', 'slug' => 'language-institute', 'description' => 'Language learning centers'],
                    ['name' => 'PhD / Research Consultancy', 'slug' => 'phd-consultancy', 'description' => 'Research & PhD guidance'],
                    ['name' => 'Career Counseling', 'slug' => 'career-counseling', 'description' => 'Career guidance services'],
                    ['name' => 'Skill Development', 'slug' => 'skill-development', 'description' => 'Vocational & skill training'],
                    ['name' => 'Other', 'slug' => 'other'],
                ],
                'modules' => [
                    ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'ChartBarIcon', 'route' => '/'],
                    ['key' => 'leads', 'label' => 'Enquiries', 'icon' => 'UserIcon', 'route' => '/leads'],
                    ['key' => 'contacts', 'label' => 'Students', 'icon' => 'AcademicCapIcon', 'route' => '/contacts'],
                    ['key' => 'courses', 'label' => 'Courses', 'icon' => 'BookOpenIcon', 'route' => '/courses'],
                    ['key' => 'applications', 'label' => 'Applications', 'icon' => 'DocumentTextIcon', 'route' => '/applications'],
                    ['key' => 'admissions', 'label' => 'Admissions', 'icon' => 'ClipboardDocumentCheckIcon', 'route' => '/admissions'],
                    ['key' => 'counselors', 'label' => 'Counselors', 'icon' => 'UserGroupIcon', 'route' => '/counselors'],
                    ['key' => 'fees', 'label' => 'Fees', 'icon' => 'CurrencyRupeeIcon', 'route' => '/fees'],
                    ['key' => 'deals', 'label' => 'Deals', 'icon' => 'Square3Stack3DIcon', 'route' => '/deals'],
                    ['key' => 'tasks', 'label' => 'Tasks', 'icon' => 'ClipboardDocumentListIcon', 'route' => '/tasks'],
                    ['key' => 'reports', 'label' => 'Reports', 'icon' => 'ChartPieIcon', 'route' => '/reports'],
                    ['key' => 'settings', 'label' => 'Settings', 'icon' => 'Cog6ToothIcon', 'route' => '/tenant/settings/navigation'],
                ],
                'pipeline' => [
                    'name' => 'Education Admission Pipeline',
                    'stages' => [
                        ['name' => 'New Enquiry', 'probability' => 5, 'type' => 'open'],
                        ['name' => 'Contacted', 'probability' => 15, 'type' => 'open'],
                        ['name' => 'Counseling Done', 'probability' => 30, 'type' => 'open'],
                        ['name' => 'Application Submitted', 'probability' => 50, 'type' => 'open'],
                        ['name' => 'Document Verification', 'probability' => 65, 'type' => 'open'],
                        ['name' => 'Admission Offered', 'probability' => 80, 'type' => 'open'],
                        ['name' => 'Fee Payment', 'probability' => 90, 'type' => 'open'],
                        ['name' => 'Enrolled', 'probability' => 100, 'type' => 'won'],
                        ['name' => 'Lost', 'probability' => 0, 'type' => 'lost'],
                    ],
                ],
                'fields' => [
                    ['key' => 'course_interested', 'label' => 'Course Interested', 'type' => 'dropdown', 'module' => 'leads', 'options' => ['B.Tech', 'MBA', 'BCA', 'MCA', 'B.Sc', 'M.Sc', 'Other']],
                    ['key' => 'qualification', 'label' => 'Highest Qualification', 'type' => 'dropdown', 'module' => 'leads', 'options' => ['10th', '12th', 'Graduate', 'Post Graduate', 'PhD']],
                    ['key' => 'percentage', 'label' => 'Percentage / CGPA', 'type' => 'text', 'module' => 'leads'],
                    ['key' => 'admission_year', 'label' => 'Admission Year', 'type' => 'dropdown', 'module' => 'leads', 'options' => ['2025', '2026', '2027']],
                    ['key' => 'preferred_location', 'label' => 'Preferred Location', 'type' => 'text', 'module' => 'leads'],
                    ['key' => 'counselor', 'label' => 'Assigned Counselor', 'type' => 'text', 'module' => 'leads'],
                    ['key' => 'application_status', 'label' => 'Application Status', 'type' => 'dropdown', 'module' => 'leads', 'options' => ['Pending', 'Submitted', 'Under Review', 'Accepted', 'Rejected']],
                ],
                'widgets' => [
                    ['key' => 'total_enquiries', 'label' => 'Total Enquiries', 'type' => 'stat', 'cols' => 1, 'config' => ['model' => 'leads', 'aggregate' => 'count', 'icon' => '📩', 'color' => 'indigo']],
                    ['key' => 'new_leads', 'label' => 'New Leads (This Week)', 'type' => 'stat', 'cols' => 1, 'config' => ['model' => 'leads', 'aggregate' => 'count', 'filter' => 'this_week', 'icon' => '🔥', 'color' => 'rose']],
                    ['key' => 'applications', 'label' => 'Applications', 'type' => 'stat', 'cols' => 1, 'config' => ['model' => 'leads', 'aggregate' => 'count', 'filter' => 'stage:Application Submitted', 'icon' => '📋', 'color' => 'amber']],
                    ['key' => 'admissions', 'label' => 'Admissions', 'type' => 'stat', 'cols' => 1, 'config' => ['model' => 'leads', 'aggregate' => 'count', 'filter' => 'stage:Enrolled', 'icon' => '🎓', 'color' => 'emerald']],
                    ['key' => 'conversion_rate', 'label' => 'Conversion Rate', 'type' => 'stat', 'cols' => 1, 'config' => ['aggregate' => 'conversion_rate', 'icon' => '📈', 'color' => 'sky']],
                    ['key' => 'fees_collected', 'label' => 'Fees Collected', 'type' => 'stat', 'cols' => 1, 'config' => ['model' => 'deals', 'aggregate' => 'sum', 'field' => 'value', 'icon' => '💰', 'color' => 'green']],
                    ['key' => 'enquiry_source_chart', 'label' => 'Enquiries by Source', 'type' => 'pie_chart', 'cols' => 2, 'config' => ['model' => 'leads', 'group_by' => 'source']],
                    ['key' => 'admission_funnel', 'label' => 'Admission Funnel', 'type' => 'funnel_chart', 'cols' => 2, 'config' => ['model' => 'leads', 'group_by' => 'stage']],
                    ['key' => 'counselor_performance', 'label' => 'Counselor Performance', 'type' => 'bar_chart', 'cols' => 2, 'config' => ['model' => 'leads', 'group_by' => 'assigned_to']],
                    ['key' => 'course_popularity', 'label' => 'Course Popularity', 'type' => 'bar_chart', 'cols' => 2, 'config' => ['model' => 'leads', 'group_by' => 'course_interested']],
                ],
            ],

            // ========== 2. HEALTHCARE (FULLY CONFIGURED) ==========
            [
                'name' => 'Healthcare',
                'slug' => 'healthcare',
                'icon' => '🏥',
                'description' => 'Hospitals, clinics, diagnostic labs, pharmacies, wellness',
                'color' => 'emerald',
                'business_types' => [
                    ['name' => 'Hospital', 'slug' => 'hospital'],
                    ['name' => 'Clinic', 'slug' => 'clinic'],
                    ['name' => 'Diagnostic Center', 'slug' => 'diagnostic'],
                    ['name' => 'Dental Clinic', 'slug' => 'dental'],
                    ['name' => 'Pharmacy', 'slug' => 'pharmacy'],
                    ['name' => 'Health-Tech', 'slug' => 'health-tech'],
                    ['name' => 'Physiotherapy', 'slug' => 'physiotherapy'],
                    ['name' => 'Wellness Center', 'slug' => 'wellness'],
                    ['name' => 'Medical Tourism', 'slug' => 'medical-tourism'],
                    ['name' => 'Other', 'slug' => 'other'],
                ],
                'modules' => [
                    ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'ChartBarIcon', 'route' => '/'],
                    ['key' => 'leads', 'label' => 'Patient Enquiries', 'icon' => 'UserIcon', 'route' => '/leads'],
                    ['key' => 'contacts', 'label' => 'Patients', 'icon' => 'HeartIcon', 'route' => '/contacts'],
                    ['key' => 'doctors', 'label' => 'Doctors', 'icon' => 'UserGroupIcon', 'route' => '/doctors'],
                    ['key' => 'appointments', 'label' => 'Appointments', 'icon' => 'CalendarIcon', 'route' => '/appointments'],
                    ['key' => 'departments', 'label' => 'Departments', 'icon' => 'BuildingOfficeIcon', 'route' => '/departments'],
                    ['key' => 'treatments', 'label' => 'Treatments', 'icon' => 'ClipboardDocumentCheckIcon', 'route' => '/treatments'],
                    ['key' => 'followups', 'label' => 'Follow-ups', 'icon' => 'ArrowPathIcon', 'route' => '/follow-ups'],
                    ['key' => 'deals', 'label' => 'Billing', 'icon' => 'Square3Stack3DIcon', 'route' => '/deals'],
                    ['key' => 'tasks', 'label' => 'Tasks', 'icon' => 'ClipboardDocumentListIcon', 'route' => '/tasks'],
                    ['key' => 'reports', 'label' => 'Reports', 'icon' => 'ChartPieIcon', 'route' => '/reports'],
                    ['key' => 'settings', 'label' => 'Settings', 'icon' => 'Cog6ToothIcon', 'route' => '/tenant/settings/navigation'],
                ],
                'pipeline' => [
                    'name' => 'Healthcare Patient Pipeline',
                    'stages' => [
                        ['name' => 'New Enquiry', 'probability' => 10, 'type' => 'open'],
                        ['name' => 'Appointment Booked', 'probability' => 30, 'type' => 'open'],
                        ['name' => 'Consultation Done', 'probability' => 50, 'type' => 'open'],
                        ['name' => 'Treatment Started', 'probability' => 70, 'type' => 'open'],
                        ['name' => 'Treatment Completed', 'probability' => 90, 'type' => 'open'],
                        ['name' => 'Follow-up', 'probability' => 95, 'type' => 'open'],
                        ['name' => 'Closed', 'probability' => 100, 'type' => 'won'],
                        ['name' => 'Lost', 'probability' => 0, 'type' => 'lost'],
                    ],
                ],
                'fields' => [
                    ['key' => 'patient_type', 'label' => 'Patient Type', 'type' => 'dropdown', 'module' => 'leads', 'options' => ['New', 'Returning', 'Emergency', 'Referred']],
                    ['key' => 'appointment_type', 'label' => 'Appointment Type', 'type' => 'dropdown', 'module' => 'leads', 'options' => ['Consultation', 'Follow-up', 'Surgery', 'Lab Test', 'Imaging']],
                    ['key' => 'doctor', 'label' => 'Preferred Doctor', 'type' => 'text', 'module' => 'leads'],
                    ['key' => 'department', 'label' => 'Department', 'type' => 'dropdown', 'module' => 'leads', 'options' => ['Cardiology', 'Orthopedics', 'Neurology', 'Pediatrics', 'General Medicine', 'Dermatology', 'ENT', 'Other']],
                    ['key' => 'preferred_date', 'label' => 'Preferred Date', 'type' => 'date', 'module' => 'leads'],
                    ['key' => 'referral_source', 'label' => 'Referral Source', 'type' => 'dropdown', 'module' => 'leads', 'options' => ['Doctor Referral', 'Walk-in', 'Website', 'Social Media', 'Insurance', 'Other']],
                ],
                'widgets' => [
                    ['key' => 'total_patients', 'label' => 'Total Patients', 'type' => 'stat', 'cols' => 1, 'config' => ['model' => 'contacts', 'aggregate' => 'count', 'icon' => '🏥', 'color' => 'emerald']],
                    ['key' => 'new_patients', 'label' => 'New Patients (Week)', 'type' => 'stat', 'cols' => 1, 'config' => ['model' => 'contacts', 'aggregate' => 'count', 'filter' => 'this_week', 'icon' => '👤', 'color' => 'sky']],
                    ['key' => 'appointments_today', 'label' => 'Appointments Today', 'type' => 'stat', 'cols' => 1, 'config' => ['model' => 'tasks', 'aggregate' => 'count', 'filter' => 'today', 'icon' => '📅', 'color' => 'amber']],
                    ['key' => 'revenue', 'label' => 'Revenue', 'type' => 'stat', 'cols' => 1, 'config' => ['model' => 'deals', 'aggregate' => 'sum', 'field' => 'value', 'icon' => '💰', 'color' => 'green']],
                    ['key' => 'pending_followups', 'label' => 'Pending Follow-ups', 'type' => 'stat', 'cols' => 1, 'config' => ['model' => 'tasks', 'aggregate' => 'count', 'filter' => 'pending', 'icon' => '⏳', 'color' => 'rose']],
                    ['key' => 'patient_retention', 'label' => 'Patient Retention', 'type' => 'stat', 'cols' => 1, 'config' => ['aggregate' => 'retention_rate', 'icon' => '🔄', 'color' => 'violet']],
                    ['key' => 'department_chart', 'label' => 'Patients by Department', 'type' => 'pie_chart', 'cols' => 2, 'config' => ['model' => 'leads', 'group_by' => 'department']],
                    ['key' => 'appointment_trend', 'label' => 'Appointment Trends', 'type' => 'line_chart', 'cols' => 2, 'config' => ['model' => 'tasks', 'group_by' => 'created_at']],
                ],
            ],

            // ========== 3. REAL ESTATE (FULLY CONFIGURED) ==========
            [
                'name' => 'Real Estate',
                'slug' => 'real-estate',
                'icon' => '🏠',
                'description' => 'Residential, commercial, property dealers, builders, developers',
                'color' => 'amber',
                'business_types' => [
                    ['name' => 'Residential Real Estate', 'slug' => 'residential'],
                    ['name' => 'Commercial Real Estate', 'slug' => 'commercial'],
                    ['name' => 'Property Dealer', 'slug' => 'dealer'],
                    ['name' => 'Builder / Developer', 'slug' => 'builder'],
                    ['name' => 'Rental Agency', 'slug' => 'rental'],
                    ['name' => 'Property Management', 'slug' => 'management'],
                    ['name' => 'Other', 'slug' => 'other'],
                ],
                'modules' => [
                    ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'ChartBarIcon', 'route' => '/'],
                    ['key' => 'leads', 'label' => 'Leads', 'icon' => 'UserIcon', 'route' => '/leads'],
                    ['key' => 'properties', 'label' => 'Properties', 'icon' => 'HomeModernIcon', 'route' => '/properties'],
                    ['key' => 'projects', 'label' => 'Projects', 'icon' => 'BuildingOfficeIcon', 'route' => '/projects'],
                    ['key' => 'site_visits', 'label' => 'Site Visits', 'icon' => 'MapPinIcon', 'route' => '/site-visits'],
                    ['key' => 'bookings', 'label' => 'Bookings', 'icon' => 'DocumentCheckIcon', 'route' => '/bookings'],
                    ['key' => 'agents', 'label' => 'Agents', 'icon' => 'UserGroupIcon', 'route' => '/agents'],
                    ['key' => 'deals', 'label' => 'Deals', 'icon' => 'Square3Stack3DIcon', 'route' => '/deals'],
                    ['key' => 'contacts', 'label' => 'Contacts', 'icon' => 'BookOpenIcon', 'route' => '/contacts'],
                    ['key' => 'tasks', 'label' => 'Tasks', 'icon' => 'ClipboardDocumentListIcon', 'route' => '/tasks'],
                    ['key' => 'reports', 'label' => 'Reports', 'icon' => 'ChartPieIcon', 'route' => '/reports'],
                    ['key' => 'settings', 'label' => 'Settings', 'icon' => 'Cog6ToothIcon', 'route' => '/tenant/settings/navigation'],
                ],
                'pipeline' => [
                    'name' => 'Real Estate Sales Pipeline',
                    'stages' => [
                        ['name' => 'New Lead', 'probability' => 5, 'type' => 'open'],
                        ['name' => 'Contacted', 'probability' => 15, 'type' => 'open'],
                        ['name' => 'Property Suggested', 'probability' => 30, 'type' => 'open'],
                        ['name' => 'Site Visit Scheduled', 'probability' => 45, 'type' => 'open'],
                        ['name' => 'Site Visit Done', 'probability' => 55, 'type' => 'open'],
                        ['name' => 'Negotiation', 'probability' => 70, 'type' => 'open'],
                        ['name' => 'Booking', 'probability' => 85, 'type' => 'open'],
                        ['name' => 'Agreement Signed', 'probability' => 95, 'type' => 'open'],
                        ['name' => 'Closed Won', 'probability' => 100, 'type' => 'won'],
                        ['name' => 'Lost', 'probability' => 0, 'type' => 'lost'],
                    ],
                ],
                'fields' => [
                    ['key' => 'property_type', 'label' => 'Property Type', 'type' => 'dropdown', 'module' => 'leads', 'options' => ['Apartment', 'Villa', 'Plot', 'Commercial', 'Office', 'Warehouse', 'Farm Land']],
                    ['key' => 'budget', 'label' => 'Budget', 'type' => 'currency', 'module' => 'leads'],
                    ['key' => 'location', 'label' => 'Preferred Location', 'type' => 'text', 'module' => 'leads'],
                    ['key' => 'bedrooms', 'label' => 'Bedrooms', 'type' => 'dropdown', 'module' => 'leads', 'options' => ['1 BHK', '2 BHK', '3 BHK', '4 BHK', '5+ BHK']],
                    ['key' => 'purpose', 'label' => 'Purpose', 'type' => 'dropdown', 'module' => 'leads', 'options' => ['Purchase', 'Rent', 'Investment', 'Resale']],
                    ['key' => 'site_visit_date', 'label' => 'Site Visit Date', 'type' => 'date', 'module' => 'leads'],
                    ['key' => 'preferred_property', 'label' => 'Preferred Property', 'type' => 'text', 'module' => 'leads'],
                ],
                'widgets' => [
                    ['key' => 'total_leads', 'label' => 'Total Leads', 'type' => 'stat', 'cols' => 1, 'config' => ['model' => 'leads', 'aggregate' => 'count', 'icon' => '👥', 'color' => 'amber']],
                    ['key' => 'new_leads', 'label' => 'New Leads', 'type' => 'stat', 'cols' => 1, 'config' => ['model' => 'leads', 'aggregate' => 'count', 'filter' => 'this_week', 'icon' => '🔥', 'color' => 'rose']],
                    ['key' => 'site_visits', 'label' => 'Site Visits', 'type' => 'stat', 'cols' => 1, 'config' => ['model' => 'tasks', 'aggregate' => 'count', 'icon' => '📍', 'color' => 'sky']],
                    ['key' => 'bookings', 'label' => 'Bookings', 'type' => 'stat', 'cols' => 1, 'config' => ['model' => 'deals', 'aggregate' => 'count', 'filter' => 'stage:Booking', 'icon' => '✅', 'color' => 'emerald']],
                    ['key' => 'revenue', 'label' => 'Revenue', 'type' => 'stat', 'cols' => 1, 'config' => ['model' => 'deals', 'aggregate' => 'sum', 'field' => 'value', 'icon' => '💰', 'color' => 'green']],
                    ['key' => 'conversion_rate', 'label' => 'Conversion Rate', 'type' => 'stat', 'cols' => 1, 'config' => ['aggregate' => 'conversion_rate', 'icon' => '📈', 'color' => 'violet']],
                    ['key' => 'source_chart', 'label' => 'Leads by Source', 'type' => 'pie_chart', 'cols' => 2, 'config' => ['model' => 'leads', 'group_by' => 'source']],
                    ['key' => 'pipeline_chart', 'label' => 'Sales Pipeline', 'type' => 'funnel_chart', 'cols' => 2, 'config' => ['model' => 'leads', 'group_by' => 'stage']],
                    ['key' => 'agent_performance', 'label' => 'Agent Performance', 'type' => 'bar_chart', 'cols' => 2, 'config' => ['model' => 'leads', 'group_by' => 'assigned_to']],
                ],
            ],

            // ========== 4. RECRUITMENT & HR (FULLY CONFIGURED) ==========
            [
                'name' => 'Recruitment & HR',
                'slug' => 'recruitment',
                'icon' => '💼',
                'description' => 'Recruitment agencies, staffing, placement, executive search',
                'color' => 'violet',
                'business_types' => [
                    ['name' => 'Recruitment Agency', 'slug' => 'agency'],
                    ['name' => 'Staffing Company', 'slug' => 'staffing'],
                    ['name' => 'Executive Search', 'slug' => 'executive-search'],
                    ['name' => 'Placement Consultant', 'slug' => 'placement'],
                    ['name' => 'Freelance Recruiter', 'slug' => 'freelance'],
                    ['name' => 'Other', 'slug' => 'other'],
                ],
                'modules' => [
                    ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'ChartBarIcon', 'route' => '/'],
                    ['key' => 'leads', 'label' => 'Candidates', 'icon' => 'UserIcon', 'route' => '/leads'],
                    ['key' => 'jobs', 'label' => 'Jobs', 'icon' => 'BriefcaseIcon', 'route' => '/jobs'],
                    ['key' => 'companies', 'label' => 'Employers', 'icon' => 'BuildingOfficeIcon', 'route' => '/companies'],
                    ['key' => 'interviews', 'label' => 'Interviews', 'icon' => 'VideoCameraIcon', 'route' => '/interviews'],
                    ['key' => 'offers', 'label' => 'Offers', 'icon' => 'DocumentTextIcon', 'route' => '/offers'],
                    ['key' => 'placements', 'label' => 'Placements', 'icon' => 'CheckBadgeIcon', 'route' => '/placements'],
                    ['key' => 'deals', 'label' => 'Deals', 'icon' => 'Square3Stack3DIcon', 'route' => '/deals'],
                    ['key' => 'tasks', 'label' => 'Tasks', 'icon' => 'ClipboardDocumentListIcon', 'route' => '/tasks'],
                    ['key' => 'reports', 'label' => 'Reports', 'icon' => 'ChartPieIcon', 'route' => '/reports'],
                    ['key' => 'settings', 'label' => 'Settings', 'icon' => 'Cog6ToothIcon', 'route' => '/tenant/settings/navigation'],
                ],
                'pipeline' => [
                    'name' => 'Recruitment Pipeline',
                    'stages' => [
                        ['name' => 'New Candidate', 'probability' => 5, 'type' => 'open'],
                        ['name' => 'Screening', 'probability' => 20, 'type' => 'open'],
                        ['name' => 'Shortlisted', 'probability' => 35, 'type' => 'open'],
                        ['name' => 'Interview Scheduled', 'probability' => 50, 'type' => 'open'],
                        ['name' => 'Interview Done', 'probability' => 65, 'type' => 'open'],
                        ['name' => 'Selected', 'probability' => 80, 'type' => 'open'],
                        ['name' => 'Offer Made', 'probability' => 90, 'type' => 'open'],
                        ['name' => 'Joined', 'probability' => 100, 'type' => 'won'],
                        ['name' => 'Rejected', 'probability' => 0, 'type' => 'lost'],
                    ],
                ],
                'fields' => [
                    ['key' => 'job_title', 'label' => 'Job Applied For', 'type' => 'text', 'module' => 'leads'],
                    ['key' => 'experience_years', 'label' => 'Years of Experience', 'type' => 'number', 'module' => 'leads'],
                    ['key' => 'skills', 'label' => 'Key Skills', 'type' => 'text', 'module' => 'leads'],
                    ['key' => 'current_company', 'label' => 'Current Company', 'type' => 'text', 'module' => 'leads'],
                    ['key' => 'current_salary', 'label' => 'Current Salary', 'type' => 'currency', 'module' => 'leads'],
                    ['key' => 'expected_salary', 'label' => 'Expected Salary', 'type' => 'currency', 'module' => 'leads'],
                    ['key' => 'notice_period', 'label' => 'Notice Period', 'type' => 'dropdown', 'module' => 'leads', 'options' => ['Immediate', '15 Days', '30 Days', '60 Days', '90 Days']],
                    ['key' => 'interview_status', 'label' => 'Interview Status', 'type' => 'dropdown', 'module' => 'leads', 'options' => ['Scheduled', 'Completed', 'No Show', 'Rescheduled']],
                ],
                'widgets' => [
                    ['key' => 'total_candidates', 'label' => 'Total Candidates', 'type' => 'stat', 'cols' => 1, 'config' => ['model' => 'leads', 'aggregate' => 'count', 'icon' => '👥', 'color' => 'violet']],
                    ['key' => 'open_jobs', 'label' => 'Open Jobs', 'type' => 'stat', 'cols' => 1, 'config' => ['model' => 'tasks', 'aggregate' => 'count', 'icon' => '💼', 'color' => 'sky']],
                    ['key' => 'interviews', 'label' => 'Interviews This Week', 'type' => 'stat', 'cols' => 1, 'config' => ['model' => 'tasks', 'aggregate' => 'count', 'filter' => 'this_week', 'icon' => '🎤', 'color' => 'amber']],
                    ['key' => 'placements', 'label' => 'Placements', 'type' => 'stat', 'cols' => 1, 'config' => ['model' => 'deals', 'aggregate' => 'count', 'filter' => 'won', 'icon' => '✅', 'color' => 'emerald']],
                    ['key' => 'offers_made', 'label' => 'Offers Made', 'type' => 'stat', 'cols' => 1, 'config' => ['model' => 'deals', 'aggregate' => 'count', 'icon' => '📄', 'color' => 'rose']],
                    ['key' => 'revenue', 'label' => 'Revenue', 'type' => 'stat', 'cols' => 1, 'config' => ['model' => 'deals', 'aggregate' => 'sum', 'field' => 'value', 'icon' => '💰', 'color' => 'green']],
                    ['key' => 'candidate_funnel', 'label' => 'Candidate Funnel', 'type' => 'funnel_chart', 'cols' => 2, 'config' => ['model' => 'leads', 'group_by' => 'stage']],
                    ['key' => 'recruiter_performance', 'label' => 'Recruiter Performance', 'type' => 'bar_chart', 'cols' => 2, 'config' => ['model' => 'leads', 'group_by' => 'assigned_to']],
                ],
            ],

            // ========== 5. IT & SOFTWARE (FULLY CONFIGURED) ==========
            [
                'name' => 'IT & Software',
                'slug' => 'it-software',
                'icon' => '💻',
                'description' => 'SaaS, IT services, web dev, mobile apps, cybersecurity, AI',
                'color' => 'sky',
                'business_types' => [
                    ['name' => 'SaaS Company', 'slug' => 'saas'],
                    ['name' => 'Software Company', 'slug' => 'software'],
                    ['name' => 'IT Services', 'slug' => 'it-services'],
                    ['name' => 'Web Development', 'slug' => 'web-dev'],
                    ['name' => 'Mobile App Development', 'slug' => 'mobile-dev'],
                    ['name' => 'Cybersecurity', 'slug' => 'cybersecurity'],
                    ['name' => 'Cloud Company', 'slug' => 'cloud'],
                    ['name' => 'AI Company', 'slug' => 'ai'],
                    ['name' => 'ERP Company', 'slug' => 'erp'],
                    ['name' => 'Other', 'slug' => 'other'],
                ],
                'modules' => [
                    ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'ChartBarIcon', 'route' => '/'],
                    ['key' => 'leads', 'label' => 'Leads', 'icon' => 'UserIcon', 'route' => '/leads'],
                    ['key' => 'contacts', 'label' => 'Contacts', 'icon' => 'BookOpenIcon', 'route' => '/contacts'],
                    ['key' => 'companies', 'label' => 'Companies', 'icon' => 'BuildingOfficeIcon', 'route' => '/companies'],
                    ['key' => 'deals', 'label' => 'Deals', 'icon' => 'Square3Stack3DIcon', 'route' => '/deals'],
                    ['key' => 'projects', 'label' => 'Projects', 'icon' => 'FolderIcon', 'route' => '/projects'],
                    ['key' => 'tasks', 'label' => 'Tasks', 'icon' => 'ClipboardDocumentListIcon', 'route' => '/tasks'],
                    ['key' => 'reports', 'label' => 'Reports', 'icon' => 'ChartPieIcon', 'route' => '/reports'],
                    ['key' => 'settings', 'label' => 'Settings', 'icon' => 'Cog6ToothIcon', 'route' => '/tenant/settings/navigation'],
                ],
                'pipeline' => [
                    'name' => 'IT Sales Pipeline',
                    'stages' => [
                        ['name' => 'New Lead', 'probability' => 10, 'type' => 'open'],
                        ['name' => 'Discovery Call', 'probability' => 20, 'type' => 'open'],
                        ['name' => 'Qualified', 'probability' => 35, 'type' => 'open'],
                        ['name' => 'Demo / POC', 'probability' => 50, 'type' => 'open'],
                        ['name' => 'Proposal Sent', 'probability' => 65, 'type' => 'open'],
                        ['name' => 'Negotiation', 'probability' => 80, 'type' => 'open'],
                        ['name' => 'Contract Signed', 'probability' => 95, 'type' => 'open'],
                        ['name' => 'Closed Won', 'probability' => 100, 'type' => 'won'],
                        ['name' => 'Closed Lost', 'probability' => 0, 'type' => 'lost'],
                    ],
                ],
                'fields' => [
                    ['key' => 'project_type', 'label' => 'Project Type', 'type' => 'dropdown', 'module' => 'leads', 'options' => ['Web App', 'Mobile App', 'SaaS', 'API', 'ERP', 'CRM', 'E-commerce', 'Consulting', 'Other']],
                    ['key' => 'technology', 'label' => 'Technology Stack', 'type' => 'text', 'module' => 'leads'],
                    ['key' => 'project_budget', 'label' => 'Project Budget', 'type' => 'currency', 'module' => 'leads'],
                    ['key' => 'timeline', 'label' => 'Expected Timeline', 'type' => 'dropdown', 'module' => 'leads', 'options' => ['< 1 Month', '1-3 Months', '3-6 Months', '6-12 Months', '12+ Months']],
                    ['key' => 'company_size', 'label' => 'Company Size', 'type' => 'dropdown', 'module' => 'leads', 'options' => ['Startup', 'SMB', 'Mid-market', 'Enterprise']],
                ],
                'widgets' => [
                    ['key' => 'total_leads', 'label' => 'Total Leads', 'type' => 'stat', 'cols' => 1, 'config' => ['model' => 'leads', 'aggregate' => 'count', 'icon' => '👥', 'color' => 'sky']],
                    ['key' => 'active_deals', 'label' => 'Active Deals', 'type' => 'stat', 'cols' => 1, 'config' => ['model' => 'deals', 'aggregate' => 'count', 'filter' => 'open', 'icon' => '🤝', 'color' => 'indigo']],
                    ['key' => 'pipeline_value', 'label' => 'Pipeline Value', 'type' => 'stat', 'cols' => 1, 'config' => ['model' => 'deals', 'aggregate' => 'sum', 'field' => 'value', 'icon' => '💎', 'color' => 'violet']],
                    ['key' => 'won_deals', 'label' => 'Won This Month', 'type' => 'stat', 'cols' => 1, 'config' => ['model' => 'deals', 'aggregate' => 'count', 'filter' => 'won_this_month', 'icon' => '🏆', 'color' => 'emerald']],
                    ['key' => 'revenue', 'label' => 'Revenue', 'type' => 'stat', 'cols' => 1, 'config' => ['model' => 'deals', 'aggregate' => 'sum', 'field' => 'value', 'filter' => 'won', 'icon' => '💰', 'color' => 'green']],
                    ['key' => 'conversion_rate', 'label' => 'Conversion Rate', 'type' => 'stat', 'cols' => 1, 'config' => ['aggregate' => 'conversion_rate', 'icon' => '📈', 'color' => 'rose']],
                    ['key' => 'pipeline_chart', 'label' => 'Sales Pipeline', 'type' => 'funnel_chart', 'cols' => 2, 'config' => ['model' => 'deals', 'group_by' => 'stage']],
                    ['key' => 'source_chart', 'label' => 'Leads by Source', 'type' => 'pie_chart', 'cols' => 2, 'config' => ['model' => 'leads', 'group_by' => 'source']],
                    ['key' => 'revenue_trend', 'label' => 'Revenue Trend', 'type' => 'line_chart', 'cols' => 2, 'config' => ['model' => 'deals', 'group_by' => 'closed_at', 'aggregate' => 'sum']],
                ],
            ],

            // ========== REMAINING 25 INDUSTRIES (Basic entries) ==========
            ['name' => 'Banking & Financial Services', 'slug' => 'banking-finance', 'icon' => '🏦', 'description' => 'Banks, NBFC, FinTech, investments, wealth management', 'color' => 'slate',
                'business_types' => [
                    ['name' => 'Bank', 'slug' => 'bank'], ['name' => 'NBFC', 'slug' => 'nbfc'], ['name' => 'FinTech', 'slug' => 'fintech'],
                    ['name' => 'Mutual Funds', 'slug' => 'mutual-funds'], ['name' => 'Wealth Management', 'slug' => 'wealth-mgmt'], ['name' => 'Other', 'slug' => 'other'],
                ],
            ],
            ['name' => 'Insurance', 'slug' => 'insurance', 'icon' => '🛡️', 'description' => 'Life, health, general, motor insurance', 'color' => 'teal',
                'business_types' => [
                    ['name' => 'Life Insurance', 'slug' => 'life'], ['name' => 'Health Insurance', 'slug' => 'health'],
                    ['name' => 'General Insurance', 'slug' => 'general'], ['name' => 'Insurance Broker', 'slug' => 'broker'], ['name' => 'Other', 'slug' => 'other'],
                ],
            ],
            ['name' => 'Manufacturing', 'slug' => 'manufacturing', 'icon' => '🏭', 'description' => 'Automobile, electronics, chemicals, pharmaceuticals', 'color' => 'zinc',
                'business_types' => [
                    ['name' => 'Automobile', 'slug' => 'automobile'], ['name' => 'Electronics', 'slug' => 'electronics'],
                    ['name' => 'Chemicals', 'slug' => 'chemicals'], ['name' => 'FMCG', 'slug' => 'fmcg'], ['name' => 'Other', 'slug' => 'other'],
                ],
            ],
            ['name' => 'Automobile', 'slug' => 'automobile', 'icon' => '🚗', 'description' => 'Car & bike dealerships, EV, auto services', 'color' => 'red',
                'business_types' => [
                    ['name' => 'Car Dealership', 'slug' => 'car'], ['name' => 'Bike Dealership', 'slug' => 'bike'],
                    ['name' => 'EV Company', 'slug' => 'ev'], ['name' => 'Used Car Dealer', 'slug' => 'used-car'], ['name' => 'Other', 'slug' => 'other'],
                ],
            ],
            ['name' => 'Travel & Tourism', 'slug' => 'travel', 'icon' => '✈️', 'description' => 'Travel agencies, tour operators, hotels, visa consultants', 'color' => 'cyan',
                'business_types' => [
                    ['name' => 'Travel Agency', 'slug' => 'agency'], ['name' => 'Tour Operator', 'slug' => 'tour-operator'],
                    ['name' => 'Hotel', 'slug' => 'hotel'], ['name' => 'Visa Consultant', 'slug' => 'visa'], ['name' => 'Other', 'slug' => 'other'],
                ],
            ],
            ['name' => 'Hospitality', 'slug' => 'hospitality', 'icon' => '🏨', 'description' => 'Hotels, resorts, restaurants, event venues', 'color' => 'orange',
                'business_types' => [
                    ['name' => 'Hotel', 'slug' => 'hotel'], ['name' => 'Restaurant', 'slug' => 'restaurant'],
                    ['name' => 'Cloud Kitchen', 'slug' => 'cloud-kitchen'], ['name' => 'Event Venue', 'slug' => 'event-venue'], ['name' => 'Other', 'slug' => 'other'],
                ],
            ],
            ['name' => 'E-commerce & Retail', 'slug' => 'ecommerce', 'icon' => '🛒', 'description' => 'Online stores, D2C brands, retail chains', 'color' => 'fuchsia',
                'business_types' => [
                    ['name' => 'Online Store', 'slug' => 'online-store'], ['name' => 'Fashion', 'slug' => 'fashion'],
                    ['name' => 'D2C Brand', 'slug' => 'd2c'], ['name' => 'Retail Chain', 'slug' => 'retail-chain'], ['name' => 'Other', 'slug' => 'other'],
                ],
            ],
            ['name' => 'Professional Services', 'slug' => 'professional-services', 'icon' => '📊', 'description' => 'CA, CS, lawyers, consultants, digital marketing, architecture', 'color' => 'slate',
                'business_types' => [
                    ['name' => 'CA Firm', 'slug' => 'ca'],
                    ['name' => 'CS (Company Secretary)', 'slug' => 'cs-firm'],
                    ['name' => 'Lawyer / Advocate Firm', 'slug' => 'lawyer-advocate'],
                    ['name' => 'Consultancy', 'slug' => 'consultancy'],
                    ['name' => 'Digital Marketing', 'slug' => 'digital-marketing'],
                    ['name' => 'Architecture', 'slug' => 'architecture'],
                    ['name' => 'Other', 'slug' => 'other'],
                ],
            ],
            ['name' => 'Legal Services', 'slug' => 'legal', 'icon' => '⚖️', 'description' => 'Law firms, Lawyers, CS (Company Secretary), legal consultants', 'color' => 'stone',
                'business_types' => [
                    ['name' => 'Law Firm', 'slug' => 'law-firm'],
                    ['name' => 'Lawyer / Advocate', 'slug' => 'lawyer'],
                    ['name' => 'CS (Company Secretary)', 'slug' => 'cs-firm'],
                    ['name' => 'Legal Consultant', 'slug' => 'consultant'],
                    ['name' => 'Other', 'slug' => 'other'],
                ],
                'modules' => [
                    ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'ChartBarIcon', 'route' => '/'],
                    ['key' => 'leads', 'label' => 'Client Inquiries', 'icon' => 'UserIcon', 'route' => '/leads'],
                    ['key' => 'contacts', 'label' => 'Clients', 'icon' => 'BookOpenIcon', 'route' => '/contacts'],
                    ['key' => 'cases', 'label' => 'Cases & Matters', 'icon' => 'DocumentTextIcon', 'route' => '/deals'],
                    ['key' => 'hearings', 'label' => 'Hearings & Filings', 'icon' => 'ClockIcon', 'route' => '/tasks'],
                    ['key' => 'documents', 'label' => 'Legal Documents', 'icon' => 'ClipboardDocumentCheckIcon', 'route' => '/tasks'],
                    ['key' => 'reports', 'label' => 'Reports', 'icon' => 'ChartPieIcon', 'route' => '/reports'],
                    ['key' => 'settings', 'label' => 'Settings', 'icon' => 'Cog6ToothIcon', 'route' => '/tenant/settings/navigation'],
                ],
                'pipeline' => [
                    'name' => 'Legal & CS Matter Process Flow',
                    'stages' => [
                        ['name' => 'Initial Consultation', 'probability' => 15, 'type' => 'open'],
                        ['name' => 'Client Intake & Retainer', 'probability' => 35, 'type' => 'open'],
                        ['name' => 'Pleadings & ROC Filing Draft', 'probability' => 55, 'type' => 'open'],
                        ['name' => 'Court Proceedings / MCA Filing', 'probability' => 75, 'type' => 'open'],
                        ['name' => 'Compliance Review & Hearing', 'probability' => 90, 'type' => 'open'],
                        ['name' => 'Verdict / Certificate Closure', 'probability' => 100, 'type' => 'won'],
                        ['name' => 'Matter Closed / Dismissed', 'probability' => 0, 'type' => 'lost'],
                    ],
                ],
                'fields' => [
                    ['key' => 'case_type', 'label' => 'Case / Compliance Type', 'type' => 'dropdown', 'module' => 'leads', 'options' => ['Civil Litigation', 'Criminal Defense', 'Corporate CS Compliance', 'ROC / MCA Annual Filing', 'Property & Real Estate', 'Family & Matrimonial', 'Intellectual Property (IPR)', 'Taxation & Audit']],
                    ['key' => 'court_forum', 'label' => 'Court / Authority Forum', 'type' => 'dropdown', 'module' => 'leads', 'options' => ['High Court', 'District Court', 'Supreme Court', 'NCLT', 'ROC / Ministry of Corporate Affairs', 'Arbitration Tribunal', 'Consumer Forum']],
                    ['key' => 'opposite_party', 'label' => 'Opposite Party / Advocate', 'type' => 'text', 'module' => 'leads'],
                    ['key' => 'case_number', 'label' => 'Case / Filing Reg Number', 'type' => 'text', 'module' => 'leads'],
                    ['key' => 'hearing_date', 'label' => 'Next Hearing / Due Date', 'type' => 'date', 'module' => 'leads'],
                ],
                'widgets' => [
                    ['key' => 'active_cases', 'label' => 'Active Matters', 'type' => 'stat', 'cols' => 1, 'config' => ['model' => 'deals', 'aggregate' => 'count', 'icon' => '⚖️', 'color' => 'indigo']],
                    ['key' => 'roc_filings', 'label' => 'Court / ROC Deadlines', 'type' => 'stat', 'cols' => 1, 'config' => ['model' => 'tasks', 'aggregate' => 'count', 'filter' => 'today', 'icon' => '🏛️', 'color' => 'rose']],
                    ['key' => 'retainers_collected', 'label' => 'Retainer Fees', 'type' => 'stat', 'cols' => 1, 'config' => ['model' => 'deals', 'aggregate' => 'sum', 'field' => 'value', 'icon' => '💰', 'color' => 'green']],
                ],
            ],
            ['name' => 'Marketing & Advertising', 'slug' => 'marketing', 'icon' => '📢', 'description' => 'Digital marketing, advertising, PR, influencer agencies', 'color' => 'pink',
                'business_types' => [
                    ['name' => 'Digital Marketing Agency', 'slug' => 'digital'], ['name' => 'Advertising Agency', 'slug' => 'advertising'],
                    ['name' => 'PR Agency', 'slug' => 'pr'], ['name' => 'SEO Agency', 'slug' => 'seo'], ['name' => 'Other', 'slug' => 'other'],
                ],
            ],
            ['name' => 'Construction & Infrastructure', 'slug' => 'construction', 'icon' => '🏗️', 'description' => 'Construction companies, contractors, interior designers', 'color' => 'amber',
                'business_types' => [
                    ['name' => 'Construction Company', 'slug' => 'construction'], ['name' => 'Contractor', 'slug' => 'contractor'],
                    ['name' => 'Interior Designer', 'slug' => 'interior'], ['name' => 'Architect', 'slug' => 'architect'], ['name' => 'Other', 'slug' => 'other'],
                ],
            ],
            ['name' => 'Retail & Wholesale', 'slug' => 'retail-wholesale', 'icon' => '🏪', 'description' => 'Distributors, wholesalers, dealers, franchise', 'color' => 'lime',
                'business_types' => [
                    ['name' => 'Distributor', 'slug' => 'distributor'], ['name' => 'Wholesaler', 'slug' => 'wholesaler'],
                    ['name' => 'Franchise', 'slug' => 'franchise'], ['name' => 'Other', 'slug' => 'other'],
                ],
            ],
            ['name' => 'Logistics & Transportation', 'slug' => 'logistics', 'icon' => '🚛', 'description' => 'Courier, freight, transport, warehousing', 'color' => 'blue',
                'business_types' => [
                    ['name' => 'Courier Company', 'slug' => 'courier'], ['name' => 'Freight Company', 'slug' => 'freight'],
                    ['name' => 'Transport Company', 'slug' => 'transport'], ['name' => 'Warehousing', 'slug' => 'warehousing'], ['name' => 'Other', 'slug' => 'other'],
                ],
            ],
            ['name' => 'Telecom', 'slug' => 'telecom', 'icon' => '📡', 'description' => 'ISP, mobile operators, broadband, cable', 'color' => 'indigo',
                'business_types' => [
                    ['name' => 'ISP', 'slug' => 'isp'], ['name' => 'Mobile Operator', 'slug' => 'mobile-operator'],
                    ['name' => 'Broadband', 'slug' => 'broadband'], ['name' => 'Other', 'slug' => 'other'],
                ],
            ],
            ['name' => 'Energy & Utilities', 'slug' => 'energy', 'icon' => '⚡', 'description' => 'Solar, electricity, renewable energy, EV charging', 'color' => 'yellow',
                'business_types' => [
                    ['name' => 'Solar Company', 'slug' => 'solar'], ['name' => 'Renewable Energy', 'slug' => 'renewable'],
                    ['name' => 'EV Charging', 'slug' => 'ev-charging'], ['name' => 'Other', 'slug' => 'other'],
                ],
            ],
            ['name' => 'Agriculture', 'slug' => 'agriculture', 'icon' => '🌾', 'description' => 'Agri-tech, fertilizers, seeds, farm equipment', 'color' => 'green',
                'business_types' => [
                    ['name' => 'Agri-Tech', 'slug' => 'agritech'], ['name' => 'Fertilizer Company', 'slug' => 'fertilizer'],
                    ['name' => 'Farm Equipment', 'slug' => 'farm-equipment'], ['name' => 'Other', 'slug' => 'other'],
                ],
            ],
            ['name' => 'Pharmaceutical', 'slug' => 'pharma', 'icon' => '💊', 'description' => 'Pharma companies, medical equipment, distributors', 'color' => 'teal',
                'business_types' => [
                    ['name' => 'Pharma Company', 'slug' => 'pharma'], ['name' => 'Medical Equipment', 'slug' => 'medical-equipment'],
                    ['name' => 'Medical Distributor', 'slug' => 'distributor'], ['name' => 'Other', 'slug' => 'other'],
                ],
            ],
            ['name' => 'Media & Entertainment', 'slug' => 'media', 'icon' => '🎬', 'description' => 'Production houses, OTT, media agencies, events', 'color' => 'rose',
                'business_types' => [
                    ['name' => 'Production House', 'slug' => 'production'], ['name' => 'OTT Platform', 'slug' => 'ott'],
                    ['name' => 'Media Agency', 'slug' => 'media-agency'], ['name' => 'Event Company', 'slug' => 'event'], ['name' => 'Other', 'slug' => 'other'],
                ],
            ],
            ['name' => 'Fitness & Wellness', 'slug' => 'fitness', 'icon' => '💪', 'description' => 'Gyms, yoga, fitness trainers, sports academies', 'color' => 'emerald',
                'business_types' => [
                    ['name' => 'Gym', 'slug' => 'gym'], ['name' => 'Yoga Center', 'slug' => 'yoga'],
                    ['name' => 'Fitness Trainer', 'slug' => 'trainer'], ['name' => 'Sports Academy', 'slug' => 'sports'], ['name' => 'Other', 'slug' => 'other'],
                ],
            ],
            ['name' => 'NGOs & Nonprofits', 'slug' => 'ngo', 'icon' => '🤝', 'description' => 'NGOs, charities, foundations, social organizations', 'color' => 'green',
                'business_types' => [
                    ['name' => 'NGO', 'slug' => 'ngo'], ['name' => 'Charity', 'slug' => 'charity'],
                    ['name' => 'Foundation', 'slug' => 'foundation'], ['name' => 'Other', 'slug' => 'other'],
                ],
            ],
            ['name' => 'Government & Public Services', 'slug' => 'government', 'icon' => '🏛️', 'description' => 'Government departments, municipal, citizen services', 'color' => 'slate',
                'business_types' => [
                    ['name' => 'Government Department', 'slug' => 'department'], ['name' => 'Municipal Services', 'slug' => 'municipal'],
                    ['name' => 'Citizen Services', 'slug' => 'citizen'], ['name' => 'Other', 'slug' => 'other'],
                ],
            ],
            ['name' => 'Events & Wedding', 'slug' => 'events', 'icon' => '🎉', 'description' => 'Wedding planners, event planners, photographers, caterers', 'color' => 'pink',
                'business_types' => [
                    ['name' => 'Wedding Planner', 'slug' => 'wedding'], ['name' => 'Event Planner', 'slug' => 'event'],
                    ['name' => 'Photographer', 'slug' => 'photographer'], ['name' => 'Caterer', 'slug' => 'caterer'], ['name' => 'Other', 'slug' => 'other'],
                ],
            ],
            ['name' => 'Beauty & Personal Care', 'slug' => 'beauty', 'icon' => '💅', 'description' => 'Salons, spas, beauty clinics, cosmetic brands', 'color' => 'fuchsia',
                'business_types' => [
                    ['name' => 'Salon', 'slug' => 'salon'], ['name' => 'Spa', 'slug' => 'spa'],
                    ['name' => 'Beauty Clinic', 'slug' => 'clinic'], ['name' => 'Cosmetic Brand', 'slug' => 'cosmetic'], ['name' => 'Other', 'slug' => 'other'],
                ],
            ],
            ['name' => 'B2B Sales', 'slug' => 'b2b', 'icon' => '🤝', 'description' => 'B2B, corporate sales, enterprise, distribution', 'color' => 'indigo',
                'business_types' => [
                    ['name' => 'B2B Business', 'slug' => 'b2b'], ['name' => 'Corporate Sales', 'slug' => 'corporate'],
                    ['name' => 'Enterprise Sales', 'slug' => 'enterprise'], ['name' => 'Distribution', 'slug' => 'distribution'], ['name' => 'Other', 'slug' => 'other'],
                ],
            ],
            ['name' => 'Freelancers & Small Businesses', 'slug' => 'freelancer', 'icon' => '🧑‍💻', 'description' => 'Freelancers, consultants, small agencies, local businesses', 'color' => 'violet',
                'business_types' => [
                    ['name' => 'Freelancer', 'slug' => 'freelancer'], ['name' => 'Consultant', 'slug' => 'consultant'],
                    ['name' => 'Small Agency', 'slug' => 'small-agency'], ['name' => 'Home Services', 'slug' => 'home-services'], ['name' => 'Other', 'slug' => 'other'],
                ],
            ],
        ];
    }
}
