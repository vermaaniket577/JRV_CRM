<?php

namespace Database\Seeders;

use App\Models\AutoUpdateRule;
use App\Models\BroadcastMessage;
use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Models\Padhadhikari;
use App\Models\User;
use Illuminate\Database\Seeder;

class SidebarModulesSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::first();

        // 1. Seed BroadCast Messages
        BroadcastMessage::create([
            'title' => 'Sunday Community Bio-data Meet Announcement',
            'channel' => 'WhatsApp',
            'target_audience' => 'All Verified Members',
            'message_body' => 'Jai Jinendra! Join our monthly community bio-data matchmaker meeting this Sunday at 10 AM.',
            'sent_count' => 3500,
            'delivered_count' => 3440,
            'read_count' => 2850,
            'status' => 'Completed',
            'created_by' => $admin?->id ?? 1,
        ]);

        BroadcastMessage::create([
            'title' => 'Special VIP Bio-data Promotion',
            'channel' => 'Email',
            'target_audience' => 'Premium Subscription Tier',
            'message_body' => 'Upgrade to Platinum Plan to unlock 50 additional candidate contact views today.',
            'sent_count' => 1200,
            'delivered_count' => 1180,
            'read_count' => 890,
            'status' => 'Completed',
            'created_by' => $admin?->id ?? 1,
        ]);

        // 2. Seed Auto Update Rules
        AutoUpdateRule::create([
            'rule_name' => 'Profile Renewal Follow-up Alert',
            'trigger_event' => 'Subscription Expiring in 7 Days',
            'frequency' => 'Daily at 08:00 AM',
            'action_type' => 'Send Automatic WhatsApp & Email Alert',
            'is_active' => true,
            'processed_count' => 482,
            'last_run_at' => now()->subHours(4),
        ]);

        AutoUpdateRule::create([
            'rule_name' => 'Document Verification Follow-up',
            'trigger_event' => 'Unverified Profile > 48 Hours',
            'frequency' => 'Daily at 10:00 AM',
            'action_type' => 'Assign to Senior Matchmaker for Review',
            'is_active' => true,
            'processed_count' => 194,
            'last_run_at' => now()->subHours(2),
        ]);

        AutoUpdateRule::create([
            'rule_name' => 'Inactive Profile Archiving',
            'trigger_event' => 'No Member Login > 90 Days',
            'frequency' => 'Weekly on Sunday',
            'action_type' => 'Mark Status as Inactive',
            'is_active' => false,
            'processed_count' => 56,
            'last_run_at' => now()->subDays(3),
        ]);

        // 3. Seed Padhadhikari Office Bearers
        Padhadhikari::create([
            'name' => 'Shri Ashok Kumar Jain',
            'designation' => 'National President',
            'caste_group' => 'Jain Digambar',
            'region' => 'National Board',
            'contact_number' => '+91 9829012345',
            'email' => 'ashok.jain@jsmcrm.test',
            'term_start' => '2024-01-01',
            'term_end' => '2027-12-31',
            'status' => 'Active',
            'responsibilities' => 'National Executive Council Leadership & Matrimonial Trust Management.',
        ]);

        Padhadhikari::create([
            'name' => 'Smt. Rekha Mehta',
            'designation' => 'General Secretary',
            'caste_group' => 'Jain Svetambara',
            'region' => 'Gujarat & Maharashtra Region',
            'contact_number' => '+91 9825198765',
            'email' => 'rekha.mehta@jsmcrm.test',
            'term_start' => '2024-01-01',
            'term_end' => '2026-12-31',
            'status' => 'Active',
            'responsibilities' => 'Counselor Coordination & Regional Bio-data Verification.',
        ]);

        Padhadhikari::create([
            'name' => 'Dr. Mahendra Shah',
            'designation' => 'State Patron & Vice President',
            'caste_group' => 'Jain Digambar',
            'region' => 'North America Chapter',
            'contact_number' => '+1 (415) 555-0198',
            'email' => 'mahendra.shah@jsmcrm.test',
            'term_start' => '2025-01-01',
            'term_end' => '2028-12-31',
            'status' => 'Active',
            'responsibilities' => 'NRI Community Directory Operations & International Conventions.',
        ]);

        // 4. Seed Healthcare Job Postings & Applications
        $cardiologistJob = JobPosting::create([
            'title' => 'Senior Consultant Cardiologist & Interventional Specialist',
            'department' => 'Cardiology & Heart Care',
            'location' => 'Metro General Hospital, New York, NY',
            'employment_type' => 'Full-Time',
            'salary_min' => 180000.00,
            'salary_max' => 260000.00,
            'status' => 'Active',
            'description' => 'Lead inpatient and outpatient clinical cardiology rounds, catheterization procedures, and diagnostic echocardiograms.',
        ]);

        $icuNurseJob = JobPosting::create([
            'title' => 'Head of Critical Care Nursing (ICU Lead)',
            'department' => 'Emergency & Intensive Care (ICU)',
            'location' => 'St. Jude Medical Center, Chicago, IL',
            'employment_type' => 'Full-Time',
            'salary_min' => 85000.00,
            'salary_max' => 120000.00,
            'status' => 'Active',
            'description' => 'Manage critical care nursing staff, trauma triage, ventilator management, and ICU patient monitoring protocols.',
        ]);

        $radiologistJob = JobPosting::create([
            'title' => 'Consultant Radiologist & Diagnostic Imaging Specialist',
            'department' => 'Radiology & Diagnostics',
            'location' => 'HealthFirst Specialty Clinic, Boston, MA',
            'employment_type' => 'Full-Time',
            'salary_min' => 160000.00,
            'salary_max' => 220000.00,
            'status' => 'Active',
            'description' => 'Interpretation of MRI, CT scans, ultrasound imaging, and interventional radiology clinical consultations.',
        ]);

        $erDoctorJob = JobPosting::create([
            'title' => 'Emergency Room Physician (ER / Trauma)',
            'department' => 'Emergency Medicine',
            'location' => 'City Central Hospital, San Francisco, CA',
            'employment_type' => 'Full-Time',
            'salary_min' => 150000.00,
            'salary_max' => 210000.00,
            'status' => 'Active',
            'description' => 'Triage, acute trauma resuscitation, emergency airway management, and urgent clinical admissions.',
        ]);

        JobApplication::create([
            'job_posting_id' => $cardiologistJob->id,
            'applicant_name' => 'Dr. Aarav Mehta, MD',
            'email' => 'aarav.mehta@healthmed.org',
            'phone' => '+1 (555) 234-7890',
            'experience_years' => '8+ years',
            'stage' => 'Interview Scheduled',
            'notes' => 'Board certified in Cardiovascular Disease. Fellowship completed at Johns Hopkins.',
        ]);

        JobApplication::create([
            'job_posting_id' => $icuNurseJob->id,
            'applicant_name' => 'Nurse Sarah Jenkins, BSN, RN',
            'email' => 's.jenkins@nursingcare.com',
            'phone' => '+1 (555) 876-5432',
            'experience_years' => '6 years',
            'stage' => 'Offer Sent',
            'notes' => 'CCRN certified with 6 years level-1 trauma and ICU ECMO experience.',
        ]);

        JobApplication::create([
            'job_posting_id' => $radiologistJob->id,
            'applicant_name' => 'Dr. Priya Sharma, MBBS, MD',
            'email' => 'priya.sharma@medclinics.org',
            'phone' => '+1 (555) 345-6789',
            'experience_years' => '7 years',
            'stage' => 'Interview Scheduled',
            'notes' => 'Subspecialty in Neuro-imaging and 3T MRI diagnostic interpretation.',
        ]);

        JobApplication::create([
            'job_posting_id' => $erDoctorJob->id,
            'applicant_name' => 'Dr. David Chen, MD',
            'email' => 'david.chen@erhealth.org',
            'phone' => '+1 (555) 654-3210',
            'experience_years' => '4 years',
            'stage' => 'Screening',
            'notes' => 'ATLS and ACLS certified with extensive ER triage and resuscitation background.',
        ]);

        JobApplication::create([
            'job_posting_id' => $icuNurseJob->id,
            'applicant_name' => 'Nurse Elena Rostova, BSN',
            'email' => 'elena.r@healthcare.net',
            'phone' => '+1 (555) 789-0123',
            'experience_years' => '5 years',
            'stage' => 'Hired',
            'notes' => 'Experienced in cardiac post-operative ICU care and patient telemetry.',
        ]);
    }
}
