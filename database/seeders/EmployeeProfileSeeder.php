<?php

namespace Database\Seeders;

use App\Models\EmployeeProfile;
use App\Models\User;
use Illuminate\Database\Seeder;

class EmployeeProfileSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();

        foreach ($users as $u) {
            EmployeeProfile::firstOrCreate(
                ['user_id' => $u->id],
                [
                    'employee_code' => 'EMP-' . str_pad($u->id, 4, '0', STR_PAD_LEFT),
                    'department' => $u->id == 1 ? 'Engineering' : 'Sales',
                    'designation' => $u->id == 1 ? 'Senior Tech Lead' : 'Account Executive',
                    'salary' => $u->id == 1 ? 85000.00 : 45000.00,
                    'attendance_status' => 'Present',
                    'gender' => 'Male',
                    'state' => 'California',
                    'city' => 'San Francisco',
                ]
            );
        }
    }
}
