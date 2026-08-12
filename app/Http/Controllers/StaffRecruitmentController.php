<?php

namespace App\Http\Controllers;

use App\Models\JobApplication;
use App\Models\JobPosting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StaffRecruitmentController extends Controller
{
    public function index(): Response
    {
        $jobPostings = JobPosting::with('applications')->latest()->get();
        $applications = JobApplication::with('jobPosting')->latest()->get();

        $metrics = [
            'open_positions' => JobPosting::where('status', 'Active')->count(),
            'total_applicants' => JobApplication::count(),
            'interviews_scheduled' => JobApplication::where('stage', 'Interview Scheduled')->count(),
            'hired_this_month' => JobApplication::where('stage', 'Hired')->count(),
        ];

        return Inertia::render('StaffRecruitment', [
            'jobPostings' => $jobPostings,
            'applications' => $applications,
            'metrics' => $metrics,
        ]);
    }

    public function storeJob(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'department' => ['required', 'string'],
            'location' => ['required', 'string'],
            'employment_type' => ['required', 'string'],
            'salary_min' => ['required', 'numeric'],
            'salary_max' => ['required', 'numeric'],
            'description' => ['nullable', 'string'],
        ]);

        JobPosting::create(array_merge($validated, [
            'status' => 'Active',
        ]));

        return redirect()->back()->with('success', "New staff position '{$validated['title']}' posted successfully.");
    }

    public function updateStage(Request $request, JobApplication $application): RedirectResponse
    {
        $validated = $request->validate([
            'stage' => ['required', 'string', 'in:Applied,Screening,Interview Scheduled,Offer Sent,Hired,Rejected'],
        ]);

        $application->update([
            'stage' => $validated['stage'],
        ]);

        return redirect()->back()->with('success', 'Candidate hiring pipeline stage updated.');
    }
}
