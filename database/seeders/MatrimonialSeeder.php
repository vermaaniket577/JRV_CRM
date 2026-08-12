<?php

namespace Database\Seeders;

use App\Models\Member;
use App\Models\MemberDocument;
use App\Models\MemberPreference;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Seeder;

class MatrimonialSeeder extends Seeder
{
    public function run(): void
    {
        $matchmaker = User::first() ?? User::create([
            'name' => 'Sarah Connor',
            'email' => 'matchmaker@jsmcrm.test',
            'password' => bcrypt('password'),
        ]);

        $sampleMembers = [
            [
                'member_code' => '143077',
                'first_name' => 'Chetan',
                'last_name' => 'Jain',
                'gender' => 'Male',
                'date_of_birth' => '1994-08-11',
                'age' => 32,
                'height_cm' => 175,
                'marital_status' => 'Never Married',
                'religion' => 'Jain',
                'caste' => 'Jain Digambar',
                'sub_caste' => 'Agarwal',
                'gotra' => 'Kashyap',
                'mother_gotra' => 'Vashishtha',
                'education_level' => 'B.Tech IT',
                'education_field' => 'Software Engineering',
                'occupation_type' => 'Senior Developer',
                'designation' => 'Lead Engineer',
                'company_name' => 'JSM Tech',
                'annual_income' => 1500000.00,
                'father_name' => 'Ramesh Jain',
                'father_occupation' => 'Business Owner',
                'mother_name' => 'Sunita Jain',
                'brothers_count' => 1,
                'sisters_count' => 0,
                'family_type' => 'Nuclear',
                'family_status' => 'Upper Middle Class',
                'email' => 'chetan.jain@example.com',
                'phone' => '+91-8088788209',
                'alternate_phone' => '8088788209',
                'city' => 'San Francisco',
                'state' => 'California',
                'country' => 'USA',
                'verification_status' => 'Verified',
                'verified_at' => now(),
                'verified_by' => $matchmaker->id,
                'assigned_matchmaker_id' => $matchmaker->id,
                'status' => 'Active',
            ],
            [
                'member_code' => '143973',
                'first_name' => 'Janvi',
                'last_name' => 'Mehta',
                'gender' => 'Female',
                'date_of_birth' => '1994-05-20',
                'age' => 32,
                'height_cm' => 165,
                'marital_status' => 'Never Married',
                'religion' => 'Jain',
                'caste' => 'Jain Svetambara',
                'sub_caste' => 'Oswal',
                'gotra' => 'Garg',
                'mother_gotra' => 'Goyal',
                'education_level' => 'MBA HR',
                'education_field' => 'Human Resources',
                'occupation_type' => 'HR Manager',
                'designation' => 'Senior Manager',
                'company_name' => 'Global Corp',
                'annual_income' => 1200000.00,
                'father_name' => 'Suresh Mehta',
                'father_occupation' => 'Chartered Accountant',
                'mother_name' => 'Rekha Mehta',
                'brothers_count' => 0,
                'sisters_count' => 1,
                'family_type' => 'Joint',
                'family_status' => 'Affluent / High Class',
                'email' => 'janvi.mehta@example.com',
                'phone' => '+91-9426381345',
                'alternate_phone' => '8866741334',
                'city' => 'Austin',
                'state' => 'Texas',
                'country' => 'USA',
                'verification_status' => 'Pending Review',
                'assigned_matchmaker_id' => $matchmaker->id,
                'status' => 'Active',
            ],
        ];

        foreach ($sampleMembers as $data) {
            $member = Member::updateOrCreate(
                ['member_code' => $data['member_code']],
                $data
            );

            MemberDocument::updateOrCreate(
                ['member_id' => $member->id],
                [
                    'document_type' => 'Aadhar Card',
                    'document_number' => 'XXXX-XXXX-' . rand(1000, 9999),
                    'file_path' => '/documents/aadhar_sample.pdf',
                    'status' => $member->verification_status === 'Verified' ? 'Approved' : 'Pending',
                    'reviewed_by' => $member->verification_status === 'Verified' ? $matchmaker->id : null,
                    'reviewed_at' => $member->verification_status === 'Verified' ? now() : null,
                ]
            );

            MemberPreference::updateOrCreate(
                ['member_id' => $member->id],
                [
                    'age_min' => $data['gender'] === 'Female' ? $data['age'] : max(21, $data['age'] - 4),
                    'age_max' => $data['gender'] === 'Female' ? $data['age'] + 5 : $data['age'],
                    'height_min_cm' => 155,
                    'height_max_cm' => 185,
                    'caste' => $data['caste'],
                    'preferred_state' => $data['state'],
                ]
            );

            Subscription::updateOrCreate(
                ['member_id' => $member->id],
                [
                    'plan_tier' => $data['verification_status'] === 'Verified' ? 'Platinum' : 'Premium',
                    'amount_paid' => $data['verification_status'] === 'Verified' ? 5100.00 : 2100.00,
                    'currency' => 'INR',
                    'payment_status' => 'Paid',
                    'contact_view_limit' => 50,
                    'starts_at' => now(),
                    'expires_at' => now()->addYear(),
                ]
            );
        }
    }
}
