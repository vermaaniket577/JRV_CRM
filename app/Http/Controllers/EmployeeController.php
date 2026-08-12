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
}
