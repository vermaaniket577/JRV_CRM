<?php

namespace App\Http\Controllers;

use App\Models\EmployeeProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $searchType = $request->query('type', 'ID');
        $searchQuery = $request->query('query', '');
        $department = $request->query('department');
        $attendanceStatus = $request->query('attendance_status');
        $gender = $request->query('gender');
        $paymentStatus = $request->query('payment_status');
        $state = $request->query('state');
        $city = $request->query('city');
        $activeOnly = $request->boolean('active', true);

        $query = User::with('employeeProfile')->where('is_super_admin', false);

        if ($activeOnly) {
            $query->where('status', 'active');
        }

        if ($department) {
            $query->whereHas('employeeProfile', fn ($q) => $q->where('department', $department));
        }

        if ($attendanceStatus) {
            $query->whereHas('employeeProfile', fn ($q) => $q->where('attendance_status', $attendanceStatus));
        }

        if ($gender) {
            $query->whereHas('employeeProfile', fn ($q) => $q->where('gender', $gender));
        }

        if ($paymentStatus) {
            $query->whereHas('employeeProfile', fn ($q) => $q->where('payment_status', $paymentStatus));
        }

        if ($state) {
            $query->whereHas('employeeProfile', fn ($q) => $q->where('state', $state));
        }

        if ($city) {
            $query->whereHas('employeeProfile', fn ($q) => $q->where('city', $city));
        }

        if ($searchQuery) {
            if ($searchType === 'ID') {
                $query->where('id', $searchQuery)
                    ->orWhereHas('employeeProfile', fn ($q) => $q->where('employee_code', 'like', "%{$searchQuery}%"));
            } else {
                $query->where('name', 'like', "%{$searchQuery}%")
                    ->orWhere('email', 'like', "%{$searchQuery}%");
            }
        }

        $totalEmployees = User::where('is_super_admin', false)->count();
        $totalAttendance = EmployeeProfile::where('attendance_status', 'Present')->count();
        $salaryRequests = EmployeeProfile::where('payment_status', 'Pending')->count();
        $leaveRequests = EmployeeProfile::where('attendance_status', 'On Leave')->count();
        $absentCount = EmployeeProfile::where('attendance_status', 'Absent')->count();

        $employees = $query->latest()->get();

        return Inertia::render('EmployeeManagement', [
            'metrics' => [
                'total_employees' => $totalEmployees,
                'total_attendance' => $totalAttendance,
                'salary_requests' => $salaryRequests,
                'leave_requests' => $leaveRequests,
                'absent_count' => $absentCount,
            ],
            'employees' => $employees,
            'filters' => [
                'type' => $searchType,
                'query' => $searchQuery,
                'department' => $department,
                'attendance_status' => $attendanceStatus,
                'gender' => $gender,
                'payment_status' => $paymentStatus,
                'state' => $state,
                'city' => $city,
                'active' => $activeOnly,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'department' => ['required', 'string', 'max:100'],
            'designation' => ['required', 'string', 'max:100'],
            'salary' => ['required', 'numeric', 'min:0'],
            'attendance_status' => ['required', 'string', 'in:Present,Absent,On Leave,Half Day'],
            'gender' => ['required', 'string', 'in:Male,Female,Other'],
            'phone' => ['nullable', 'string', 'max:50'],
            'state' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        EmployeeProfile::create([
            'user_id' => $user->id,
            'employee_code' => 'EMP-' . str_pad($user->id, 4, '0', STR_PAD_LEFT),
            'department' => $validated['department'],
            'designation' => $validated['designation'],
            'salary' => $validated['salary'],
            'attendance_status' => $validated['attendance_status'],
            'gender' => $validated['gender'],
            'phone' => $validated['phone'] ?? null,
            'state' => $validated['state'] ?? 'California',
            'city' => $validated['city'] ?? 'San Francisco',
            'joining_date' => now(),
        ]);

        return redirect()->back()->with('success', 'Employee added successfully.');
    }

    public function update(Request $request, User $employee): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,' . $employee->id],
            'department' => ['required', 'string', 'max:100'],
            'designation' => ['required', 'string', 'max:100'],
            'salary' => ['required', 'numeric', 'min:0'],
            'attendance_status' => ['required', 'string', 'in:Present,Absent,On Leave,Half Day'],
            'gender' => ['required', 'string', 'in:Male,Female,Other'],
            'status' => ['required', 'string', 'in:active,inactive'],
        ]);

        $employee->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'status' => $validated['status'],
        ]);

        $employee->employeeProfile()->updateOrCreate(
            ['user_id' => $employee->id],
            [
                'department' => $validated['department'],
                'designation' => $validated['designation'],
                'salary' => $validated['salary'],
                'attendance_status' => $validated['attendance_status'],
                'gender' => $validated['gender'],
            ]
        );

        return redirect()->back()->with('success', 'Employee updated successfully.');
    }

    public function destroy(User $employee): RedirectResponse
    {
        $employee->delete();
        return redirect()->back()->with('success', 'Employee removed.');
    }

    public function export(Request $request)
    {
        $employees = User::with('employeeProfile')->where('is_super_admin', false)->latest()->get();
        $filename = 'employees_export_' . date('Y_m_d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($employees) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Employee Code', 'Full Name', 'Email', 'Phone', 'Department', 'Designation', 'Salary', 'Attendance Status', 'Gender', 'State', 'City', 'Status']);

            foreach ($employees as $emp) {
                $p = $emp->employeeProfile;
                fputcsv($file, [
                    $p?->employee_code ?? ('EMP-' . str_pad($emp->id, 4, '0', STR_PAD_LEFT)),
                    $emp->name,
                    $emp->email,
                    $p?->phone ?? '',
                    $p?->department ?? 'General',
                    $p?->designation ?? 'Staff',
                    $p?->salary ?? 0,
                    $p?->attendance_status ?? 'Present',
                    $p?->gender ?? 'Male',
                    $p?->state ?? '',
                    $p?->city ?? '',
                    $emp->status ?? 'active',
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function downloadSample()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"sample_employees_template.csv\"",
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Full Name', 'Email', 'Phone', 'Department', 'Designation', 'Salary', 'Attendance Status', 'Gender', 'State', 'City']);
            fputcsv($file, ['John Doe', 'john.doe@company.com', '+91 9876543210', 'Sales', 'Senior Manager', '65000', 'Present', 'Male', 'California', 'San Francisco']);
            fputcsv($file, ['Priya Sharma', 'priya.sharma@company.com', '+91 9876543211', 'Matchmaking', 'Relationship Manager', '55000', 'Present', 'Female', 'New York', 'New York City']);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');
        $header = fgetcsv($handle);
        $importedCount = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (empty($row[0]) || empty($row[1])) continue;

            $name = trim($row[0]);
            $email = trim($row[1]);
            $phone = isset($row[2]) ? trim($row[2]) : null;
            $department = isset($row[3]) && !empty(trim($row[3])) ? trim($row[3]) : 'Sales';
            $designation = isset($row[4]) && !empty(trim($row[4])) ? trim($row[4]) : 'Staff Representative';
            $salary = isset($row[5]) && is_numeric(trim($row[5])) ? (float) trim($row[5]) : 45000;
            $attendance = isset($row[6]) && in_array(trim($row[6]), ['Present', 'Absent', 'On Leave', 'Half Day']) ? trim($row[6]) : 'Present';
            $gender = isset($row[7]) && in_array(trim($row[7]), ['Male', 'Female', 'Other']) ? trim($row[7]) : 'Male';
            $state = isset($row[8]) ? trim($row[8]) : 'California';
            $city = isset($row[9]) ? trim($row[9]) : 'San Francisco';

            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make('password123'),
                    'status' => 'active',
                ]
            );

            EmployeeProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'employee_code' => 'EMP-' . str_pad($user->id, 4, '0', STR_PAD_LEFT),
                    'department' => $department,
                    'designation' => $designation,
                    'salary' => $salary,
                    'attendance_status' => $attendance,
                    'gender' => $gender,
                    'phone' => $phone,
                    'state' => $state,
                    'city' => $city,
                    'joining_date' => now(),
                ]
            );

            $importedCount++;
        }

        fclose($handle);

        return redirect()->back()->with('success', "{$importedCount} employee records successfully imported.");
    }
}
