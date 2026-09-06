<?php

namespace App\Services;

class IndustrySchemaService
{
    /**
     * Get dynamic industry configuration, entities, stats, templates, and presets
     */
    public static function getIndustryConfig(?string $slug = null): array
    {
        $slug = strtolower(trim((string) $slug));

        $configs = [
            'research-publication' => [
                'slug' => 'research-publication',
                'name' => 'Research Journal & Paper Publication',
                'icon' => '📑',
                'color' => 'indigo',
                'banner_title' => 'Research Journal & Manuscript Import Hub',
                'banner_desc' => 'Import manuscripts, author submissions, peer reviewers, publication issues, and APC fee records from Excel spreadsheets and database dumps.',
                'stats_labels' => [
                    'candidates' => ['title' => 'Peer Reviewers', 'desc' => 'Active editors & reviewers'],
                    'vacancies' => ['title' => 'Journal Issues', 'desc' => 'Upcoming volumes & editions'],
                    'contacts' => ['title' => 'Authors & Researchers', 'desc' => 'Active submitting researchers'],
                ],
                'entities' => [
                    [
                        'id' => 'manuscripts',
                        'name' => '📑 Research Manuscripts & Papers',
                        'desc' => 'Paper title, author, target journal, domain, status, plagiarism %, DOI',
                        'icon' => '📑',
                        'table' => 'contacts',
                        'columns' => ['paper_title', 'author_name', 'email', 'phone', 'target_journal', 'research_domain', 'similarity_percentage', 'status'],
                        'sample_rows' => [
                            ['Deep Learning Approaches for Autonomous Drone Navigation', 'Dr. Ramesh Kulkarni', 'ramesh.k@iitd.ac.in', '+91 98451 23456', 'Scopus Index Engineering Journal', 'Computer Science & AI', '8%', 'Accepted for Publication'],
                            ['Novel Phytochemical Extraction from Zingiberaceae for Antimicrobial Efficacy', 'Dr. Sunita Deshmukh', 's.deshmukh@univ.edu', '+91 97654 32109', 'Medical & Pharma Research Journal', 'Medical & Health Sciences', '5%', 'Published & DOI Assigned'],
                        ]
                    ],
                ]
            ],
            'insurance' => [
                'slug' => 'insurance',
                'name' => 'Insurance',
                'icon' => '🛡️',
                'color' => 'teal',
                'banner_title' => 'Insurance CRM Data Import Hub',
                'banner_desc' => 'Import policyholders, claims, insurance brokers, underwriting files, and coverage records from Excel spreadsheets and database dumps.',
                'stats_labels' => [
                    'candidates' => ['title' => 'Underwriters & Adjusters', 'desc' => 'Claims adjusters & brokers in pipeline'],
                    'vacancies' => ['title' => 'Insurance Vacancies', 'desc' => 'Active insurance & agency roles'],
                    'contacts' => ['title' => 'Policyholders & Leads', 'desc' => 'Active insured member database'],
                ],
                'entities' => [
                    [
                        'id' => 'policyholders',
                        'name' => '🛡️ Policyholders & Insured Members',
                        'desc' => 'Policy number, insured name, email, phone, coverage type, premium, expiry date',
                        'icon' => '🛡️',
                        'table' => 'contacts',
                        'columns' => ['policy_number', 'insured_name', 'email', 'phone', 'coverage_type', 'premium_amount', 'expiry_date', 'status'],
                        'sample_rows' => [
                            ['POL-984210', 'Rahul Sharma', 'rahul.sharma@gmail.com', '+91 98765 43210', 'Comprehensive Health Shield', '24500.00', '2027-08-30', 'Active'],
                            ['POL-773192', 'Priya Deshmukh', 'priya.deshmukh@yahoo.com', '+91 91234 56789', 'Motor Zero-Depreciation Auto', '14200.00', '2027-03-15', 'Active'],
                            ['POL-551094', 'Aniket Verma', 'aniket.v@corporate.com', '+91 99887 76655', 'Term Life Cover (1 Crore)', '18900.00', '2035-12-31', 'Active'],
                        ]
                    ],
                    [
                        'id' => 'claims',
                        'name' => '📋 Claims & Underwriting Applications',
                        'desc' => 'Claim ID, policy reference, incident date, claim amount, adjuster assigned, approval stage',
                        'icon' => '📋',
                        'table' => 'job_applications',
                        'columns' => ['claim_id', 'applicant_name', 'email', 'phone', 'experience_years', 'stage', 'notes'],
                        'sample_rows' => [
                            ['CLM-2026-001', 'Arjun Kapoor (Cashless Mediclaim)', 'arjun.k@apollo.org', '+91 98111 22334', 'Cashless Hospitalization', 'Interview Scheduled', 'Pre-authorized for knee arthroscopy at Fortis Hospital.'],
                            ['CLM-2026-002', 'Sunita Rao (Vehicle Collision Claim)', 'sunita.rao@outlook.com', '+91 98222 33445', 'Motor Accident Triage', 'Offer Sent', 'Surveyor report approved. Parts reimbursement cleared.'],
                        ]
                    ],
                    [
                        'id' => 'insurance_vacancies',
                        'name' => '💼 Insurance Careers & Staff Openings',
                        'desc' => 'Job title, underwriting department, branch location, employment type, salary',
                        'icon' => '💼',
                        'table' => 'job_postings',
                        'columns' => ['title', 'department', 'location', 'employment_type', 'salary_min', 'salary_max', 'status', 'description'],
                        'sample_rows' => [
                            ['Senior Health Underwriter', 'Underwriting & Risk Analysis', 'Mumbai HQ, BKC', 'Full-Time', '900000', '1500000', 'Active', 'Assess retail and corporate health underwriting proposals and medical risk margins.'],
                            ['Motor Claims Surveyor & Adjuster', 'Motor Claims Division', 'Delhi NCR Regional Office', 'Full-Time', '600000', '950000', 'Active', 'On-site vehicle accidental damage inspection and cashless garage coordination.'],
                            ['Actuarial Pricing Specialist', 'Actuarial & Product Design', 'Bengaluru Branch', 'Full-Time', '1400000', '2200000', 'Active', 'Design statistical premium models and loss-ratio analytics for term products.'],
                        ]
                    ],
                    [
                        'id' => 'agents',
                        'name' => '👥 Insurance Agents & Agency Brokers',
                        'desc' => 'Agent code, license number, branch, commission rate, active policies',
                        'icon' => '👥',
                        'table' => 'users',
                        'columns' => ['name', 'email', 'phone', 'role', 'department', 'designation', 'salary'],
                        'sample_rows' => [
                            ['Vikram Sethi (Agent #AG-404)', 'vikram.sethi@jrvinsurance.com', '+91 98444 55667', 'Agent', 'Agency Channel', 'Senior Insurance Advisor', '45000'],
                            ['Meera Iyer (Corporate Broker)', 'meera.iyer@jrvinsurance.com', '+91 98555 66778', 'Broker', 'Corporate Partnerships', 'Direct Broker Lead', '65000'],
                        ]
                    ],
                    [
                        'id' => 'crm_records',
                        'name' => '📊 Custom Dynamic Policy Records',
                        'desc' => 'Dynamic JSON key-value pairs matching custom insurance columns',
                        'icon' => '📊',
                        'table' => 'tenant_crm_records',
                        'columns' => ['policy_holder', 'policy_type', 'premium', 'agent_assigned', 'status'],
                        'sample_rows' => [
                            ['Tata Motors Commercial Fleet', 'Commercial Fleet Cover', '450000.00', 'Meera Iyer', 'Active'],
                        ]
                    ],
                ],
                'ai_presets' => [
                    [
                        'id' => 'health_motor_insurance',
                        'name' => 'Health & Motor Insurance Hub',
                        'icon' => '🛡️',
                        'color' => 'teal',
                        'description' => 'Policy issuance, cashless hospital desk, and motor claim surveyor dispatch.',
                        'prompt' => 'Transform my CRM into a comprehensive Health and Auto Insurance Platform with policy issuance stages, cashless TPA hospital desk, and surveyor claim triage.',
                        'specialty' => 'General & Health Insurance',
                        'pipeline_stages' => [
                            ['name' => 'Quote Generated & Proposal', 'color' => 'teal', 'sla_hours' => 4],
                            ['name' => 'Medical / Vehicle Inspection', 'color' => 'indigo', 'sla_hours' => 24],
                            ['name' => 'Underwriting Approval', 'color' => 'purple', 'sla_hours' => 12],
                            ['name' => 'Policy Active & Bound', 'color' => 'emerald', 'sla_hours' => 1],
                            ['name' => 'Claim Triage / Cashless Despatch', 'color' => 'rose', 'sla_hours' => 6],
                            ['name' => 'Settlement & Renewal Recall', 'color' => 'cyan', 'sla_hours' => 720],
                        ],
                        'custom_fields' => [
                            ['key' => 'policy_number', 'label' => 'Policy Number', 'type' => 'text', 'default' => 'POL-2026-XXXX'],
                            ['key' => 'sum_insured', 'label' => 'Sum Insured (₹ / $)', 'type' => 'number', 'default' => '1000000'],
                            ['key' => 'premium_amount', 'label' => 'Annual Premium', 'type' => 'number', 'default' => '22500'],
                            ['key' => 'tpa_cashless_desk', 'label' => 'TPA Cashless Network Hospital', 'type' => 'text', 'default' => 'Apollo Specialty Hospital'],
                            ['key' => 'claim_status', 'label' => 'Claim Processing Status', 'type' => 'select', 'options' => ['No Claim', 'Cashless Claim Approved', 'Surveyor Under Review', 'Reimbursement Cleared', 'Claim Denied']],
                        ],
                        'automations' => [
                            'Automated policy renewal WhatsApp reminder 30 days before expiration date',
                            'Instant alert to Claims Manager if cashless claim amount exceeds ₹5,00,000',
                            'Auto-generate digitally signed Policy Certificate PDF upon payment clearance',
                        ],
                    ],
                    [
                        'id' => 'life_term_insurance',
                        'name' => 'Life & Term Insurance Engine',
                        'icon' => '💼',
                        'color' => 'indigo',
                        'description' => 'Term life underwriting, medical checkup coordination, and nominee management.',
                        'prompt' => 'Adapt my CRM for a Life Insurance Agency with term plan underwriting, tele-medical verification, and nominee allocation tracking.',
                        'specialty' => 'Life & Pension Products',
                        'pipeline_stages' => [
                            ['name' => 'Lead Inquiry & Term Quote', 'color' => 'indigo', 'sla_hours' => 6],
                            ['name' => 'Tele-MER (Medical Phone Interview)', 'color' => 'cyan', 'sla_hours' => 24],
                            ['name' => 'Diagnostic Lab Blood / ECG Test', 'color' => 'purple', 'sla_hours' => 48],
                            ['name' => 'Chief Underwriter Assessment', 'color' => 'amber', 'sla_hours' => 24],
                            ['name' => 'Policy Dispatched & Active', 'color' => 'emerald', 'sla_hours' => 12],
                        ],
                        'custom_fields' => [
                            ['key' => 'term_cover_amount', 'label' => 'Term Cover Sum Insured', 'type' => 'select', 'options' => ['₹50 Lakhs', '₹1 Crore', '₹2 Crores', '₹5 Crores+']],
                            ['key' => 'smoker_status', 'label' => 'Tobacco / Nicotine Usage', 'type' => 'select', 'options' => ['Non-Smoker', 'Smoker (<10/day)', 'Smoker (>10/day)']],
                            ['key' => 'nominee_name', 'label' => 'Primary Nominee Name', 'type' => 'text', 'default' => 'Spouse / Parent'],
                            ['key' => 'nominee_relationship', 'label' => 'Nominee Relationship', 'type' => 'text', 'default' => 'Spouse'],
                        ],
                        'automations' => [
                            'Auto-schedule at-home diagnostic blood collection on proposal submission',
                            'Send annual tax benefit certificate (80C / 10(10D)) at end of financial year',
                            'Notify relationship manager when term premium installment is due in 7 days',
                        ],
                    ],
                ],
            ],

            'healthcare' => [
                'slug' => 'healthcare',
                'name' => 'Healthcare',
                'icon' => '🏥',
                'color' => 'emerald',
                'banner_title' => 'Healthcare CRM Data Import Hub',
                'banner_desc' => 'Import medical candidates, hospital job openings, patient contacts, and clinical diagnostics from Excel spreadsheets and database dumps.',
                'stats_labels' => [
                    'candidates' => ['title' => 'Medical Candidates', 'desc' => 'Doctors & nurses in pipeline'],
                    'vacancies' => ['title' => 'Clinical Vacancies', 'desc' => 'Active hospital openings'],
                    'contacts' => ['title' => 'Patients & Contacts', 'desc' => 'Active patient database'],
                ],
                'entities' => [
                    [
                        'id' => 'healthcare_candidates',
                        'name' => '🩺 Medical Candidates & Doctors',
                        'desc' => 'Doctor name, email, phone, experience, role, pipeline stage, notes',
                        'icon' => '🩺',
                        'table' => 'job_applications',
                        'columns' => ['applicant_name', 'email', 'phone', 'experience_years', 'stage', 'notes', 'job_title', 'department'],
                        'sample_rows' => [
                            ['Dr. Aarav Mehta, MD', 'aarav.mehta@healthmed.org', '+1 (555) 234-7890', '8+ years', 'Interview Scheduled', 'Board certified in Cardiovascular Disease. Fellowship at Johns Hopkins.', 'Consultant Cardiologist', 'Cardiology & Heart Care'],
                            ['Nurse Sarah Jenkins, BSN', 's.jenkins@nursingcare.com', '+1 (555) 876-5432', '6 years', 'Offer Sent', 'CCRN certified with 6 years level-1 trauma and ICU ECMO experience.', 'Critical Care Nurse Lead', 'Emergency & ICU'],
                        ]
                    ],
                    [
                        'id' => 'healthcare_vacancies',
                        'name' => '🏥 Clinical & Hospital Vacancies',
                        'desc' => 'Job title, department, hospital location, employment type, salary range',
                        'icon' => '🏥',
                        'table' => 'job_postings',
                        'columns' => ['title', 'department', 'location', 'employment_type', 'salary_min', 'salary_max', 'status', 'description'],
                        'sample_rows' => [
                            ['Consultant Cardiologist & Heart Specialist', 'Cardiology & Heart Care', 'Metro General Hospital, NY', 'Full-Time', '180000', '260000', 'Active', 'Lead clinical cardiology rounds, catheterization procedures, and diagnostic echo.'],
                            ['Head of Critical Care Nursing (ICU Lead)', 'Emergency & Intensive Care', 'St. Jude Medical Center, Chicago', 'Full-Time', '85000', '120000', 'Active', 'Manage critical care nursing staff, trauma triage, ventilator management.'],
                        ]
                    ],
                    [
                        'id' => 'patients',
                        'name' => '👥 Patients & Clinical Inquiries',
                        'desc' => 'Patient name, email, phone, stage, clinical notes',
                        'icon' => '👥',
                        'table' => 'contacts',
                        'columns' => ['name', 'email', 'phone', 'company', 'notes', 'stage'],
                        'sample_rows' => [
                            ['Eleanor Vance', 'e.vance@gmail.com', '+1 (555) 345-6789', 'Apollo Clinic', 'Scheduled for cardiology evaluation and treadmill stress test.', 'Active Patient'],
                            ['Robert Sterling', 'robert.s@outlook.com', '+1 (555) 987-6543', 'General Hospital', 'Post-operative follow-up appointment for laparoscopic surgery.', 'Follow-up Due'],
                        ]
                    ],
                    [
                        'id' => 'doctors',
                        'name' => '👨‍⚕️ Doctors & Medical Staff Accounts',
                        'desc' => 'Full name, email, department, designation, salary, status',
                        'icon' => '👨‍⚕️',
                        'table' => 'users',
                        'columns' => ['name', 'email', 'phone', 'role', 'department', 'designation', 'salary'],
                        'sample_rows' => [
                            ['Dr. Priya Sharma, MD', 'dr.priya@hospital.org', '+1 (555) 234-9988', 'Doctor', 'Radiology', 'Chief Radiologist', '190000'],
                        ]
                    ],
                    [
                        'id' => 'crm_records',
                        'name' => '📊 Custom Dynamic Health Records',
                        'desc' => 'Dynamic JSON key-value pairs matching custom columns',
                        'icon' => '📊',
                        'table' => 'tenant_crm_records',
                        'columns' => ['patient_name', 'diagnosis', 'blood_group', 'attending_physician', 'status'],
                        'sample_rows' => [
                            ['David Wilson', 'Hypertension & Arrhythmia', 'O+', 'Dr. Aarav Mehta', 'Under Treatment'],
                        ]
                    ],
                ],
                'ai_presets' => [
                    [
                        'id' => 'cardiology_clinic',
                        'name' => 'Cardiology & Cath-Lab Center',
                        'icon' => '❤️',
                        'color' => 'rose',
                        'description' => 'ECG triage, Angiography scheduling, Holter monitoring, and Cardiac ICU bed allocation.',
                        'prompt' => 'Transform my CRM into a full-scale Cardiology Center with ECG triage stages and catheterization scheduling.',
                        'specialty' => 'Cardiology & Cardiovascular Care',
                    ],
                    [
                        'id' => 'pediatrics_vaccine',
                        'name' => 'Pediatrics & Child Wellness',
                        'icon' => '👶',
                        'color' => 'indigo',
                        'description' => 'Growth milestones tracking, WHO immunization scheduler, and pediatric consultations.',
                        'prompt' => 'Adapt my CRM for a Pediatric Specialty Hospital with WHO vaccination tracking and pediatric workflows.',
                        'specialty' => 'Pediatrics & Neonatal Care',
                    ],
                ],
            ],

            'real-estate' => [
                'slug' => 'real-estate',
                'name' => 'Real Estate',
                'icon' => '🏠',
                'color' => 'amber',
                'banner_title' => 'Real Estate CRM Data Import Hub',
                'banner_desc' => 'Import property buyers, tenant inquiries, property listings, real estate brokers, and site visits from spreadsheets and database dumps.',
                'stats_labels' => [
                    'candidates' => ['title' => 'Realtors & Agents', 'desc' => 'Property advisors in pipeline'],
                    'vacancies' => ['title' => 'Agency Openings', 'desc' => 'Active property sales openings'],
                    'contacts' => ['title' => 'Buyers & Investors', 'desc' => 'Active property buyer database'],
                ],
                'entities' => [
                    [
                        'id' => 'property_leads',
                        'name' => '🏠 Property Buyers & Tenant Inquiries',
                        'desc' => 'Buyer name, budget range, preferred location, property type, status',
                        'icon' => '🏠',
                        'table' => 'contacts',
                        'columns' => ['name', 'email', 'phone', 'budget', 'preferred_location', 'property_type', 'status'],
                        'sample_rows' => [
                            ['Amit Singhal', 'amit.singhal@gmail.com', '+91 98123 45678', '₹1.5 - 2.0 Cr', 'Bandra West, Mumbai', '3 BHK Luxury Apartment', 'Site Visit Scheduled'],
                            ['Kavita Nair', 'kavita.n@yahoo.com', '+91 98234 56789', '₹75 Lakhs', 'Whitefield, Bangalore', '2 BHK Villa / Apartment', 'Negotiation Stage'],
                        ]
                    ],
                    [
                        'id' => 'properties',
                        'name' => '🏢 Property Listings & Units',
                        'desc' => 'Property title, price, BHK/area, address, possession status',
                        'icon' => '🏢',
                        'table' => 'properties',
                        'columns' => ['title', 'price', 'type', 'bhk', 'area_sqft', 'location', 'status'],
                        'sample_rows' => [
                            ['Godrej Horizon Tower A-1204', '18500000', 'Apartment', '3 BHK', '1450', 'Wadala East, Mumbai', 'Ready to Move'],
                            ['Prestige Green Glen Villa #18', '25000000', 'Villa', '4 BHK', '2800', 'Sarjapur Road, Bengaluru', 'Under Construction'],
                        ]
                    ],
                    [
                        'id' => 'real_estate_vacancies',
                        'name' => '💼 Real Estate Careers & Agent Roles',
                        'desc' => 'Job title, agency branch, employment type, commission structure',
                        'icon' => '💼',
                        'table' => 'job_postings',
                        'columns' => ['title', 'department', 'location', 'employment_type', 'salary_min', 'salary_max', 'status', 'description'],
                        'sample_rows' => [
                            ['Luxury Real Estate Sales Manager', 'Residential Sales', 'Worli Hub, Mumbai', 'Full-Time', '800000', '1600000', 'Active', 'Lead high-ticket ultra-luxury client pitches and channel partner closures.'],
                        ]
                    ],
                    [
                        'id' => 'crm_records',
                        'name' => '📊 Custom Dynamic Property Records',
                        'desc' => 'Dynamic JSON key-value pairs matching custom columns',
                        'icon' => '📊',
                        'table' => 'tenant_crm_records',
                        'columns' => ['lead_name', 'project_name', 'unit_no', 'token_amount', 'status'],
                        'sample_rows' => [
                            ['Ramesh Kumar', 'Oberoi Elysian', 'Tower C-1802', '₹5,00,000', 'Booking Confirmed'],
                        ]
                    ],
                ],
                'ai_presets' => [],
            ],

            'education' => [
                'slug' => 'education',
                'name' => 'Education & Training',
                'icon' => '🎓',
                'color' => 'indigo',
                'banner_title' => 'Education & Admissions CRM Data Import Hub',
                'banner_desc' => 'Import student applicants, course registrations, academic batches, and counselors from spreadsheets and databases.',
                'stats_labels' => [
                    'candidates' => ['title' => 'Faculty & Tutors', 'desc' => 'Educators & lecturers in pipeline'],
                    'vacancies' => ['title' => 'Academic Openings', 'desc' => 'Active teaching & counselor roles'],
                    'contacts' => ['title' => 'Students & Enquiries', 'desc' => 'Active student admissions pool'],
                ],
                'entities' => [
                    [
                        'id' => 'student_applicants',
                        'name' => '🎓 Student Admissions & Inquiries',
                        'desc' => 'Student name, course interested, intake year, test score, fee status',
                        'icon' => '🎓',
                        'table' => 'contacts',
                        'columns' => ['name', 'email', 'phone', 'course_interested', 'intake_year', 'admission_stage'],
                        'sample_rows' => [
                            ['Aditya Roy', 'aditya.roy@edu.org', '+91 98777 66554', 'B.Tech Computer Science', '2026-27', 'Document Verification'],
                            ['Simran Kaur', 'simran.k@gmail.com', '+91 98888 77665', 'MBA Finance & Analytics', '2026-27', 'Fee Paid - Enrolled'],
                        ]
                    ],
                    [
                        'id' => 'academic_vacancies',
                        'name' => '💼 Faculty & Counselor Vacancies',
                        'desc' => 'Job title, faculty department, campus location, salary package',
                        'icon' => '💼',
                        'table' => 'job_postings',
                        'columns' => ['title', 'department', 'location', 'employment_type', 'salary_min', 'salary_max', 'status', 'description'],
                        'sample_rows' => [
                            ['Assistant Professor - Artificial Intelligence', 'School of Computing', 'Main Campus', 'Full-Time', '750000', '1300000', 'Active', 'Teach Machine Learning and Data Structures to undergraduate cohorts.'],
                        ]
                    ],
                    [
                        'id' => 'crm_records',
                        'name' => '📊 Custom Dynamic Admission Records',
                        'desc' => 'Dynamic JSON key-value pairs matching custom academic columns',
                        'icon' => '📊',
                        'table' => 'tenant_crm_records',
                        'columns' => ['student_name', 'program', 'enrollment_no', 'scholarship_percent', 'status'],
                        'sample_rows' => [
                            ['Manish Gupta', 'B.Sc Data Science', 'ENR-2026-4401', '25%', 'Admitted'],
                        ]
                    ],
                ],
                'ai_presets' => [],
            ],
        ];

        // Match requested slug or fallback to general default
        if ($slug && isset($configs[$slug])) {
            return $configs[$slug];
        }

        // Generic fallback for any other industry
        $name = !empty($slug) ? ucwords(str_replace(['-', '_'], ' ', $slug)) : 'General Business';
        return [
            'slug' => $slug ?: 'general',
            'name' => $name,
            'icon' => '⚡',
            'color' => 'indigo',
            'banner_title' => "{$name} CRM Data Import Hub",
            'banner_desc' => "Import {$name} contacts, leads, deals, job openings, and records from spreadsheets and databases.",
            'stats_labels' => [
                'candidates' => ['title' => 'Candidates & Applicants', 'desc' => 'Applicants in pipeline'],
                'vacancies' => ['title' => 'Open Vacancies', 'desc' => 'Active team & job openings'],
                'contacts' => ['title' => 'Customers & Leads', 'desc' => 'Active customer database'],
            ],
            'entities' => [
                [
                    'id' => 'contacts',
                    'name' => "👥 {$name} Leads & Contacts",
                    'desc' => 'Contact name, email, phone, company, lead status, deal value',
                    'icon' => '👥',
                    'table' => 'contacts',
                    'columns' => ['name', 'email', 'phone', 'company', 'status', 'notes'],
                    'sample_rows' => [
                        ['John Doe', 'john.doe@enterprise.com', '+1 (555) 019-2831', 'Acme Corp', 'Lead Qualified', 'Initial consultation completed.'],
                    ]
                ],
                [
                    'id' => 'vacancies',
                    'name' => "💼 Careers & Job Openings",
                    'desc' => 'Job title, department, location, employment type, salary',
                    'icon' => '💼',
                    'table' => 'job_postings',
                    'columns' => ['title', 'department', 'location', 'employment_type', 'salary_min', 'salary_max', 'status', 'description'],
                    'sample_rows' => [
                        ['Senior Operations Specialist', 'Operations', 'Headquarters', 'Full-Time', '70000', '110000', 'Active', 'Lead operations and client success.'],
                    ]
                ],
                [
                    'id' => 'candidates',
                    'name' => "📋 Job Applicants & Resumes",
                    'desc' => 'Applicant name, email, phone, experience, pipeline stage, notes',
                    'icon' => '📋',
                    'table' => 'job_applications',
                    'columns' => ['applicant_name', 'email', 'phone', 'experience_years', 'stage', 'notes'],
                    'sample_rows' => [
                        ['Jane Smith', 'jane.smith@email.com', '+1 (555) 837-1928', '5 years', 'Interview Scheduled', 'Strong background and credentials.'],
                    ]
                ],
                [
                    'id' => 'crm_records',
                    'name' => '📊 Custom Dynamic CRM Records',
                    'desc' => 'Dynamic JSON key-value pairs matching custom columns',
                    'icon' => '📊',
                    'table' => 'tenant_crm_records',
                    'columns' => ['record_name', 'category', 'amount', 'owner', 'status'],
                    'sample_rows' => [
                        ['Q3 Expansion Contract', 'Enterprise Client', '125000.00', 'Account Lead', 'In Progress'],
                    ]
                ],
            ],
            'ai_presets' => [],
        ];
    }
}
